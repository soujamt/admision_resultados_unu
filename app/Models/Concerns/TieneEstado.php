<?php

namespace App\Models\Concerns;

use App\Enums\EstadoRegistro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Habilitar y deshabilitar registros sin repetir el mismo scope en cada modelo.
 *
 * La columna se deduce del nombre de la clave primaria siguiendo la
 * nomenclatura del proyecto: `id_fac` implica `estado_fac`. Un modelo que se
 * salga de esa convencion solo tiene que sobrescribir `columnaDeEstado()`.
 *
 * @phpstan-require-extends Model
 */
trait TieneEstado
{
    public function columnaDeEstado(): string
    {
        return 'estado_'.Str::after($this->getKeyName(), 'id_');
    }

    public function estado(): EstadoRegistro
    {
        return $this->{$this->columnaDeEstado()};
    }

    public function estaHabilitado(): bool
    {
        return $this->estado() === EstadoRegistro::Habilitado;
    }

    /**
     * Cambia el estado al contrario y lo guarda.
     */
    public function alternarEstado(): static
    {
        $this->{$this->columnaDeEstado()} = $this->estaHabilitado()
            ? EstadoRegistro::Deshabilitado
            : EstadoRegistro::Habilitado;

        $this->save();

        return $this;
    }

    /**
     * @param  Builder<static>  $consulta
     */
    public function scopeHabilitado(Builder $consulta): void
    {
        $consulta->where($this->columnaDeEstado(), EstadoRegistro::Habilitado);
    }

    /**
     * Alias en femenino, para que en las consultas se lea igual de bien
     * `Carrera::habilitada()` que `Proceso::habilitado()`. Ambos scopes van
     * en singular: `habilitadas()` no existe.
     *
     * @param  Builder<static>  $consulta
     */
    public function scopeHabilitada(Builder $consulta): void
    {
        $this->scopeHabilitado($consulta);
    }
}
