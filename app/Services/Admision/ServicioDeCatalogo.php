<?php

namespace App\Services\Admision;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Base de los servicios que administran los catalogos de configuracion.
 *
 * Todos hacen lo mismo —crear, editar, habilitar y borrar— y solo se
 * diferencian en cuando esta prohibido borrar, asi que cada servicio concreto
 * se reduce a declarar su modelo y esa regla.
 *
 * @template TModelo of Model
 */
abstract class ServicioDeCatalogo
{
    /**
     * @return class-string<TModelo>
     */
    abstract public function modelo(): string;

    /**
     * Motivo por el que el registro no se puede borrar, o null si se puede.
     *
     * @param  TModelo  $registro
     */
    protected function razonParaNoEliminar(Model $registro): ?string
    {
        return null;
    }

    /**
     * Crea o actualiza segun se reciba un registro existente.
     *
     * @param  array<string, mixed>  $datos
     * @param  ?TModelo  $registro
     * @return TModelo
     */
    public function guardar(array $datos, ?Model $registro = null): Model
    {
        $registro ??= new ($this->modelo());
        $registro->fill($datos);
        $registro->save();

        return $registro;
    }

    /**
     * @param  TModelo  $registro
     * @return TModelo
     */
    public function alternarEstado(Model $registro): Model
    {
        return $registro->alternarEstado();
    }

    /**
     * Borrado logico. Lanza cuando hay registros que dependen de este, para
     * que la pantalla muestre el motivo en vez de dejar datos huerfanos.
     *
     * @param  TModelo  $registro
     */
    public function eliminar(Model $registro): void
    {
        $razon = $this->razonParaNoEliminar($registro);

        if ($razon !== null) {
            throw new RuntimeException($razon);
        }

        $registro->delete();
    }
}
