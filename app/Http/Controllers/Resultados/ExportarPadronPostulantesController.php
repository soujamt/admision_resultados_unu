<?php

namespace App\Http\Controllers\Resultados;

use App\Enums\Permiso;
use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Services\Admision\PadronPostulantesPdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ExportarPadronPostulantesController extends Controller
{
    public function __invoke(Examen $examen, PadronPostulantesPdf $padron): Response
    {
        Gate::authorize(Permiso::ResultadosVer->value);

        return $padron->documento($examen)->download($padron->nombreArchivo($examen).'.pdf');
    }
}
