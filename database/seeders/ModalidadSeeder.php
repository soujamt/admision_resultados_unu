<?php

namespace Database\Seeders;

use App\Enums\EstadoRegistro;
use App\Enums\GrupoModalidad;
use App\Models\Modalidad;
use Illuminate\Database\Seeder;

/**
 * Las modalidades de admision que enumera el Art. 5 del reglamento.
 *
 * `codigo_externo` solo se conoce para las modalidades que ya se reportan por
 * el formato oficial (2 = Exoneracion CEPREUNU, 8 = Reserva CEPREUNU); el
 * resto queda nulo hasta que la Direccion de Admision lo confirme.
 */
class ModalidadSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->modalidades() as $modalidad) {
            Modalidad::updateOrCreate(
                ['codigo_mod' => $modalidad['codigo_mod']],
                $modalidad + ['estado_mod' => EstadoRegistro::Habilitado],
            );
        }
    }

    /**
     * @return list<array{codigo_mod: string, nombre_mod: string, grupo_mod: GrupoModalidad, codigo_externo_mod: ?int, articulo_mod: string}>
     */
    private function modalidades(): array
    {
        return [
            [
                'codigo_mod' => 'ORDINARIO',
                'nombre_mod' => 'Examen ordinario',
                'grupo_mod' => GrupoModalidad::Ordinario,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 39',
            ],
            [
                'codigo_mod' => 'EXO_CEPREUNU',
                'nombre_mod' => 'Exoneración - CEPREUNU',
                'grupo_mod' => GrupoModalidad::Exoneracion,
                'codigo_externo_mod' => 2,
                'articulo_mod' => 'Art. 41',
            ],
            [
                'codigo_mod' => 'EXO_PRIMEROS_PUESTOS',
                'nombre_mod' => 'Exoneración - Primer y segundo puesto',
                'grupo_mod' => GrupoModalidad::Exoneracion,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 42',
            ],
            [
                'codigo_mod' => 'EXO_DEPORTISTA',
                'nombre_mod' => 'Exoneración - Deportista destacado',
                'grupo_mod' => GrupoModalidad::Exoneracion,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 44',
            ],
            [
                'codigo_mod' => 'EXO_VICTIMA_VIOLENCIA',
                'nombre_mod' => 'Exoneración - Víctima de la violencia',
                'grupo_mod' => GrupoModalidad::Exoneracion,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 48',
            ],
            [
                'codigo_mod' => 'EXO_DISCAPACIDAD',
                'nombre_mod' => 'Exoneración - Persona con discapacidad',
                'grupo_mod' => GrupoModalidad::Exoneracion,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 51',
            ],
            [
                'codigo_mod' => 'EXO_TITULADOS',
                'nombre_mod' => 'Exoneración - Titulados o graduados',
                'grupo_mod' => GrupoModalidad::Exoneracion,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 54',
            ],
            [
                'codigo_mod' => 'RES_CEPREUNU',
                'nombre_mod' => 'Reserva - CEPREUNU',
                'grupo_mod' => GrupoModalidad::Reserva,
                'codigo_externo_mod' => 8,
                'articulo_mod' => 'Art. 59',
            ],
            [
                'codigo_mod' => 'RES_ORDINARIO',
                'nombre_mod' => 'Reserva - Examen ordinario',
                'grupo_mod' => GrupoModalidad::Reserva,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 60',
            ],
            [
                'codigo_mod' => 'CON_GOREU_CCNN',
                'nombre_mod' => 'Convenio - GOREU / Comunidades Nativas',
                'grupo_mod' => GrupoModalidad::Convenio,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 61',
            ],
            [
                'codigo_mod' => 'CON_COAR',
                'nombre_mod' => 'Convenio - COAR Ucayali',
                'grupo_mod' => GrupoModalidad::Convenio,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 63',
            ],
            [
                'codigo_mod' => 'PRONABEC_BECA18',
                'nombre_mod' => 'PRONABEC - Beca 18',
                'grupo_mod' => GrupoModalidad::Pronabec,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 67',
            ],
            [
                'codigo_mod' => 'TRA_INTERNO',
                'nombre_mod' => 'Traslado interno',
                'grupo_mod' => GrupoModalidad::Traslado,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 69',
            ],
            [
                'codigo_mod' => 'TRA_EXTERNO',
                'nombre_mod' => 'Traslado externo',
                'grupo_mod' => GrupoModalidad::Traslado,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 69',
            ],
            [
                'codigo_mod' => 'TRA_INTERFILIAL',
                'nombre_mod' => 'Traslado inter-filial',
                'grupo_mod' => GrupoModalidad::Traslado,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 69',
            ],
            [
                'codigo_mod' => 'TRA_EXTERNO_EXTRAORDINARIO',
                'nombre_mod' => 'Traslado externo extraordinario',
                'grupo_mod' => GrupoModalidad::Traslado,
                'codigo_externo_mod' => null,
                'articulo_mod' => 'Art. 69',
            ],
        ];
    }
}
