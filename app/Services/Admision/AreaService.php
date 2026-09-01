<?php

namespace App\Services\Admision;

use App\Models\Area;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ServicioDeCatalogo<Area>
 */
class AreaService extends ServicioDeCatalogo
{
    public function modelo(): string
    {
        return Area::class;
    }

    protected function razonParaNoEliminar(Model $registro): ?string
    {
        $carreras = $registro->carreras()->count();

        return $carreras === 0
            ? null
            : "No se puede eliminar: el área agrupa {$carreras} carrera(s). Reasígnalas antes o deshabilítala.";
    }
}
