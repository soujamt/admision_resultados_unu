<?php

use App\Models\Examen;
use App\Models\ExamenPostulante;
use App\Models\ExamenRespuesta;
use App\Models\Inscripcion;
use App\Services\Admision\ImportadorExamenTxt;
use Illuminate\Support\Facades\Storage;

it('importa y cruza el padrón con las respuestas del lector óptico', function () {
    Storage::fake('local');
    $inscripcion = Inscripcion::factory()->create();
    $examen = Examen::create(['id_pro' => $inscripcion->id_pro, 'nombre_exa' => 'Examen de prueba']);

    Storage::disk('local')->put('padron.txt', "DARACOD;COD POSTULANTE;APELLDOS Y NOMBRES;CARRERAS;MODALIDAD;MOD EXTRA;AULA;\n3894;{$inscripcion->postulante->numero_documento_pos};POSTULANTE DE PRUEBA;INGAA;OR;;009;\n");
    Storage::disk('local')->put('respuestas.txt', 'DARACOD;DIRECTA;TRANSFORMADA;ACIERTOS;ERRORES;BLANCOS;DOBLES;RESPUESTAS;'."\n".'3894;23,00000;5,60976;23;77;0;0;'.implode(';', array_fill(0, 100, 'A')).";\n");

    $importador = app(ImportadorExamenTxt::class);
    expect($importador->importarPadron($examen, Storage::disk('local')->path('padron.txt')))->toBe(1)
        ->and($importador->importarRespuestas($examen, Storage::disk('local')->path('respuestas.txt')))->toBe(1);

    $postulante = ExamenPostulante::where('id_exa', $examen->id_exa)->sole();
    $respuesta = ExamenRespuesta::where('id_exp', $postulante->id_exp)->sole();

    expect($postulante->id_ins)->toBe($inscripcion->id_ins)
        ->and($respuesta->aciertos_exr)->toBe(23)
        ->and($respuesta->respuestas_exr)->toHaveCount(100);
});
