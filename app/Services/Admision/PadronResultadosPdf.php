<?php

namespace App\Services\Admision;

use App\Enums\OrdenPadronResultados;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\Resultado;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Arma el padron de resultados que el Art. 84 manda publicar en estricto orden
 * de merito, de forma general y por carrera profesional.
 *
 * El formato es el que ya publica la Direccion de Admision: cabecera, listado y
 * nada mas. La carrera va en su columna de cada fila, no en el titulo, y el
 * estado de cada postulante cierra la linea; no se imprime resumen ni linea de
 * corte entre la cabecera y los resultados.
 */
class PadronResultadosPdf
{
    public function documento(
        Examen $examen,
        ?int $idCarrera = null,
        ?int $idVacante = null,
        OrdenPadronResultados $orden = OrdenPadronResultados::PorCarrera,
    ): DocumentoPdf {
        return Pdf::loadView('pdf.resultados.general', $this->datos($examen, $idCarrera, $idVacante, $orden))
            ->setPaper('a4');
    }

    /**
     * Carreras con resultados en la jornada, para el juego completo del Art. 84.
     *
     * @return Collection<int, Carrera>
     */
    public function carrerasConResultados(Examen $examen): Collection
    {
        return Carrera::query()
            ->whereIn('id_car', Resultado::query()
                ->where('tbl_resultado.id_exa', $examen->id_exa)
                ->join('tbl_examen_postulante', 'tbl_examen_postulante.id_exp', '=', 'tbl_resultado.id_exp')
                ->join('tbl_inscripcion', 'tbl_inscripcion.id_ins', '=', 'tbl_examen_postulante.id_ins')
                ->select('tbl_inscripcion.id_car'))
            ->orderBy('nombre_car')
            ->get();
    }

    public function nombreArchivo(
        Examen $examen,
        ?Carrera $carrera = null,
        OrdenPadronResultados $orden = OrdenPadronResultados::PorCarrera,
    ): string {
        $base = 'resultados-'.$examen->proceso->codigo_pro.'-'.$examen->nombre_exa;

        return Str::slug($carrera === null
            ? $base.$orden->sufijoArchivo()
            : $base.'-'.$carrera->nombre_corto_car);
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(
        Examen $examen,
        ?int $idCarrera = null,
        ?int $idVacante = null,
        OrdenPadronResultados $orden = OrdenPadronResultados::PorCarrera,
    ): array {
        $examen->loadMissing('proceso');
        $consulta = Resultado::query()
            ->where('id_exa', $examen->id_exa)
            ->with([
                'postulante.inscripcion.carrera',
                'postulante.inscripcion.modalidad',
                'postulante.inscripcion.sede',
                'vacante.modalidad',
            ]);

        if ($idVacante !== null) {
            $consulta->where('id_vac', $idVacante);
        }

        if ($idCarrera !== null) {
            $consulta->whereHas('postulante.inscripcion', fn ($query) => $query->where('id_car', $idCarrera));
        }

        $esPorCarrera = $idVacante !== null || $idCarrera !== null;
        $columnaOrden = $esPorCarrera ? 'orden_carrera_res' : 'orden_general_res';
        $resultados = $consulta
            ->orderByRaw($columnaOrden.' is null')
            ->orderBy($columnaOrden)
            ->orderBy('orden_general_res')
            ->orderBy('id_res')
            ->get();

        if ($resultados->isEmpty()) {
            throw new RuntimeException('La jornada todavía no tiene resultados generados.');
        }

        /*
         * El alfabetico se reordena en memoria; el de merito ya sale ordenado
         * de la consulta, y el filtrado por carrera respeta su orden de merito
         * dentro de la carrera.
         */
        $listado = ! $esPorCarrera && $orden === OrdenPadronResultados::Alfabetico
            ? $this->alfabeticamente($resultados)
            : $resultados;
        $seccion = $this->seccion($listado);

        return [
            'examen' => $examen,
            'resultados' => $listado,
            'esPorCarrera' => $esPorCarrera,
            'tituloListado' => $esPorCarrera
                ? OrdenPadronResultados::PorCarrera->titulo()
                : $orden->titulo(),
            'modalidades' => $seccion['modalidades'],
            'ubicacion' => $seccion['ubicacion'],
            'secciones' => ! $esPorCarrera && $orden->agrupaPorCarrera()
                ? $this->seccionesPorCarrera($listado)
                : collect([$seccion]),
        ];
    }

    /**
     * Orden alfabetico real: `Str::ascii` es lo que evita que un apellido con
     * tilde caiga al final, porque comparado byte a byte la «Á» va despues de
     * la «B». El documento del examen solo desempata homonimos.
     *
     * @param  Collection<int, Resultado>  $resultados
     * @return Collection<int, Resultado>
     */
    private function alfabeticamente(Collection $resultados): Collection
    {
        return $resultados
            ->sortBy(
                fn (Resultado $resultado): string => Str::ascii(
                    mb_strtoupper((string) $resultado->postulante->nombre_exp),
                ).'|'.$resultado->postulante->documento_exp,
                SORT_NATURAL,
            )
            ->values();
    }

    /**
     * Cada seccion arrastra su propia cabecera, porque una pagina por carrera
     * puede tener modalidades y sede distintas al resto del padron.
     *
     * @param  Collection<int, Resultado>  $resultados
     * @return array{resultados: Collection<int, Resultado>, modalidades: string, ubicacion: string}
     */
    private function seccion(Collection $resultados): array
    {
        $sedes = $resultados->pluck('postulante.inscripcion.sede')->filter()->unique('id_sed');

        return [
            'resultados' => $resultados->values(),
            'modalidades' => $resultados->pluck('postulante.inscripcion.modalidad.nombre_mod')
                ->filter()
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->implode(' / '),
            'ubicacion' => $sedes->count() === 1 ? $sedes->first()->ubicacionCabecera() : 'UCAYALI',
        ];
    }

    /**
     * @param  Collection<int, Resultado>  $resultados
     * @return Collection<int, array{resultados: Collection<int, Resultado>, modalidades: string, ubicacion: string}>
     */
    private function seccionesPorCarrera(Collection $resultados): Collection
    {
        return $resultados
            ->groupBy('postulante.inscripcion.id_car')
            ->map(fn (Collection $delaCarrera): array => $this->seccion(
                $delaCarrera->sortBy(fn (Resultado $resultado): string => sprintf(
                    '%010d-%010d-%010d',
                    $resultado->orden_carrera_res ?? PHP_INT_MAX,
                    $resultado->orden_general_res ?? PHP_INT_MAX,
                    $resultado->id_res,
                )),
            ))
            ->sortBy(fn (array $seccion) => $seccion['resultados']->first()->postulante->inscripcion->carrera->nombre_car, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }
}
