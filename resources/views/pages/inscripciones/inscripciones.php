<?php

use App\Enums\EstadoInscripcion;
use App\Enums\Permiso;
use App\Exports\PadronInscripcionesExport;
use App\Models\Carrera;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\Sede;
use App\Services\Admision\InscripcionService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new
#[Title('Inscripciones | Admisión UNU')]
class extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'proceso', except: '')]
    public string $codigoProceso = '';

    #[Url(as: 'q', except: '')]
    public string $busqueda = '';

    #[Url(as: 'modalidad', except: '')]
    public string $filtroModalidad = '';

    #[Url(as: 'carrera', except: '')]
    public string $filtroCarrera = '';

    #[Url(as: 'sede', except: '')]
    public string $filtroSede = '';

    #[Url(as: 'estado', except: '')]
    public string $filtroEstado = '';

    /** Archivo del formato oficial que se esta subiendo. */
    public ?TemporaryUploadedFile $archivo = null;

    /** Proceso contra el que se carga el archivo. */
    public string $procesoDestino = '';

    /** Resumen de la ultima importacion, para mostrarlo tras cerrar el modal. */
    public ?array $ultimaImportacion = null;

    /** Ficha que se esta viendo en el panel de detalle. */
    public ?int $fichaSeleccionada = null;

    public function mount(): void
    {
        $this->authorize(Permiso::InscripcionesVer->value);

        if ($this->codigoProceso === '') {
            $this->codigoProceso = Proceso::query()
                ->habilitado()
                ->orderByDesc('anio_pro')
                ->orderByDesc('convocatoria_pro')
                ->value('codigo_pro') ?? '';
        }
    }

    public function updated(string $propiedad): void
    {
        if ($propiedad === 'busqueda' || str_starts_with($propiedad, 'filtro') || $propiedad === 'codigoProceso') {
            $this->resetPage();
        }
    }

    public function proceso(): ?Proceso
    {
        return $this->codigoProceso === ''
            ? null
            : Proceso::where('codigo_pro', $this->codigoProceso)->first();
    }

    public function limpiarFiltros(): void
    {
        $this->reset('busqueda', 'filtroModalidad', 'filtroCarrera', 'filtroSede', 'filtroEstado');
        $this->resetPage();
    }

    public function abrirImportacion(): void
    {
        $this->authorize(Permiso::InscripcionesImportar->value);

        $this->reset('archivo', 'ultimaImportacion');
        $this->procesoDestino = $this->codigoProceso;
        $this->resetValidation();

        Flux::modal('importar')->show();
    }

    public function importar(InscripcionService $servicio): void
    {
        $this->authorize(Permiso::InscripcionesImportar->value);

        $this->validate([
            'procesoDestino' => ['required', 'string', 'exists:tbl_proceso,codigo_pro'],
            'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ], [
            'archivo.mimes' => 'Sube el archivo Excel del formato oficial (.xlsx).',
            'archivo.max' => 'El archivo no puede pesar más de 20 MB.',
            'procesoDestino.exists' => 'Ese proceso no existe.',
        ], [
            'procesoDestino' => 'proceso',
            'archivo' => 'archivo',
        ]);

        $proceso = Proceso::where('codigo_pro', $this->procesoDestino)->sole();

        try {
            $resultado = $servicio->importar($proceso, $this->archivo->getRealPath());
        } catch (RuntimeException $error) {
            $this->addError('archivo', $error->getMessage());

            return;
        }

        $this->ultimaImportacion = [
            'proceso' => $proceso->codigo_pro,
            'creados' => $resultado->creados,
            'actualizados' => $resultado->actualizados,
            'omitidos' => $resultado->omitidos,
            'errores' => array_slice($resultado->errores, 0, 50),
            'errores_totales' => count($resultado->errores),
        ];

        $this->reset('archivo');
        $this->codigoProceso = $proceso->codigo_pro;
        $this->resetPage();

        Flux::toast(
            text: "Se cargaron {$resultado->procesados()} inscripción(es) en {$proceso->codigo_pro}.",
            variant: $resultado->tieneErrores() ? 'warning' : 'success',
            duration: 6000,
        );
    }

    public function exportar(InscripcionService $servicio): BinaryFileResponse
    {
        $this->authorize(Permiso::InscripcionesExportar->value);

        $proceso = $this->proceso();
        $nombre = 'padron-inscripciones-'.($proceso?->codigo_pro ?? 'todos').'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new PadronInscripcionesExport($servicio->consulta($this->filtros())), $nombre);
    }

    public function verFicha(int $id): void
    {
        $this->authorize(Permiso::InscripcionesVer->value);

        $this->fichaSeleccionada = $id;

        Flux::modal('ficha')->show();
    }

    public function anular(int $id, InscripcionService $servicio): void
    {
        $this->authorize(Permiso::InscripcionesEliminar->value);

        $servicio->anular(Inscripcion::findOrFail($id));

        Flux::toast(text: 'La ficha fue anulada.', variant: 'success');
    }

    /**
     * @return array<string, mixed>
     */
    private function filtros(): array
    {
        return [
            'proceso' => $this->proceso()?->id_pro,
            'modalidad' => $this->filtroModalidad === '' ? null : (int) $this->filtroModalidad,
            'carrera' => $this->filtroCarrera === '' ? null : (int) $this->filtroCarrera,
            'sede' => $this->filtroSede === '' ? null : (int) $this->filtroSede,
            'estado' => $this->filtroEstado === '' ? null : (int) $this->filtroEstado,
            'busqueda' => $this->busqueda,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function with(InscripcionService $servicio): array
    {
        $proceso = $this->proceso();

        return [
            'proceso' => $proceso,
            'procesos' => Proceso::orderByDesc('anio_pro')->orderByDesc('convocatoria_pro')->get(),
            'inscripciones' => $servicio->consulta($this->filtros())
                ->orderBy('codigo_ins')
                ->paginate(20),
            'resumen' => $proceso === null ? null : $servicio->resumen($proceso),
            'ficha' => $this->fichaSeleccionada === null
                ? null
                : Inscripcion::with([
                    'postulante.ubigeoNacimiento',
                    'postulante.ubigeoDireccion',
                    'postulante.paisNacimiento',
                    'postulante.nacionalidad',
                    'carrera.facultad',
                    'carrera.area',
                    'modalidad',
                    'sede',
                    'colegio',
                    'proceso',
                ])->find($this->fichaSeleccionada),
            'modalidades' => Modalidad::orderBy('nombre_mod')->get(),
            'carreras' => Carrera::orderBy('nombre_car')->get(),
            'sedes' => Sede::orderBy('nombre_sed')->get(),
            'estadosInscripcion' => EstadoInscripcion::cases(),
        ];
    }
};
