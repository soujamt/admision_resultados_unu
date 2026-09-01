<?php

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Livewire\Forms\AulaForm;
use App\Models\Aula;
use App\Models\Sede;
use App\Services\Admision\AulaService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

new
#[Title('Aulas | Admisión UNU')]
class extends Component
{
    use WithPagination;

    public AulaForm $form;

    #[Url(as: 'q', except: '')]
    public string $busqueda = '';

    #[Url(as: 'sede', except: '')]
    public string $filtroSede = '';

    public function mount(): void
    {
        $this->authorize(Permiso::AulasVer->value);
    }

    public function updated(string $propiedad): void
    {
        if ($propiedad === 'busqueda' || $propiedad === 'filtroSede') {
            $this->resetPage();
        }
    }

    public function nueva(): void
    {
        $this->authorize(Permiso::AulasCrear->value);

        $this->form->reset();
        $this->resetValidation();

        /* Si hay una sede filtrada, el aula nueva nace en esa. */
        if ($this->filtroSede !== '') {
            $this->form->sede = (int) $this->filtroSede;
        }

        Flux::modal('aula')->show();
    }

    public function editar(int $id): void
    {
        $this->authorize(Permiso::AulasEditar->value);

        $this->form->llenar(Aula::findOrFail($id));
        $this->resetValidation();

        Flux::modal('aula')->show();
    }

    public function guardar(AulaService $servicio): void
    {
        $this->authorize($this->form->id === null
            ? Permiso::AulasCrear->value
            : Permiso::AulasEditar->value);

        $this->form->validate();

        $aula = $this->form->id === null ? null : Aula::findOrFail($this->form->id);
        $servicio->guardar($this->form->datos(), $aula);

        Flux::modal('aula')->close();
        $this->form->reset();

        Flux::toast(text: 'El aula fue guardada.', variant: 'success');
    }

    public function alternarEstado(int $id, AulaService $servicio): void
    {
        $this->authorize(Permiso::AulasEditar->value);

        $servicio->alternarEstado(Aula::findOrFail($id));

        Flux::toast(text: 'El estado fue actualizado.', variant: 'success');
    }

    public function eliminar(int $id, AulaService $servicio): void
    {
        $this->authorize(Permiso::AulasEliminar->value);

        try {
            $servicio->eliminar(Aula::findOrFail($id));
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'El aula fue eliminada.', variant: 'success');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $consulta = Aula::query()
            ->with('sede')
            ->when(filled($this->busqueda), fn (Builder $q) => $q->where(
                fn (Builder $q) => $q
                    ->where('nombre_aul', 'like', '%'.trim($this->busqueda).'%')
                    ->orWhere('codigo_aul', 'like', '%'.trim($this->busqueda).'%')
                    ->orWhere('pabellon_aul', 'like', '%'.trim($this->busqueda).'%')
            ))
            ->when($this->filtroSede !== '', fn (Builder $q) => $q->where('id_sed', (int) $this->filtroSede));

        return [
            'aulas' => $consulta->clone()->ordenadas()->paginate(15),
            'capacidadTotal' => (int) $consulta->clone()->habilitado()->sum('capacidad_aul'),
            'sedes' => Sede::orderBy('nombre_sed')->get(),
            'estados' => EstadoRegistro::cases(),
        ];
    }
};
