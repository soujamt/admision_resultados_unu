<?php

use App\Enums\Convocatoria;
use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Livewire\Forms\ProcesoForm;
use App\Models\Proceso;
use App\Services\Admision\ProcesoService;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

new
#[Title('Procesos de admisión | Admisión UNU')]
class extends Component
{
    public ProcesoForm $form;

    public function mount(): void
    {
        $this->authorize(Permiso::ProcesosVer->value);
    }

    public function nuevo(): void
    {
        $this->authorize(Permiso::ProcesosCrear->value);

        $this->form->reset();
        $this->form->anio = (int) now()->addYear()->format('Y');
        $this->form->convocatoria = Convocatoria::Primera->value;
        $this->resetValidation();

        Flux::modal('proceso')->show();
    }

    public function editar(int $id): void
    {
        $this->authorize(Permiso::ProcesosEditar->value);

        $this->form->llenar(Proceso::findOrFail($id));
        $this->resetValidation();

        Flux::modal('proceso')->show();
    }

    public function guardar(ProcesoService $servicio): void
    {
        $this->authorize($this->form->id === null
            ? Permiso::ProcesosCrear->value
            : Permiso::ProcesosEditar->value);

        $this->form->validate();

        $proceso = $this->form->id === null ? null : Proceso::findOrFail($this->form->id);
        $servicio->guardar($this->form->datos(), $proceso);

        Flux::modal('proceso')->close();
        $this->form->reset();

        Flux::toast(text: 'El proceso fue guardado.', variant: 'success');
    }

    public function alternarEstado(int $id, ProcesoService $servicio): void
    {
        $this->authorize(Permiso::ProcesosEditar->value);

        $servicio->alternarEstado(Proceso::findOrFail($id));

        Flux::toast(text: 'El estado fue actualizado.', variant: 'success');
    }

    public function eliminar(int $id, ProcesoService $servicio): void
    {
        $this->authorize(Permiso::ProcesosEliminar->value);

        try {
            $servicio->eliminar(Proceso::findOrFail($id));
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'El proceso fue eliminado.', variant: 'success');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'procesos' => Proceso::query()
                ->withCount(['vacantes', 'inscripciones'])
                ->withSum('vacantes', 'cantidad_vac')
                ->orderByDesc('anio_pro')
                ->orderByDesc('convocatoria_pro')
                ->get(),
            'convocatorias' => Convocatoria::cases(),
            'estados' => EstadoRegistro::cases(),
        ];
    }
};
