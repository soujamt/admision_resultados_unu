<?php

namespace App\Http\Controllers\Resultados;

use App\Enums\Permiso;
use App\Http\Controllers\Controller;
use App\Models\ExamenAula;
use App\Services\Admision\ListaAsistenciaPdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ExportarListaAsistenciaController extends Controller
{
    public function __invoke(ExamenAula $aulaExamen, ListaAsistenciaPdf $lista): Response
    {
        Gate::authorize(Permiso::ResultadosVer->value);

        return $lista->documento($aulaExamen)->download($lista->nombreArchivo($aulaExamen).'.pdf');
    }
}
