<?php

namespace Database\Factories;

use App\Enums\EstadoResultado;
use App\Models\Examen;
use App\Models\ExamenPostulante;
use App\Models\Resultado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resultado>
 */
class ResultadoFactory extends Factory
{
    protected $model = Resultado::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_exa' => Examen::factory(),
            'id_exp' => ExamenPostulante::factory(),
            'puntaje_res' => 50,
            'puntaje_directo_res' => 50,
            'factor_dificultad_res' => 1,
            'puntaje_minimo_res' => 50,
            'orden_general_res' => 1,
            'orden_carrera_res' => 1,
            'estado_res' => EstadoResultado::Pendiente,
        ];
    }
}
