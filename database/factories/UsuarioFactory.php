<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $correo = fake()->unique()->safeEmail();

        return [
            'id_rol' => Rol::factory(),
            'nombre_usu' => fake()->name(),
            'usuario_usu' => $correo,
            'correo_usu' => $correo,
            'clave_usu' => 'clave-de-prueba',
            'estado_usu' => EstadoRegistro::Habilitado,
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['id_rol' => Rol::factory()->administrador()]);
    }

    public function deshabilitado(): static
    {
        return $this->state(fn () => ['estado_usu' => EstadoRegistro::Deshabilitado]);
    }
}
