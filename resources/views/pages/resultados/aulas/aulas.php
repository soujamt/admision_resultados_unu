<?php

use App\Enums\Permiso;
use App\Livewire\Forms\DistribucionAulaForm;
use App\Livewire\Forms\ExamenForm;
use App\Models\Area;
use App\Models\Aula;
use App\Models\Examen;
use App\Models\Proceso;
use App\Services\Admision\DistribucionAulasService;
use App\Services\Admision\SorteadorAulasService;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Examen y aulas | Admisión UNU')]
class extends Component
{
    public ExamenForm $formExamen;

    public DistribucionAulaForm $formAula;

    public string $procesoSeleccionado = '';

    public string $examenSeleccionado = '';

    public function mount(): void
    {
        $this->authorize(Permiso::ResultadosVer->value);
        $this->procesoSeleccionado = (string) (Proceso::query()->habilitado()->orderByDesc('anio_pro')->value('id_pro') ?? '');
    }

    public function updatedProcesoSeleccionado(): void
    {
        $this->examenSeleccionado = '';
    }

    public function crearExamen(): void
    {
        $this->authorize(Permiso::ResultadosConfigurarAulas->value);
        $this->formExamen->validate();

        if ($this->procesoSeleccionado === '' || ! Proceso::whereKey($this->procesoSeleccionado)->exists()) {
            $this->addError('procesoSeleccionado', 'Elige un proceso válido.');

            return;
        }

        $examen = Examen::create([
            'id_pro' => (int) $this->procesoSeleccionado,
            'nombre_exa' => trim($this->formExamen->nombre),
            'fecha_exa' => blank($this->formExamen->fecha) ? null : $this->formExamen->fecha,
        ]);

        $this->examenSeleccionado = (string) $examen->id_exa;
        $this->formExamen->reset();
        Flux::modal('examen')->close();
        Flux::toast(text: 'La jornada de examen fue creada.', variant: 'success');
    }

    public function nuevoExamen(): void
    {
        $this->authorize(Permiso::ResultadosConfigurarAulas->value);
        $this->formExamen->reset();
        $this->resetValidation();
        Flux::modal('examen')->show();
    }

    public function agregarAula(DistribucionAulasService $servicio): void
    {
        $this->authorize(Permiso::ResultadosConfigurarAulas->value);
        $this->formAula->validate();
        $examen = $this->examen();
        if ($examen === null) {
            $this->addError('examenSeleccionado', 'Elige una jornada de examen.');

            return;
        }

        try {
            $servicio->agregar($examen, ['id_aul' => $this->formAula->aula, 'id_are' => $this->formAula->area, 'capacidad_eau' => $this->formAula->capacidad]);
        } catch (RuntimeException $error) {
            $this->addError('formAula.aula', $error->getMessage());

            return;
        }

        $this->formAula->reset();
        Flux::toast(text: 'El aula fue incorporada a la distribución.', variant: 'success');
    }

    public function retirarAula(int $id, DistribucionAulasService $servicio): void
    {
        $this->authorize(Permiso::ResultadosConfigurarAulas->value);
        $examen = $this->examen();
        if ($examen === null) {
            return;
        }

        $servicio->retirar($examen, $id);
    }

    public function sortear(SorteadorAulasService $sorteador, DistribucionAulasService $distribucion): void
    {
        $this->authorize(Permiso::ResultadosSortearAulas->value);
        $examen = $this->examen();
        if ($examen === null) {
            return;
        }

        try {
            $total = $sorteador->sortear($examen, $distribucion);
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: "Se asignaron {$total} postulantes.", variant: 'success');
    }

    private function examen(): ?Examen
    {
        return $this->examenSeleccionado === '' ? null : Examen::find($this->examenSeleccionado);
    }

    public function with(DistribucionAulasService $servicio): array
    {
        $examen = $this->examen();
        $distribucion = $examen?->aulas()->with(['aula.sede', 'area'])->orderBy('id_eau')->get() ?? collect();
        $aulasOcupadas = $distribucion->pluck('id_aul');

        return [
            'procesos' => Proceso::query()->orderByDesc('anio_pro')->get(),
            'examenes' => $this->procesoSeleccionado === '' ? collect() : Examen::query()->where('id_pro', $this->procesoSeleccionado)->orderBy('fecha_exa')->get(),
            'examen' => $examen,
            'aulas' => Aula::query()->habilitado()->whereNotIn('id_aul', $aulasOcupadas)->with('sede')->ordenadas()->get(),
            'areas' => Area::query()->habilitado()->orderBy('numero_are')->get(),
            'distribucion' => $distribucion,
            'totales' => $examen === null ? collect() : $servicio->totalesPorArea($examen),
        ];
    }
};
