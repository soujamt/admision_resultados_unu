<?php

namespace App\Services\Admision;

use App\Models\Inscripcion;
use App\Models\Proceso;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Gestiona las fotografias de los postulantes.
 *
 * Viven en el disco privado (`storage/app/private`) y no en `public`: son datos
 * personales de menores de edad en buena parte, y la Ley 29733 que cita el
 * reglamento no admite que queden accesibles por URL directa. Se sirven desde
 * una ruta autenticada.
 *
 * La carpeta se separa por proceso, de modo que la foto que se tomo en 2027-I
 * no se pisa con la de 2027-II aunque sea el mismo postulante.
 */
class AlmacenFotos
{
    private const DISCO = 'local';

    /** @var list<string> */
    private const EXTENSIONES = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Ruta relativa dentro del disco privado.
     */
    public function carpeta(Proceso $proceso): string
    {
        return $proceso->carpeta().'/fotos';
    }

    /**
     * Ruta absoluta en el sistema de archivos: es la que hay que darle al
     * operador para que copie ahi el lote de fotos.
     *
     * Flysystem devuelve la ruta con barras normales aunque el resto sea de
     * Windows, y una ruta a medias no se puede pegar en el explorador.
     */
    public function carpetaAbsoluta(Proceso $proceso): string
    {
        $ruta = Storage::disk(self::DISCO)->path($this->carpeta($proceso));

        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ruta);
    }

    public function prepararCarpeta(Proceso $proceso): string
    {
        Storage::disk(self::DISCO)->makeDirectory($this->carpeta($proceso));

        return $this->carpetaAbsoluta($proceso);
    }

    public function existe(Inscripcion $inscripcion): bool
    {
        return $inscripcion->tieneFoto()
            && Storage::disk(self::DISCO)->exists($inscripcion->foto_ins);
    }

    /**
     * Contenido del archivo, para servirlo desde una ruta autenticada.
     */
    public function contenido(Inscripcion $inscripcion): ?string
    {
        if (! $this->existe($inscripcion)) {
            return null;
        }

        return Storage::disk(self::DISCO)->get($inscripcion->foto_ins);
    }

    /**
     * Tipo MIME del archivo privado. Nunca se toma de una cabecera enviada por
     * el navegador, sino del archivo que ya se encuentra en el almacenamiento.
     */
    public function tipoMime(Inscripcion $inscripcion): ?string
    {
        if (! $this->existe($inscripcion)) {
            return null;
        }

        return Storage::disk(self::DISCO)->mimeType($inscripcion->foto_ins) ?: null;
    }

    /**
     * Recorre las inscripciones del proceso y le asigna a cada una el archivo
     * que lleva su numero de documento por nombre.
     *
     * Cuando se indica una carpeta de origen los archivos se copian primero al
     * almacen del proceso; sin ella solo se vincula lo que ya este dentro.
     */
    public function vincular(Proceso $proceso, ?string $origen = null): ResultadoImportacion
    {
        if ($origen !== null && ! is_dir($origen)) {
            throw new RuntimeException("La carpeta de origen no existe: {$origen}");
        }

        $this->prepararCarpeta($proceso);

        $disponibles = $origen !== null
            ? $this->archivosPorDocumento($origen)
            : $this->archivosPorDocumento($this->carpetaAbsoluta($proceso));

        $resultado = new ResultadoImportacion;

        Inscripcion::with('postulante')
            ->where('id_pro', $proceso->id_pro)
            ->chunkById(200, function ($inscripciones) use ($proceso, $origen, $disponibles, $resultado): void {
                foreach ($inscripciones as $inscripcion) {
                    $documento = $inscripcion->postulante->numero_documento_pos;
                    $archivo = $disponibles[$documento] ?? null;

                    if ($archivo === null) {
                        $resultado->fallar(0, 'No se encontró la foto.', $documento);

                        continue;
                    }

                    $destino = $this->carpeta($proceso).'/'.$documento.'.'.mb_strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

                    if ($origen !== null) {
                        Storage::disk(self::DISCO)->put($destino, (string) file_get_contents($archivo));
                    }

                    $yaVinculada = $inscripcion->foto_ins === $destino;
                    $inscripcion->foto_ins = $destino;
                    $inscripcion->save();

                    $yaVinculada ? $resultado->actualizar() : $resultado->crear();
                }
            }, 'id_ins');

        return $resultado;
    }

    /**
     * Indexa los archivos de una carpeta por el nombre sin extension, que es
     * el numero de documento del postulante (72155069.jpg).
     *
     * @return array<string, string>
     */
    private function archivosPorDocumento(string $carpeta): array
    {
        if (! is_dir($carpeta)) {
            return [];
        }

        $indice = [];

        foreach (scandir($carpeta) ?: [] as $archivo) {
            $ruta = $carpeta.DIRECTORY_SEPARATOR.$archivo;

            if (! is_file($ruta)) {
                continue;
            }

            $extension = mb_strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

            if (! in_array($extension, self::EXTENSIONES, true)) {
                continue;
            }

            $indice[pathinfo($archivo, PATHINFO_FILENAME)] = $ruta;
        }

        return $indice;
    }
}
