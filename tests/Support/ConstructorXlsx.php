<?php

namespace Tests\Support;

use ZipArchive;

/**
 * Arma un .xlsx minimo en disco para las pruebas.
 *
 * Los padrones reales traen datos personales de postulantes y no pueden vivir
 * en el repositorio, asi que cada prueba se construye el archivo que necesita
 * con solo las filas que va a comprobar.
 *
 * El texto se escribe en `sharedStrings.xml`, que es como lo genera Excel, para
 * que el lector se ejercite por el mismo camino que en produccion.
 */
class ConstructorXlsx
{
    /** @var array<string, list<list<string>>> */
    private array $hojas = [];

    /** @var list<string> */
    private array $textos = [];

    /** @var array<string, int> */
    private array $indiceDeTexto = [];

    /**
     * @param  list<list<string|int>>  $filas
     */
    public function hoja(string $nombre, array $filas): static
    {
        $this->hojas[$nombre] = array_map(
            static fn (array $fila): array => array_map(strval(...), $fila),
            $filas,
        );

        return $this;
    }

    /**
     * Escribe el archivo y devuelve su ruta.
     */
    public function escribir(?string $ruta = null): string
    {
        $ruta ??= tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';

        $hojasXml = [];
        $numero = 1;

        foreach ($this->hojas as $filas) {
            $hojasXml[$numero++] = $this->xmlDeHoja($filas);
        }

        $zip = new ZipArchive;
        $zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->xmlDeTipos(count($hojasXml)));
        $zip->addFromString('_rels/.rels', $this->xmlDeRelacionRaiz());
        $zip->addFromString('xl/workbook.xml', $this->xmlDeLibro());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xmlDeRelacionesDelLibro(count($hojasXml)));
        $zip->addFromString('xl/sharedStrings.xml', $this->xmlDeTextoCompartido());

        foreach ($hojasXml as $indice => $xml) {
            $zip->addFromString("xl/worksheets/sheet{$indice}.xml", $xml);
        }

        $zip->close();

        return $ruta;
    }

    /**
     * @param  list<list<string>>  $filas
     */
    private function xmlDeHoja(array $filas): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($filas as $indice => $fila) {
            $numeroDeFila = $indice + 1;
            $xml .= "<row r=\"{$numeroDeFila}\">";

            foreach ($fila as $columna => $valor) {
                if ($valor === '') {
                    continue;
                }

                $referencia = self::letraDeColumna($columna).$numeroDeFila;

                if (is_numeric($valor) && ! str_starts_with($valor, '0')) {
                    $xml .= "<c r=\"{$referencia}\"><v>{$valor}</v></c>";

                    continue;
                }

                $xml .= "<c r=\"{$referencia}\" t=\"s\"><v>{$this->texto($valor)}</v></c>";
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private function texto(string $valor): int
    {
        if (! isset($this->indiceDeTexto[$valor])) {
            $this->indiceDeTexto[$valor] = count($this->textos);
            $this->textos[] = $valor;
        }

        return $this->indiceDeTexto[$valor];
    }

    private function xmlDeTextoCompartido(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($this->textos).'" uniqueCount="'.count($this->textos).'">';

        foreach ($this->textos as $texto) {
            $xml .= '<si><t xml:space="preserve">'.htmlspecialchars($texto, ENT_XML1).'</t></si>';
        }

        return $xml.'</sst>';
    }

    private function xmlDeLibro(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';

        $indice = 1;

        foreach (array_keys($this->hojas) as $nombre) {
            $xml .= '<sheet name="'.htmlspecialchars($nombre, ENT_XML1 | ENT_COMPAT).'" sheetId="'.$indice.'" r:id="rId'.$indice.'"/>';
            $indice++;
        }

        return $xml.'</sheets></workbook>';
    }

    private function xmlDeRelacionesDelLibro(int $hojas): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        for ($i = 1; $i <= $hojas; $i++) {
            $xml .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }

        $xml .= '<Relationship Id="rId'.($hojas + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';

        return $xml.'</Relationships>';
    }

    private function xmlDeRelacionRaiz(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function xmlDeTipos(int $hojas): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>';

        for ($i = 1; $i <= $hojas; $i++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return $xml.'</Types>';
    }

    private static function letraDeColumna(int $indice): string
    {
        $letras = '';

        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letras = chr(65 + ($n - 1) % 26).$letras;
        }

        return $letras;
    }
}
