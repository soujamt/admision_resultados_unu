<?php

namespace App\Services\Excel;

use Generator;
use RuntimeException;
use SimpleXMLElement;
use XMLReader;
use ZipArchive;

/**
 * Lector minimo de archivos .xlsx.
 *
 * La universidad recibe los padrones en el formato oficial en Excel y no hay
 * una libreria de hojas de calculo entre las dependencias del proyecto, asi
 * que aqui se lee el paquete OOXML directamente: un .xlsx es un ZIP con el
 * texto compartido en `sharedStrings.xml` y una hoja por archivo.
 *
 * Las filas se entregan con un generador y la hoja se recorre con XMLReader
 * sobre el flujo `zip://`, porque el padron de colegios pasa de veinte mil
 * filas y cargarlo entero en memoria no es opcion.
 */
class LectorXlsx
{
    /** @var list<string> */
    private array $textoCompartido;

    /** @var array<string, string> */
    private array $rutasDeHoja;

    public function __construct(private readonly string $archivo)
    {
        if (! is_readable($this->archivo)) {
            throw new RuntimeException("No se puede leer el archivo: {$this->archivo}");
        }

        $zip = new ZipArchive;

        if ($zip->open($this->archivo) !== true) {
            throw new RuntimeException("El archivo no es un .xlsx valido: {$this->archivo}");
        }

        $this->textoCompartido = $this->leerTextoCompartido($zip);
        $this->rutasDeHoja = $this->leerRutasDeHoja($zip);

        $zip->close();
    }

    /**
     * Nombres de las hojas, en el orden en que aparecen en el libro.
     *
     * @return list<string>
     */
    public function hojas(): array
    {
        return array_keys($this->rutasDeHoja);
    }

    public function tieneHoja(string $hoja): bool
    {
        return isset($this->rutasDeHoja[$hoja]);
    }

    /**
     * Recorre una hoja devolviendo cada fila indexada por el nombre de columna
     * que aparece en la fila de cabecera.
     *
     * @return Generator<int, array<string, string>>
     */
    public function filas(string $hoja): Generator
    {
        $cabecera = null;

        foreach ($this->filasCrudas($hoja) as $numero => $fila) {
            if ($cabecera === null) {
                $cabecera = array_map(
                    static fn (string $celda): string => trim($celda),
                    $fila,
                );

                continue;
            }

            $asociativa = [];

            foreach ($cabecera as $indice => $nombre) {
                if ($nombre === '') {
                    continue;
                }

                $asociativa[$nombre] = $fila[$indice] ?? '';
            }

            yield $numero => $asociativa;
        }
    }

    /**
     * Recorre una hoja devolviendo cada fila como una lista indexada desde la
     * columna A. La clave del generador es el numero de fila de Excel, que es
     * el que hay que citar cuando se reporta un error al usuario.
     *
     * @return Generator<int, array<int, string>>
     */
    public function filasCrudas(string $hoja): Generator
    {
        if (! isset($this->rutasDeHoja[$hoja])) {
            throw new RuntimeException("La hoja «{$hoja}» no existe en el archivo.");
        }

        $lector = new XMLReader;
        $lector->open('zip://'.$this->archivo.'#'.$this->rutasDeHoja[$hoja]);

        try {
            while ($lector->read()) {
                if ($lector->nodeType !== XMLReader::ELEMENT || $lector->name !== 'row') {
                    continue;
                }

                $numero = (int) $lector->getAttribute('r');
                $fila = $this->leerFila(new SimpleXMLElement($lector->readOuterXml()));

                if ($fila !== []) {
                    yield $numero => $fila;
                }
            }
        } finally {
            $lector->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private function leerFila(SimpleXMLElement $nodo): array
    {
        $celdas = [];

        foreach ($nodo->c as $celda) {
            $celdas[self::indiceDeColumna((string) $celda['r'])] = $this->valorDeCelda($celda);
        }

        if ($celdas === []) {
            return [];
        }

        $completa = [];

        for ($i = 0; $i <= max(array_keys($celdas)); $i++) {
            $completa[$i] = $celdas[$i] ?? '';
        }

        return array_filter($completa, static fn (string $valor): bool => $valor !== '') === []
            ? []
            : $completa;
    }

    private function valorDeCelda(SimpleXMLElement $celda): string
    {
        $tipo = (string) $celda['t'];

        if ($tipo === 'inlineStr') {
            $texto = '';

            foreach ($celda->xpath('.//*[local-name()="t"]') ?: [] as $nodo) {
                $texto .= (string) $nodo;
            }

            return trim($texto);
        }

        $valor = isset($celda->v) ? (string) $celda->v : '';

        if ($tipo === 's' && $valor !== '') {
            return trim($this->textoCompartido[(int) $valor] ?? '');
        }

        return trim($valor);
    }

    /**
     * Convierte la referencia de una celda (por ejemplo «AB12») en el indice de
     * su columna empezando en cero.
     */
    public static function indiceDeColumna(string $referencia): int
    {
        preg_match('/^([A-Z]+)/', $referencia, $coincidencias);

        $indice = 0;

        foreach (str_split($coincidencias[1] ?? 'A') as $letra) {
            $indice = $indice * 26 + (ord($letra) - 64);
        }

        return $indice - 1;
    }

    /**
     * @return list<string>
     */
    private function leerTextoCompartido(ZipArchive $zip): array
    {
        $contenido = $zip->getFromName('xl/sharedStrings.xml');

        if ($contenido === false) {
            return [];
        }

        $textos = [];
        $lector = new XMLReader;
        $lector->XML($contenido);

        while ($lector->read()) {
            if ($lector->nodeType !== XMLReader::ELEMENT || $lector->name !== 'si') {
                continue;
            }

            $nodo = new SimpleXMLElement($lector->readOuterXml());
            $texto = '';

            foreach ($nodo->xpath('.//*[local-name()="t"]') ?: [] as $trozo) {
                $texto .= (string) $trozo;
            }

            $textos[] = $texto;
        }

        $lector->close();

        return $textos;
    }

    /**
     * El nombre visible de la hoja vive en workbook.xml y la ruta del archivo
     * que la contiene en el .rels que lo acompana; hay que cruzarlos.
     *
     * @return array<string, string>
     */
    private function leerRutasDeHoja(ZipArchive $zip): array
    {
        $libro = new SimpleXMLElement((string) $zip->getFromName('xl/workbook.xml'));
        $relaciones = new SimpleXMLElement((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));

        $destinos = [];

        foreach ($relaciones->Relationship as $relacion) {
            $destinos[(string) $relacion['Id']] = (string) $relacion['Target'];
        }

        $rutas = [];

        foreach ($libro->sheets->sheet as $hoja) {
            $identificador = (string) $hoja->attributes('r', true)['id'];
            $destino = ltrim(str_replace('/xl/', '', $destinos[$identificador] ?? ''), '/');

            if (! str_starts_with($destino, 'worksheets/')) {
                $destino = 'worksheets/'.basename($destino);
            }

            $rutas[(string) $hoja['name']] = 'xl/'.$destino;
        }

        return $rutas;
    }
}
