<?php

namespace App\Livewire\Forms;

use App\Enums\EstadoRegistro;
use App\Models\Carrera;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CarreraForm extends Form
{
    public ?int $id = null;

    public ?int $facultad = null;

    public ?int $area = null;

    public string $codigo = '';

    public string $nombre = '';

    public string $nombreCorto = '';

    public int $estado = EstadoRegistro::Habilitado->value;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'facultad' => ['required', 'integer', Rule::exists('tbl_facultad', 'id_fac')->whereNull('deleted_at')],
            'area' => ['required', 'integer', Rule::exists('tbl_area', 'id_are')->whereNull('deleted_at')],
            'codigo' => [
                'required', 'string', 'max:30', 'regex:/^[A-Z0-9_]+$/',
                Rule::unique('tbl_carrera', 'codigo_car')->ignore($this->id, 'id_car')->whereNull('deleted_at'),
            ],
            'nombre' => [
                'required', 'string', 'min:4', 'max:180',
                Rule::unique('tbl_carrera', 'nombre_car')->ignore($this->id, 'id_car')->whereNull('deleted_at'),
            ],
            'nombreCorto' => ['required', 'string', 'min:3', 'max:80'],
            'estado' => ['required', Rule::enum(EstadoRegistro::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'facultad' => 'facultad',
            'area' => 'área',
            'codigo' => 'código',
            'nombre' => 'nombre',
            'nombreCorto' => 'nombre corto',
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

    public function llenar(Carrera $carrera): void
    {
        $this->id = $carrera->id_car;
        $this->facultad = $carrera->id_fac;
        $this->area = $carrera->id_are;
        $this->codigo = $carrera->codigo_car;
        $this->nombre = $carrera->nombre_car;
        $this->nombreCorto = $carrera->nombre_corto_car;
        $this->estado = $carrera->estado_car->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return [
            'id_fac' => $this->facultad,
            'id_are' => $this->area,
            'codigo_car' => mb_strtoupper(trim($this->codigo)),
            'nombre_car' => trim($this->nombre),
            'nombre_corto_car' => trim($this->nombreCorto),
            'estado_car' => EstadoRegistro::from($this->estado),
        ];
    }
}
