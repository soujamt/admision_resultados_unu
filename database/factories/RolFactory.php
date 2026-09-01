<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rol>
 */
class RolFactory extends Factory
{
    protected $model = Rol::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_rol' => ucfirst(fake()->unique()->words(2, true)),
            'descripcion_rol' => fake()->sentence(),
            'permisos_rol' => [],
            'estado_rol' => EstadoRegistro::Habilitado,
        ];
    }

    /**
     * Rol con todos los permisos del sistema.
     */
    public function administrador(): static
    {
        return $this->state(fn () => [
            'nombre_rol' => 'Administrador',
            'permisos_rol' => Permiso::valores(),
        ]);
    }

    /**
     * @param  list<Permiso>  $permisos
     */
    public function con(array $permisos): static
    {
        return $this->state(fn () => [
            'permisos_rol' => array_column($permisos, 'value'),
        ]);
    }

    public function deshabilitado(): static
    {
        return $this->state(fn () => ['estado_rol' => EstadoRegistro::Deshabilitado]);
    }
}
