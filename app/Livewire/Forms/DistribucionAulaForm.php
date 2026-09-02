<?php

namespace App\Livewire\Forms;

use App\Models\Area;
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
            'aula' => ['required', 'integer', Rule::exists('tbl_aula', 'id_aul')->whereNull('deleted_at')],
            'area' => ['required', 'integer', Rule::exists((new Area)->getTable(), 'id_are')->whereNull('deleted_at')],
            'capacidad' => ['required', 'integer', 'min:1', 'max:40'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'aula' => 'aula',
            'area' => 'área',
            'capacidad' => 'cantidad de postulantes',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'aula.required' => 'Elige el aula que se va a usar.',
            'area.required' => 'Elige a qué área pertenece el aula.',
            'capacidad.max' => 'La capacidad máxima permitida por aula es 40.',
        ];
    }

    public function limpiar(): void
    {
        $this->reset('aula', 'area', 'capacidad');
    }

    /** @return array{id_aul:int, id_are:int, capacidad_eau:int} */
    public function datos(): array
    {
        return [
            'id_aul' => (int) $this->aula,
            'id_are' => (int) $this->area,
            'capacidad_eau' => (int) $this->capacidad,
        ];
    }
}
