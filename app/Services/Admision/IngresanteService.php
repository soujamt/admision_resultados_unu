<?php

namespace App\Services\Admision;

use App\Enums\CondicionIngresante;
use App\Enums\Convocatoria;
use App\Enums\EstadoResultado;
use App\Enums\GrupoModalidad;
use App\Models\Examen;
use App\Models\Ingresante;
use App\Models\Proceso;
use App\Models\Resultado;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Mantiene el padron oficial de ingresantes del Art. 89 y aplica el Art. 93
 * cuando un ingresante pierde su condicion por no matricularse.
 */
class IngresanteService
{
    /**
     * Vuelca al padron los ingresantes de una jornada ya resuelta.
     *
     * Conserva la condicion que ya tuviera cada ingresante y a los sustitutos
     * del Art. 93, porque volver a resolver el examen no deshace una renuncia
     * ni una matricula no efectuada.
     *
     * @return array{creados:int,actualizados:int,retirados:int,total:int}
     */
    public function generar(Examen $examen): array
    {
        if ($examen->resuelto_en_exa === null) {
            throw new RuntimeException('Genera primero los resultados de la jornada.');
        }

        $resultados = Resultado::query()
            ->where('id_exa', $examen->id_exa)
            ->where('estado_res', EstadoResultado::Ingreso)
            ->with('postulante')
            ->get();

        if ($resultados->isEmpty()) {
            throw new RuntimeException('La jornada resuelta no tiene ningún ingresante.');
        }

        $idProceso = $examen->id_pro;
        $idExamen = $examen->id_exa;
        $sinInscripcion = $resultados->filter(fn (Resultado $resultado): bool => $resultado->postulante?->id_ins === null);

        if ($sinInscripcion->isNotEmpty()) {
            throw new RuntimeException('Hay ingresantes sin inscripción asociada. Vuelve a generar los resultados.');
        }

        try {
            return DB::transaction(function () use ($resultados, $idProceso, $idExamen): array {
                $existentes = Ingresante::query()
                    ->where('id_pro', $idProceso)
                    ->get()
                    ->keyBy('id_ins');
                $creados = 0;
                $actualizados = 0;
                $vigentes = [];

                foreach ($resultados as $resultado) {
                    $idInscripcion = $resultado->postulante->id_ins;
                    $vigentes[] = $idInscripcion;
                    $ingresante = $existentes->get($idInscripcion);
                    $datos = [
                        'id_vac' => $resultado->id_vac,
                        'id_exa' => $idExamen,
                        'id_res' => $resultado->id_res,
                        'puntaje_ing' => $resultado->puntaje_res,
                        'orden_carrera_ing' => $resultado->orden_carrera_res,
                    ];

                    if ($ingresante === null) {
                        Ingresante::create($datos + [
                            'id_pro' => $idProceso,
                            'id_ins' => $idInscripcion,
                            'condicion_ing' => CondicionIngresante::Vigente,
                        ]);
                        $creados++;

                        continue;
                    }

                    $ingresante->update($datos);
                    $actualizados++;
                }

                /*
                 * Se retira solo a quien dejo de ser ingresante de esta misma
                 * jornada y sigue vigente: los sustitutos del Art. 93 no salen
                 * del padron aunque no figuren como ingresantes del examen.
                 */
                $retirados = Ingresante::query()
                    ->where('id_pro', $idProceso)
                    ->where('id_exa', $idExamen)
                    ->whereNotIn('id_ins', $vigentes)
                    ->whereNull('id_sustituido_ing')
                    ->where('condicion_ing', CondicionIngresante::Vigente)
                    ->delete();

                return [
                    'creados' => $creados,
                    'actualizados' => $actualizados,
                    'retirados' => $retirados,
                    'total' => Ingresante::where('id_pro', $idProceso)->count(),
                ];
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo generar el padrón de ingresantes. No se modificó el padrón anterior.');
        }
    }

    /**
     * Registra que un ingresante perdio su condicion y, cuando fue por no
     * matricularse, llama al inmediato inferior segun el Art. 93.
     *
     * Devuelve el sustituto cuando lo hubo.
     */
    public function perderCondicion(Ingresante $ingresante, CondicionIngresante $condicion, string $motivo): ?Ingresante
    {
        if (! $condicion->perdioCondicion()) {
            throw new RuntimeException('Elige el motivo por el que pierde la condición de ingresante.');
        }

        if (! $ingresante->estaVigente()) {
            throw new RuntimeException('Este ingresante ya perdió su condición.');
        }

        try {
            return DB::transaction(function () use ($ingresante, $condicion, $motivo): ?Ingresante {
                $ingresante->update([
                    'condicion_ing' => $condicion,
                    'motivo_ing' => trim($motivo),
                    'condicion_en_ing' => now(),
                ]);

                return $condicion === CondicionIngresante::SinMatricula
                    ? $this->llamarInmediatoInferior($ingresante)
                    : null;
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo registrar la pérdida de la condición de ingresante.');
        }
    }

    public function restaurarCondicion(Ingresante $ingresante): void
    {
        if ($ingresante->estaVigente()) {
            return;
        }

        try {
            DB::transaction(function () use ($ingresante): void {
                $ingresante->sustituto()->delete();
                $ingresante->update([
                    'condicion_ing' => CondicionIngresante::Vigente,
                    'motivo_ing' => null,
                    'condicion_en_ing' => null,
                ]);
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo restaurar la condición de ingresante.');
        }
    }

    /**
     * Art. 93: la vacante que libera quien no se matricula pasa al postulante
     * inmediato inferior de la carrera de origen en la tercera convocatoria,
     * siempre que haya alcanzado la nota minima del Art. 81. El sustituto sale
     * de la misma modalidad, salvo el de reserva, que el articulo manda buscar
     * en el examen ordinario.
     */
    private function llamarInmediatoInferior(Ingresante $ingresante): ?Ingresante
    {
        $ingresante->loadMissing(['proceso', 'vacante.modalidad', 'inscripcion']);
        $tercera = $this->terceraConvocatoria($ingresante->proceso);

        if ($tercera === null) {
            return null;
        }

        $esReserva = $ingresante->vacante->modalidad->grupo_mod === GrupoModalidad::Reserva;
        $ocupadas = Ingresante::query()
            ->where('id_pro', $tercera->id_pro)
            ->pluck('id_ins');

        $candidato = Resultado::query()
            ->whereHas('examen', fn ($query) => $query->where('id_pro', $tercera->id_pro))
            ->where('estado_res', EstadoResultado::NoIngreso)
            ->whereNotNull('puntaje_res')
            ->whereColumn('puntaje_res', '>=', 'puntaje_minimo_res')
            ->whereHas('postulante.inscripcion', function ($query) use ($ingresante, $esReserva, $ocupadas): void {
                $query->where('id_car', $ingresante->vacante->id_car)
                    ->where('id_sed', $ingresante->vacante->id_sed)
                    ->whereNotIn('id_ins', $ocupadas);

                $esReserva
                    ? $query->whereHas('modalidad', fn ($modalidad) => $modalidad->where('grupo_mod', GrupoModalidad::Ordinario))
                    : $query->where('id_mod', $ingresante->vacante->id_mod);
            })
            ->with('postulante.inscripcion')
            ->orderByDesc('puntaje_res')
            ->orderBy('id_res')
            ->first();

        if ($candidato === null) {
            return null;
        }

        return Ingresante::create([
            'id_pro' => $tercera->id_pro,
            'id_ins' => $candidato->postulante->id_ins,
            'id_vac' => $ingresante->id_vac,
            'id_exa' => $candidato->id_exa,
            'id_res' => $candidato->id_res,
            'id_sustituido_ing' => $ingresante->id_ing,
            'puntaje_ing' => $candidato->puntaje_res,
            'orden_carrera_ing' => $candidato->orden_carrera_res,
            'condicion_ing' => CondicionIngresante::Vigente,
            'motivo_ing' => 'Ocupa por el Art. 93 la vacante de un ingresante que no se matriculó.',
        ]);
    }

    /**
     * El Art. 93 siempre busca al sustituto en la tercera convocatoria del
     * mismo anio, sea cual sea la convocatoria en que ingreso el titular.
     */
    private function terceraConvocatoria(Proceso $proceso): ?Proceso
    {
        if ($proceso->convocatoria_pro === Convocatoria::Tercera) {
            return $proceso;
        }

        return Proceso::query()
            ->where('anio_pro', $proceso->anio_pro)
            ->where('convocatoria_pro', Convocatoria::Tercera)
            ->first();
    }
}
