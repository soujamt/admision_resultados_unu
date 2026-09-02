<?php

namespace App\Services\Admision;

use App\Models\AsignacionExamen;
use App\Models\Examen;
use App\Models\Inscripcion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplPriorityQueue;

class SorteadorAulasService
{
    /** Filas por sentencia al guardar las asignaciones. */
    private const TAMANIO_LOTE = 500;

    public function sortear(Examen $examen, DistribucionAulasService $distribucion): int
    {
        $motivo = $distribucion->motivoParaNoSortear($examen);

        if ($motivo !== null) {
            throw new RuntimeException($motivo);
        }

        $postulantes = Inscripcion::query()
            ->where('id_pro', $examen->id_pro)
            ->with('postulante', 'carrera')
            ->get();

        if ($postulantes->contains(
            fn (Inscripcion $inscripcion): bool => $inscripcion->postulante === null || $inscripcion->carrera === null,
        )) {
            throw new RuntimeException('Hay inscripciones sin postulante o carrera y no se pueden sortear.');
        }

        $porArea = $postulantes->groupBy(
            fn (Inscripcion $inscripcion): int => $inscripcion->carrera->id_are,
        );
        $aulasPorArea = $examen->aulas()->orderBy('id_eau')->get()->groupBy('id_are');
        $asignaciones = [];
        $ahora = now();

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
                        'id_ins' => $postulante->id_ins,
                        'id_eau' => $aula->id_eau,
                        'asiento_ase' => $asiento,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
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

            /*
             * Un examen ordinario pasa de tres mil postulantes: insertarlos en
             * una sola sentencia revienta el limite de paquete de MySQL.
             */
            foreach (array_chunk($asignaciones, self::TAMANIO_LOTE) as $lote) {
                AsignacionExamen::insert($lote);
            }
        });

        return count($asignaciones);
    }

    /**
     * Ordena a los postulantes de un area separando en lo posible a los que
     * comparten primer apellido, para que dos parientes no queden en asientos
     * contiguos.
     *
     * Es el mismo problema que reordenar una cadena sin caracteres repetidos
     * adyacentes: en cada paso se toma el apellido que mas gente tiene pendiente
     * y se aparta el que se acaba de usar hasta el turno siguiente. Con una cola
     * de prioridad el costo es O(n log k) sobre k apellidos distintos, en vez de
     * reordenar todos los grupos en cada una de las n vueltas.
     *
     * @param  Collection<int, Inscripcion>  $postulantes
     * @return Collection<int, Inscripcion>
     */
    private function intercalarApellidos(Collection $postulantes): Collection
    {
        /** @var SplPriorityQueue<array{int, int}, array{string, list<Inscripcion>}> $cola */
        $cola = new SplPriorityQueue;
        $cola->setExtractFlags(SplPriorityQueue::EXTR_DATA);

        $grupos = $postulantes->shuffle()->groupBy(fn (Inscripcion $inscripcion): string => $this->apellido($inscripcion));

        foreach ($grupos as $apellido => $miembros) {
            /*
             * El desempate va al azar: si no, dos apellidos con la misma
             * cantidad de postulantes saldrian siempre en el mismo orden y el
             * sorteo dejaria de serlo.
             */
            $cola->insert([(string) $apellido, $miembros->all()], [$miembros->count(), random_int(0, PHP_INT_MAX)]);
        }

        $ordenados = [];
        $enEspera = null;

        while (! $cola->isEmpty()) {
            [$apellido, $miembros] = $cola->extract();

            $ordenados[] = array_shift($miembros);

            /* El apellido del turno anterior vuelve a la cola recien ahora. */
            if ($enEspera !== null) {
                $cola->insert($enEspera, [count($enEspera[1]), random_int(0, PHP_INT_MAX)]);
                $enEspera = null;
            }

            if ($miembros !== []) {
                $enEspera = [$apellido, $miembros];
            }
        }

        /*
         * Queda pendiente cuando un apellido concentra mas de la mitad del
         * area: ahi es imposible no repetir y sus ultimos postulantes van al
         * final.
         */
        if ($enEspera !== null) {
            $ordenados = array_merge($ordenados, $enEspera[1]);
        }

        return collect($ordenados);
    }

    private function apellido(Inscripcion $inscripcion): string
    {
        return mb_strtoupper($inscripcion->postulante->primer_apellido_pos);
    }
}
