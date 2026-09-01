<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('actualiza el nombre y mueve el usuario de acceso junto al correo', function () {
    $usuario = Usuario::factory()->create([
        'nombre_usu' => 'Ana Rios',
        'usuario_usu' => 'ana.rios@unu.edu.pe',
        'correo_usu' => 'ana.rios@unu.edu.pe',
    ]);

    Livewire::actingAs($usuario)
        ->test('pages::perfil')
        ->set('nombre', 'Ana Maria Rios')
        ->set('correo', 'Ana.Maria@unu.edu.pe')
        ->call('guardarContacto')
        ->assertHasNoErrors();

    $usuario->refresh();

    expect($usuario->nombre_usu)->toBe('Ana Maria Rios')
        ->and($usuario->correo_usu)->toBe('ana.maria@unu.edu.pe')
        ->and($usuario->usuario_usu)->toBe('ana.maria@unu.edu.pe');
});

it('rechaza un correo que ya usa otra cuenta', function () {
    Usuario::factory()->create(['correo_usu' => 'ocupado@unu.edu.pe']);
    $usuario = Usuario::factory()->create();

    Livewire::actingAs($usuario)
        ->test('pages::perfil')
        ->set('correo', 'ocupado@unu.edu.pe')
        ->call('guardarContacto')
        ->assertHasErrors(['correo' => 'unique']);
});

it('acepta que el usuario conserve su propio correo', function () {
    $usuario = Usuario::factory()->create(['correo_usu' => 'ana.rios@unu.edu.pe']);

    Livewire::actingAs($usuario)
        ->test('pages::perfil')
        ->set('nombre', 'Ana Maria Rios')
        ->call('guardarContacto')
        ->assertHasNoErrors();
});

it('cambia la contrasena cuando la actual es correcta', function () {
    $usuario = Usuario::factory()->create(['clave_usu' => 'clave-vieja']);

    Livewire::actingAs($usuario)
        ->test('pages::perfil')
        ->set('claveActual', 'clave-vieja')
        ->set('claveNueva', 'clave-nueva-2026')
        ->set('claveConfirmacion', 'clave-nueva-2026')
        ->call('guardarClave')
        ->assertHasNoErrors();

    expect(Hash::check('clave-nueva-2026', $usuario->fresh()->clave_usu))->toBeTrue()
        ->and($usuario->fresh()->clave_cambiada_en_usu)->not->toBeNull();
});

it('no cambia la contrasena si la actual no coincide', function () {
    $usuario = Usuario::factory()->create(['clave_usu' => 'clave-vieja']);

    Livewire::actingAs($usuario)
        ->test('pages::perfil')
        ->set('claveActual', 'no-es-esta')
        ->set('claveNueva', 'clave-nueva-2026')
        ->set('claveConfirmacion', 'clave-nueva-2026')
        ->call('guardarClave')
        ->assertHasErrors('claveActual');

    expect(Hash::check('clave-vieja', $usuario->fresh()->clave_usu))->toBeTrue();
});
