<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Models\Carrera;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Vacante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vacante>
 */
class VacanteFactory extends Factory
{
    protected $model = Vacante::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pro' => Proceso::factory(),
            'id_mod' => Modalidad::factory(),
            'id_car' => Carrera::factory(),
            'id_sed' => Sede::factory(),
            'cantidad_vac' => fake()->numberBetween(5, 60),
            'codigo_externo_vac' => fake()->unique()->numberBetween(1000, 9999),
            'estado_vac' => EstadoRegistro::Habilitado,
        ];
    }
}
