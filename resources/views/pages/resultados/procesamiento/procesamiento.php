<?php

use App\Enums\Convocatoria;
use App\Enums\EstadoResultado;
use App\Enums\Permiso;
use App\Livewire\Forms\ConfiguracionResultadosForm;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\ExamenPostulante;
use App\Models\Proceso;
use App\Models\Resultado;
use App\Services\Admision\ExamenService;
use App\Services\Admision\ImportadorExamenTxt;
use App\Services\Admision\ResolverResultadosService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new
#[Title('Resultados | Admisión UNU')]
class extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ConfiguracionResultadosForm $configuracion;

    #[Url(as: 'proceso', except: '')]
    public string $procesoSeleccionado = '';

    #[Url(as: 'jornada', except: '')]
    public string $examenSeleccionado = '';

    #[Url(except: '')]
    public string $buscar = '';

    #[Url(except: '')]
    public string $estado = '';

    public mixed $archivoPadron = null;

    /** @var array<int, mixed> */
    public array $archivosRespuestas = [];

    /** @var array<int, string|null> */
    public array $minimosCarreras = [];

    public string $postulanteAnular = '';

    public string $motivoAnulacion = '';

    /** @var list<string> */
    public array $observacionesImportacion = [];

    /** @var array<string, int|float|bool> */
    public array $ultimoResumen = [];

    public function mount(): void
    {
        $this->authorize(Permiso::ResultadosVer->value);

        if ($this->procesoSeleccionado === '') {
            $this->procesoSeleccionado = (string) (Proceso::query()
                ->orderByDesc('anio_pro')
                ->orderByDesc('convocatoria_pro')
                ->value('id_pro') ?? '');
        }

        if ($this->examenSeleccionado === '') {
            $this->examenSeleccionado = (string) (Examen::query()
                ->where('id_pro', $this->procesoSeleccionado)
                ->orderByDesc('fecha_exa')
                ->value('id_exa') ?? '');
        }

        $this->cargarConfiguracion();
    }

    public function updatedProcesoSeleccionado(): void
    {
        $this->examenSeleccionado = '';
        $this->minimosCarreras = [];
        $this->observacionesImportacion = [];
        $this->ultimoResumen = [];
        $this->resetPage();
    }

    public function updatedExamenSeleccionado(): void
    {
        $this->observacionesImportacion = [];
        $this->ultimoResumen = [];
        $this->cargarConfiguracion();
        $this->resetPage();
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function guardarConfiguracion(ExamenService $servicio): void
    {
        $this->authorize(Permiso::ResultadosGenerar->value);
        $examen = $this->examen();

        if ($examen === null) {
            $this->addError('examenSeleccionado', 'Elige una jornada de examen.');

            return;
        }

        $this->configuracion->validate();
        $this->validate([
            'minimosCarreras.*' => ['nullable', 'numeric', 'between:0,100'],
        ]);
        $minimos = collect($this->minimosCarreras)
            ->map(fn (mixed $valor): ?float => blank($valor) ? null : (float) $valor)
            ->all();

        try {
            $servicio->configurarResultados($examen, $this->configuracion->datos(), $minimos);
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 8000);

            return;
        }

        Flux::toast(text: 'La configuración fue guardada. Debes generar nuevamente los resultados.', variant: 'success');
    }

    public function importarPadron(ImportadorExamenTxt $importador): void
    {
        $this->authorize(Permiso::ResultadosImportar->value);
        $this->validate(['archivoPadron' => ['required', 'file', 'max:20480']]);
        $examen = $this->examen();

        if ($examen === null) {
            return;
        }

        try {
            $resumen = $importador->importarPadron(
                $examen,
                $this->archivoPadron->getRealPath(),
                $this->archivoPadron->getClientOriginalName(),
                auth()->id(),
            );
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 8000);

            return;
        }

        $this->observacionesImportacion = $resumen->observaciones;
        $this->archivoPadron = null;
        Flux::toast(
            text: $resumen->mensaje('postulantes'),
            variant: $resumen->importado ? 'success' : 'danger',
            duration: 8000,
        );
    }

    public function importarRespuestas(ImportadorExamenTxt $importador): void
    {
        $this->authorize(Permiso::ResultadosImportar->value);
        $this->validate([
            'archivosRespuestas' => ['required', 'array', 'min:1'],
            'archivosRespuestas.*' => ['file', 'max:20480'],
        ]);
        $examen = $this->examen();

        if ($examen === null) {
            return;
        }

        $observaciones = [];
        $importados = 0;

        foreach ($this->archivosRespuestas as $archivo) {
            try {
                $resumen = $importador->importarRespuestas(
                    $examen,
                    $archivo->getRealPath(),
                    $archivo->getClientOriginalName(),
                    auth()->id(),
                );
            } catch (RuntimeException $error) {
                $observaciones[] = $archivo->getClientOriginalName().': '.$error->getMessage();

                continue;
            }

            $importados += $resumen->importado ? $resumen->filas : 0;
            $observaciones = [...$observaciones, ...$resumen->observaciones];
        }

        $this->observacionesImportacion = $observaciones;
        $this->archivosRespuestas = [];
        Flux::toast(
            text: "Se importaron {$importados} respuestas. ".($observaciones === [] ? '' : count($observaciones).' observación(es) requieren revisión.'),
            variant: $observaciones === [] ? 'success' : 'warning',
            duration: 8000,
        );
    }

    public function generar(ResolverResultadosService $servicio): void
    {
        $this->authorize(Permiso::ResultadosGenerar->value);
        $examen = $this->examen();

        if ($examen === null) {
            return;
        }

        try {
            $this->ultimoResumen = $servicio->resolver($examen);
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 10000);

            return;
        }

        $this->resetPage();
        Flux::toast(text: 'Los resultados fueron generados correctamente.', variant: 'success');
    }

    public function prepararAnulacion(int $idPostulante): void
    {
        $this->authorize(Permiso::ResultadosAnular->value);
        $this->postulanteAnular = (string) $idPostulante;
        $this->motivoAnulacion = '';
        $this->resetValidation();
        Flux::modal('anular-postulacion')->show();
    }

    public function anular(ExamenService $servicio): void
    {
        $this->authorize(Permiso::ResultadosAnular->value);
        $this->validate(['motivoAnulacion' => ['required', 'string', 'min:10', 'max:255']]);
        $postulante = $this->postulante();

        if ($postulante === null) {
            return;
        }

        try {
            $servicio->anularPostulante($postulante, $this->motivoAnulacion);
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 8000);

            return;
        }

        $this->postulanteAnular = '';
        $this->motivoAnulacion = '';
        $this->ultimoResumen = [];
        Flux::modal('anular-postulacion')->close();
        Flux::toast(text: 'La postulación fue anulada. Debes generar nuevamente los resultados.', variant: 'success');
    }

    public function restaurar(int $idPostulante, ExamenService $servicio): void
    {
        $this->authorize(Permiso::ResultadosAnular->value);
        $this->postulanteAnular = (string) $idPostulante;
        $postulante = $this->postulante();
        $this->postulanteAnular = '';

        if ($postulante === null) {
            return;
        }

        try {
            $servicio->restaurarPostulante($postulante);
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 8000);

            return;
        }

        $this->ultimoResumen = [];
        Flux::toast(text: 'La postulación fue restaurada. Debes generar nuevamente los resultados.', variant: 'success');
    }

    private function postulante(): ?ExamenPostulante
    {
        $examen = $this->examen();

        if ($examen === null || $this->postulanteAnular === '') {
            return null;
        }

        return ExamenPostulante::query()
            ->where('id_exa', $examen->id_exa)
            ->find($this->postulanteAnular);
    }

    private function cargarConfiguracion(): void
    {
        $examen = $this->examen();

        if ($examen === null) {
            $this->minimosCarreras = [];

            return;
        }

        $this->configuracion->cargar($examen);
        $this->minimosCarreras = Carrera::query()
            ->whereIn('id_car', $examen->proceso->vacantes()->select('id_car'))
            ->pluck('puntaje_minimo_car', 'id_car')
            ->map(fn (mixed $valor): ?string => $valor === null ? null : (string) $valor)
            ->all();
    }

    private function examen(): ?Examen
    {
        return $this->examenSeleccionado === ''
            ? null
            : Examen::query()->with('proceso')->find($this->examenSeleccionado);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $procesos = Proceso::query()
            ->select(['id_pro', 'codigo_pro', 'anio_pro', 'convocatoria_pro'])
            ->orderByDesc('anio_pro')
            ->orderByDesc('convocatoria_pro')
            ->get();
        $examenes = $this->procesoSeleccionado === ''
            ? collect()
            : Examen::query()
                ->select(['id_exa', 'id_pro', 'nombre_exa', 'fecha_exa'])
                ->where('id_pro', $this->procesoSeleccionado)
                ->orderByDesc('fecha_exa')
                ->get();
        $examen = $this->examen();

        if ($examen === null) {
            return compact('procesos', 'examenes', 'examen') + [
                'vacantes' => collect(),
                'carreras' => collect(),
                'importaciones' => collect(),
                'resultados' => null,
                'estadisticas' => [],
                'resumenVacantes' => [],
                'resumenResolucion' => [],
            ];
        }

        $vacantes = $examen->proceso->vacantes()
            ->habilitada()
            ->with(['carrera', 'modalidad', 'sede'])
            ->orderBy('id_car')
            ->orderBy('id_mod')
            ->get();
        $consulta = Resultado::query()
            ->where('id_exa', $examen->id_exa)
            ->with(['postulante.inscripcion.carrera', 'postulante.inscripcion.modalidad', 'vacante.modalidad']);

        if ($this->buscar !== '') {
            $termino = '%'.trim($this->buscar).'%';
            $consulta->whereHas('postulante', fn ($query) => $query
                ->where('documento_exp', 'like', $termino)
                ->orWhere('nombre_exp', 'like', $termino));
        }

        if ($this->estado !== '') {
            $consulta->where('estado_res', $this->estado);
        }

        $resultados = $consulta
            ->orderByRaw('orden_general_res is null')
            ->orderBy('orden_general_res')
            ->orderBy('id_res')
            ->paginate(25);
        $estadisticas = [
            'padron' => $examen->postulantes()->count(),
            'respuestas' => $examen->postulantes()->whereHas('respuesta')->count(),
            'sin_cruce' => $examen->postulantes()->whereNull('id_ins')->count(),
            'resultados' => $examen->resultados()->count(),
            'ingresantes' => $examen->resultados()->where('estado_res', EstadoResultado::Ingreso)->count(),
            'nsp' => $examen->resultados()->where('estado_res', EstadoResultado::Nsp)->count(),
            'anulados' => $examen->postulantes()->whereNotNull('anulado_en_exp')->count(),
            'repescados' => $examen->resultados()->where('repesca_res', true)->count(),
        ];
        $ingresantesPorVacante = $examen->resultados()
            ->where('estado_res', EstadoResultado::Ingreso)
            ->selectRaw('id_vac, count(*) as total')
            ->groupBy('id_vac')
            ->pluck('total', 'id_vac');
        $resumenVacantes = $vacantes->mapWithKeys(function ($vacante) use ($ingresantesPorVacante): array {
            $ingresantes = (int) ($ingresantesPorVacante[$vacante->id_vac] ?? 0);

            return [$vacante->id_vac => [
                'ingresantes' => $ingresantes,
                'desiertas' => max(0, $vacante->plazas() - $ingresantes),
            ]];
        })->all();
        $carreras = $vacantes->groupBy('id_car')->map(fn ($filas): array => [
            'carrera' => $filas->first()->carrera,
            'vacantes' => $filas,
            'ofrecidas' => (int) $filas->sum(fn ($vacante): int => $vacante->plazas()),
            'ingresantes' => (int) $filas->sum(fn ($vacante): int => $resumenVacantes[$vacante->id_vac]['ingresantes']),
        ])->values();
        $ofrecidas = (int) $vacantes->sum(fn ($vacante): int => $vacante->plazas());
        $ingresantes = (int) $estadisticas['ingresantes'];
        $desiertas = max(0, $ofrecidas - $ingresantes);
        $porcentajeDesiertas = $ofrecidas > 0 ? round(($desiertas / $ofrecidas) * 100, 2) : 0;
        $resumenResolucion = [
            'ofrecidas' => $ofrecidas,
            'ingresantes' => $ingresantes,
            'desiertas' => $desiertas,
            'porcentaje_desiertas' => $porcentajeDesiertas,
            'requiere_examen_complementario' => $examen->proceso->convocatoria_pro === Convocatoria::Tercera
                && $examen->resuelto_en_exa !== null
                && $porcentajeDesiertas > 20,
        ];

        return compact('procesos', 'examenes', 'examen', 'vacantes', 'carreras', 'resultados', 'estadisticas', 'resumenVacantes', 'resumenResolucion') + [
            'importaciones' => $examen->importaciones()->latest('id_exi')->limit(8)->get(),
        ];
    }
};
