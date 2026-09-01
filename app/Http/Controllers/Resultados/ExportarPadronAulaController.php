<?php

namespace App\Http\Controllers\Resultados;

use App\Enums\Permiso;
use App\Http\Controllers\Controller;
use App\Models\ExamenAula;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ExportarPadronAulaController extends Controller
{
    public function __invoke(ExamenAula $aulaExamen): Response
    {
        Gate::authorize(Permiso::ResultadosVer->value);
        $aulaExamen->load(['examen.proceso', 'area', 'aula.sede', 'asignaciones.postulante.inscripcion.postulante']);
        $asignaciones = $aulaExamen->asignaciones->sortBy('asiento_ase');

        return Pdf::loadView('pdf.padron-aula', compact('aulaExamen', 'asignaciones'))->setPaper('a4')->download('padron-aula-'.$aulaExamen->id_eau.'.pdf');
    }
}
