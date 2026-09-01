<?php

use App\Enums\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Auth\AccesoService;

it('concede los permisos que tiene el rol', function () {
    $usuario = Usuario::factory()->create([
        'id_rol' => Rol::factory()->con([Permiso::UsuariosVer]),
    ]);

    $accesos = app(AccesoService::class);

    expect($accesos->puede($usuario, Permiso::UsuariosVer))->toBeTrue()
        ->and($accesos->puede($usuario, Permiso::UsuariosCrear))->toBeFalse();
});

it('publica cada permiso como un Gate', function () {
    $usuario = Usuario::factory()->create([
        'id_rol' => Rol::factory()->con([Permiso::RolesVer]),
    ]);

    expect($usuario->can(Permiso::RolesVer->value))->toBeTrue()
        ->and($usuario->can(Permiso::RolesEditar->value))->toBeFalse();
});

it('niega todo permiso a un usuario deshabilitado aunque su rol los tenga', function () {
    $usuario = Usuario::factory()->deshabilitado()->create([
        'id_rol' => Rol::factory()->administrador(),
    ]);

    expect(app(AccesoService::class)->puede($usuario, Permiso::UsuariosVer))->toBeFalse();
});

it('niega todo permiso cuando el rol esta deshabilitado', function () {
    $usuario = Usuario::factory()->create([
        'id_rol' => Rol::factory()->administrador()->deshabilitado(),
    ]);

    expect(app(AccesoService::class)->puede($usuario, Permiso::UsuariosVer))->toBeFalse();
});

it('niega todo permiso a un visitante', function () {
    expect(app(AccesoService::class)->puede(null, Permiso::UsuariosVer))->toBeFalse();
});

it('refresca la cache al cambiar los permisos del rol', function () {
    $rol = Rol::factory()->create();
    $usuario = Usuario::factory()->create(['id_rol' => $rol]);
    $accesos = app(AccesoService::class);

    expect($accesos->puede($usuario, Permiso::UsuariosVer))->toBeFalse();

    $rol->update(['permisos_rol' => [Permiso::UsuariosVer->value]]);

    expect($accesos->puede($usuario->fresh(), Permiso::UsuariosVer))->toBeTrue();
});

it('descarta los permisos que ya no existen en el enum', function () {
    $usuario = Usuario::factory()->create([
        'id_rol' => Rol::factory()->create([
            'permisos_rol' => [Permiso::UsuariosVer->value, 'modulo.retirado'],
        ]),
    ]);

    expect(app(AccesoService::class)->permisos($usuario))->toBe([Permiso::UsuariosVer]);
});

it('reconoce si el usuario tiene alguno de varios permisos', function () {
    $usuario = Usuario::factory()->create([
        'id_rol' => Rol::factory()->con([Permiso::RolesVer]),
    ]);

    $accesos = app(AccesoService::class);

    expect($accesos->puedeAlguno($usuario, [Permiso::UsuariosVer, Permiso::RolesVer]))->toBeTrue()
        ->and($accesos->puedeAlguno($usuario, [Permiso::UsuariosVer, Permiso::UsuariosCrear]))->toBeFalse();
});
