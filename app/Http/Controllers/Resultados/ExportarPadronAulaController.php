<?php

namespace App\Http\Controllers\Resultados;

use App\Enums\Permiso;
use App\Http\Controllers\Controller;
use App\Models\ExamenAula;
use App\Services\Admision\PadronAulaPdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ExportarPadronAulaController extends Controller
{
    public function __invoke(ExamenAula $aulaExamen, PadronAulaPdf $padron): Response
    {
        Gate::authorize(Permiso::ResultadosVer->value);

        return $padron->documento($aulaExamen)->download($padron->nombreArchivo($aulaExamen).'.pdf');
    }
}
