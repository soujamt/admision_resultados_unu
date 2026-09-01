<?php

namespace App\Services\Auth;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PerfilService
{
    /**
     * Actualiza los datos de contacto.
     *
     * El correo es tambien el usuario de acceso, asi que ambas columnas se
     * mueven juntas: si se dejaran separadas, quien cambiara su correo seguiria
     * entrando con el anterior y no lo sabria.
     *
     * @param  array{nombre: string, correo: string}  $datos
     */
    public function actualizarContacto(Usuario $usuario, array $datos): void
    {
        $usuario->update([
            'nombre_usu' => $datos['nombre'],
            'correo_usu' => $datos['correo'],
            'usuario_usu' => $datos['correo'],
        ]);
    }

    /**
     * @throws ValidationException cuando la contrasena actual no coincide.
     */
    public function cambiarClave(Usuario $usuario, string $claveActual, string $claveNueva): void
    {
        if (! Hash::check($claveActual, $usuario->clave_usu)) {
            throw ValidationException::withMessages([
                'claveActual' => 'La contrasena actual no es correcta.',
            ]);
        }

        $usuario->update([
            'clave_usu' => $claveNueva,
            'clave_cambiada_en_usu' => now(),
        ]);
    }
}
