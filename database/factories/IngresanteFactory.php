<?php

namespace Database\Factories;

use App\Enums\CondicionIngresante;
use App\Models\Ingresante;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Vacante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingresante>
 */
class IngresanteFactory extends Factory
{
    protected $model = Ingresante::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pro' => Proceso::factory(),
            'id_ins' => Inscripcion::factory(),
            'id_vac' => Vacante::factory(),
            'id_exa' => null,
            'id_res' => null,
            'id_sustituido_ing' => null,
            'puntaje_ing' => fake()->randomFloat(4, 50, 100),
            'orden_carrera_ing' => fake()->numberBetween(1, 200),
            'condicion_ing' => CondicionIngresante::Vigente,
            'motivo_ing' => null,
            'condicion_en_ing' => null,
        ];
    }

    public function conCondicion(CondicionIngresante $condicion, string $motivo): static
    {
        return $this->state([
            'condicion_ing' => $condicion,
            'motivo_ing' => $motivo,
            'condicion_en_ing' => now(),
        ]);
    }
}
