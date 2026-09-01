<?php

use App\Enums\Permiso;
use App\Livewire\Forms\DistribucionAulaForm;
use App\Livewire\Forms\ExamenForm;
use App\Models\Area;
use App\Models\Aula;
use App\Models\Examen;
use App\Models\Proceso;
use App\Services\Admision\DistribucionAulasService;
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

    /**
     * Un error de validacion se borra en cuanto se corrige el campo que lo
     * provoco. Sin esto el mensaje sobrevive al arreglo: el usuario elige el
     * aula que le faltaba y sigue viendo «Elige el aula que se va a usar» en
     * rojo sobre un campo que ya esta lleno.
     */
    public function updated(string $propiedad): void
    {
        if (str_starts_with($propiedad, 'formAula.')) {
            $this->resetValidation($propiedad);
        }
    }

    /**
     * Al elegir un aula se propone su capacidad como cantidad de postulantes,
     * pero solo si el campo esta vacio o si lo que hay escrito ya no cabe: si
     * el usuario tecleo 33 a proposito, cambiar de aula no se lo puede pisar.
     */
    public function updatedFormAulaAula(): void
    {
        $this->formAula->olvidarAula();

        $maximo = $this->formAula->capacidadMaxima();

        if ($maximo === null) {
            $this->formAula->capacidad = null;

            return;
        }

        if ($this->formAula->capacidad === null || $this->formAula->capacidad > $maximo) {
            $this->formAula->capacidad = $maximo;
            $this->resetValidation('formAula.capacidad');
        }
    }

    public function nuevoExamen(): void
    {
        $this->authorize(Permiso::ResultadosConfigurarAulas->value);

        $this->formExamen->reset();
        $this->resetValidation();

        Flux::modal('examen')->show();
    }

    public function crearExamen(): void
    {
        $this->authorize(Permiso::ResultadosConfigurarAulas->value);

        if ($this->procesoSeleccionado === '' || ! Proceso::whereKey($this->procesoSeleccionado)->exists()) {
            $this->addError('procesoSeleccionado', 'Elige un proceso válido.');

            return;
        }

        $this->formExamen->validate();

        $examen = Examen::create([
            'id_pro' => (int) $this->procesoSeleccionado,
            'nombre_exa' => trim($this->formExamen->nombre),
            'fecha_exa' => blank($this->formExamen->fecha) ? null : $this->formExamen->fecha,
        ]);

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

        $this->formAula->olvidarAula();
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

        if (! $servicio->retirar($examen, $id)) {
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

        $distribucion = $examen === null
            ? collect()
            : $examen->aulas()->with(['aula.sede', 'area'])->orderBy('id_eau')->get();

        $aulasDisponibles = Aula::query()
            ->habilitado()
            ->whereNotIn('id_aul', $distribucion->pluck('id_aul'))
            ->with('sede')
            ->ordenadas()
            ->get();

        return [
            'procesos' => Proceso::query()->orderByDesc('anio_pro')->orderByDesc('convocatoria_pro')->get(),
            'examenes' => $this->procesoSeleccionado === ''
                ? collect()
                : Examen::query()->where('id_pro', $this->procesoSeleccionado)->orderBy('fecha_exa')->get(),
            'examen' => $examen,
            'aulas' => $aulasDisponibles,
            /*
             * El desplegable se reconstruye cuando cambia el juego de aulas
             * disponibles. Sin esta clave Livewire reutiliza el mismo <select>
             * y el navegador conserva la posicion elegida, que tras agregar un
             * aula ya apunta a otra: se ve una seleccionada y el servidor
             * recibe vacio.
             */
            'claveAulas' => md5($aulasDisponibles->pluck('id_aul')->join(',')),
            'areas' => Area::query()->habilitado()->orderBy('numero_are')->get(),
            'areasPorId' => Area::query()->get()->keyBy('id_are'),
            'distribucion' => $distribucion,
            'totales' => $examen === null ? collect() : $servicio->totalesPorArea($examen),
            'motivoParaNoSortear' => $examen === null ? null : $servicio->motivoParaNoSortear($examen),
            'capacidadMaxima' => $this->formAula->capacidadMaxima(),
        ];
    }
};
