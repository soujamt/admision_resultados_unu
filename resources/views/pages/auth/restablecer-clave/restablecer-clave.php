<?php

use App\Models\Usuario;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts::auth')]
#[Title('Nueva contraseña | Admisión UNU')]
class extends Component
{
    public string $token = '';

    #[Validate('required|email|max:150')]
    public string $correo = '';

    #[Validate('required|string|min:8|confirmed:claveConfirmacion')]
    public string $clave = '';

    public string $claveConfirmacion = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->correo = request()->string('correo')->toString();
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['correo' => 'correo', 'clave' => 'contraseña'];
    }

    public function restablecer(): void
    {
        $this->validate();

        $estado = Password::reset(
            [
                'usuario_usu' => mb_strtolower(trim($this->correo)),
                'password' => $this->clave,
                'password_confirmation' => $this->claveConfirmacion,
                'token' => $this->token,
            ],
            function (Usuario $usuario, string $clave) {
                $usuario->forceFill([
                    'clave_usu' => $clave,
                    'clave_cambiada_en_usu' => now(),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($usuario));
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            $this->addError('correo', __($estado));

            return;
        }

        session()->flash('acceso', 'Tu contraseña fue actualizada. Ya puedes iniciar sesión.');

        $this->redirect(route('auth.login'), navigate: true);
    }
};
