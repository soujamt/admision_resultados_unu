<?php

namespace Database\Seeders;

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nombre_rol' => 'Administrador',
                'descripcion_rol' => 'Acceso completo a la configuracion del sistema.',
                'permisos_rol' => Permiso::valores(),
            ],
            [
                'nombre_rol' => 'Postulante',
                'descripcion_rol' => 'Cuenta creada desde el registro publico.',
                'permisos_rol' => [],
            ],
        ];

        foreach ($roles as $rol) {
            Rol::updateOrCreate(
                ['nombre_rol' => $rol['nombre_rol']],
                $rol + ['estado_rol' => EstadoRegistro::Habilitado],
            );
        }
    }
}
