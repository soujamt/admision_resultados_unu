<?php

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Livewire\Forms\FacultadForm;
use App\Models\Facultad;
use App\Services\Admision\FacultadService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

new
#[Title('Facultades | Admisión UNU')]
class extends Component
{
    use WithPagination;

    public FacultadForm $form;

    #[Url(as: 'q', except: '')]
    public string $busqueda = '';

    #[Url(as: 'estado', except: '')]
    public string $filtroEstado = '';

    public function mount(): void
    {
        $this->authorize(Permiso::FacultadesVer->value);
    }

    public function updatedBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function nueva(): void
    {
        $this->authorize(Permiso::FacultadesCrear->value);

        $this->form->reset();
        $this->resetValidation();

        Flux::modal('facultad')->show();
    }

    public function editar(int $id): void
    {
        $this->authorize(Permiso::FacultadesEditar->value);

        $this->form->llenar(Facultad::findOrFail($id));
        $this->resetValidation();

        Flux::modal('facultad')->show();
    }

    public function guardar(FacultadService $servicio): void
    {
        $this->authorize($this->form->id === null
            ? Permiso::FacultadesCrear->value
            : Permiso::FacultadesEditar->value);

        $this->form->validate();

        $facultad = $this->form->id === null ? null : Facultad::findOrFail($this->form->id);
        $servicio->guardar($this->form->datos(), $facultad);

        Flux::modal('facultad')->close();
        $this->form->reset();

        Flux::toast(text: 'La facultad fue guardada.', variant: 'success');
    }

    public function alternarEstado(int $id, FacultadService $servicio): void
    {
        $this->authorize(Permiso::FacultadesEditar->value);

        $servicio->alternarEstado(Facultad::findOrFail($id));

        Flux::toast(text: 'El estado fue actualizado.', variant: 'success');
    }

    public function eliminar(int $id, FacultadService $servicio): void
    {
        $this->authorize(Permiso::FacultadesEliminar->value);

        try {
            $servicio->eliminar(Facultad::findOrFail($id));
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'La facultad fue eliminada.', variant: 'success');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'facultades' => Facultad::query()
                ->withCount('carreras')
                ->when(filled($this->busqueda), fn (Builder $q) => $q->where(
                    fn (Builder $q) => $q
                        ->where('nombre_fac', 'like', '%'.trim($this->busqueda).'%')
                        ->orWhere('codigo_fac', 'like', '%'.trim($this->busqueda).'%')
                ))
                ->when($this->filtroEstado !== '', fn (Builder $q) => $q->where('estado_fac', (int) $this->filtroEstado))
                ->orderBy('nombre_fac')
                ->paginate(10),
            'estados' => EstadoRegistro::cases(),
        ];
    }
};
