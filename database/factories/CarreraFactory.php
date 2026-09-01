<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Models\Area;
use App\Models\Carrera;
use App\Models\Facultad;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Carrera>
 */
class CarreraFactory extends Factory
{
    protected $model = Carrera::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = Str::title(fake()->unique()->words(3, true));

        return [
            'id_fac' => Facultad::factory(),
            'id_are' => Area::factory(),
            'codigo_car' => Str::upper(Str::random(12)),
            'nombre_car' => $nombre,
            'nombre_corto_car' => Str::limit($nombre, 40, ''),
            'estado_car' => EstadoRegistro::Habilitado,
        ];
    }

    public function llamada(string $nombre): static
    {
        return $this->state([
            'nombre_car' => $nombre,
            'nombre_corto_car' => Str::limit($nombre, 40, ''),
        ]);
    }
}
