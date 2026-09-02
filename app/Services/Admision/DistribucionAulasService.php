<?php

namespace App\Services\Admision;

use App\Models\Area;
use App\Models\Aula;
use App\Models\Examen;
use App\Models\ExamenAula;
use App\Models\Inscripcion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Reparto de las aulas de una jornada entre las areas academicas.
 *
 * Cada aula se asigna a una sola area y admite como maximo 40 postulantes,
 * siempre que su capacidad fisica tambien lo permita.
 */
class DistribucionAulasService
{
    public const CAPACIDAD_MAXIMA = 40;

    /**
     * @param  array{id_aul:int, id_are:int, capacidad_eau:int}  $fila
     */
    public function agregar(Examen $examen, array $fila): ExamenAula
    {
        try {
            return DB::transaction(function () use ($examen, $fila): ExamenAula {
                $this->validarFila($fila);

                if ($this->yaAsignada($examen, $fila['id_aul'])) {
                    throw new RuntimeException('Esta aula ya está asignada a la jornada seleccionada.');
                }

                return ExamenAula::create($fila + ['id_exa' => $examen->id_exa]);
            }, 3);
        } catch (RuntimeException $error) {
            throw $error;
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo agregar el aula. Inténtalo nuevamente.');
        }
    }

    /**
     * Si el aula ya forma parte de la distribucion de la jornada. La pantalla
     * lo consulta antes de guardar para poder senalar el campo correcto en vez
     * de dejar que salte la excepcion.
     */
    public function yaAsignada(Examen $examen, int $idAula): bool
    {
        return ExamenAula::query()
            ->where('id_exa', $examen->id_exa)
            ->where('id_aul', $idAula)
            ->exists();
    }

    public function retirar(Examen $examen, int $idAulaExamen): bool
    {
        try {
            return DB::transaction(fn (): bool => ExamenAula::query()
                ->where('id_exa', $examen->id_exa)
                ->whereKey($idAulaExamen)
                ->delete() > 0, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo retirar el aula. Inténtalo nuevamente.');
        }
    }

    /**
     * @param  array<int, array{id_aul:int, id_are:int, capacidad_eau:int}>  $filas
     */
    public function guardar(Examen $examen, array $filas): void
    {
        $aulas = [];

        foreach ($filas as $fila) {
            if (isset($aulas[$fila['id_aul']])) {
                throw new RuntimeException('Un aula solo puede pertenecer a una área en la misma jornada.');
            }

            $aulas[$fila['id_aul']] = true;
            $this->validarFila($fila);
        }

        try {
            DB::transaction(function () use ($examen, $filas): void {
                ExamenAula::where('id_exa', $examen->id_exa)->delete();
                ExamenAula::insert(array_map(
                    fn (array $fila): array => $fila + ['id_exa' => $examen->id_exa, 'created_at' => now(), 'updated_at' => now()],
                    $filas,
                ));
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo guardar la distribución de aulas. Inténtalo nuevamente.');
        }
    }

    /**
     * @param  array{id_aul:int, id_are:int, capacidad_eau:int}  $fila
     */
    private function validarFila(array $fila): void
    {
        $aula = Aula::find($fila['id_aul']);
        if ($aula === null) {
            throw new RuntimeException('Una de las aulas seleccionadas no existe.');
        }

        $capacidadPermitida = min(self::CAPACIDAD_MAXIMA, $aula->capacidad_aul);

        if ($fila['capacidad_eau'] < 1 || $fila['capacidad_eau'] > $capacidadPermitida) {
            throw new RuntimeException(
                "El aula «{$aula->etiqueta()}» permite hasta {$capacidadPermitida} postulantes: ".
                "no se le pueden asignar {$fila['capacidad_eau']} postulantes."
            );
        }
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
        return $this->areasIncompletas($examen)->isEmpty();
    }

    /**
     * Areas cuya capacidad no coincide con sus inscritos.
     *
     * @return Collection<int, array{id_are:int, inscritos:int, capacidad:int, diferencia:int}>
     */
    public function areasIncompletas(Examen $examen): Collection
    {
        return $this->totalesPorArea($examen)
            ->filter(fn (array $total): bool => $total['diferencia'] !== 0)
            ->values();
    }

    /**
     * Explica por que no se puede sortear, reutilizando los totales y areas que
     * la pantalla ya consulto para no repetir esas operaciones.
     *
     * @param  Collection<int, array{id_are:int, inscritos:int, capacidad:int, diferencia:int}>|null  $totales
     * @param  Collection<int, Area>|null  $areas
     */
    public function motivoParaNoSortear(Examen $examen, ?Collection $totales = null, ?Collection $areas = null): ?string
    {
        $incompletas = ($totales ?? $this->totalesPorArea($examen))
            ->filter(fn (array $total): bool => $total['diferencia'] !== 0)
            ->values();

        if ($incompletas->isEmpty()) {
            return null;
        }

        $areas ??= Area::query()
            ->whereIn('id_are', $incompletas->pluck('id_are'))
            ->get()
            ->keyBy('id_are');

        $detalle = $incompletas->map(function (array $total) use ($areas): string {
            $nombre = $areas[$total['id_are']]?->etiqueta() ?? 'Área '.$total['id_are'];

            return $total['diferencia'] > 0
                ? "{$nombre}: sobran {$total['diferencia']} cupos"
                : "{$nombre}: faltan ".abs($total['diferencia']).' cupos';
        })->join('; ');

        return 'La distribución todavía no cuadra con los inscritos. '.$detalle.'.';
    }
}
