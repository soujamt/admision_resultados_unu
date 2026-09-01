<?php

namespace App\Http\Controllers\Inscripciones;

use App\Enums\Permiso;
use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Services\Admision\AlmacenFotos;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MostrarFotoController extends Controller
{
    /**
     * Sirve una foto desde el disco privado solo a quien puede ver el padrón.
     * El id viaja codificado para no exponer el correlativo de la ficha.
     */
    public function __invoke(string $inscripcion, AlmacenFotos $fotos): Response
    {
        Gate::authorize(Permiso::InscripcionesVer->value);

        $ficha = Inscripcion::findOrFail(decode_id_or_fail($inscripcion));
        $contenido = $fotos->contenido($ficha);

        abort_if($contenido === null, 404);

        return response($contenido, 200, [
            'Content-Type' => $fotos->tipoMime($ficha) ?? 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
