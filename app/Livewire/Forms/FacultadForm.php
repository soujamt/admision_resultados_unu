<?php

namespace App\Livewire\Forms;

use App\Enums\EstadoRegistro;
use App\Models\Facultad;
use Illuminate\Validation\Rule;
use Livewire\Form;

class FacultadForm extends Form
{
    public ?int $id = null;

    public string $codigo = '';

    public string $nombre = '';

    public int $estado = EstadoRegistro::Habilitado->value;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required', 'string', 'max:20', 'regex:/^[A-Z0-9_]+$/',
                Rule::unique('tbl_facultad', 'codigo_fac')->ignore($this->id, 'id_fac')->whereNull('deleted_at'),
            ],
            'nombre' => [
                'required', 'string', 'min:5', 'max:150',
                Rule::unique('tbl_facultad', 'nombre_fac')->ignore($this->id, 'id_fac')->whereNull('deleted_at'),
            ],
            'estado' => ['required', Rule::enum(EstadoRegistro::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'codigo' => 'código',
            'nombre' => 'nombre',
            'estado' => 'estado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.regex' => 'El código solo admite mayúsculas, números y guion bajo.',
        ];
    }

    public function llenar(Facultad $facultad): void
    {
        $this->id = $facultad->id_fac;
        $this->codigo = $facultad->codigo_fac;
        $this->nombre = $facultad->nombre_fac;
        $this->estado = $facultad->estado_fac->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return [
            'codigo_fac' => mb_strtoupper(trim($this->codigo)),
            'nombre_fac' => trim($this->nombre),
            'estado_fac' => EstadoRegistro::from($this->estado),
        ];
    }
}
