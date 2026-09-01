<?php

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Livewire\Forms\SedeForm;
use App\Models\Sede;
use App\Models\Ubigeo;
use App\Services\Admision\SedeService;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

new
#[Title('Sedes y filiales | Admisión UNU')]
class extends Component
{
    public SedeForm $form;

    /**
     * Texto con el que se busca el distrito en el formulario. El padron tiene
     * casi dos mil filas, asi que el desplegable se llena con lo que coincide
     * en vez de traerlas todas.
     */
    public string $busquedaUbigeo = '';

    public function mount(): void
    {
        $this->authorize(Permiso::SedesVer->value);
    }

    public function nueva(): void
    {
        $this->authorize(Permiso::SedesCrear->value);

        $this->form->reset();
        $this->reset('busquedaUbigeo');
        $this->resetValidation();

        Flux::modal('sede')->show();
    }

    public function editar(int $id): void
    {
        $this->authorize(Permiso::SedesEditar->value);

        $sede = Sede::findOrFail($id);

        $this->form->llenar($sede);
        $this->busquedaUbigeo = $sede->ubigeo?->distrito_ubi ?? '';
        $this->resetValidation();

        Flux::modal('sede')->show();
    }

    public function guardar(SedeService $servicio): void
    {
        $this->authorize($this->form->id === null
            ? Permiso::SedesCrear->value
            : Permiso::SedesEditar->value);

        $this->form->validate();

        $sede = $this->form->id === null ? null : Sede::findOrFail($this->form->id);
        $servicio->guardar($this->form->datos(), $sede);

        Flux::modal('sede')->close();
        $this->form->reset();
        $this->reset('busquedaUbigeo');

        Flux::toast(text: 'La sede fue guardada.', variant: 'success');
    }

    public function alternarEstado(int $id, SedeService $servicio): void
    {
        $this->authorize(Permiso::SedesEditar->value);

        $servicio->alternarEstado(Sede::findOrFail($id));

        Flux::toast(text: 'El estado fue actualizado.', variant: 'success');
    }

    public function eliminar(int $id, SedeService $servicio): void
    {
        $this->authorize(Permiso::SedesEliminar->value);

        try {
            $servicio->eliminar(Sede::findOrFail($id));
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'La sede fue eliminada.', variant: 'success');
    }

    /**
     * Distritos que ofrece el desplegable: los que coinciden con la busqueda,
     * mas el que ya estuviera elegido para que no desaparezca al filtrar.
     *
     * @return \Illuminate\Support\Collection<int, Ubigeo>
     */
    public function ubigeos(): \Illuminate\Support\Collection
    {
        $elegido = blank($this->form->ubigeo)
            ? collect()
            : Ubigeo::where('codigo_ubi', $this->form->ubigeo)->get();

        if (blank($this->busquedaUbigeo)) {
            return $elegido;
        }

        $termino = '%'.trim($this->busquedaUbigeo).'%';

        return Ubigeo::query()
            ->where(fn ($q) => $q
                ->where('distrito_ubi', 'like', $termino)
                ->orWhere('provincia_ubi', 'like', $termino)
                ->orWhere('departamento_ubi', 'like', $termino)
                ->orWhere('codigo_ubi', 'like', $termino))
            ->orderBy('departamento_ubi')
            ->orderBy('provincia_ubi')
            ->orderBy('distrito_ubi')
            ->limit(30)
            ->get()
            ->concat($elegido)
            ->unique('codigo_ubi');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'sedes' => Sede::query()
                ->with('ubigeo')
                ->withCount('aulas')
                ->withSum('aulas', 'capacidad_aul')
                ->orderBy('es_filial_sed')
                ->orderBy('nombre_sed')
                ->get(),
            'ubigeos' => $this->ubigeos(),
            'estados' => EstadoRegistro::cases(),
        ];
    }
};
