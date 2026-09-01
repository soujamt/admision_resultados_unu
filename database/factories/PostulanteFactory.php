<?php

namespace Database\Factories;

use App\Enums\EstadoCivil;
use App\Enums\EstadoRegistro;
use App\Enums\Sexo;
use App\Enums\TipoDocumento;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Postulante;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Postulante>
 */
class PostulanteFactory extends Factory
{
    protected $model = Postulante::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_documento_pos' => TipoDocumento::Dni,
            'numero_documento_pos' => fake()->unique()->numerify('########'),
            'solo_un_apellido_pos' => false,
            'primer_apellido_pos' => Str::upper(fake()->lastName()),
            'segundo_apellido_pos' => Str::upper(fake()->lastName()),
            'nombres_pos' => Str::upper(fake()->firstName()),
            'estado_civil_pos' => EstadoCivil::Soltero,
            'sexo_pos' => fake()->randomElement(Sexo::cases()),
            'fecha_nacimiento_pos' => fake()->dateTimeBetween('-30 years', '-16 years'),
            'id_pai' => Pais::factory(),
            'id_nac' => Nacionalidad::factory(),
            'celular_pos' => fake()->numerify('9########'),
            'correo_pos' => fake()->unique()->safeEmail(),
            'lengua_materna_pos' => 'CASTELLANO',
            'estado_pos' => EstadoRegistro::Habilitado,
        ];
    }
}
