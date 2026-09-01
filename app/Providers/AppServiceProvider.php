<?php

namespace App\Providers;

use App\Enums\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Auth\AccesoService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccesoService::class);
    }

    public function boot(): void
    {
        $this->registrarGates();
        $this->invalidarCacheDeAccesos();
    }

    /**
     * Cada caso del enum Permiso se publica como Gate, de modo que en las
     * vistas baste con `@can('usuarios.crear')`.
     */
    private function registrarGates(): void
    {
        foreach (Permiso::cases() as $permiso) {
            Gate::define(
                $permiso->value,
                fn (Usuario $usuario) => app(AccesoService::class)->puede($usuario, $permiso)
            );
        }
    }

    /**
     * Los permisos se cachean por rol. Si se editan desde la pantalla de roles
     * y no se limpia la cache, el usuario sigue viendo el menu anterior hasta
     * que expire —y como se guarda para siempre, eso no pasa nunca.
     */
    private function invalidarCacheDeAccesos(): void
    {
        $olvidar = fn (Rol $rol) => app(AccesoService::class)->olvidar($rol->id_rol);

        Rol::saved($olvidar);
        Rol::deleted($olvidar);
    }
}
