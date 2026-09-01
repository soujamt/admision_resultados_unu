<?php

namespace Database\Factories;

use App\Enums\Convocatoria;
use App\Enums\EstadoRegistro;
use App\Models\Proceso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proceso>
 */
class ProcesoFactory extends Factory
{
    protected $model = Proceso::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $anio = fake()->numberBetween(2024, 2030);
        $convocatoria = fake()->randomElement(Convocatoria::cases());

        return [
            'codigo_pro' => Proceso::componerCodigo($anio, $convocatoria),
            'nombre_pro' => 'Proceso de Admisión '.Proceso::componerCodigo($anio, $convocatoria),
            'anio_pro' => $anio,
            'convocatoria_pro' => $convocatoria,
            'estado_pro' => EstadoRegistro::Habilitado,
        ];
    }

    public function codigo(string $codigo): static
    {
        $partes = Proceso::interpretarCodigo($codigo);

        return $this->state([
            'codigo_pro' => $codigo,
            'nombre_pro' => 'Proceso de Admisión '.$codigo,
            'anio_pro' => $partes['anio'],
            'convocatoria_pro' => $partes['convocatoria'],
        ]);
    }
}
