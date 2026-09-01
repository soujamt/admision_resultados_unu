<?php

use App\Enums\Permiso;
use App\Models\Carrera;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Vacante;
use App\Services\Admision\VacanteService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Title('Cuadro de vacantes | Admisión UNU')]
class extends Component
{
    /** Codigo del proceso que se esta configurando, por ejemplo «2027-I». */
    #[Url(as: 'proceso', except: '')]
    public string $codigoProceso = '';

    /**
     * Cantidad escrita en cada fila de la tabla, indexada por id de vacante.
     * Se edita todo junto y se guarda de una sola vez.
     *
     * @var array<int, int|string>
     */
    public array $cantidades = [];

    /** Campos del formulario para sumar una carrera al cuadro. */
    public ?int $nuevaModalidad = null;

    public ?int $nuevaCarrera = null;

    public ?int $nuevaSede = null;

    public int $nuevaCantidad = 0;

    public ?int $nuevoCodigoExterno = null;

    public function mount(): void
    {
        $this->authorize(Permiso::VacantesVer->value);

        if ($this->codigoProceso === '') {
            $this->codigoProceso = Proceso::query()
                ->habilitado()
                ->orderByDesc('anio_pro')
                ->orderByDesc('convocatoria_pro')
                ->value('codigo_pro') ?? '';
        }

        $this->sincronizarCantidades();
    }

    public function updatedCodigoProceso(): void
    {
        $this->sincronizarCantidades();
    }

    /**
     * Rellena los inputs con lo que hay guardado. Se llama al entrar y cada vez
     * que se cambia de proceso, para que la tabla nunca muestre cifras de otro.
     */
    private function sincronizarCantidades(): void
    {
        $proceso = $this->proceso();

        $this->cantidades = $proceso === null
            ? []
            : Vacante::where('id_pro', $proceso->id_pro)->pluck('cantidad_vac', 'id_vac')->all();
    }

    public function proceso(): ?Proceso
    {
        return $this->codigoProceso === ''
            ? null
            : Proceso::where('codigo_pro', $this->codigoProceso)->first();
    }

    public function guardar(VacanteService $servicio): void
    {
        $this->authorize(Permiso::VacantesEditar->value);

        $proceso = $this->proceso();

        if ($proceso === null) {
            return;
        }

        $this->validate(
            ['cantidades.*' => ['required', 'integer', 'min:0', 'max:9999']],
            ['cantidades.*.required' => 'Cada carrera necesita un número de vacantes.'],
            ['cantidades.*' => 'vacantes'],
        );

        $modificadas = $servicio->guardarCantidades($proceso, $this->cantidades);

        Flux::toast(
            text: $modificadas === 0
                ? 'No había cambios que guardar.'
                : "Se actualizaron {$modificadas} carrera(s) del cuadro.",
            variant: $modificadas === 0 ? 'warning' : 'success',
        );
    }

    public function abrirAgregar(): void
    {
        $this->authorize(Permiso::VacantesEditar->value);

        $this->reset('nuevaModalidad', 'nuevaCarrera', 'nuevaSede', 'nuevaCantidad', 'nuevoCodigoExterno');
        $this->resetValidation();

        Flux::modal('agregar-vacante')->show();
    }

    public function agregar(VacanteService $servicio): void
    {
        $this->authorize(Permiso::VacantesEditar->value);

        $proceso = $this->proceso();

        if ($proceso === null) {
            return;
        }

        $this->validate([
            'nuevaModalidad' => ['required', 'integer', 'exists:tbl_modalidad,id_mod'],
            'nuevaCarrera' => ['required', 'integer', 'exists:tbl_carrera,id_car'],
            'nuevaSede' => ['required', 'integer', 'exists:tbl_sede,id_sed'],
            'nuevaCantidad' => ['required', 'integer', 'min:0', 'max:9999'],
            'nuevoCodigoExterno' => ['nullable', 'integer', 'min:1'],
        ], [], [
            'nuevaModalidad' => 'modalidad',
            'nuevaCarrera' => 'carrera',
            'nuevaSede' => 'sede',
            'nuevaCantidad' => 'vacantes',
            'nuevoCodigoExterno' => 'código del formato',
        ]);

        try {
            $servicio->agregar($proceso, [
                'id_mod' => $this->nuevaModalidad,
                'id_car' => $this->nuevaCarrera,
                'id_sed' => $this->nuevaSede,
                'cantidad_vac' => $this->nuevaCantidad,
                'codigo_externo_vac' => $this->nuevoCodigoExterno,
            ]);
        } catch (RuntimeException $error) {
            $this->addError('nuevaCarrera', $error->getMessage());

            return;
        }

        Flux::modal('agregar-vacante')->close();
        $this->sincronizarCantidades();

        Flux::toast(text: 'La carrera fue agregada al cuadro.', variant: 'success');
    }

    public function eliminar(int $id, VacanteService $servicio): void
    {
        $this->authorize(Permiso::VacantesEditar->value);

        try {
            $servicio->eliminar(Vacante::findOrFail($id));
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        $this->sincronizarCantidades();

        Flux::toast(text: 'La fila fue eliminada del cuadro.', variant: 'success');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(VacanteService $servicio): array
    {
        $proceso = $this->proceso();

        return [
            'proceso' => $proceso,
            'procesos' => Proceso::orderByDesc('anio_pro')->orderByDesc('convocatoria_pro')->get(),
            'cuadro' => $proceso === null ? collect() : $servicio->cuadro($proceso)->groupBy(
                fn (Vacante $vacante): string => $vacante->modalidad->nombre_mod.' · '.$vacante->sede->nombre_sed,
            ),
            'resumen' => $proceso === null ? null : $servicio->resumen($proceso),
            'modalidades' => Modalidad::habilitada()->orderBy('nombre_mod')->get(),
            'carreras' => Carrera::habilitada()->orderBy('nombre_car')->get(),
            'sedes' => Sede::habilitada()->orderBy('nombre_sed')->get(),
        ];
    }
};
