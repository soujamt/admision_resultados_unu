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
    $padron = $importador->importarPadron($examen, Storage::disk('local')->path('padron.txt'));
    $respuestas = $importador->importarRespuestas($examen, Storage::disk('local')->path('respuestas.txt'));

    expect($padron->filas)->toBe(1)
        ->and($padron->importado)->toBeTrue()
        ->and($respuestas->filas)->toBe(1)
        ->and($respuestas->importado)->toBeTrue();

    $postulante = ExamenPostulante::where('id_exa', $examen->id_exa)->sole();
    $respuesta = ExamenRespuesta::where('id_exp', $postulante->id_exp)->sole();

    expect($postulante->id_ins)->toBe($inscripcion->id_ins)
        ->and($respuesta->aciertos_exr)->toBe(23)
        ->and($respuesta->respuestas_exr)->toHaveCount(100);
});

it('convierte el padrón windows 1252 y reporta documentos que no cruzan', function () {
    Storage::fake('local');
    $examen = Examen::factory()->create();
    $contenido = "DARACOD;COD POSTULANTE;APELLDOS Y NOMBRES;CARRERAS;MODALIDAD;MOD EXTRA;AULA;\n3894;87654321;PIÑA LÓPEZ, ANA;INGAA;OR;;009;\n";
    Storage::disk('local')->put('padron-ansi.txt', mb_convert_encoding($contenido, 'Windows-1252', 'UTF-8'));

    $resumen = app(ImportadorExamenTxt::class)->importarPadron(
        $examen,
        Storage::disk('local')->path('padron-ansi.txt'),
    );

    expect($resumen->importado)->toBeTrue()
        ->and($resumen->observaciones)->toHaveCount(1)
        ->and(ExamenPostulante::sole()->nombre_exp)->toBe('PIÑA LÓPEZ, ANA')
        ->and(ExamenPostulante::sole()->id_ins)->toBeNull();
});

it('no altera las respuestas si una cartilla del archivo no existe en el padrón', function () {
    Storage::fake('local');
    $postulante = ExamenPostulante::factory()->create();
    ExamenRespuesta::factory()->create(['id_exp' => $postulante->id_exp, 'aciertos_exr' => 75]);
    $fila = 'CARTILLA-AJENA;23;23;23;77;0;0;'.implode(';', array_fill(0, 100, 'A')).";\n";
    Storage::disk('local')->put('respuestas-invalidas.txt', "CABECERA\n{$fila}");

    $resumen = app(ImportadorExamenTxt::class)->importarRespuestas(
        $postulante->examen,
        Storage::disk('local')->path('respuestas-invalidas.txt'),
    );

    expect($resumen->importado)->toBeFalse()
        ->and($resumen->observaciones[0])->toContain('no existe en el padrón')
        ->and($postulante->respuesta()->value('aciertos_exr'))->toBe(75);
});

it('lee aciertos, errores, blancos y dobles cada uno de su columna del TXT', function () {
    Storage::fake('local');
    $inscripcion = Inscripcion::factory()->create();
    $examen = Examen::create(['id_pro' => $inscripcion->id_pro, 'nombre_exa' => 'Examen de prueba']);

    Storage::disk('local')->put('padron.txt', "DARACOD;COD POSTULANTE;APELLDOS Y NOMBRES;CARRERAS;MODALIDAD;MOD EXTRA;AULA;\n3894;{$inscripcion->postulante->numero_documento_pos};POSTULANTE DE PRUEBA;INGAA;OR;;009;\n");

    /* Cuatro valores distintos: si se permutan las columnas, falla. */
    Storage::disk('local')->put('respuestas.txt', 'DARACOD;DIRECTA;TRANSFORMADA;ACIERTOS;ERRORES;BLANCOS;DOBLES;RESPUESTAS;'."\n".'3894;60,00000;12,00000;60;20;15;5;'.implode(';', array_fill(0, 100, 'A')).";\n");

    $importador = app(ImportadorExamenTxt::class);
    $importador->importarPadron($examen, Storage::disk('local')->path('padron.txt'));
    $importador->importarRespuestas($examen, Storage::disk('local')->path('respuestas.txt'));

    $respuesta = ExamenRespuesta::sole();

    expect($respuesta->aciertos_exr)->toBe(60)
        ->and($respuesta->errores_exr)->toBe(20)
        ->and($respuesta->blancos_exr)->toBe(15)
        ->and($respuesta->dobles_exr)->toBe(5);
});
