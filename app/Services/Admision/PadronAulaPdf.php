<?php

namespace App\Services\Admision;

use App\Models\AsignacionExamen;
use App\Models\ExamenAula;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Padron de postulantes de un aula: el mismo formato que el padron general,
 * pero acotado a un aula y con una hoja por aula en vez de una lista de toda
 * la jornada.
 *
 * Comparte la vista con el general, para que ambos formatos no se separen con
 * el tiempo. La diferencia con la lista de asistencia, que tambien es por
 * aula, es el orden y el uso: esta va alfabetica y se publica para que el
 * postulante encuentre su carpeta; aquella va por carpeta y la recorre el
 * docente asiento por asiento.
 */
class PadronAulaPdf
{
    public function documento(ExamenAula $aulaExamen): DocumentoPdf
    {
        return Pdf::loadView('pdf.padron-postulantes', $this->datos($aulaExamen))->setPaper('a4');
    }

    public function nombreArchivo(ExamenAula $aulaExamen): string
    {
        $aulaExamen->loadMissing(['examen.proceso', 'aula']);

        return Str::slug(
            'padron-aula-'.$aulaExamen->examen->proceso->codigo_pro.'-'.$aulaExamen->aula->nombre_aul,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(ExamenAula $aulaExamen): array
    {
        $aulaExamen->loadMissing(['examen.proceso', 'aula.sede']);
        $asignaciones = $this->asignaciones($aulaExamen);
        $modalidades = $asignaciones
            ->pluck('inscripcion.modalidad.nombre_mod')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->implode(' / ');

        return [
            'examen' => $aulaExamen->examen,
            'asignaciones' => $asignaciones,
            'titulo' => 'Padrón de postulantes por aula',
            /* Sin esta linea las hojas de dos aulas son indistinguibles una vez
               impresas: el pabellon y el aula se repiten en cada fila, pero no
               dicen de que aula es el juego. */
            'aulaCabecera' => $aulaExamen->aula->etiqueta(),
            'modalidadCabecera' => filled($modalidades) ? $modalidades : $aulaExamen->examen->nombre_exa,
            'ubicacion' => $aulaExamen->aula->sede->ubicacionCabecera(),
        ];
    }

    /**
     * @return Collection<int, AsignacionExamen>
     */
    private function asignaciones(ExamenAula $aulaExamen): Collection
    {
        return $aulaExamen->asignaciones()
            ->with([
                'aulaExamen.aula.sede',
                'inscripcion.postulante',
                'inscripcion.modalidad',
                'inscripcion.carrera',
                'inscripcion.sede',
            ])
            ->get()
            ->sortBy(fn (AsignacionExamen $asignacion): string => $asignacion->claveAlfabetica(), SORT_NATURAL)
            ->values();
    }
}
