<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class ExamenForm extends Form
{
    public string $nombre = '';

    public string $fecha = '';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'fecha' => ['nullable', 'date'],
        ];
    }
}
