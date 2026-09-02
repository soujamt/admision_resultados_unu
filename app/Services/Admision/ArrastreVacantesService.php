<?php

namespace App\Services\Admision;

use App\Enums\CondicionIngresante;
use App\Enums\Convocatoria;
use App\Enums\GrupoModalidad;
use App\Models\Ingresante;
use App\Models\Proceso;
use App\Models\Vacante;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Traslada al cuadro de vacantes las plazas que el reglamento manda arrastrar.
 *
 * Art. 17: las vacantes de la primera y segunda convocatoria que no se
 * cubrieron pasan a la tercera en su misma modalidad.
 * Art. 18: las que liberaron los ingresantes que perdieron su condicion por la
 * Primera Disposicion Complementaria y el Art. 92 pasan por la misma via.
 * Art. 19: dentro de la tercera convocatoria, lo que no cubren Exoneracion,
 * Convenios, PRONABEC y Traslados incrementa el examen ordinario.
 *
 * El resultado se guarda en `cantidad_arrastre_vac`, nunca sobre la cifra que
 * aprobo el Consejo Universitario, y se recalcula entero en cada pasada para
 * que aplicarlo dos veces no duplique plazas.
 */
class ArrastreVacantesService
{
    /**
     * Grupos cuyas vacantes sin cubrir incrementan el examen ordinario.
     */
    private const GRUPOS_ART_19 = [
        GrupoModalidad::Exoneracion,
        GrupoModalidad::Convenio,
        GrupoModalidad::Pronabec,
        GrupoModalidad::Traslado,
    ];

    /**
     * @return array{lineas: list<array{vacante:Vacante, art17:int, art18:int, art19:int, total:int, origenes:list<string>}>, total:int, sin_sustituto:int}
     */
    public function calcular(Proceso $proceso): array
    {
        if ($proceso->convocatoria_pro !== Convocatoria::Tercera) {
            throw new RuntimeException('El arrastre de los Arts. 17, 18 y 19 solo corresponde a la tercera convocatoria.');
        }

        $destinos = $proceso->vacantes()->habilitada()->with(['carrera', 'modalidad', 'sede'])->get();

        if ($destinos->isEmpty()) {
            throw new RuntimeException('La tercera convocatoria todavía no tiene cuadro de vacantes.');
        }

        $acumulado = [];
        $sinSustituto = 0;

        foreach ($this->convocatoriasPrevias($proceso) as $previo) {
            foreach ($this->conteos($previo) as $origen) {
                $destino = $this->destinoMismaModalidad($destinos, $origen['vacante']);

                if ($destino === null) {
                    continue;
                }

                $sinSustituto += $origen['sin_sustituto'];
                $this->acumular($acumulado, $destino, 'art17', $origen['nunca_cubiertas'], $previo->codigo_pro);
                $this->acumular($acumulado, $destino, 'art18', $origen['liberadas'], $previo->codigo_pro);
            }
        }

        foreach ($this->conteos($proceso) as $origen) {
            $sinSustituto += $origen['sin_sustituto'];

            if (! in_array($origen['vacante']->modalidad->grupo_mod, self::GRUPOS_ART_19, true)) {
                continue;
            }

            $destino = $this->destinoOrdinario($destinos, $origen['vacante']);

            if ($destino === null) {
                continue;
            }

            $this->acumular(
                $acumulado,
                $destino,
                'art19',
                $origen['nunca_cubiertas'] + $origen['liberadas'],
                $origen['vacante']->modalidad->nombre_mod,
            );
        }

        $lineas = array_values(array_filter(
            $acumulado,
            fn (array $linea): bool => $linea['total'] > 0,
        ));
        usort($lineas, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return [
            'lineas' => $lineas,
            'total' => array_sum(array_column($lineas, 'total')),
            'sin_sustituto' => $sinSustituto,
        ];
    }

    /**
     * @return array{lineas: list<array{vacante:Vacante, art17:int, art18:int, art19:int, total:int, origenes:list<string>}>, total:int, sin_sustituto:int, vacantes_tocadas:int}
     */
    public function aplicar(Proceso $proceso): array
    {
        $calculo = $this->calcular($proceso);
        $porVacante = [];

        foreach ($calculo['lineas'] as $linea) {
            $porVacante[$linea['vacante']->id_vac] = $linea;
        }

        try {
            DB::transaction(function () use ($proceso, $porVacante): void {
                foreach ($proceso->vacantes()->get() as $vacante) {
                    $linea = $porVacante[$vacante->id_vac] ?? null;
                    $arrastre = $linea === null ? 0 : $linea['total'];

                    if ($vacante->cantidad_arrastre_vac === $arrastre && $arrastre === 0) {
                        continue;
                    }

                    $vacante->update([
                        'cantidad_arrastre_vac' => $arrastre,
                        'motivo_arrastre_vac' => $linea === null ? null : $this->motivo($linea),
                    ]);
                }
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo aplicar el arrastre de vacantes.');
        }

        return $calculo + ['vacantes_tocadas' => count($porVacante)];
    }

    /**
     * @return Collection<int, Proceso>
     */
    private function convocatoriasPrevias(Proceso $proceso): Collection
    {
        return Proceso::query()
            ->where('anio_pro', $proceso->anio_pro)
            ->whereIn('convocatoria_pro', [Convocatoria::Primera, Convocatoria::Segunda])
            ->orderBy('convocatoria_pro')
            ->get();
    }

    /**
     * Plazas nunca cubiertas y plazas liberadas de cada vacante de un proceso.
     *
     * Se mide contra el padron de ingresantes, no contra los resultados: una
     * plaza solo esta cubierta si su ingresante conserva la condicion.
     *
     * @return list<array{vacante:Vacante, nunca_cubiertas:int, liberadas:int, sin_sustituto:int}>
     */
    private function conteos(Proceso $proceso): array
    {
        $vacantes = $proceso->vacantes()->habilitada()->with('modalidad')->get();
        $ingresantes = Ingresante::query()
            ->where('id_pro', $proceso->id_pro)
            ->with('sustituto')
            ->get()
            ->groupBy('id_vac');

        if ($ingresantes->isEmpty() && $proceso->examenes()->whereNotNull('resuelto_en_exa')->exists()) {
            throw new RuntimeException("El proceso {$proceso->codigo_pro} tiene resultados pero no tiene padrón de ingresantes generado.");
        }

        $conteos = [];

        foreach ($vacantes as $vacante) {
            /** @var Collection<int, Ingresante> $suyos */
            $suyos = $ingresantes->get($vacante->id_vac, collect());
            $vigentes = $suyos->filter(fn (Ingresante $ingresante): bool => $ingresante->estaVigente())->count();
            $liberadas = $suyos->filter(
                fn (Ingresante $ingresante): bool => $ingresante->condicion_ing->generaArrastre(),
            )->count();

            /*
             * El Art. 93 resuelve la plaza de quien no matricula llamando al
             * inmediato inferior, asi que no se arrastra. Cuando no hubo a
             * quien llamar se informa aparte, para que la comision lo vea.
             */
            $sinSustituto = $suyos->filter(
                fn (Ingresante $ingresante): bool => $ingresante->condicion_ing === CondicionIngresante::SinMatricula
                    && $ingresante->sustituto === null,
            )->count();

            $conteos[] = [
                'vacante' => $vacante,
                'nunca_cubiertas' => max(0, $vacante->plazas() - $suyos->count()),
                'liberadas' => $liberadas,
                'sin_sustituto' => $sinSustituto,
            ];
        }

        return $conteos;
    }

    /**
     * @param  Collection<int, Vacante>  $destinos
     */
    private function destinoMismaModalidad(Collection $destinos, Vacante $origen): ?Vacante
    {
        return $destinos->first(fn (Vacante $destino): bool => $destino->id_mod === $origen->id_mod
            && $destino->id_car === $origen->id_car
            && $destino->id_sed === $origen->id_sed);
    }

    /**
     * @param  Collection<int, Vacante>  $destinos
     */
    private function destinoOrdinario(Collection $destinos, Vacante $origen): ?Vacante
    {
        return $destinos->first(fn (Vacante $destino): bool => $destino->modalidad->grupo_mod === GrupoModalidad::Ordinario
            && $destino->id_car === $origen->id_car
            && $destino->id_sed === $origen->id_sed);
    }

    /**
     * @param  array<int, array{vacante:Vacante, art17:int, art18:int, art19:int, total:int, origenes:list<string>}>  $acumulado
     */
    private function acumular(array &$acumulado, Vacante $destino, string $articulo, int $plazas, string $origen): void
    {
        if ($plazas <= 0) {
            return;
        }

        $acumulado[$destino->id_vac] ??= [
            'vacante' => $destino,
            'art17' => 0,
            'art18' => 0,
            'art19' => 0,
            'total' => 0,
            'origenes' => [],
        ];
        $acumulado[$destino->id_vac][$articulo] += $plazas;
        $acumulado[$destino->id_vac]['total'] += $plazas;

        if (! in_array($origen, $acumulado[$destino->id_vac]['origenes'], true)) {
            $acumulado[$destino->id_vac]['origenes'][] = $origen;
        }
    }

    /**
     * @param  array{art17:int, art18:int, art19:int, origenes:list<string>}  $linea
     */
    private function motivo(array $linea): string
    {
        $partes = [];

        foreach (['art17' => 'Art. 17', 'art18' => 'Art. 18', 'art19' => 'Art. 19'] as $clave => $etiqueta) {
            if ($linea[$clave] > 0) {
                $partes[] = "{$etiqueta}: {$linea[$clave]}";
            }
        }

        return mb_substr(implode(' · ', $partes).' · Origen: '.implode(', ', $linea['origenes']), 0, 255);
    }
}
