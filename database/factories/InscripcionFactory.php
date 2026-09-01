<?php

namespace Database\Factories;

use App\Enums\EstadoInscripcion;
use App\Enums\TipoColegio;
use App\Models\Carrera;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\Postulante;
use App\Models\Proceso;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inscripcion>
 */
class InscripcionFactory extends Factory
{
    protected $model = Inscripcion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pro' => Proceso::factory(),
            'id_pos' => Postulante::factory(),
            'id_mod' => Modalidad::factory(),
            'id_car' => Carrera::factory(),
            'id_sed' => Sede::factory(),
            'codigo_ins' => fake()->unique()->numerify('2027I-#####'),
            'anio_graduacion_ins' => fake()->numberBetween(2015, 2026),
            'tipo_colegio_ins' => TipoColegio::Nacional,
            'veces_unu_ins' => fake()->numberBetween(0, 3),
            'veces_otros_ins' => fake()->numberBetween(0, 2),
            'fecha_ins' => now()->toDateString(),
            'estado_ins' => EstadoInscripcion::Inscrito,
        ];
    }

    public function conFoto(string $ruta): static
    {
        return $this->state(['foto_ins' => $ruta]);
    }
}
