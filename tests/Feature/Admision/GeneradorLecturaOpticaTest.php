<?php

use App\Enums\NivelDeExamen;
use App\Models\AsignacionExamen;
use App\Models\Aula;
use App\Models\Examen;
use App\Models\ExamenAula;
use App\Models\ExamenPostulante;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\Postulante;
use App\Models\Proceso;
use App\Models\Vacante;
use App\Services\Admision\GeneradorLecturaOptica;
use App\Services\Admision\ImportadorExamenTxt;
use App\Services\Admision\ResolverResultadosService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Un proceso con su cuadro de vacantes, inscripciones vigentes y una jornada:
 * es todo lo que el generador necesita para escribir la lectura optica.
 */
function jornadaConInscritos(int $inscritos): Examen
{
    $proceso = Proceso::factory()->create();
    $vacante = Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => Modalidad::factory()->conCodigoExterno(2),
        'cantidad_vac' => $inscritos,
        'codigo_externo_vac' => 2561,
    ]);

    Inscripcion::factory()->count($inscritos)->create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => $vacante->id_mod,
        'id_car' => $vacante->id_car,
        'id_sed' => $vacante->id_sed,
    ]);

    return Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => false,
    ]);
}

it('genera una lectura que el importador cruza y el resolutor alcanza a adjudicar', function () {
    Storage::fake('local');
    $examen = jornadaConInscritos(6);

    $lectura = app(GeneradorLecturaOptica::class)->generar(
        examen: $examen,
        nivel: NivelDeExamen::Facil,
        ausentes: 0,
        semilla: 2027,
    );

    $importador = app(ImportadorExamenTxt::class);
    $padron = $importador->importarPadron($examen, $lectura->padron);
    $respuestas = $importador->importarRespuestas($examen, $lectura->respuestas);

    expect($padron->importado)->toBeTrue()
        ->and($padron->observaciones)->toBe([])
        ->and($respuestas->importado)->toBeTrue()
        ->and($respuestas->filas)->toBe(6);

    $resultado = app(ResolverResultadosService::class)->resolver($examen);

    expect($resultado['postulantes'])->toBe(6)
        ->and($resultado['nsp'])->toBe(0)
        ->and($resultado['ingresantes'])->toBeGreaterThan(0);
});

it('deja sin tarjeta óptica a los ausentes y el resolutor los publica como NSP', function () {
    Storage::fake('local');
    $examen = jornadaConInscritos(4);

    $lectura = app(GeneradorLecturaOptica::class)->generar(
        examen: $examen,
        nivel: NivelDeExamen::Normal,
        ausentes: 50,
        semilla: 2027,
    );

    expect($lectura->filasPadron)->toBe(4)
        ->and($lectura->filasRespuestas)->toBe(2);

    $importador = app(ImportadorExamenTxt::class);
    $importador->importarPadron($examen, $lectura->padron);
    $importador->importarRespuestas($examen, $lectura->respuestas);

    expect(app(ResolverResultadosService::class)->resolver($examen)['nsp'])->toBe(2);
});

it('escribe el padrón en Windows-1252 como lo entrega el lector óptico', function () {
    Storage::fake('local');
    $examen = jornadaConInscritos(1);
    Postulante::sole()->update(['primer_apellido_pos' => 'PIÑA', 'segundo_apellido_pos' => 'MUÑOZ']);

    $lectura = app(GeneradorLecturaOptica::class)->generar(examen: $examen, semilla: 2027);

    expect(File::get($lectura->padron))->toContain(mb_convert_encoding('PIÑA MUÑOZ', 'Windows-1252', 'UTF-8'));

    app(ImportadorExamenTxt::class)->importarPadron($examen, $lectura->padron);

    expect(ExamenPostulante::sole()->nombre_exp)->toStartWith('PIÑA MUÑOZ');
});

it('repite los mismos archivos cuando se le da la misma semilla', function () {
    Storage::fake('local');
    $examen = jornadaConInscritos(3);
    $generador = app(GeneradorLecturaOptica::class);

    $primera = $generador->generar(examen: $examen, semilla: 77);
    $respuestas = File::get($primera->respuestas);

    $segunda = $generador->generar(examen: $examen, semilla: 77);

    expect(File::get($segunda->respuestas))->toBe($respuestas);
});

it('agrupa el padrón por aula y asiento, como lo devuelve el lector óptico', function () {
    Storage::fake('local');
    $examen = jornadaConInscritos(4);
    $inscripciones = Inscripcion::orderBy('id_ins')->get();
    $aulas = collect(['001', '002'])->map(fn (string $codigo): ExamenAula => ExamenAula::factory()->create([
        'id_exa' => $examen->id_exa,
        'id_aul' => Aula::factory()->create(['codigo_aul' => 'A-'.$codigo]),
    ]));

    foreach ([[0, 1, 2], [1, 0, 2], [2, 0, 1], [3, 1, 1]] as [$inscripcion, $aula, $asiento]) {
        AsignacionExamen::factory()->create([
            'id_ins' => $inscripciones[$inscripcion]->id_ins,
            'id_eau' => $aulas[$aula]->id_eau,
            'asiento_ase' => $asiento,
        ]);
    }

    $lectura = app(GeneradorLecturaOptica::class)->generar(examen: $examen, semilla: 2027);
    $filas = collect(explode("\r\n", trim(File::get($lectura->padron))))
        ->skip(1)
        ->map(fn (string $fila): array => explode(';', $fila));

    expect($filas->pluck(6)->all())->toBe(['001', '001', '002', '002'])
        ->and($filas->pluck(1)->all())->toBe([
            $inscripciones[2]->postulante->numero_documento_pos,
            $inscripciones[1]->postulante->numero_documento_pos,
            $inscripciones[3]->postulante->numero_documento_pos,
            $inscripciones[0]->postulante->numero_documento_pos,
        ]);
});

it('agrega filas con documentos ajenos al proceso cuando se piden intrusos', function () {
    Storage::fake('local');
    $examen = jornadaConInscritos(2);

    $lectura = app(GeneradorLecturaOptica::class)->generar(
        examen: $examen,
        ausentes: 0,
        intrusos: 2,
        semilla: 2027,
    );

    $padron = app(ImportadorExamenTxt::class)->importarPadron($examen, $lectura->padron);

    expect($lectura->filasPadron)->toBe(4)
        ->and($padron->observaciones)->toHaveCount(2)
        ->and(ExamenPostulante::whereNull('id_ins')->count())->toBe(2);
});
