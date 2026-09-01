<?php

namespace Database\Factories;

use App\Models\Examen;
use App\Models\Proceso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Examen>
 */
class ExamenFactory extends Factory
{
    protected $model = Examen::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pro' => Proceso::factory(),
            'nombre_exa' => 'Examen '.fake()->unique()->word(),
            'fecha_exa' => fake()->date(),
            'puntaje_acierto_exa' => 1,
            'puntaje_error_exa' => -0.010,
            'puntaje_blanco_exa' => 0.100,
        ];
    }
}
