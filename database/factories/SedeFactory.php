<?php

namespace Database\Factories;

use App\Enums\EstadoRegistro;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sede>
 */
class SedeFactory extends Factory
{
    protected $model = Sede::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_sed' => Str::upper(Str::random(10)),
            'nombre_sed' => 'Sede '.fake()->city(),
            'codigo_ubi' => null,
            'es_filial_sed' => false,
            'estado_sed' => EstadoRegistro::Habilitado,
        ];
    }

    public function llamada(string $nombre): static
    {
        return $this->state(['nombre_sed' => $nombre]);
    }
}
