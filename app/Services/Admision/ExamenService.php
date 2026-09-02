<?php

namespace App\Services\Admision;

use App\Models\Carrera;
use App\Models\Examen;
use App\Models\ExamenPostulante;
use App\Models\Proceso;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ExamenService
{
    /**
     * @param  array{nombre:string, fecha:?string}  $datos
     */
    public function crear(Proceso $proceso, array $datos): Examen
    {
        try {
            return DB::transaction(fn (): Examen => Examen::create([
                'id_pro' => $proceso->id_pro,
                'nombre_exa' => trim($datos['nombre']),
                'fecha_exa' => $datos['fecha'],
            ]), 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo crear la jornada. Verifica que el nombre no esté repetido.');
        }
    }

    /**
     * Guarda la calificacion del Art. 77 y las notas minimas del Art. 81, que
     * viven en la carrera porque el articulo las fija por carrera profesional.
     * Cualquier cambio invalida la resolucion anterior.
     *
     * @param  array<string, float|bool>  $configuracion
     * @param  array<int, float|null>  $minimosCarreras
     */
    public function configurarResultados(Examen $examen, array $configuracion, array $minimosCarreras): void
    {
        try {
            DB::transaction(function () use ($examen, $configuracion, $minimosCarreras): void {
                $examen->update($configuracion + ['resuelto_en_exa' => null]);

                foreach ($minimosCarreras as $idCarrera => $puntajeMinimo) {
                    Carrera::query()
                        ->whereKey($idCarrera)
                        ->update(['puntaje_minimo_car' => $puntajeMinimo]);
                }

                $examen->resultados()->delete();
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo guardar la configuración de resultados.');
        }
    }

    /**
     * Anula la postulacion de un postulante por los Arts. 79, 96 y 105 al 108.
     * La anulacion vive en el padron del examen y no en el resultado, para que
     * sobreviva a cada nueva generacion del padron oficial.
     */
    public function anularPostulante(ExamenPostulante $postulante, string $motivo): void
    {
        try {
            DB::transaction(function () use ($postulante, $motivo): void {
                $postulante->update([
                    'anulado_en_exp' => now(),
                    'motivo_anulacion_exp' => trim($motivo),
                ]);

                $postulante->examen->resultados()->delete();
                $postulante->examen->update(['resuelto_en_exa' => null]);
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo anular la postulación.');
        }
    }

    public function restaurarPostulante(ExamenPostulante $postulante): void
    {
        try {
            DB::transaction(function () use ($postulante): void {
                $postulante->update(['anulado_en_exp' => null, 'motivo_anulacion_exp' => null]);

                $postulante->examen->resultados()->delete();
                $postulante->examen->update(['resuelto_en_exa' => null]);
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo restaurar la postulación.');
        }
    }
}
