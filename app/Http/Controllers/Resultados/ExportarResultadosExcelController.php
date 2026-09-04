<?php

namespace App\Http\Controllers\Resultados;

use App\Enums\Permiso;
use App\Exports\ResultadosExport;
use App\Http\Controllers\Controller;
use App\Models\Examen;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportarResultadosExcelController extends Controller
{
    public function __invoke(Examen $examen): BinaryFileResponse
    {
        Gate::authorize(Permiso::ResultadosExportar->value);
        $examen->load('proceso');

        if ($examen->resultados()->doesntExist()) {
            abort(404, 'La jornada todavía no tiene resultados generados.');
        }

        $export = new ResultadosExport($examen);

        return Excel::download($export, $export->nombreArchivo());
    }
}
