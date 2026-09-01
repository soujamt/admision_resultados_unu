<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    protected $model = Area::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_are' => fake()->unique()->numberBetween(1, 200),
            'nombre_are' => fake()->words(2, true),
            'estado_are' => EstadoRegistro::Habilitado,
        ];
    }
}
