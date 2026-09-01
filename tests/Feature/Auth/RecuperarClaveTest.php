<?php

use App\Models\Usuario;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

it('envia el enlace de recuperacion al correo de la cuenta', function () {
    Notification::fake();

    $usuario = Usuario::factory()->create([
        'usuario_usu' => 'postulante@unu.edu.pe',
        'correo_usu' => 'postulante@unu.edu.pe',
    ]);

    Livewire::test('pages::auth.recuperar-clave')
        ->set('correo', 'postulante@unu.edu.pe')
        ->call('enviarEnlace')
        ->assertHasNoErrors()
        ->assertSet('enviado', true);

    Notification::assertSentTo($usuario, ResetPassword::class);
});

it('no revela si el correo esta registrado', function () {
    Notification::fake();

    Livewire::test('pages::auth.recuperar-clave')
        ->set('correo', 'desconocido@unu.edu.pe')
        ->call('enviarEnlace')
        ->assertHasNoErrors()
        ->assertSet('enviado', true);

    Notification::assertNothingSent();
});

it('restablece la contrasena con un token valido', function () {
    $usuario = Usuario::factory()->create([
        'usuario_usu' => 'postulante@unu.edu.pe',
        'correo_usu' => 'postulante@unu.edu.pe',
        'clave_usu' => 'clave-vieja',
    ]);

    $token = Password::createToken($usuario);

    Livewire::test('pages::auth.restablecer-clave', ['token' => $token])
        ->set('correo', 'postulante@unu.edu.pe')
        ->set('clave', 'clave-nueva-2026')
        ->set('claveConfirmacion', 'clave-nueva-2026')
        ->call('restablecer')
        ->assertHasNoErrors()
        ->assertRedirect(route('auth.login'));

    expect(Hash::check('clave-nueva-2026', $usuario->fresh()->clave_usu))->toBeTrue()
        ->and($usuario->fresh()->clave_cambiada_en_usu)->not->toBeNull();
});

it('rechaza un token invalido', function () {
    Usuario::factory()->create([
        'usuario_usu' => 'postulante@unu.edu.pe',
        'correo_usu' => 'postulante@unu.edu.pe',
        'clave_usu' => 'clave-vieja',
    ]);

    Livewire::test('pages::auth.restablecer-clave', ['token' => 'token-inventado'])
        ->set('correo', 'postulante@unu.edu.pe')
        ->set('clave', 'clave-nueva-2026')
        ->set('claveConfirmacion', 'clave-nueva-2026')
        ->call('restablecer')
        ->assertHasErrors('correo');
});
