<?php

namespace App\Services\Admision;

use App\Models\Facultad;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ServicioDeCatalogo<Facultad>
 */
class FacultadService extends ServicioDeCatalogo
{
    public function modelo(): string
    {
        return Facultad::class;
    }

    protected function razonParaNoEliminar(Model $registro): ?string
    {
        $carreras = $registro->carreras()->count();

        return $carreras === 0
            ? null
            : "No se puede eliminar: la facultad tiene {$carreras} carrera(s). Muévelas a otra facultad o deshabilítala.";
    }
}
