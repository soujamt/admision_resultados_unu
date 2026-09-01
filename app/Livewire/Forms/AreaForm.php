<?php

namespace App\Livewire\Forms;

use App\Enums\EstadoRegistro;
use App\Models\Area;
use Illuminate\Validation\Rule;
use Livewire\Form;

class AreaForm extends Form
{
    public ?int $id = null;

    public ?int $numero = null;

    public string $nombre = '';

    public int $estado = EstadoRegistro::Habilitado->value;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero' => [
                'required', 'integer', 'min:1', 'max:99',
                Rule::unique('tbl_area', 'numero_are')->ignore($this->id, 'id_are')->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'min:4', 'max:120'],
            'estado' => ['required', Rule::enum(EstadoRegistro::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'numero' => 'número de área',
            'nombre' => 'denominación',
            'estado' => 'estado',
        ];
    }

    public function llenar(Area $area): void
    {
        $this->id = $area->id_are;
        $this->numero = $area->numero_are;
        $this->nombre = $area->nombre_are;
        $this->estado = $area->estado_are->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return [
            'numero_are' => $this->numero,
            'nombre_are' => trim($this->nombre),
            'estado_are' => EstadoRegistro::from($this->estado),
        ];
    }
}
