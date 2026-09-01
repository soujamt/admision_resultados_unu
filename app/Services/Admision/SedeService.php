<?php

namespace App\Services\Admision;

use App\Models\Aula;
use App\Models\Inscripcion;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ServicioDeCatalogo<Sede>
 */
class SedeService extends ServicioDeCatalogo
{
    public function modelo(): string
    {
        return Sede::class;
    }

    protected function razonParaNoEliminar(Model $registro): ?string
    {
        $inscripciones = Inscripcion::where('id_sed', $registro->id_sed)->count();

        if ($inscripciones > 0) {
            return "No se puede eliminar: hay {$inscripciones} inscripción(es) registradas en esta sede.";
        }

        $aulas = Aula::where('id_sed', $registro->id_sed)->count();

        return $aulas === 0
            ? null
            : "No se puede eliminar: la sede tiene {$aulas} aula(s) registradas.";
    }
}
