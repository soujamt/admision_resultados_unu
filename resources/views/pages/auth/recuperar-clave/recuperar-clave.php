<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts::auth')]
#[Title('Recuperar contraseña | Admisión UNU')]
class extends Component
{
    #[Validate('required|email|max:150')]
    public string $correo = '';

    public bool $enviado = false;

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['correo' => 'correo'];
    }

    public function enviarEnlace(): void
    {
        $this->validate();

        $estado = Password::sendResetLink(['usuario_usu' => mb_strtolower(trim($this->correo))]);

        // No se distingue entre un correo registrado y uno que no lo esta: eso
        // permitiria averiguar que cuentas existen.
        if ($estado === Password::RESET_THROTTLED) {
            $this->addError('correo', __($estado));

            return;
        }

        $this->enviado = true;
    }
};
