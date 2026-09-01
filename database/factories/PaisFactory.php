<?php

namespace Database\Factories;

use App\Models\Pais;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pais>
 */
class PaisFactory extends Factory
{
    protected $model = Pais::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_pai' => fake()->unique()->numberBetween(1, 30000),
            'nombre_pai' => Str::upper(fake()->country()),
        ];
    }

    public function peru(): static
    {
        return $this->state([
            'codigo_pai' => Pais::CODIGO_PERU,
            'nombre_pai' => 'PERÚ',
        ]);
    }
}
