<?php

use App\Http\Controllers\Auth\SalirController;
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

    Route::post('/salir', SalirController::class)->name('auth.salir');
});