<?php

use App\Livewire\Forms\LoginForm;
use App\Services\Auth\AutenticacionService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::auth')]
#[Title('Acceder | Admisión UNU')]
class extends Component
{
    public LoginForm $form;

    public function autenticar(AutenticacionService $servicio): void
    {
        if ($this->form->autenticar($servicio)) {
            $this->redirectIntended(route('inicio.dashboard'), navigate: true);
        }
    }
};
