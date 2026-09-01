<?php

namespace Database\Factories;

use App\Models\Examen;
use App\Models\ExamenImportacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamenImportacion>
 */
class ExamenImportacionFactory extends Factory
{
    protected $model = ExamenImportacion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_exa' => Examen::factory(),
            'tipo_exi' => 'padron',
            'archivo_exi' => 'examenes/padron.txt',
            'hash_exi' => hash('sha256', fake()->uuid()),
            'filas_exi' => 1,
        ];
    }
}
