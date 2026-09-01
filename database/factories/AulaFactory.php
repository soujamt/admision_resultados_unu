<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Models\Aula;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aula>
 */
class AulaFactory extends Factory
{
    protected $model = Aula::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_sed' => Sede::factory(),
            'codigo_aul' => fake()->unique()->bothify('A-###'),
            'nombre_aul' => 'Aula '.fake()->unique()->numberBetween(1, 999),
            'pabellon_aul' => 'Pabellón '.fake()->randomLetter(),
            'capacidad_aul' => 40,
            'orden_aul' => fake()->numberBetween(1, 999),
            'estado_aul' => EstadoRegistro::Habilitado,
        ];
    }
}
