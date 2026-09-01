<?php

namespace App\Services\Admision;

use App\Models\AsignacionExamen;
use App\Models\Examen;
use App\Models\ExamenPostulante;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SorteadorAulasService
{
    public function sortear(Examen $examen, DistribucionAulasService $distribucion): int
    {
        if (! $distribucion->distribucionEstaCompleta($examen)) {
            throw new RuntimeException('La distribución de aulas no coincide con el total de inscritos por área.');
        }

        $postulantes = ExamenPostulante::query()
            ->where('id_exa', $examen->id_exa)
            ->with('inscripcion.postulante', 'inscripcion.carrera')
            ->get();

        if ($postulantes->contains(fn (ExamenPostulante $postulante): bool => $postulante->inscripcion === null)) {
            throw new RuntimeException('El padrón contiene postulantes que no se pudieron vincular a una inscripción.');
        }

        $porArea = $postulantes->groupBy(
            fn (ExamenPostulante $postulante): int => $postulante->inscripcion->carrera->id_are,
        );
        $aulasPorArea = $examen->aulas()->orderBy('id_eau')->get()->groupBy('id_are');
        $asignaciones = [];

        foreach ($aulasPorArea as $idArea => $aulas) {
            $ordenados = $this->intercalarApellidos($porArea->get($idArea, collect()));
            $cursor = 0;

            foreach ($aulas as $aula) {
                for ($asiento = 1; $asiento <= $aula->capacidad_eau; $asiento++) {
                    $postulante = $ordenados->get($cursor++);
                    if ($postulante === null) {
                        throw new RuntimeException('La distribución tiene más asientos que postulantes en un área.');
                    }

                    $asignaciones[] = [
                        'id_exp' => $postulante->id_exp,
                        'id_eau' => $aula->id_eau,
                        'asiento_ase' => $asiento,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if ($ordenados->count() !== $cursor) {
                throw new RuntimeException('La distribución no tiene asientos suficientes para un área.');
            }
        }

        DB::transaction(function () use ($examen, $asignaciones): void {
            AsignacionExamen::query()
                ->whereIn('id_eau', $examen->aulas()->select('id_eau'))
                ->delete();

            AsignacionExamen::insert($asignaciones);
        });

        return count($asignaciones);
    }

    /**
     * @param  Collection<int, ExamenPostulante>  $postulantes
     * @return Collection<int, ExamenPostulante>
     */
    private function intercalarApellidos(Collection $postulantes): Collection
    {
        $grupos = $postulantes->shuffle()->groupBy(
            fn (ExamenPostulante $postulante): string => $this->apellido($postulante),
        )->map(fn (Collection $grupo): Collection => $grupo->shuffle());
        $ordenados = collect();
        $apellidoAnterior = null;

        while ($grupos->isNotEmpty()) {
            $grupo = $grupos
                ->filter(fn (Collection $grupo, string $apellido): bool => $apellido !== $apellidoAnterior)
                ->sortByDesc(fn (Collection $grupo): int => $grupo->count())
                ->first();

            if ($grupo === null) {
                $grupo = $grupos->sortByDesc(fn (Collection $grupo): int => $grupo->count())->first();
            }

            $postulante = $grupo->shift();
            $apellidoAnterior = $this->apellido($postulante);
            $ordenados->push($postulante);

            if ($grupo->isEmpty()) {
                $grupos = $grupos->reject(fn (Collection $candidatos): bool => $candidatos->isEmpty());
            }
        }

        return $ordenados;
    }

    private function apellido(ExamenPostulante $postulante): string
    {
        return mb_strtoupper($postulante->inscripcion->postulante->primer_apellido_pos);
    }
}
