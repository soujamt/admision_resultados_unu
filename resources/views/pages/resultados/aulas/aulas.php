<?php

use App\Enums\Permiso;
use App\Livewire\Forms\DistribucionAulaForm;
use App\Livewire\Forms\ExamenForm;
use App\Models\Area;
use App\Models\Aula;
use App\Models\Examen;
use App\Models\Proceso;
use App\Services\Admision\DistribucionAulasService;
use App\Services\Admision\ExamenService;
use App\Services\Admision\SorteadorAulasService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Title('Examen y aulas | Admisión UNU')]
class extends Component
{
    public ExamenForm $formExamen;

    public DistribucionAulaForm $formAula;

    #[Url(as: 'proceso', except: '')]
    public string $procesoSeleccionado = '';

    #[Url(as: 'jornada', except: '')]
    public string $examenSeleccionado = '';

    /**
     * La jornada se consulta varias veces por peticion (en la accion y otra vez
     * al pintar), asi que se resuelve una sola vez.
     */
    private ?Examen $examenResuelto = null;

    private bool $examenBuscado = false;

    public function mount(): void
    {
        $this->authorize(Permiso::ResultadosVer->value);

        if ($this->procesoSeleccionado === '') {
            $this->procesoSeleccionado = (string) (Proceso::query()
                ->habilitado()
                ->orderByDesc('anio_pro')
                ->orderByDesc('convocatoria_pro')
                ->value('id_pro') ?? '');
        }
    }

    public function updatedProcesoSeleccionado(): void
    {
        $this->examenSeleccionado = '';
        $this->olvidarExamen();
        $this->formAula->limpiar();
    }

    public function updatedExamenSeleccionado(): void
    {
        $this->olvidarExamen();
        $this->formAula->limpiar();
        $this->resetValidation();
    }

    public function updated(string $propiedad): void
    {
        if (str_starts_with($propiedad, 'formAula.')) {
            $this->resetValidation($propiedad);
        }
    }

    public function nuevoExamen(): void
    {
        $this->authorize(Permiso::ResultadosConfigurarAulas->value);

        $this->formExamen->reset();
        $this->resetValidation();

        Flux::modal('examen')->show();
    }

    public function crearExamen(ExamenService $servicio): void
    {
        $this->authorize(Permiso::ResultadosConfigurarAulas->value);

        $proceso = $this->procesoSeleccionado === '' ? null : Proceso::find($this->procesoSeleccionado);

        if ($proceso === null) {
            $this->addError('procesoSeleccionado', 'Elige un proceso válido.');

            return;
        }

        $this->formExamen->validate();

        try {
            $examen = $servicio->crear($proceso, $this->formExamen->datos());
        } catch (RuntimeException $error) {
            $this->addError('formExamen.nombre', $error->getMessage());

            return;
        }

        $this->examenSeleccionado = (string) $examen->id_exa;
        $this->olvidarExamen();
        $this->formExamen->reset();

        Flux::modal('examen')->close();
        Flux::toast(text: 'La jornada de examen fue creada.', variant: 'success');
    }

    public function agregarAula(DistribucionAulasService $servicio): void
    {
        $this->authorize(Permiso::ResultadosConfigurarAulas->value);

        $examen = $this->examen();

        if ($examen === null) {
            $this->addError('examenSeleccionado', 'Elige una jornada de examen.');

            return;
        }

        $this->formAula->validate();

        /*
         * Se comprueba antes de guardar para poder senalar el campo del aula.
         * El servicio vuelve a comprobarlo: es el que protege la regla cuando
         * la distribucion se arma desde el comando o desde otra pantalla.
         */
        if ($servicio->yaAsignada($examen, (int) $this->formAula->aula)) {
            $this->addError('formAula.aula', 'Esa aula ya está en la distribución de esta jornada.');

            return;
        }

        try {
            $servicio->agregar($examen, $this->formAula->datos());
        } catch (RuntimeException $error) {
            $this->addError('formAula.capacidad', $error->getMessage());

            return;
        }

        $this->formAula->limpiar();

        Flux::toast(text: 'El aula fue incorporada a la distribución.', variant: 'success');
    }

    public function retirarAula(int $id, DistribucionAulasService $servicio): void
    {
        $this->authorize(Permiso::ResultadosConfigurarAulas->value);

        $examen = $this->examen();

        if ($examen === null) {
            return;
        }

        try {
            if (! $servicio->retirar($examen, $id)) {
                return;
            }
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 8000);

            return;
        }

        /* El aula vuelve al desplegable, asi que el formulario se limpia. */
        $this->formAula->limpiar();

        Flux::toast(text: 'El aula fue retirada de la distribución.', variant: 'success');
    }

    public function sortear(SorteadorAulasService $sorteador, DistribucionAulasService $distribucion): void
    {
        $this->authorize(Permiso::ResultadosSortearAulas->value);

        $examen = $this->examen();

        if ($examen === null) {
            return;
        }

        try {
            $total = $sorteador->sortear($examen, $distribucion);
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 8000);

            return;
        }

        Flux::toast(text: "Se asignaron {$total} postulantes a su aula y asiento.", variant: 'success');
    }

    private function examen(): ?Examen
    {
        if (! $this->examenBuscado) {
            $this->examenResuelto = $this->examenSeleccionado === ''
                ? null
                : Examen::find($this->examenSeleccionado);
            $this->examenBuscado = true;
        }

        return $this->examenResuelto;
    }

    private function olvidarExamen(): void
    {
        $this->examenResuelto = null;
        $this->examenBuscado = false;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(DistribucionAulasService $servicio): array
    {
        $examen = $this->examen();
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
                ->orderBy('fecha_exa')
                ->orderBy('id_exa')
                ->get();

        if ($examen === null) {
            return [
                'procesos' => $procesos,
                'examenes' => $examenes,
                'examen' => null,
                'aulas' => collect(),
                'aulasAsignadas' => [],
                'areas' => collect(),
                'areasPorId' => collect(),
                'distribucion' => collect(),
                'totales' => collect(),
                'motivoParaNoSortear' => null,
            ];
        }

        $distribucion = $examen->aulas()
            ->with(['aula.sede', 'area'])
            ->orderBy('id_eau')
            ->get();
        $aulas = Aula::query()->habilitado()->with('sede')->ordenadas()->get();
        $aulasAsignadas = array_fill_keys($distribucion->pluck('id_aul')->all(), true);
        $areasPorId = Area::query()->orderBy('numero_are')->get()->keyBy('id_are');
        $areas = $areasPorId
            ->filter(fn (Area $area): bool => $area->estaHabilitado())
            ->values();
        $totales = $servicio->totalesPorArea($examen);

        return [
            'procesos' => $procesos,
            'examenes' => $examenes,
            'examen' => $examen,
            'aulas' => $aulas,
            'aulasAsignadas' => $aulasAsignadas,
            'areas' => $areas,
            'areasPorId' => $areasPorId,
            'distribucion' => $distribucion,
            'totales' => $totales,
            'motivoParaNoSortear' => $servicio->motivoParaNoSortear($examen, $totales, $areasPorId),
        ];
    }
};
