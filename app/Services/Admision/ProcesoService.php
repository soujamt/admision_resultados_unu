<?php

namespace App\Services\Admision;

use App\Enums\Convocatoria;
use App\Models\Proceso;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ServicioDeCatalogo<Proceso>
 */
class ProcesoService extends ServicioDeCatalogo
{
    public function modelo(): string
    {
        return Proceso::class;
    }

    /**
     * El codigo y el nombre no se escriben a mano: salen del anio y la
     * convocatoria, para que «2027-I» signifique siempre lo mismo y el unique
     * de (anio, convocatoria) no pueda contradecir al de codigo.
     *
     * @param  array<string, mixed>  $datos
     * @param  ?Proceso  $registro
     */
    public function guardar(array $datos, ?Model $registro = null): Model
    {
        $convocatoria = $datos['convocatoria_pro'] instanceof Convocatoria
            ? $datos['convocatoria_pro']
            : Convocatoria::from((int) $datos['convocatoria_pro']);

        $codigo = Proceso::componerCodigo((int) $datos['anio_pro'], $convocatoria);

        $datos['codigo_pro'] = $codigo;
        $datos['nombre_pro'] = trim($datos['nombre_pro'] ?? '') ?: 'Proceso de Admisión '.$codigo;

        return parent::guardar($datos, $registro);
    }

    protected function razonParaNoEliminar(Model $registro): ?string
    {
        $inscripciones = $registro->inscripciones()->count();

        if ($inscripciones > 0) {
            return "No se puede eliminar: el proceso tiene {$inscripciones} inscripción(es) cargadas.";
        }

        $vacantes = $registro->vacantes()->count();

        return $vacantes === 0
            ? null
            : "No se puede eliminar: el proceso tiene {$vacantes} fila(s) en su cuadro de vacantes. Bórralas primero.";
    }
}
