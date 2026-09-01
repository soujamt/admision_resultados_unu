<?php

use App\Enums\EstadoRegistro;
use App\Models\Usuario;
use Livewire\Livewire;

it('muestra la pantalla de acceso a los visitantes', function () {
    $this->get(route('auth.login'))
        ->assertOk()
        ->assertSee('Iniciar sesión');
});

it('envia al escritorio a quien ya inicio sesion', function () {
    $this->actingAs(Usuario::factory()->create())
        ->get(route('auth.login'))
        ->assertRedirect(route('inicio.dashboard'));
});

it('inicia sesion con credenciales correctas', function () {
    $usuario = Usuario::factory()->create([
        'usuario_usu' => 'postulante@unu.edu.pe',
        'clave_usu' => 'clave-correcta',
    ]);

    Livewire::test('pages::auth.login')
        ->set('form.usuario', 'postulante@unu.edu.pe')
        ->set('form.clave', 'clave-correcta')
        ->call('autenticar')
        ->assertHasNoErrors()
        ->assertRedirect(route('inicio.dashboard'));

    expect(auth()->id())->toBe($usuario->id_usu);
});

it('normaliza el correo antes de autenticar', function () {
    Usuario::factory()->create([
        'usuario_usu' => 'postulante@unu.edu.pe',
        'clave_usu' => 'clave-correcta',
    ]);

    Livewire::test('pages::auth.login')
        ->set('form.usuario', 'POSTULANTE@UNU.EDU.PE')
        ->set('form.clave', 'clave-correcta')
        ->call('autenticar')
        ->assertHasNoErrors();

    expect(auth()->check())->toBeTrue();
});

it('rechaza una contrasena incorrecta', function () {
    Usuario::factory()->create([
        'usuario_usu' => 'postulante@unu.edu.pe',
        'clave_usu' => 'clave-correcta',
    ]);

    Livewire::test('pages::auth.login')
        ->set('form.usuario', 'postulante@unu.edu.pe')
        ->set('form.clave', 'clave-equivocada')
        ->call('autenticar')
        ->assertHasErrors('form.usuario');

    expect(auth()->check())->toBeFalse();
});

it('no deja entrar a un usuario deshabilitado', function () {
    Usuario::factory()->deshabilitado()->create([
        'usuario_usu' => 'inactivo@unu.edu.pe',
        'clave_usu' => 'clave-correcta',
    ]);

    Livewire::test('pages::auth.login')
        ->set('form.usuario', 'inactivo@unu.edu.pe')
        ->set('form.clave', 'clave-correcta')
        ->call('autenticar')
        ->assertHasErrors('form.usuario');

    expect(auth()->check())->toBeFalse();
});

it('bloquea la cuenta tras cinco intentos fallidos', function () {
    Usuario::factory()->create([
        'usuario_usu' => 'postulante@unu.edu.pe',
        'clave_usu' => 'clave-correcta',
    ]);

    $componente = Livewire::test('pages::auth.login')
        ->set('form.usuario', 'postulante@unu.edu.pe')
        ->set('form.clave', 'clave-equivocada');

    foreach (range(1, 5) as $intento) {
        $componente->call('autenticar');
    }

    $componente->set('form.clave', 'clave-correcta')
        ->call('autenticar')
        ->assertHasErrors('form.usuario');

    expect(auth()->check())->toBeFalse();
});

it('exige correo y contrasena', function () {
    Livewire::test('pages::auth.login')
        ->call('autenticar')
        ->assertHasErrors(['form.usuario' => 'required', 'form.clave' => 'required']);
});

it('cierra la sesion y regresa al acceso', function () {
    $this->actingAs(Usuario::factory()->create())
        ->post(route('auth.salir'))
        ->assertRedirect(route('auth.login'));

    expect(auth()->check())->toBeFalse();
});

it('exige sesion iniciada para entrar al escritorio', function () {
    $this->get(route('inicio.dashboard'))->assertRedirect(route('auth.login'));
    $this->get(route('perfil'))->assertRedirect(route('auth.login'));
});

it('guarda el estado del usuario como enum', function () {
    expect(Usuario::factory()->create()->estado_usu)->toBe(EstadoRegistro::Habilitado);
});
