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

    public ?int $capacidad = null;

    /**
     * El aula elegida, resuelta una sola vez por peticion: `rules()` y la
     * pantalla la consultan varias veces en el mismo ciclo.
     */
    private ?Aula $aulaResuelta = null;

    private bool $aulaBuscada = false;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'aula' => ['required', 'integer', Rule::exists((new Aula)->getTable(), 'id_aul')->whereNull('deleted_at')],
            'area' => ['required', 'integer', Rule::exists((new Area)->getTable(), 'id_are')->whereNull('deleted_at')],
            /*
             * El tope son las carpetas del aula elegida. Mientras no haya aula
             * no hay tope que comprobar: `aula` es obligatoria, asi que el
             * formulario no llega a guardarse sin ella.
             */
            'capacidad' => array_filter([
                'required', 'integer', 'min:1',
                $this->capacidadMaxima() === null ? null : 'max:'.$this->capacidadMaxima(),
            ]),
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
            'capacidad.max' => 'Esa aula tiene :max carpetas; no caben más postulantes.',
        ];
    }

    /**
     * Carpetas del aula elegida, o null mientras no haya ninguna elegida.
     */
    public function capacidadMaxima(): ?int
    {
        $aula = $this->aulaElegida();

        return $aula === null ? null : max(1, $aula->capacidad_aul);
    }

    public function aulaElegida(): ?Aula
    {
        if (! $this->aulaBuscada) {
            $this->aulaResuelta = $this->aula === null ? null : Aula::find($this->aula);
            $this->aulaBuscada = true;
        }

        return $this->aulaResuelta;
    }

    /**
     * Deja el formulario limpio y olvida el aula memorizada, que si no
     * sobrevive a la siguiente peticion con un valor que ya no corresponde.
     */
    public function limpiar(): void
    {
        $this->reset('aula', 'area', 'capacidad');
        $this->olvidarAula();
    }

    public function olvidarAula(): void
    {
        $this->aulaResuelta = null;
        $this->aulaBuscada = false;
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
