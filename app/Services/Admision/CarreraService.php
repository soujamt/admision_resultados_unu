<?php

namespace App\Services\Admision;

use App\Models\Carrera;
use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ServicioDeCatalogo<Carrera>
 */
class CarreraService extends ServicioDeCatalogo
{
    public function modelo(): string
    {
        return Carrera::class;
    }

    protected function razonParaNoEliminar(Model $registro): ?string
    {
        $inscripciones = Inscripcion::where('id_car', $registro->id_car)->count();

        if ($inscripciones > 0) {
            return "No se puede eliminar: hay {$inscripciones} inscripción(es) a esta carrera. Deshabilítala para retirarla de la oferta.";
        }

        $vacantes = $registro->vacantes()->count();

        return $vacantes === 0
            ? null
            : "No se puede eliminar: la carrera está en {$vacantes} fila(s) del cuadro de vacantes.";
    }
}
