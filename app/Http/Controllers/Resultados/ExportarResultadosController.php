<?php

namespace App\Http\Controllers\Resultados;

use App\Enums\Permiso;
use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Examen;
use App\Services\Admision\PadronResultadosPdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class ExportarResultadosController extends Controller
{
    public function __invoke(Request $request, Examen $examen, PadronResultadosPdf $padron): Response
    {
        Gate::authorize(Permiso::ResultadosExportar->value);

        $idCarrera = $request->filled('carrera') ? $request->integer('carrera') : null;
        $idVacante = $request->filled('vacante') ? $request->integer('vacante') : null;

        try {
            $documento = $padron->documento($examen, $idCarrera, $idVacante);
        } catch (RuntimeException $error) {
            abort(404, $error->getMessage());
        }

        $carrera = $idCarrera === null ? null : Carrera::find($idCarrera);

        return $documento->download($padron->nombreArchivo($examen, $carrera).'.pdf');
    }
}
