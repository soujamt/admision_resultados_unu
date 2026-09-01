<?php

namespace Database\Seeders;

use App\Enums\EstadoRegistro;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $idRol = Rol::where('nombre_rol', 'Administrador')->value('id_rol');

        Usuario::updateOrCreate(
            ['usuario_usu' => 'admin@unu.edu.pe'],
            [
                'id_rol' => $idRol,
                'nombre_usu' => 'Administrador del sistema',
                'correo_usu' => 'admin@unu.edu.pe',
                'clave_usu' => 'admin1234',
                'estado_usu' => EstadoRegistro::Habilitado,
            ],
        );
    }
}
