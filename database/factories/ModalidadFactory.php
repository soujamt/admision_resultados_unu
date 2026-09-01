<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Enums\GrupoModalidad;
use App\Models\Modalidad;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Modalidad>
 */
class ModalidadFactory extends Factory
{
    protected $model = Modalidad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_mod' => Str::upper(Str::random(12)),
            'nombre_mod' => Str::title(fake()->unique()->words(2, true)),
            'grupo_mod' => fake()->randomElement(GrupoModalidad::cases()),
            'codigo_externo_mod' => null,
            'articulo_mod' => null,
            'estado_mod' => EstadoRegistro::Habilitado,
        ];
    }

    public function conCodigoExterno(int $codigo): static
    {
        return $this->state(['codigo_externo_mod' => $codigo]);
    }
}
