<?php

namespace App\Http\Controllers\Resultados;

use App\Enums\Permiso;
use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Services\Admision\PadronResultadosPdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Entrega en un solo archivo el juego que el Art. 84 manda publicar: el padron
 * general y uno por cada carrera profesional, todos en estricto orden de
 * merito. Se arma en disco porque son decenas de PDF y no caben en memoria.
 */
class ExportarJuegoResultadosController extends Controller
{
    public function __invoke(Examen $examen, PadronResultadosPdf $padron): BinaryFileResponse
    {
        Gate::authorize(Permiso::ResultadosExportar->value);
        $examen->load('proceso');
        $carreras = $padron->carrerasConResultados($examen);

        if ($carreras->isEmpty()) {
            abort(404, 'La jornada todavía no tiene resultados generados.');
        }

        $ruta = tempnam(sys_get_temp_dir(), 'padron');
        $zip = new ZipArchive;

        if ($zip->open($ruta, ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo preparar el juego de padrones.');
        }

        $zip->addFromString(
            '00-general.pdf',
            $padron->documento($examen)->output(),
        );

        foreach ($carreras->values() as $indice => $carrera) {
            $zip->addFromString(
                sprintf('%02d-%s.pdf', $indice + 1, $padron->nombreArchivo($examen, $carrera)),
                $padron->documento($examen, $carrera->id_car)->output(),
            );
        }

        $zip->close();

        return response()
            ->download($ruta, $padron->nombreArchivo($examen).'.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }
}
