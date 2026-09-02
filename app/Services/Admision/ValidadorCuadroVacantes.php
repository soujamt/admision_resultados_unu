<?php

namespace App\Services\Admision;

use App\Enums\Convocatoria;
use App\Models\Carrera;
use App\Models\Proceso;
use App\Models\Vacante;
use Illuminate\Support\Collection;

/**
 * Contrasta el cuadro general de admision de un anio contra los dos limites
 * que el reglamento le pone.
 *
 * Art. 14: el cuadro general se reparte 25% en la primera convocatoria, 25%
 * en la segunda y 50% en la tercera.
 * Art. 16: las vacantes del CEPREUNU son hasta el 30% del total de cada
 * Escuela Profesional.
 *
 * Ninguno de los dos bloquea: la Primera Disposicion Transitoria somete la
 * cifra del Art. 16 a lo que aprueba cada Consejo de Facultad y ratifica el
 * Consejo Universitario, asi que aqui se informa y decide la comision.
 */
class ValidadorCuadroVacantes
{
    /** Tope del Art. 16, en porcentaje del total de la Escuela Profesional. */
    private const LIMITE_CEPREUNU = 30.0;

    /**
     * Margen con el que se acepta el reparto del Art. 14. El cuadro se arma
     * carrera por carrera y en numeros enteros, asi que un 25% exacto sobre el
     * total del anio casi nunca sale redondo.
     */
    private const TOLERANCIA_ART_14 = 1.0;

    /**
     * @return array{
     *     anio: int,
     *     total: int,
     *     completo: bool,
     *     art14: list<array{convocatoria: Convocatoria, proceso: ?Proceso, vacantes: int, esperadas: int, porcentaje: float, porcentaje_esperado: int, desvio: int, cumple: bool}>,
     *     art16: list<array{carrera: Carrera, total: int, cepreunu: int, porcentaje: float, excede: bool}>,
     *     observaciones: list<array{articulo: string, mensaje: string}>,
     *     cumple: bool
     * }
     */
    public function revisar(int $anio): array
    {
        $procesos = Proceso::query()
            ->where('anio_pro', $anio)
            ->get()
            ->keyBy(fn (Proceso $proceso): int => $proceso->convocatoria_pro->value);
        $vacantes = Vacante::query()
            ->whereIn('id_pro', $procesos->pluck('id_pro'))
            ->habilitada()
            ->with(['carrera', 'modalidad'])
            ->get();

        /*
         * Se mide sobre `cantidad_vac`, la cifra que aprueba el Consejo
         * Universitario. El arrastre de los Arts. 17, 18 y 19 engorda la
         * tercera convocatoria por mandato del propio reglamento y contarlo
         * aqui haria fallar el reparto del Art. 14 siempre.
         */
        $total = (int) $vacantes->sum('cantidad_vac');
        $completo = $procesos->count() === count(Convocatoria::cases());
        $observaciones = [];

        $art14 = $this->repartoPorConvocatoria($procesos, $vacantes, $total);
        $art16 = $this->cupoCepreunuPorCarrera($vacantes);

        if (! $completo) {
            $faltantes = collect(Convocatoria::cases())
                ->reject(fn (Convocatoria $convocatoria): bool => $procesos->has($convocatoria->value))
                ->map(fn (Convocatoria $convocatoria): string => $convocatoria->etiqueta())
                ->implode(', ');
            $observaciones[] = [
                'articulo' => 'Art. 14',
                'mensaje' => "El cuadro general de {$anio} todavía no está completo: falta configurar {$faltantes}. El reparto se revisará cuando existan las tres.",
            ];
        }

        if ($completo && $total > 0) {
            foreach ($art14 as $fila) {
                if ($fila['cumple']) {
                    continue;
                }

                $observaciones[] = [
                    'articulo' => 'Art. 14',
                    'mensaje' => sprintf(
                        '%s ofrece %d vacante(s), el %s%% del cuadro general, cuando le corresponde el %d%% (%d).',
                        $fila['convocatoria']->etiqueta(),
                        $fila['vacantes'],
                        number_format($fila['porcentaje'], 2),
                        $fila['porcentaje_esperado'],
                        $fila['esperadas'],
                    ),
                ];
            }
        }

        foreach ($art16 as $fila) {
            if (! $fila['excede']) {
                continue;
            }

            $observaciones[] = [
                'articulo' => 'Art. 16',
                'mensaje' => sprintf(
                    '%s da %d de %d vacante(s) al CEPREUNU, el %s%%, por encima del 30%% que permite el artículo.',
                    $fila['carrera']->nombre_car,
                    $fila['cepreunu'],
                    $fila['total'],
                    number_format($fila['porcentaje'], 2),
                ),
            ];
        }

        return [
            'anio' => $anio,
            'total' => $total,
            'completo' => $completo,
            'art14' => $art14,
            'art16' => $art16,
            'observaciones' => $observaciones,
            'cumple' => $observaciones === [],
        ];
    }

    /**
     * Art. 14: cuanto ofrece cada convocatoria frente al 25, 25 y 50 por
     * ciento que le toca del cuadro general del anio.
     *
     * @param  Collection<int, Proceso>  $procesos
     * @param  Collection<int, Vacante>  $vacantes
     * @return list<array{convocatoria: Convocatoria, proceso: ?Proceso, vacantes: int, esperadas: int, porcentaje: float, porcentaje_esperado: int, desvio: int, cumple: bool}>
     */
    private function repartoPorConvocatoria(Collection $procesos, Collection $vacantes, int $total): array
    {
        $filas = [];

        foreach (Convocatoria::cases() as $convocatoria) {
            $proceso = $procesos->get($convocatoria->value);
            $suyas = $proceso === null
                ? 0
                : (int) $vacantes->where('id_pro', $proceso->id_pro)->sum('cantidad_vac');
            $esperado = $convocatoria->porcentajeVacantes();
            $porcentaje = $total > 0 ? ($suyas / $total) * 100 : 0.0;
            $filas[] = [
                'convocatoria' => $convocatoria,
                'proceso' => $proceso,
                'vacantes' => $suyas,
                'esperadas' => (int) round($total * $esperado / 100),
                'porcentaje' => round($porcentaje, 2),
                'porcentaje_esperado' => $esperado,
                'desvio' => $suyas - (int) round($total * $esperado / 100),
                'cumple' => abs($porcentaje - $esperado) <= self::TOLERANCIA_ART_14,
            ];
        }

        return $filas;
    }

    /**
     * Art. 16: que parte del cuadro de cada Escuela Profesional se lleva el
     * CEPREUNU, sumando el anio completo.
     *
     * @param  Collection<int, Vacante>  $vacantes
     * @return list<array{carrera: Carrera, total: int, cepreunu: int, porcentaje: float, excede: bool}>
     */
    private function cupoCepreunuPorCarrera(Collection $vacantes): array
    {
        $filas = [];

        foreach ($vacantes->groupBy('id_car') as $suyas) {
            $total = (int) $suyas->sum('cantidad_vac');

            if ($total === 0) {
                continue;
            }

            $cepreunu = (int) $suyas
                ->filter(fn (Vacante $vacante): bool => $vacante->modalidad->esCepreunu())
                ->sum('cantidad_vac');
            $porcentaje = ($cepreunu / $total) * 100;
            $filas[] = [
                'carrera' => $suyas->first()->carrera,
                'total' => $total,
                'cepreunu' => $cepreunu,
                'porcentaje' => round($porcentaje, 2),
                'excede' => $porcentaje > self::LIMITE_CEPREUNU,
            ];
        }

        usort($filas, fn (array $a, array $b): int => $b['porcentaje'] <=> $a['porcentaje']);

        return $filas;
    }
}
