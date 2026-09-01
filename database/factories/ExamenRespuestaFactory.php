<?php

namespace Database\Factories;

use App\Models\ExamenPostulante;
use App\Models\ExamenRespuesta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamenRespuesta>
 */
class ExamenRespuestaFactory extends Factory
{
    protected $model = ExamenRespuesta::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_exp' => ExamenPostulante::factory(),
            'nota_directa_exr' => 50,
            'nota_transformada_exr' => 50,
            'aciertos_exr' => 50,
            'errores_exr' => 25,
            'blancos_exr' => 25,
            'dobles_exr' => 0,
            'respuestas_exr' => array_fill(0, 100, 'A'),
        ];
    }
}
