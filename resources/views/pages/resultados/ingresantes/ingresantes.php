<?php

use App\Enums\CondicionIngresante;
use App\Enums\Convocatoria;
use App\Enums\Permiso;
use App\Models\Examen;
use App\Models\Ingresante;
use App\Models\Proceso;
use App\Services\Admision\ArrastreVacantesService;
use App\Services\Admision\IngresanteService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Title('Ingresantes | Admisión UNU')]
class extends Component
{
    use WithPagination;

    #[Url(as: 'proceso', except: '')]
    public string $procesoSeleccionado = '';

    #[Url(as: 'jornada', except: '')]
    public string $examenSeleccionado = '';

    #[Url(except: '')]
    public string $buscar = '';

    #[Url(except: '')]
    public string $condicion = '';

    public string $ingresanteCondicion = '';

    public string $nuevaCondicion = '';

    public string $motivoCondicion = '';

    /** @var array<string, mixed> */
    public array $arrastre = [];

    public function mount(): void
    {
        $this->authorize(Permiso::IngresantesVer->value);

        if ($this->procesoSeleccionado === '') {
            $this->procesoSeleccionado = (string) (Proceso::query()
                ->orderByDesc('anio_pro')
                ->orderByDesc('convocatoria_pro')
                ->value('id_pro') ?? '');
        }

        $this->elegirJornada();
    }

    public function updatedProcesoSeleccionado(): void
    {
        $this->examenSeleccionado = '';
        $this->arrastre = [];
        $this->elegirJornada();
        $this->resetPage();
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedCondicion(): void
    {
        $this->resetPage();
    }

    public function generarPadron(IngresanteService $servicio): void
    {
        $this->authorize(Permiso::IngresantesGenerar->value);
        $examen = $this->examen();

        if ($examen === null) {
            $this->addError('examenSeleccionado', 'Elige una jornada de examen ya resuelta.');

            return;
        }

        try {
            $resumen = $servicio->generar($examen);
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 8000);

            return;
        }

        $this->resetPage();
        Flux::toast(
            text: "Padrón actualizado: {$resumen['creados']} nuevo(s), {$resumen['actualizados']} revisado(s), {$resumen['retirados']} retirado(s).",
            variant: 'success',
            duration: 8000,
        );
    }

    public function previsualizarArrastre(ArrastreVacantesService $servicio): void
    {
        $this->authorize(Permiso::IngresantesArrastrar->value);
        $proceso = $this->proceso();

        if ($proceso === null) {
            return;
        }

        try {
            $this->arrastre = $servicio->calcular($proceso);
        } catch (RuntimeException $error) {
            $this->arrastre = [];
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 10000);
        }
    }

    public function aplicarArrastre(ArrastreVacantesService $servicio): void
    {
        $this->authorize(Permiso::IngresantesArrastrar->value);
        $proceso = $this->proceso();

        if ($proceso === null) {
            return;
        }

        try {
            $this->arrastre = $servicio->aplicar($proceso);
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 10000);

            return;
        }

        Flux::toast(
            text: "Se arrastraron {$this->arrastre['total']} plaza(s). Vuelve a generar los resultados para repartirlas.",
            variant: 'success',
            duration: 10000,
        );
    }

    public function prepararCondicion(int $idIngresante): void
    {
        $this->authorize(Permiso::IngresantesCondicion->value);
        $this->ingresanteCondicion = (string) $idIngresante;
        $this->nuevaCondicion = '';
        $this->motivoCondicion = '';
        $this->resetValidation();
        Flux::modal('perder-condicion')->show();
    }

    public function registrarCondicion(IngresanteService $servicio): void
    {
        $this->authorize(Permiso::IngresantesCondicion->value);
        $this->validate([
            'nuevaCondicion' => ['required', 'string', 'in:'.implode(',', array_column(CondicionIngresante::perdidas(), 'value'))],
            'motivoCondicion' => ['required', 'string', 'min:10', 'max:255'],
        ]);
        $ingresante = $this->ingresante();

        if ($ingresante === null) {
            return;
        }

        try {
            $sustituto = $servicio->perderCondicion(
                $ingresante,
                CondicionIngresante::from($this->nuevaCondicion),
                $this->motivoCondicion,
            );
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 8000);

            return;
        }

        $this->ingresanteCondicion = '';
        $this->nuevaCondicion = '';
        $this->motivoCondicion = '';
        $this->arrastre = [];
        Flux::modal('perder-condicion')->close();
        Flux::toast(
            text: $sustituto === null
                ? 'Se registró la pérdida de la condición de ingresante.'
                : 'Se registró la pérdida y el Art. 93 llamó a '.$sustituto->inscripcion->postulante->nombreCompleto().'.',
            variant: 'success',
            duration: 10000,
        );
    }

    public function restaurar(int $idIngresante, IngresanteService $servicio): void
    {
        $this->authorize(Permiso::IngresantesCondicion->value);
        $this->ingresanteCondicion = (string) $idIngresante;
        $ingresante = $this->ingresante();
        $this->ingresanteCondicion = '';

        if ($ingresante === null) {
            return;
        }

        try {
            $servicio->restaurarCondicion($ingresante);
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 8000);

            return;
        }

        $this->arrastre = [];
        Flux::toast(text: 'El ingresante volvió a estar vigente.', variant: 'success');
    }

    private function elegirJornada(): void
    {
        if ($this->examenSeleccionado !== '' || $this->procesoSeleccionado === '') {
            return;
        }

        $this->examenSeleccionado = (string) (Examen::query()
            ->where('id_pro', $this->procesoSeleccionado)
            ->whereNotNull('resuelto_en_exa')
            ->orderByDesc('fecha_exa')
            ->value('id_exa') ?? '');
    }

    private function proceso(): ?Proceso
    {
        return $this->procesoSeleccionado === ''
            ? null
            : Proceso::find($this->procesoSeleccionado);
    }

    private function examen(): ?Examen
    {
        return $this->examenSeleccionado === ''
            ? null
            : Examen::query()->whereNotNull('resuelto_en_exa')->find($this->examenSeleccionado);
    }

    private function ingresante(): ?Ingresante
    {
        if ($this->ingresanteCondicion === '' || $this->procesoSeleccionado === '') {
            return null;
        }

        return Ingresante::query()
            ->where('id_pro', $this->procesoSeleccionado)
            ->find($this->ingresanteCondicion);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $procesos = Proceso::query()
            ->select(['id_pro', 'codigo_pro', 'anio_pro', 'convocatoria_pro'])
            ->orderByDesc('anio_pro')
            ->orderByDesc('convocatoria_pro')
            ->get();
        $proceso = $this->proceso();
        $examenes = $this->procesoSeleccionado === ''
            ? collect()
            : Examen::query()
                ->select(['id_exa', 'id_pro', 'nombre_exa', 'fecha_exa'])
                ->where('id_pro', $this->procesoSeleccionado)
                ->whereNotNull('resuelto_en_exa')
                ->orderByDesc('fecha_exa')
                ->get();

        if ($proceso === null) {
            return compact('procesos', 'examenes', 'proceso') + [
                'ingresantes' => null,
                'estadisticas' => [],
                'esTercera' => false,
            ];
        }

        $consulta = Ingresante::query()
            ->where('id_pro', $proceso->id_pro)
            ->with([
                'inscripcion.postulante',
                'inscripcion.carrera',
                'inscripcion.modalidad',
                'vacante.modalidad',
                'sustituido.inscripcion.postulante',
                'sustituto.inscripcion.postulante',
            ]);

        if ($this->buscar !== '') {
            $termino = '%'.trim($this->buscar).'%';
            $consulta->whereHas('inscripcion.postulante', fn ($query) => $query
                ->where('numero_documento_pos', 'like', $termino)
                ->orWhere('primer_apellido_pos', 'like', $termino)
                ->orWhere('nombres_pos', 'like', $termino));
        }

        if ($this->condicion !== '') {
            $consulta->where('condicion_ing', $this->condicion);
        }

        $porCondicion = Ingresante::query()
            ->where('id_pro', $proceso->id_pro)
            ->selectRaw('condicion_ing, count(*) as total')
            ->groupBy('condicion_ing')
            ->pluck('total', 'condicion_ing');

        return compact('procesos', 'examenes', 'proceso') + [
            'ingresantes' => $consulta
                ->orderBy('orden_carrera_ing')
                ->orderBy('id_ing')
                ->paginate(25),
            'estadisticas' => [
                'total' => (int) $porCondicion->sum(),
                'vigentes' => (int) ($porCondicion[CondicionIngresante::Vigente->value] ?? 0),
                'perdidas' => (int) $porCondicion->sum() - (int) ($porCondicion[CondicionIngresante::Vigente->value] ?? 0),
                'sustitutos' => Ingresante::where('id_pro', $proceso->id_pro)->whereNotNull('id_sustituido_ing')->count(),
            ],
            'esTercera' => $proceso->convocatoria_pro === Convocatoria::Tercera,
        ];
    }
};
