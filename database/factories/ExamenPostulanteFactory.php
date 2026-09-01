<?php

namespace Database\Factories;

use App\Models\Examen;
use App\Models\ExamenPostulante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamenPostulante>
 */
class ExamenPostulanteFactory extends Factory
{
    protected $model = ExamenPostulante::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_exa' => Examen::factory(),
            'codigo_cartilla_exp' => fake()->unique()->numerify('##########'),
            'documento_exp' => fake()->unique()->numerify('########'),
            'nombre_exp' => fake()->name(),
            'codigo_carrera_exp' => fake()->bothify('???##'),
            'codigo_modalidad_exp' => fake()->bothify('??'),
            'aula_origen_exp' => fake()->numerify('###'),
        ];
    }
}
