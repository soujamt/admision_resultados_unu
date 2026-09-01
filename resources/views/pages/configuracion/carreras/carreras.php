<?php

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Livewire\Forms\CarreraForm;
use App\Models\Area;
use App\Models\Carrera;
use App\Models\Facultad;
use App\Services\Admision\CarreraService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Title('Carreras profesionales | Admisión UNU')]
class extends Component
{
    use WithPagination;

    public CarreraForm $form;

    #[Url(as: 'q', except: '')]
    public string $busqueda = '';

    #[Url(as: 'facultad', except: '')]
    public string $filtroFacultad = '';

    #[Url(as: 'area', except: '')]
    public string $filtroArea = '';

    public function mount(): void
    {
        $this->authorize(Permiso::CarrerasVer->value);
    }

    public function updated(string $propiedad): void
    {
        if (str_starts_with($propiedad, 'filtro') || $propiedad === 'busqueda') {
            $this->resetPage();
        }
    }

    public function nueva(): void
    {
        $this->authorize(Permiso::CarrerasCrear->value);

        $this->form->reset();
        $this->resetValidation();

        Flux::modal('carrera')->show();
    }

    public function editar(int $id): void
    {
        $this->authorize(Permiso::CarrerasEditar->value);

        $this->form->llenar(Carrera::findOrFail($id));
        $this->resetValidation();

        Flux::modal('carrera')->show();
    }

    public function guardar(CarreraService $servicio): void
    {
        $this->authorize($this->form->id === null
            ? Permiso::CarrerasCrear->value
            : Permiso::CarrerasEditar->value);

        $this->form->validate();

        $carrera = $this->form->id === null ? null : Carrera::findOrFail($this->form->id);
        $servicio->guardar($this->form->datos(), $carrera);

        Flux::modal('carrera')->close();
        $this->form->reset();

        Flux::toast(text: 'La carrera fue guardada.', variant: 'success');
    }

    public function alternarEstado(int $id, CarreraService $servicio): void
    {
        $this->authorize(Permiso::CarrerasEditar->value);

        $servicio->alternarEstado(Carrera::findOrFail($id));

        Flux::toast(text: 'El estado fue actualizado.', variant: 'success');
    }

    public function eliminar(int $id, CarreraService $servicio): void
    {
        $this->authorize(Permiso::CarrerasEliminar->value);

        try {
            $servicio->eliminar(Carrera::findOrFail($id));
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'La carrera fue eliminada.', variant: 'success');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'carreras' => Carrera::query()
                ->with(['facultad', 'area'])
                ->when(filled($this->busqueda), fn (Builder $q) => $q->where(
                    fn (Builder $q) => $q
                        ->where('nombre_car', 'like', '%'.trim($this->busqueda).'%')
                        ->orWhere('codigo_car', 'like', '%'.trim($this->busqueda).'%')
                ))
                ->when($this->filtroFacultad !== '', fn (Builder $q) => $q->where('id_fac', (int) $this->filtroFacultad))
                ->when($this->filtroArea !== '', fn (Builder $q) => $q->where('id_are', (int) $this->filtroArea))
                ->orderBy('nombre_car')
                ->paginate(15),
            'facultades' => Facultad::orderBy('nombre_fac')->get(),
            'areas' => Area::orderBy('numero_are')->get(),
            'estados' => EstadoRegistro::cases(),
        ];
    }
};
