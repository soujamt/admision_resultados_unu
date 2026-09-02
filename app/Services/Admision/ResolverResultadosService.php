<?php

namespace App\Services\Admision;

use App\Enums\Convocatoria;
use App\Enums\EstadoResultado;
use App\Enums\GrupoModalidad;
use App\Models\Examen;
use App\Models\Resultado;
use App\Models\Vacante;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Convierte las lecturas opticas en el padron oficial de resultados.
 *
 * Aplica el Art. 77 al puntaje directo, el Art. 80 al factor de dificultad,
 * el Art. 81 a la nota final minima, el Art. 23 al pase automatico al examen
 * ordinario, el Art. 85 al empate de la ultima vacante y el Art. 76 al NSP.
 */
class ResolverResultadosService
{
    /**
     * @return array{postulantes:int,ingresantes:int,no_ingresantes:int,nsp:int,anulados:int,repescados:int,vacantes:int,desiertas:int,porcentaje_desiertas:float,factor_aplicado:bool,requiere_examen_complementario:bool}
     */
    public function resolver(Examen $examen): array
    {
        $examen->load([
            'proceso',
            'postulantes.respuesta',
            'postulantes.inscripcion.modalidad',
            'postulantes.inscripcion.carrera',
        ]);

        if ($examen->postulantes->isEmpty()) {
            throw new RuntimeException('Importa primero el padrón de postulantes del lector óptico.');
        }

        $sinInscripcion = $examen->postulantes->whereNull('id_ins')->count();

        if ($sinInscripcion > 0) {
            throw new RuntimeException("Hay {$sinInscripcion} postulante(s) que no cruzan con las inscripciones del proceso.");
        }

        $vacantes = $examen->proceso->vacantes()
            ->habilitada()
            ->with(['carrera', 'modalidad', 'sede'])
            ->get();
        $datos = $this->puntajesDirectos($examen, $vacantes);
        $ofrecidas = (int) $vacantes->sum(fn (Vacante $vacante): int => $vacante->plazas());

        $sinFactor = $this->adjudicar($datos, $vacantes);
        $factores = $this->factoresPorCarrera($examen, $datos, $ofrecidas, count($sinFactor));

        if ($factores !== []) {
            foreach ($datos as $id => $fila) {
                $factor = $factores[$fila['id_car']] ?? 1.0;
                $datos[$id]['factor'] = round($factor, 6);
                $datos[$id]['puntaje'] = $fila['puntaje_directo'] === null
                    ? null
                    : round($fila['puntaje_directo'] * $factor, 4);
            }
        }

        $admitidos = $factores === [] ? $sinFactor : $this->adjudicar($datos, $vacantes);
        $datos = $this->resolverEstados($datos, $admitidos);

        $this->guardar($examen, $datos);

        return $this->resumen($examen, $ofrecidas, $datos, $factores !== []);
    }

    /**
     * Art. 77: cada acierto, error y respuesta en blanco vale lo que configure
     * la jornada. El postulante sin lectura optica es NSP por el Art. 76 y el
     * anulado por los Arts. 79, 96 y 105 al 108 queda fuera del concurso.
     *
     * @param  Collection<int, Vacante>  $vacantes
     * @return array<int, array<string, mixed>>
     */
    private function puntajesDirectos(Examen $examen, Collection $vacantes): array
    {
        $vacantesPorClave = $vacantes->keyBy(
            fn (Vacante $vacante): string => $this->claveVacante($vacante->id_mod, $vacante->id_car, $vacante->id_sed),
        );
        $datos = [];
        $sinVacante = [];
        $codigosIncompatibles = [];

        foreach ($examen->postulantes as $postulante) {
            $inscripcion = $postulante->inscripcion;
            $vacante = $vacantesPorClave->get(
                $this->claveVacante($inscripcion->id_mod, $inscripcion->id_car, $inscripcion->id_sed),
            );

            if ($vacante === null) {
                $sinVacante[] = $postulante->documento_exp;

                continue;
            }

            $codigoCarrera = trim((string) $postulante->codigo_carrera_exp);
            $codigoModalidad = trim((string) $postulante->codigo_modalidad_exp);

            if (
                ($codigoCarrera !== '' && $vacante->codigo_externo_vac !== null && $codigoCarrera !== (string) $vacante->codigo_externo_vac)
                || ($codigoModalidad !== '' && $inscripcion->modalidad->codigo_externo_mod !== null && $codigoModalidad !== (string) $inscripcion->modalidad->codigo_externo_mod)
            ) {
                $codigosIncompatibles[] = $postulante->documento_exp;
            }

            $respuesta = $postulante->respuesta;
            $anulado = $postulante->estaAnulado();
            $puntajeDirecto = $respuesta === null || $anulado ? null : round(
                ($respuesta->aciertos_exr * (float) $examen->puntaje_acierto_exa)
                + (($respuesta->errores_exr + $respuesta->dobles_exr) * (float) $examen->puntaje_error_exa)
                + ($respuesta->blancos_exr * (float) $examen->puntaje_blanco_exa),
                4,
            );

            $datos[$postulante->id_exp] = [
                'postulante' => $postulante,
                'vacante' => $vacante,
                'modalidad' => $inscripcion->modalidad,
                'id_car' => $inscripcion->id_car,
                'id_sed' => $inscripcion->id_sed,
                'puntaje_directo' => $puntajeDirecto,
                'puntaje' => $puntajeDirecto,
                'factor' => 1.0,
                'minimo' => (float) ($inscripcion->carrera->puntaje_minimo_car ?? $examen->puntaje_minimo_exa),
                'id_vac' => $vacante->id_vac,
                'repesca' => false,
                'estado' => match (true) {
                    $anulado => EstadoResultado::Anulado,
                    $respuesta === null => EstadoResultado::Nsp,
                    default => EstadoResultado::NoIngreso,
                },
                'motivo' => match (true) {
                    $anulado => $postulante->motivo_anulacion_exp ?? 'Postulación anulada por la Comisión Central de Admisión.',
                    $respuesta === null => 'No se presentó o no existe lectura óptica.',
                    default => null,
                },
            ];
        }

        if ($sinVacante !== []) {
            throw new RuntimeException('No existe una vacante habilitada para '.count($sinVacante).' postulante(s). Revisa modalidad, carrera y sede.');
        }

        if ($codigosIncompatibles !== []) {
            throw new RuntimeException('Los códigos de carrera o modalidad del TXT no coinciden con la oferta configurada para '.count($codigosIncompatibles).' postulante(s).');
        }

        return $datos;
    }

    /**
     * Art. 80: si el examen deja sin cubrir al menos el umbral configurado de
     * las vacantes ofrecidas, se aplica un factor de dificultad por carrera
     * profesional, con FDE = 1 + (100 - PME) / 100 sobre el puntaje maximo
     * obtenido en esa carrera. Devuelve un arreglo vacio si no corresponde.
     *
     * @param  array<int, array<string, mixed>>  $datos
     * @return array<int, float>
     */
    private function factoresPorCarrera(Examen $examen, array $datos, int $ofrecidas, int $cubiertas): array
    {
        if (! $examen->aplicar_factor_dificultad_exa || $ofrecidas === 0) {
            return [];
        }

        $porcentajeSinCubrir = (max(0, $ofrecidas - $cubiertas) / $ofrecidas) * 100;

        if ($porcentajeSinCubrir < (float) $examen->umbral_factor_dificultad_exa) {
            return [];
        }

        $factores = [];

        foreach (collect($datos)->groupBy('id_car') as $idCarrera => $filas) {
            $puntajeMaximo = $filas->pluck('puntaje_directo')->filter(fn (?float $puntaje): bool => $puntaje !== null)->max();

            if ($puntajeMaximo === null || $puntajeMaximo <= 0) {
                continue;
            }

            $factores[(int) $idCarrera] = 1 + ((100 - $puntajeMaximo) / 100);
        }

        return $factores;
    }

    /**
     * Reparte las plazas de cada vacante en estricto orden de merito. Las
     * vacantes propias de cada modalidad se adjudican primero para que el
     * Art. 23 solo alcance a quien ya perdio su concurso original.
     *
     * @param  array<int, array<string, mixed>>  $datos
     * @param  Collection<int, Vacante>  $vacantes
     * @return array<int, array{id_vac: int, repesca: bool}>
     */
    private function adjudicar(array $datos, Collection $vacantes): array
    {
        $admitidos = [];
        [$ordinarias, $propias] = $vacantes->partition(
            fn (Vacante $vacante): bool => $vacante->modalidad->grupo_mod === GrupoModalidad::Ordinario,
        );

        foreach ($propias as $vacante) {
            foreach ($this->ganadores($this->postulantesDe($datos, $vacante->id_vac), $vacante->plazas(), $datos) as $id) {
                $admitidos[$id] = ['id_vac' => $vacante->id_vac, 'repesca' => false];
            }
        }

        foreach ($ordinarias as $vacante) {
            $repescados = $this->repescados($datos, $admitidos, $vacante);
            $competidores = [...$this->postulantesDe($datos, $vacante->id_vac), ...$repescados];

            foreach ($this->ganadores($competidores, $vacante->plazas(), $datos) as $id) {
                $admitidos[$id] = ['id_vac' => $vacante->id_vac, 'repesca' => in_array($id, $repescados, true)];
            }
        }

        return $admitidos;
    }

    /**
     * Postulantes inscritos en esa vacante que alcanzan la nota minima.
     *
     * @param  array<int, array<string, mixed>>  $datos
     * @return list<int>
     */
    private function postulantesDe(array $datos, int $idVacante): array
    {
        return array_keys(array_filter(
            $datos,
            fn (array $fila): bool => $fila['vacante']->id_vac === $idVacante && $this->alcanzaMinimo($fila),
        ));
    }

    /**
     * Art. 23: quien no logro vacante por exoneracion, convenio o traslado
     * compite por el examen ordinario de su misma carrera y sede.
     *
     * @param  array<int, array<string, mixed>>  $datos
     * @param  array<int, array{id_vac: int, repesca: bool}>  $admitidos
     * @return list<int>
     */
    private function repescados(array $datos, array $admitidos, Vacante $ordinaria): array
    {
        return array_keys(array_filter(
            $datos,
            fn (array $fila, int $id): bool => ! isset($admitidos[$id])
                && $fila['id_car'] === $ordinaria->id_car
                && $fila['id_sed'] === $ordinaria->id_sed
                && $fila['modalidad']->pasaAlExamenOrdinario()
                && $this->alcanzaMinimo($fila),
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    /**
     * Art. 85: las plazas se reparten en estricto orden de merito y el empate
     * en la ultima vacante admite el ingreso de todos los empatados.
     *
     * @param  list<int>  $ids
     * @param  array<int, array<string, mixed>>  $datos
     * @return list<int>
     */
    private function ganadores(array $ids, int $plazas, array $datos): array
    {
        if ($plazas <= 0 || $ids === []) {
            return [];
        }

        usort($ids, fn (int $a, int $b): int => $datos[$b]['puntaje'] <=> $datos[$a]['puntaje']);

        if (count($ids) <= $plazas) {
            return $ids;
        }

        $corte = $datos[$ids[$plazas - 1]]['puntaje'];

        return array_values(array_filter($ids, fn (int $id): bool => $datos[$id]['puntaje'] >= $corte));
    }

    /** @param array<string, mixed> $fila */
    private function alcanzaMinimo(array $fila): bool
    {
        return $fila['puntaje'] !== null && $fila['puntaje'] >= $fila['minimo'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $datos
     * @param  array<int, array{id_vac: int, repesca: bool}>  $admitidos
     * @return array<int, array<string, mixed>>
     */
    private function resolverEstados(array $datos, array $admitidos): array
    {
        foreach ($datos as $id => $fila) {
            if (in_array($fila['estado'], [EstadoResultado::Anulado, EstadoResultado::Nsp], true)) {
                continue;
            }

            if (! isset($admitidos[$id])) {
                $datos[$id]['motivo'] = $fila['puntaje'] < $fila['minimo']
                    ? 'No alcanzó la nota final mínima del Art. 81.'
                    : 'No alcanzó vacante por orden de mérito.';

                continue;
            }

            $datos[$id]['estado'] = EstadoResultado::Ingreso;
            $datos[$id]['id_vac'] = $admitidos[$id]['id_vac'];
            $datos[$id]['repesca'] = $admitidos[$id]['repesca'];
            $datos[$id]['motivo'] = $admitidos[$id]['repesca']
                ? 'Pasó al examen ordinario por el Art. 23 y alcanzó vacante por orden de mérito.'
                : 'Ingresó por orden de mérito y disponibilidad de vacante.';
        }

        return $datos;
    }

    /** @param array<int, array<string, mixed>> $datos */
    private function guardar(Examen $examen, array $datos): void
    {
        $idsConPuntaje = array_keys(array_filter($datos, fn (array $fila): bool => $fila['puntaje'] !== null));
        $ordenGeneral = $this->ordenes($idsConPuntaje, $datos);
        $ordenCarrera = [];

        foreach (collect($idsConPuntaje)->groupBy(fn (int $id): int => $datos[$id]['id_car']) as $idsCarrera) {
            $ordenCarrera += $this->ordenes($idsCarrera->all(), $datos);
        }

        $ahora = now();
        $registros = [];

        foreach ($datos as $id => $fila) {
            $registros[] = [
                'id_exa' => $examen->id_exa,
                'id_exp' => $id,
                'id_vac' => $fila['id_vac'],
                'puntaje_directo_res' => $fila['puntaje_directo'],
                'factor_dificultad_res' => $fila['factor'],
                'puntaje_res' => $fila['puntaje'],
                'puntaje_minimo_res' => $fila['minimo'],
                'orden_general_res' => $ordenGeneral[$id] ?? null,
                'orden_carrera_res' => $ordenCarrera[$id] ?? null,
                'repesca_res' => $fila['repesca'],
                'estado_res' => $fila['estado']->value,
                'motivo_res' => $fila['motivo'],
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        try {
            DB::transaction(function () use ($examen, $registros): void {
                $examen->resultados()->delete();

                foreach (array_chunk($registros, 500) as $lote) {
                    Resultado::insert($lote);
                }

                $examen->update(['resuelto_en_exa' => now()]);
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudieron generar los resultados. No se modificó la resolución anterior.');
        }
    }

    /**
     * @param  list<int>  $ids
     * @param  array<int, array<string, mixed>>  $datos
     * @return array<int, int>
     */
    private function ordenes(array $ids, array $datos): array
    {
        usort($ids, function (int $a, int $b) use ($datos): int {
            $porPuntaje = $datos[$b]['puntaje'] <=> $datos[$a]['puntaje'];

            return $porPuntaje !== 0
                ? $porPuntaje
                : strcmp($datos[$a]['postulante']->documento_exp, $datos[$b]['postulante']->documento_exp);
        });

        $ordenes = [];
        $puntajeAnterior = null;
        $ordenAnterior = 0;

        foreach ($ids as $indice => $id) {
            $puntaje = $datos[$id]['puntaje'];
            $orden = $puntajeAnterior !== null && $puntaje === $puntajeAnterior
                ? $ordenAnterior
                : $indice + 1;
            $ordenes[$id] = $orden;
            $puntajeAnterior = $puntaje;
            $ordenAnterior = $orden;
        }

        return $ordenes;
    }

    /**
     * @param  array<int, array<string, mixed>>  $datos
     * @return array{postulantes:int,ingresantes:int,no_ingresantes:int,nsp:int,anulados:int,repescados:int,vacantes:int,desiertas:int,porcentaje_desiertas:float,factor_aplicado:bool,requiere_examen_complementario:bool}
     */
    private function resumen(Examen $examen, int $vacantes, array $datos, bool $factorAplicado): array
    {
        $porEstado = fn (EstadoResultado $estado): int => count(array_filter(
            $datos,
            fn (array $fila): bool => $fila['estado'] === $estado,
        ));
        $ingresantes = $porEstado(EstadoResultado::Ingreso);
        $desiertas = max(0, $vacantes - $ingresantes);
        $porcentaje = $vacantes > 0 ? round(($desiertas / $vacantes) * 100, 2) : 0.0;

        return [
            'postulantes' => count($datos),
            'ingresantes' => $ingresantes,
            'no_ingresantes' => $porEstado(EstadoResultado::NoIngreso),
            'nsp' => $porEstado(EstadoResultado::Nsp),
            'anulados' => $porEstado(EstadoResultado::Anulado),
            'repescados' => count(array_filter($datos, fn (array $fila): bool => $fila['repesca'])),
            'vacantes' => $vacantes,
            'desiertas' => $desiertas,
            'porcentaje_desiertas' => $porcentaje,
            'factor_aplicado' => $factorAplicado,
            'requiere_examen_complementario' => $examen->proceso->convocatoria_pro === Convocatoria::Tercera
                && $porcentaje > 20,
        ];
    }

    private function claveVacante(int $idModalidad, int $idCarrera, int $idSede): string
    {
        return "{$idModalidad}|{$idCarrera}|{$idSede}";
    }
}
