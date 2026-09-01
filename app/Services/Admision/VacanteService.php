<?php

namespace App\Services\Admision;

use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Vacante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cuadro de vacantes de un proceso (Art. 15).
 *
 * La cantidad la aprueban las Escuelas Profesionales y no viene en el archivo
 * del formato oficial, asi que la importacion crea las filas en cero y esta
 * pantalla es la que las llena.
 *
 * @extends ServicioDeCatalogo<Vacante>
 */
class VacanteService extends ServicioDeCatalogo
{
    public function modelo(): string
    {
        return Vacante::class;
    }

    /**
     * Filas del cuadro, cada una con cuantos postulantes lleva inscritos.
     *
     * El conteo va como subconsulta correlacionada y no como conteo por fila:
     * el cuadro tiene una fila por carrera, modalidad y sede, y contarlas una
     * por una seria una consulta por carrera.
     *
     * @return Collection<int, Vacante>
     */
    public function cuadro(Proceso $proceso): Collection
    {
        return Vacante::query()
            ->select('tbl_vacante.*')
            ->selectSub($this->conteoDeInscritos(), 'inscritos')
            ->with(['modalidad', 'carrera.area', 'carrera.facultad', 'sede'])
            ->where('id_pro', $proceso->id_pro)
            ->get()
            ->sortBy(fn (Vacante $vacante): string => implode('|', [
                $vacante->modalidad->nombre_mod,
                $vacante->sede->nombre_sed,
                $vacante->carrera->area->numero_are,
                $vacante->carrera->nombre_car,
            ]))
            ->values();
    }

    /**
     * Inscripciones vigentes que corresponden a la fila del cuadro que se esta
     * seleccionando.
     *
     * @return Builder<Inscripcion>
     */
    private function conteoDeInscritos(): Builder
    {
        return Inscripcion::query()
            ->selectRaw('count(*)')
            ->whereColumn('tbl_inscripcion.id_pro', 'tbl_vacante.id_pro')
            ->whereColumn('tbl_inscripcion.id_mod', 'tbl_vacante.id_mod')
            ->whereColumn('tbl_inscripcion.id_car', 'tbl_vacante.id_car')
            ->whereColumn('tbl_inscripcion.id_sed', 'tbl_vacante.id_sed')
            ->vigente();
    }

    /**
     * Guarda de golpe las cantidades que se editaron en la tabla.
     *
     * @param  array<int|string, int|string|null>  $cantidades  id de la vacante => cantidad
     * @return int filas modificadas
     */
    public function guardarCantidades(Proceso $proceso, array $cantidades): int
    {
        $modificadas = 0;

        DB::transaction(function () use ($proceso, $cantidades, &$modificadas): void {
            $filas = Vacante::where('id_pro', $proceso->id_pro)
                ->whereIn('id_vac', array_keys($cantidades))
                ->get();

            foreach ($filas as $vacante) {
                $nueva = (int) ($cantidades[$vacante->id_vac] ?? 0);

                if ($nueva === $vacante->cantidad_vac) {
                    continue;
                }

                $vacante->cantidad_vac = max(0, $nueva);
                $vacante->save();
                $modificadas++;
            }
        });

        return $modificadas;
    }

    /**
     * @param  Vacante  $registro
     */
    protected function razonParaNoEliminar(Model $registro): ?string
    {
        $inscritos = Inscripcion::query()
            ->where('id_pro', $registro->id_pro)
            ->where('id_mod', $registro->id_mod)
            ->where('id_car', $registro->id_car)
            ->where('id_sed', $registro->id_sed)
            ->count();

        return $inscritos === 0
            ? null
            : "No se puede eliminar: hay {$inscritos} postulante(s) inscritos en esta carrera y modalidad.";
    }

    /**
     * Agrega una carrera al cuadro del proceso.
     *
     * @param  array<string, mixed>  $datos
     */
    public function agregar(Proceso $proceso, array $datos): Vacante
    {
        $existente = Vacante::where('id_pro', $proceso->id_pro)
            ->where('id_mod', $datos['id_mod'])
            ->where('id_car', $datos['id_car'])
            ->where('id_sed', $datos['id_sed'])
            ->exists();

        if ($existente) {
            throw new RuntimeException('Esa carrera ya está en el cuadro para la misma modalidad y sede.');
        }

        return $this->guardar($datos + ['id_pro' => $proceso->id_pro]);
    }

    /**
     * Totales de la cabecera de la pantalla.
     *
     * @return array{ofertadas: int, inscritos: int, sin_configurar: int}
     */
    public function resumen(Proceso $proceso): array
    {
        return [
            'ofertadas' => (int) Vacante::where('id_pro', $proceso->id_pro)->sum('cantidad_vac'),
            'inscritos' => Inscripcion::where('id_pro', $proceso->id_pro)->vigente()->count(),
            'sin_configurar' => Vacante::where('id_pro', $proceso->id_pro)->where('cantidad_vac', 0)->count(),
        ];
    }
}
