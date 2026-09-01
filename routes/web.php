<?php

use App\Http\Controllers\Auth\SalirController;
use App\Http\Controllers\Inscripciones\MostrarFotoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/inicio');

/*
 * No hay registro publico: las cuentas las crea la administracion. Por eso
 * aqui solo viven el acceso y la recuperacion de contrasena.
 */
Route::middleware('guest')->group(function () {
    Route::livewire('/acceder', 'pages::auth.login')->name('auth.login');
    Route::livewire('/recuperar-clave', 'pages::auth.recuperar-clave')->name('auth.recuperar');
    Route::livewire('/restablecer-clave/{token}', 'pages::auth.restablecer-clave')->name('auth.restablecer');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/inicio', 'pages::inicio')->name('inicio.dashboard');
    Route::livewire('/perfil', 'pages::perfil')->name('perfil');

    Route::prefix('configuracion')->name('configuracion.')->group(function (): void {
        Route::livewire('/procesos', 'pages::configuracion.procesos')->name('procesos');
        Route::livewire('/vacantes', 'pages::configuracion.vacantes')->name('vacantes');
        Route::livewire('/facultades', 'pages::configuracion.facultades')->name('facultades');
        Route::livewire('/areas', 'pages::configuracion.areas')->name('areas');
        Route::livewire('/carreras', 'pages::configuracion.carreras')->name('carreras');
        Route::livewire('/sedes', 'pages::configuracion.sedes')->name('sedes');
        Route::livewire('/aulas', 'pages::configuracion.aulas')->name('aulas');
    });

    Route::livewire('/inscripciones', 'pages::inscripciones')->name('inscripciones.index');
    Route::get('/inscripciones/{inscripcion}/foto', MostrarFotoController::class)->name('inscripciones.foto');

    Route::livewire('/resultados/aulas', 'pages::resultados.aulas')->name('resultados.aulas');

    Route::post('/salir', SalirController::class)->name('auth.salir');
});
