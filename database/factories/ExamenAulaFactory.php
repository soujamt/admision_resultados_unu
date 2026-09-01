<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Aula;
use App\Models\Examen;
use App\Models\ExamenAula;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamenAula>
 */
class ExamenAulaFactory extends Factory
{
    protected $model = ExamenAula::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_exa' => Examen::factory(),
            'id_aul' => Aula::factory(),
            'id_are' => Area::factory(),
            'capacidad_eau' => 40,
        ];
    }
}
