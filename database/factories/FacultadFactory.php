<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Models\Facultad;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Facultad>
 */
class FacultadFactory extends Factory
{
    protected $model = Facultad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = 'Facultad de '.fake()->unique()->words(2, true);

        return [
            'codigo_fac' => Str::upper(Str::random(10)),
            'nombre_fac' => $nombre,
            'estado_fac' => EstadoRegistro::Habilitado,
        ];
    }
}
