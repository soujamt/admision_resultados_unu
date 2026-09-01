<?php

namespace App\Livewire\Forms;

use App\Enums\Convocatoria;
use App\Enums\EstadoRegistro;
use App\Models\Proceso;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ProcesoForm extends Form
{
    public ?int $id = null;

    public ?int $anio = null;

    public ?int $convocatoria = null;

    public string $nombre = '';

    public ?string $inicioInscripcion = null;

    public ?string $finInscripcion = null;

    public ?string $fechaExamen = null;

    public string $resolucion = '';

    public int $estado = EstadoRegistro::Habilitado->value;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'convocatoria' => [
                'required', Rule::enum(Convocatoria::class),
                /*
                 * El codigo del proceso se compone del anio y la convocatoria,
                 * asi que el par no se puede repetir: seria otro «2027-I».
                 */
                Rule::unique('tbl_proceso', 'convocatoria_pro')
                    ->where('anio_pro', $this->anio)
                    ->ignore($this->id, 'id_pro')
                    ->whereNull('deleted_at'),
            ],
            'nombre' => ['nullable', 'string', 'max:150'],
            'inicioInscripcion' => ['nullable', 'date'],
            'finInscripcion' => ['nullable', 'date', 'after_or_equal:inicioInscripcion'],
            'fechaExamen' => ['nullable', 'date'],
            'resolucion' => ['nullable', 'string', 'max:100'],
            'estado' => ['required', Rule::enum(EstadoRegistro::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'anio' => 'año',
            'convocatoria' => 'convocatoria',
            'nombre' => 'nombre',
            'inicioInscripcion' => 'inicio de inscripciones',
            'finInscripcion' => 'cierre de inscripciones',
            'fechaExamen' => 'fecha del examen',
            'resolucion' => 'resolución',
            'estado' => 'estado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'convocatoria.unique' => 'Ese año ya tiene registrada esa convocatoria.',
            'finInscripcion.after_or_equal' => 'El cierre de inscripciones no puede ser anterior al inicio.',
        ];
    }

    public function llenar(Proceso $proceso): void
    {
        $this->id = $proceso->id_pro;
        $this->anio = $proceso->anio_pro;
        $this->convocatoria = $proceso->convocatoria_pro->value;
        $this->nombre = $proceso->nombre_pro;
        $this->inicioInscripcion = $proceso->fecha_inicio_inscripcion_pro?->toDateString();
        $this->finInscripcion = $proceso->fecha_fin_inscripcion_pro?->toDateString();
        $this->fechaExamen = $proceso->fecha_examen_pro?->toDateString();
        $this->resolucion = $proceso->resolucion_pro ?? '';
        $this->estado = $proceso->estado_pro->value;
    }

    /**
     * El codigo y el nombre por defecto los compone ProcesoService a partir del
     * anio y la convocatoria.
     *
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return [
            'anio_pro' => $this->anio,
            'convocatoria_pro' => Convocatoria::from($this->convocatoria),
            'nombre_pro' => trim($this->nombre),
            'fecha_inicio_inscripcion_pro' => $this->inicioInscripcion ?: null,
            'fecha_fin_inscripcion_pro' => $this->finInscripcion ?: null,
            'fecha_examen_pro' => $this->fechaExamen ?: null,
            'resolucion_pro' => blank($this->resolucion) ? null : trim($this->resolucion),
            'estado_pro' => EstadoRegistro::from($this->estado),
        ];
    }

    /**
     * Vista previa del codigo mientras se escribe el formulario.
     */
    public function codigoPrevisto(): ?string
    {
        $convocatoria = Convocatoria::tryFrom((int) $this->convocatoria);

        return $this->anio === null || $convocatoria === null
            ? null
            : Proceso::componerCodigo($this->anio, $convocatoria);
    }
}
