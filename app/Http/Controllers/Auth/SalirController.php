<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AutenticacionService;
use Illuminate\Http\RedirectResponse;

class SalirController extends Controller
{
    public function __construct(private readonly AutenticacionService $autenticacion) {}

    /**
     * El cierre de sesion es un POST a una ruta normal, no una accion Livewire:
     * al invalidar la sesion se regenera el token CSRF, y una respuesta
     * Livewire dejaria la pagina con un token que ya no vale.
     */
    public function __invoke(): RedirectResponse
    {
        $this->autenticacion->cerrarSesion();

        return redirect()->route('auth.login');
    }
}
