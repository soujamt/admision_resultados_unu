<?php

namespace App\Livewire\Forms;

use App\Enums\EstadoRegistro;
use App\Models\Sede;
use Illuminate\Validation\Rule;
use Livewire\Form;

class SedeForm extends Form
{
    public ?int $id = null;

    public string $codigo = '';

    public string $nombre = '';

    public ?string $ubigeo = null;

    public bool $esFilial = false;

    public int $estado = EstadoRegistro::Habilitado->value;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required', 'string', 'max:20', 'regex:/^[A-Z0-9_]+$/',
                Rule::unique('tbl_sede', 'codigo_sed')->ignore($this->id, 'id_sed')->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'min:4', 'max:150'],
            'ubigeo' => ['nullable', 'string', 'size:6', Rule::exists('tbl_ubigeo', 'codigo_ubi')],
            'esFilial' => ['boolean'],
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
            'ubigeo' => 'distrito',
            'esFilial' => 'filial',
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
            'ubigeo.exists' => 'Ese distrito no está en el padrón de ubigeos.',
        ];
    }

    public function llenar(Sede $sede): void
    {
        $this->id = $sede->id_sed;
        $this->codigo = $sede->codigo_sed;
        $this->nombre = $sede->nombre_sed;
        $this->ubigeo = $sede->codigo_ubi;
        $this->esFilial = $sede->es_filial_sed;
        $this->estado = $sede->estado_sed->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return [
            'codigo_sed' => mb_strtoupper(trim($this->codigo)),
            'nombre_sed' => trim($this->nombre),
            'codigo_ubi' => blank($this->ubigeo) ? null : $this->ubigeo,
            'es_filial_sed' => $this->esFilial,
            'estado_sed' => EstadoRegistro::from($this->estado),
        ];
    }
}
