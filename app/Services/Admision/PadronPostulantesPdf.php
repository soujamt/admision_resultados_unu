<?php

namespace App\Services\Admision;

use App\Models\AsignacionExamen;
use App\Models\Examen;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Arma el padron general de postulantes de una jornada: una sola lista en
 * orden alfabetico con el pabellon, el aula y la carpeta al costado de cada
 * nombre. No se agrupa por aula, asi que el postulante se busca por su
 * apellido y de ahi lee donde le toca sentarse.
 */
class PadronPostulantesPdf
{
    public function documento(Examen $examen): DocumentoPdf
    {
        return Pdf::loadView('pdf.padron-postulantes', $this->datos($examen))->setPaper('a4');
    }

    public function nombreArchivo(Examen $examen): string
    {
        $examen->loadMissing('proceso');

        return Str::slug('padron-'.$examen->proceso->codigo_pro.'-'.$examen->nombre_exa);
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(Examen $examen): array
    {
        $examen->loadMissing('proceso');
        $asignaciones = $this->asignaciones($examen);
        $modalidades = $asignaciones
            ->pluck('inscripcion.modalidad.nombre_mod')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->implode(' / ');
        $sedes = $asignaciones->pluck('aulaExamen.aula.sede')->filter()->unique('id_sed');

        return [
            'examen' => $examen,
            'asignaciones' => $asignaciones,
            'modalidadCabecera' => filled($modalidades) ? $modalidades : $examen->nombre_exa,
            'ubicacion' => $sedes->count() === 1 ? $sedes->first()->ubicacionCabecera() : 'UCAYALI',
        ];
    }

    /**
     * Orden alfabetico real: `Str::ascii` es lo que evita que un apellido con
     * tilde caiga al final, porque comparado byte a byte la «Á» va despues de
     * la «B». El documento solo desempata, para que dos homonimos salgan
     * siempre en el mismo orden en un padron que se publica.
     *
     * @return Collection<int, AsignacionExamen>
     */
    private function asignaciones(Examen $examen): Collection
    {
        return AsignacionExamen::query()
            ->whereHas('aulaExamen', fn (Builder $consulta) => $consulta->where('id_exa', $examen->id_exa))
            ->with([
                'aulaExamen.aula.sede',
                'inscripcion.postulante',
                'inscripcion.modalidad',
            ])
            ->get()
            ->sortBy(
                fn (AsignacionExamen $asignacion): string => Str::ascii(
                    mb_strtoupper($asignacion->inscripcion->postulante->nombreCompleto()),
                ).'|'.$asignacion->inscripcion->postulante->numero_documento_pos,
                SORT_NATURAL,
            )
            ->values();
    }
}
