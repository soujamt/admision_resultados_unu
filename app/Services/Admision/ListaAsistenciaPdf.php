<?php

namespace App\Services\Admision;

use App\Models\AsignacionExamen;
use App\Models\ExamenAula;
use App\Models\Inscripcion;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Lista de asistencia de un aula: una tarjeta por postulante con su foto, el
 * codigo de barras de su documento y los espacios para la huella y la firma.
 *
 * El codigo de barras lleva el numero de documento tal cual, para que el
 * docente lo lea con el escaner y no tenga que teclearlo. La foto va incrustada
 * en el PDF y nunca por URL: es un dato personal que vive en el disco privado.
 */
class ListaAsistenciaPdf
{
    /** Tarjetas por columna; la pagina lleva dos columnas. */
    private const POR_COLUMNA = 5;

    public function __construct(private readonly AlmacenFotos $fotos) {}

    public function documento(ExamenAula $aulaExamen): DocumentoPdf
    {
        return Pdf::loadView('pdf.lista-asistencia', $this->datos($aulaExamen))->setPaper('a4');
    }

    public function nombreArchivo(ExamenAula $aulaExamen): string
    {
        $aulaExamen->loadMissing(['examen.proceso', 'aula']);

        return Str::slug(
            'asistencia-'.$aulaExamen->examen->proceso->codigo_pro.'-'.$aulaExamen->aula->nombre_aul,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(ExamenAula $aulaExamen): array
    {
        $aulaExamen->loadMissing([
            'examen.proceso',
            'aula.sede',
            'area',
            'asignaciones.inscripcion.postulante',
            'asignaciones.inscripcion.modalidad',
            'asignaciones.inscripcion.sede',
            'asignaciones.inscripcion.carrera.area',
        ]);

        $tarjetas = $this->tarjetas($aulaExamen->asignaciones);
        $modalidades = $aulaExamen->asignaciones
            ->pluck('inscripcion.modalidad.nombre_mod')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->implode(' / ');

        return [
            'aulaExamen' => $aulaExamen,
            'tituloProceso' => 'PROCESO DE ADMISIÓN '.$aulaExamen->examen->proceso->tituloConvocatoria(),
            'modalidadCabecera' => filled($modalidades) ? $modalidades : $aulaExamen->examen->nombre_exa,
            'ubicacion' => $aulaExamen->aula->sede->ubicacionCabecera(),
            'fecha' => $aulaExamen->examen->fecha_exa ?? now(),
            'total' => $tarjetas->count(),
            'paginas' => $this->paginas($tarjetas),
        ];
    }

    /**
     * Una tarjeta por postulante, en orden alfabetico. `Str::ascii` es lo que
     * evita que un apellido con tilde caiga al final, porque comparado byte a
     * byte la «Á» va despues de la «B».
     *
     * @param  Collection<int, AsignacionExamen>  $asignaciones
     * @return Collection<int, array<string, mixed>>
     */
    private function tarjetas(Collection $asignaciones): Collection
    {
        $generador = new BarcodeGeneratorPNG;

        return $asignaciones
            ->sortBy(
                fn (AsignacionExamen $asignacion): string => Str::ascii(
                    mb_strtoupper($asignacion->inscripcion->postulante->nombreCompleto()),
                ).'|'.$asignacion->inscripcion->postulante->numero_documento_pos,
                SORT_NATURAL,
            )
            ->values()
            ->map(function (AsignacionExamen $asignacion, int $indice) use ($generador): array {
                $inscripcion = $asignacion->inscripcion;
                $documento = $inscripcion->postulante->numero_documento_pos;

                return [
                    'numero' => $indice + 1,
                    'documento' => $documento,
                    'nombre' => mb_strtoupper($inscripcion->postulante->nombreCompleto()),
                    'procedencia' => $this->procedencia($inscripcion),
                    'carpeta' => $asignacion->asiento_ase,
                    'foto' => $this->foto($inscripcion),
                    'barras' => 'data:image/png;base64,'.base64_encode(
                        $generador->getBarcode($documento, BarcodeGeneratorPNG::TYPE_CODE_128, 2, 45),
                    ),
                ];
            });
    }

    /**
     * El area que se imprime es la de la carrera, no la del aula: un aula puede
     * recibir carreras de areas distintas y lo que ubica al postulante es la
     * suya.
     */
    private function procedencia(Inscripcion $inscripcion): string
    {
        return sprintf(
            'AREA %d: %s - %s',
            $inscripcion->carrera->area->numero_are,
            $inscripcion->sede->abreviatura(),
            mb_strtoupper($inscripcion->carrera->nombre_car),
        );
    }

    /**
     * La foto viaja incrustada en el PDF porque el disco es privado y DomPDF no
     * puede pedirla por una ruta autenticada.
     */
    private function foto(Inscripcion $inscripcion): ?string
    {
        $contenido = $this->fotos->contenido($inscripcion);

        if ($contenido === null) {
            return null;
        }

        $mime = $this->fotos->tipoMime($inscripcion) ?? 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }

    /**
     * Reparte las tarjetas como las lee el docente: la pagina lleva dos
     * columnas de cinco, y la numeracion baja por la izquierda antes de pasar
     * a la derecha.
     *
     * @param  Collection<int, array<string, mixed>>  $tarjetas
     * @return Collection<int, list<array{izquierda: ?array<string, mixed>, derecha: ?array<string, mixed>}>>
     */
    private function paginas(Collection $tarjetas): Collection
    {
        return $tarjetas
            ->chunk(self::POR_COLUMNA * 2)
            ->map(function (Collection $pagina): array {
                $columnas = $pagina->values()->chunk(self::POR_COLUMNA)->values();
                $filas = [];

                for ($indice = 0; $indice < self::POR_COLUMNA; $indice++) {
                    $izquierda = $columnas->get(0)?->values()->get($indice);
                    $derecha = $columnas->get(1)?->values()->get($indice);

                    if ($izquierda === null && $derecha === null) {
                        continue;
                    }

                    $filas[] = ['izquierda' => $izquierda, 'derecha' => $derecha];
                }

                return $filas;
            })
            ->values();
    }
}
