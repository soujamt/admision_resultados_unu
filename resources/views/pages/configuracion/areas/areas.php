<?php

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Livewire\Forms\AreaForm;
use App\Models\Area;
use App\Services\Admision\AreaService;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Áreas académicas | Admisión UNU')]
class extends Component
{
    public AreaForm $form;

    public function mount(): void
    {
        $this->authorize(Permiso::AreasVer->value);
    }

    public function nueva(): void
    {
        $this->authorize(Permiso::AreasCrear->value);

        $this->form->reset();
        $this->resetValidation();

        Flux::modal('area')->show();
    }

    public function editar(int $id): void
    {
        $this->authorize(Permiso::AreasEditar->value);

        $this->form->llenar(Area::findOrFail($id));
        $this->resetValidation();

        Flux::modal('area')->show();
    }

    public function guardar(AreaService $servicio): void
    {
        $this->authorize($this->form->id === null
            ? Permiso::AreasCrear->value
            : Permiso::AreasEditar->value);

        $this->form->validate();

        $area = $this->form->id === null ? null : Area::findOrFail($this->form->id);
        $servicio->guardar($this->form->datos(), $area);

        Flux::modal('area')->close();
        $this->form->reset();

        Flux::toast(text: 'El área fue guardada.', variant: 'success');
    }

    public function alternarEstado(int $id, AreaService $servicio): void
    {
        $this->authorize(Permiso::AreasEditar->value);

        $servicio->alternarEstado(Area::findOrFail($id));

        Flux::toast(text: 'El estado fue actualizado.', variant: 'success');
    }

    public function eliminar(int $id, AreaService $servicio): void
    {
        $this->authorize(Permiso::AreasEliminar->value);

        try {
            $servicio->eliminar(Area::findOrFail($id));
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'El área fue eliminada.', variant: 'success');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            /*
             * Son cinco filas (Art. 4): no hay paginacion ni busqueda, y se
             * traen las carreras para poder listarlas debajo de cada area.
             */
            'areas' => Area::query()
                ->with(['carreras' => fn ($q) => $q->orderBy('nombre_car')])
                ->orderBy('numero_are')
                ->get(),
            'estados' => EstadoRegistro::cases(),
        ];
    }
};
