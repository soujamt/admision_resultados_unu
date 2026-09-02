<?php

namespace App\Http\Controllers\Resultados;

use App\Enums\Permiso;
use App\Http\Controllers\Controller;
use App\Models\AsignacionExamen;
use App\Models\ExamenAula;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ExportarPadronAulaController extends Controller
{
    public function __invoke(ExamenAula $aulaExamen): Response
    {
        Gate::authorize(Permiso::ResultadosVer->value);
        $aulaExamen->load(['examen.proceso', 'area', 'aula.sede', 'asignaciones.inscripcion.postulante']);
        $asignaciones = $aulaExamen->asignaciones
            ->sortBy(
                fn (AsignacionExamen $asignacion): string => Str::ascii(
                    mb_strtoupper($asignacion->inscripcion->postulante->nombreCompleto()),
                ).'|'.$asignacion->inscripcion->postulante->numero_documento_pos,
                SORT_NATURAL,
            )
            ->values();
        $nombreAula = Str::slug($aulaExamen->aula->nombre_aul);

        return Pdf::loadView('pdf.padron-aula', compact('aulaExamen', 'asignaciones'))->setPaper('a4')->download('padron-aula-'.$nombreAula.'.pdf');
    }
}
