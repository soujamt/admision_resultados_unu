<?php

namespace App\Services\Auth;

use App\Enums\EstadoRegistro;
use App\Models\Usuario;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AutenticacionService
{
    /**
     * Intentos fallidos permitidos antes de bloquear la combinacion de usuario
     * y direccion IP.
     */
    private const MAXIMO_INTENTOS = 5;

    private const MINUTOS_BLOQUEO = 1;

    /**
     * Verifica las credenciales e inicia la sesion.
     *
     * @throws ValidationException cuando la cuenta esta bloqueada o las
     *                             credenciales no coinciden.
     */
    public function autenticar(string $usuario, string $clave, bool $recordar = false): Usuario
    {
        $usuario = (string) Str::of($usuario)->trim()->lower();

        $this->asegurarQueNoEstaBloqueado($usuario);

        if (! Auth::attempt([
            'usuario_usu' => $usuario,
            'password' => $clave,
            'estado_usu' => EstadoRegistro::Habilitado->value,
        ], $recordar)) {
            $this->registrarIntentoFallido($usuario);

            throw ValidationException::withMessages([
                'usuario' => 'Las credenciales ingresadas no son correctas.',
            ]);
        }

        RateLimiter::clear($this->claveDeIntentos($usuario));
        session()->regenerate();

        return Auth::user();
    }

    public function cerrarSesion(): void
    {
        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * @throws ValidationException
     */
    private function asegurarQueNoEstaBloqueado(string $usuario): void
    {
        if (! RateLimiter::tooManyAttempts($this->claveDeIntentos($usuario), self::MAXIMO_INTENTOS)) {
            return;
        }

        event(new Lockout(request()));

        $segundos = RateLimiter::availableIn($this->claveDeIntentos($usuario));

        throw ValidationException::withMessages([
            'usuario' => trans('auth.throttle', [
                'seconds' => $segundos,
                'minutes' => ceil($segundos / 60),
            ]),
        ]);
    }

    private function registrarIntentoFallido(string $usuario): void
    {
        RateLimiter::hit($this->claveDeIntentos($usuario), self::MINUTOS_BLOQUEO * 60);
    }

    /**
     * El bloqueo es por usuario y direccion IP, no solo por usuario: asi un
     * atacante no puede dejar fuera a una cuenta ajena a fuerza de fallos.
     */
    private function claveDeIntentos(string $usuario): string
    {
        return Str::transliterate(Str::lower($usuario).'|'.request()->ip());
    }
}
