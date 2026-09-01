<?php

namespace App\Livewire\Forms;

use App\Models\Area;
use App\Models\Aula;
use Illuminate\Validation\Rule;
use Livewire\Form;

class DistribucionAulaForm extends Form
{
    public ?int $aula = null;

    public ?int $area = null;

    public int $capacidad = 40;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'aula' => ['required', 'integer', Rule::exists((new Aula)->getTable(), 'id_aul')->whereNull('deleted_at')],
            'area' => ['required', 'integer', Rule::exists((new Area)->getTable(), 'id_are')->whereNull('deleted_at')],
            'capacidad' => ['required', 'integer', 'min:1', 'max:40'],
        ];
    }
}
