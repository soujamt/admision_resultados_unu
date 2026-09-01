<?php

namespace App\Services\Admision;

use App\Models\Aula;
use App\Models\Examen;
use App\Models\ExamenAula;
use App\Models\Inscripcion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DistribucionAulasService
{
    public const CAPACIDAD_MAXIMA = 40;

    /**
     * @param  array<int, array{id_aul:int, id_are:int, capacidad_eau:int}>  $filas
     */
    public function guardar(Examen $examen, array $filas): void
    {
        $aulas = [];
        $aulasConfiguradas = Aula::query()
            ->whereIn('id_aul', array_column($filas, 'id_aul'))
            ->get()
            ->keyBy('id_aul');

        foreach ($filas as $fila) {
            $aula = $aulasConfiguradas->get($fila['id_aul']);
            if ($aula === null) {
                throw new RuntimeException('Una de las aulas seleccionadas no existe.');
            }

            if ($fila['capacidad_eau'] < 1 || $fila['capacidad_eau'] > min(self::CAPACIDAD_MAXIMA, $aula->capacidad_aul)) {
                throw new RuntimeException('Cada aula debe tener entre 1 y 40 postulantes.');
            }

            if (isset($aulas[$fila['id_aul']])) {
                throw new RuntimeException('Un aula solo puede pertenecer a una área en la misma jornada.');
            }

            $aulas[$fila['id_aul']] = true;
        }

        DB::transaction(function () use ($examen, $filas): void {
            ExamenAula::where('id_exa', $examen->id_exa)->delete();
            ExamenAula::insert(array_map(
                fn (array $fila): array => $fila + ['id_exa' => $examen->id_exa, 'created_at' => now(), 'updated_at' => now()],
                $filas,
            ));
        });
    }

    /**
     * @return Collection<int, array{id_are:int, inscritos:int, capacidad:int, diferencia:int}>
     */
    public function totalesPorArea(Examen $examen): Collection
    {
        $inscritos = Inscripcion::query()
            ->where('tbl_inscripcion.id_pro', $examen->id_pro)
            ->join('tbl_carrera', 'tbl_carrera.id_car', '=', 'tbl_inscripcion.id_car')
            ->selectRaw('tbl_carrera.id_are, count(*) as total')
            ->groupBy('tbl_carrera.id_are')
            ->pluck('total', 'id_are');

        $capacidades = ExamenAula::query()
            ->where('id_exa', $examen->id_exa)
            ->selectRaw('id_are, sum(capacidad_eau) as total')
            ->groupBy('id_are')
            ->pluck('total', 'id_are');

        return $inscritos->keys()->merge($capacidades->keys())->unique()->sort()->map(
            fn (int|string $idArea): array => [
                'id_are' => (int) $idArea,
                'inscritos' => (int) ($inscritos[$idArea] ?? 0),
                'capacidad' => (int) ($capacidades[$idArea] ?? 0),
                'diferencia' => (int) ($capacidades[$idArea] ?? 0) - (int) ($inscritos[$idArea] ?? 0),
            ],
        )->values();
    }

    public function distribucionEstaCompleta(Examen $examen): bool
    {
        return $this->totalesPorArea($examen)->every(
            fn (array $total): bool => $total['diferencia'] === 0,
        );
    }
}
