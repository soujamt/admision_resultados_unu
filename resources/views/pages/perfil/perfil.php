<?php

use App\Services\Auth\PerfilService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Mi perfil | Admisión UNU')]
class extends Component
{
    public string $nombre = '';

    public string $correo = '';

    public string $claveActual = '';

    public string $claveNueva = '';

    public string $claveConfirmacion = '';

    public function mount(): void
    {
        $this->nombre = auth()->user()->nombre_usu;
        $this->correo = auth()->user()->correo_usu;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nombre' => 'nombre',
            'correo' => 'correo',
            'claveActual' => 'contraseña actual',
            'claveNueva' => 'contraseña nueva',
        ];
    }

    public function guardarContacto(PerfilService $perfil): void
    {
        $usuario = auth()->user();

        $datos = $this->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'correo' => [
                'required',
                'email',
                'max:150',
                Rule::unique('tbl_usuario', 'correo_usu')->ignore($usuario->id_usu, 'id_usu'),
            ],
        ]);

        $perfil->actualizarContacto($usuario, [
            'nombre' => $datos['nombre'],
            'correo' => mb_strtolower(trim($datos['correo'])),
        ]);

        Flux::toast(text: 'Tus datos fueron actualizados.', variant: 'success');
    }

    public function guardarClave(PerfilService $perfil): void
    {
        $this->validate([
            'claveActual' => ['required', 'string'],
            'claveNueva' => ['required', 'string', 'min:8', 'confirmed:claveConfirmacion'],
        ]);

        try {
            $perfil->cambiarClave(auth()->user(), $this->claveActual, $this->claveNueva);
        } catch (ValidationException $e) {
            $this->addError('claveActual', $e->validator->errors()->first());

            return;
        }

        $this->reset('claveActual', 'claveNueva', 'claveConfirmacion');

        Flux::toast(text: 'Tu contraseña fue actualizada.', variant: 'success');
    }
};
