<?php

namespace Database\Factories;

use App\Models\AsignacionExamen;
use App\Models\ExamenAula;
use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsignacionExamen>
 */
class AsignacionExamenFactory extends Factory
{
    protected $model = AsignacionExamen::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_ins' => Inscripcion::factory(),
            'id_eau' => ExamenAula::factory(),
            'asiento_ase' => fake()->numberBetween(1, 40),
        ];
    }
}
