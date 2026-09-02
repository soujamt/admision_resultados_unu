<?php

namespace App\Services\Admision;

use App\Models\Examen;
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
}
