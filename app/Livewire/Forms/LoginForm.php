<?php

namespace App\Livewire\Forms;

use App\Services\Auth\AutenticacionService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|email|max:150')]
    public string $usuario = '';

    #[Validate('required|string')]
    public string $clave = '';

    public bool $recordarme = false;

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'usuario' => 'correo',
            'clave' => 'contrasena',
        ];
    }

    /**
     * Devuelve false cuando las credenciales no pasan, para que el componente
     * se quede en la pantalla mostrando el error.
     */
    public function autenticar(AutenticacionService $servicio): bool
    {
        $this->validate();

        try {
            $servicio->autenticar($this->usuario, $this->clave, $this->recordarme);
        } catch (ValidationException $e) {
            $this->addError('usuario', $e->validator->errors()->first());

            return false;
        }

        $this->reset('clave');

        return true;
    }
}
