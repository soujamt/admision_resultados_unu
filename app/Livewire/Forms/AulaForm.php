<?php

namespace App\Livewire\Forms;

use App\Enums\EstadoRegistro;
use App\Models\Aula;
use Illuminate\Validation\Rule;
use Livewire\Form;

class AulaForm extends Form
{
    public ?int $id = null;

    public ?int $sede = null;

    public string $codigo = '';

    public string $nombre = '';

    public string $pabellon = '';

    public int $capacidad = 0;

    public int $orden = 0;

    public int $estado = EstadoRegistro::Habilitado->value;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sede' => ['required', 'integer', Rule::exists('tbl_sede', 'id_sed')->whereNull('deleted_at')],
            'codigo' => [
                'required', 'string', 'max:20',
                /* El codigo solo tiene que ser unico dentro de su sede. */
                Rule::unique('tbl_aula', 'codigo_aul')
                    ->where('id_sed', $this->sede)
                    ->ignore($this->id, 'id_aul')
                    ->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'pabellon' => ['nullable', 'string', 'max:80'],
            'capacidad' => ['required', 'integer', 'min:0', 'max:2000'],
            'orden' => ['required', 'integer', 'min:0', 'max:999'],
            'estado' => ['required', Rule::enum(EstadoRegistro::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'sede' => 'sede',
            'codigo' => 'código',
            'nombre' => 'nombre',
            'pabellon' => 'pabellón',
            'capacidad' => 'capacidad',
            'orden' => 'orden',
            'estado' => 'estado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.unique' => 'Esa sede ya tiene un aula con ese código.',
        ];
    }

    public function llenar(Aula $aula): void
    {
        $this->id = $aula->id_aul;
        $this->sede = $aula->id_sed;
        $this->codigo = $aula->codigo_aul;
        $this->nombre = $aula->nombre_aul;
        $this->pabellon = $aula->pabellon_aul ?? '';
        $this->capacidad = $aula->capacidad_aul;
        $this->orden = $aula->orden_aul;
        $this->estado = $aula->estado_aul->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return [
            'id_sed' => $this->sede,
            'codigo_aul' => mb_strtoupper(trim($this->codigo)),
            'nombre_aul' => trim($this->nombre),
            'pabellon_aul' => blank($this->pabellon) ? null : trim($this->pabellon),
            'capacidad_aul' => $this->capacidad,
            'orden_aul' => $this->orden,
            'estado_aul' => EstadoRegistro::from($this->estado),
        ];
    }
}
