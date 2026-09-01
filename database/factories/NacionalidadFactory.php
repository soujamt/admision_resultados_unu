<?php

namespace Database\Factories;

use App\Models\Nacionalidad;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Nacionalidad>
 */
class NacionalidadFactory extends Factory
{
    protected $model = Nacionalidad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_nac' => fake()->unique()->numberBetween(1, 30000),
            'nombre_nac' => Str::upper(fake()->word()),
        ];
    }

    public function peruana(): static
    {
        return $this->state([
            'codigo_nac' => 1,
            'nombre_nac' => 'PERUANA',
        ]);
    }
}
