<?php

namespace App\Services\Admision;

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
    public function documento(Examen $examen, ?int $idCarrera = null, ?int $idVacante = null): DocumentoPdf
    {
        return Pdf::loadView('pdf.resultados.general', $this->datos($examen, $idCarrera, $idVacante))
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

    public function nombreArchivo(Examen $examen, ?Carrera $carrera = null): string
    {
        $base = 'resultados-'.$examen->proceso->codigo_pro.'-'.$examen->nombre_exa;

        return Str::slug($carrera === null ? $base : $base.'-'.$carrera->nombre_corto_car);
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(Examen $examen, ?int $idCarrera = null, ?int $idVacante = null): array
    {
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

        $sedes = $resultados->pluck('postulante.inscripcion.sede')->filter()->unique('id_sed');

        return [
            'examen' => $examen,
            'resultados' => $resultados,
            'esPorCarrera' => $esPorCarrera,
            'tituloListado' => $esPorCarrera ? 'Por carrera profesional' : 'Resultado general',
            'modalidades' => $resultados->pluck('postulante.inscripcion.modalidad.nombre_mod')
                ->filter()
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->implode(' / '),
            'ubicacion' => $sedes->count() === 1 ? $sedes->first()->ubicacionCabecera() : 'UCAYALI',
        ];
    }
}
