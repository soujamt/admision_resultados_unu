<?php

namespace App\Services\Auth;

use App\Enums\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\Cache;

/**
 * Resuelve que puede hacer un usuario.
 *
 * Los permisos se cachean por rol —no por usuario—, porque dos usuarios del
 * mismo rol tienen exactamente los mismos accesos. Lo que se guarda es una
 * lista de strings, no modelos: un Eloquent serializado no sobrevive al
 * `unserialize()` del store de cache sin la clase cargada de antemano.
 */
class AccesoService
{
    private const PREFIJO_CACHE = 'admision:permisos:rol:';

    /**
     * Permisos concedidos al rol, tal como estan guardados.
     *
     * @return list<string>
     */
    public function permisosDelRol(int $idRol): array
    {
        return Cache::rememberForever(
            self::PREFIJO_CACHE.$idRol,
            fn () => Rol::query()
                ->habilitado()
                ->whereKey($idRol)
                ->value('permisos_rol') ?? []
        );
    }

    public function puede(?Usuario $usuario, Permiso $permiso): bool
    {
        if ($usuario === null || ! $usuario->estaHabilitado()) {
            return false;
        }

        return in_array($permiso->value, $this->permisosDelRol($usuario->id_rol), true);
    }

    /**
     * Permisos efectivos del usuario, resueltos a casos del enum. Se descartan
     * los valores que quedaron huerfanos al retirar un permiso del codigo.
     *
     * @return list<Permiso>
     */
    public function permisos(?Usuario $usuario): array
    {
        if ($usuario === null || ! $usuario->estaHabilitado()) {
            return [];
        }

        return array_values(array_filter(
            array_map(Permiso::tryFrom(...), $this->permisosDelRol($usuario->id_rol))
        ));
    }

    /**
     * Si el usuario tiene alguno de los permisos indicados. Sirve para decidir
     * si se pinta un grupo entero del menu lateral.
     *
     * @param  list<Permiso>  $permisos
     */
    public function puedeAlguno(?Usuario $usuario, array $permisos): bool
    {
        foreach ($permisos as $permiso) {
            if ($this->puede($usuario, $permiso)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Se llama al cambiar los permisos de un rol. Sin argumentos limpia todos:
     * es lo que hace falta cuando se retira un permiso del enum.
     */
    public function olvidar(?int $idRol = null): void
    {
        if ($idRol !== null) {
            Cache::forget(self::PREFIJO_CACHE.$idRol);

            return;
        }

        foreach (Rol::withTrashed()->pluck('id_rol') as $id) {
            Cache::forget(self::PREFIJO_CACHE.$id);
        }
    }
}
