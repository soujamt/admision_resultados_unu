<?php

use App\Enums\CondicionIngresante;
use App\Enums\Convocatoria;
use App\Enums\GrupoModalidad;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\Ingresante;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Vacante;
use App\Services\Admision\IngresanteService;
use App\Services\Admision\ResolverResultadosService;
use Tests\Support\PadronDeExamen;

/**
 * Arma una tercera convocatoria con una vacante por modalidad y resuelve el
 * examen, que es el punto de partida de todo lo que miden estos tests.
 *
 * @param  array<string, int>  $plazas  cantidad de vacantes por clave de modalidad
 * @param  list<array{modalidad:string, numero:int, aciertos:?int}>  $postulantes
 * @return array{proceso:Proceso, examen:Examen, vacantes:array<string, Vacante>}
 */
function escenarioDeIngresantes(array $plazas, array $postulantes, ?Proceso $proceso = null): array
{
    $proceso ??= Proceso::factory()->codigo('2027-III')->create(['convocatoria_pro' => Convocatoria::Tercera]);
    $carrera = Carrera::factory()->create();
    $sede = Sede::factory()->create();
    $grupos = [
        'ordinario' => GrupoModalidad::Ordinario,
        'exoneracion' => GrupoModalidad::Exoneracion,
        'reserva' => GrupoModalidad::Reserva,
    ];
    $vacantes = [];

    foreach ($plazas as $clave => $cantidad) {
        $vacantes[$clave] = Vacante::factory()->create([
            'id_pro' => $proceso->id_pro,
            'id_mod' => Modalidad::factory()->create(['grupo_mod' => $grupos[$clave]]),
            'id_car' => $carrera->id_car,
            'id_sed' => $sede->id_sed,
            'cantidad_vac' => $cantidad,
        ]);
    }

    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => false,
    ]);

    foreach ($postulantes as $fila) {
        PadronDeExamen::postulante($examen, $vacantes[$fila['modalidad']], $fila['numero'], $fila['aciertos']);
    }

    app(ResolverResultadosService::class)->resolver($examen);

    return ['proceso' => $proceso, 'examen' => $examen, 'vacantes' => $vacantes];
}

it('genera el padrón de ingresantes desde la jornada resuelta', function () {
    $escenario = escenarioDeIngresantes(['ordinario' => 2], [
        ['modalidad' => 'ordinario', 'numero' => 101, 'aciertos' => 90],
        ['modalidad' => 'ordinario', 'numero' => 102, 'aciertos' => 80],
        ['modalidad' => 'ordinario', 'numero' => 103, 'aciertos' => 40],
    ]);

    $resumen = app(IngresanteService::class)->generar($escenario['examen']);

    expect($resumen['creados'])->toBe(2)
        ->and($resumen['total'])->toBe(2)
        ->and(Ingresante::where('id_pro', $escenario['proceso']->id_pro)->count())->toBe(2)
        ->and(Ingresante::first()->condicion_ing)->toBe(CondicionIngresante::Vigente);
});

it('conserva la condición registrada al volver a generar el padrón', function () {
    $escenario = escenarioDeIngresantes(['ordinario' => 2], [
        ['modalidad' => 'ordinario', 'numero' => 111, 'aciertos' => 90],
        ['modalidad' => 'ordinario', 'numero' => 112, 'aciertos' => 80],
    ]);
    $servicio = app(IngresanteService::class);
    $servicio->generar($escenario['examen']);
    $ingresante = Ingresante::orderBy('id_ing')->first();
    $servicio->perderCondicion($ingresante, CondicionIngresante::SinConstancia, 'No recogió la constancia en plazo.');

    $servicio->generar($escenario['examen']);

    expect($ingresante->refresh()->condicion_ing)->toBe(CondicionIngresante::SinConstancia)
        ->and($ingresante->motivo_ing)->toBe('No recogió la constancia en plazo.');
});

it('llama al inmediato inferior de la misma modalidad cuando no hay matrícula', function () {
    $escenario = escenarioDeIngresantes(['ordinario' => 1, 'exoneracion' => 1], [
        ['modalidad' => 'ordinario', 'numero' => 121, 'aciertos' => 90],
        ['modalidad' => 'ordinario', 'numero' => 122, 'aciertos' => 70],
        ['modalidad' => 'exoneracion', 'numero' => 123, 'aciertos' => 85],
        ['modalidad' => 'exoneracion', 'numero' => 124, 'aciertos' => 60],
    ]);
    $servicio = app(IngresanteService::class);
    $servicio->generar($escenario['examen']);
    $titular = Ingresante::query()
        ->where('id_vac', $escenario['vacantes']['ordinario']->id_vac)
        ->firstOrFail();

    $sustituto = $servicio->perderCondicion($titular, CondicionIngresante::SinMatricula, 'No se matriculó en plazo.');

    expect($sustituto)->not->toBeNull()
        ->and($sustituto->id_sustituido_ing)->toBe($titular->id_ing)
        ->and($sustituto->id_vac)->toBe($escenario['vacantes']['ordinario']->id_vac)
        ->and((float) $sustituto->puntaje_ing)->toBe(70.0)
        ->and($titular->refresh()->condicion_ing)->toBe(CondicionIngresante::SinMatricula);
});

it('busca en el examen ordinario al sustituto de una reserva que no matricula', function () {
    $escenario = escenarioDeIngresantes(['ordinario' => 1, 'reserva' => 1], [
        ['modalidad' => 'ordinario', 'numero' => 131, 'aciertos' => 90],
        ['modalidad' => 'ordinario', 'numero' => 132, 'aciertos' => 75],
        ['modalidad' => 'reserva', 'numero' => 133, 'aciertos' => 88],
        ['modalidad' => 'reserva', 'numero' => 134, 'aciertos' => 80],
    ]);
    $servicio = app(IngresanteService::class);
    $servicio->generar($escenario['examen']);
    $reserva = Ingresante::query()
        ->where('id_vac', $escenario['vacantes']['reserva']->id_vac)
        ->firstOrFail();

    $sustituto = $servicio->perderCondicion($reserva, CondicionIngresante::SinMatricula, 'No se matriculó en plazo.');

    expect($sustituto)->not->toBeNull()
        ->and((float) $sustituto->puntaje_ing)->toBe(75.0)
        ->and($sustituto->inscripcion->id_mod)->toBe($escenario['vacantes']['ordinario']->id_mod);
});

it('no llama a nadie cuando el inmediato inferior no alcanza la nota mínima', function () {
    $escenario = escenarioDeIngresantes(['ordinario' => 1], [
        ['modalidad' => 'ordinario', 'numero' => 141, 'aciertos' => 90],
        ['modalidad' => 'ordinario', 'numero' => 142, 'aciertos' => 30],
    ]);
    $servicio = app(IngresanteService::class);
    $servicio->generar($escenario['examen']);
    $titular = Ingresante::firstOrFail();

    $sustituto = $servicio->perderCondicion($titular, CondicionIngresante::SinMatricula, 'No se matriculó en plazo.');

    expect($sustituto)->toBeNull()
        ->and(Ingresante::count())->toBe(1);
});

it('restaurar la condición retira al sustituto que había entrado', function () {
    $escenario = escenarioDeIngresantes(['ordinario' => 1], [
        ['modalidad' => 'ordinario', 'numero' => 151, 'aciertos' => 90],
        ['modalidad' => 'ordinario', 'numero' => 152, 'aciertos' => 70],
    ]);
    $servicio = app(IngresanteService::class);
    $servicio->generar($escenario['examen']);
    $titular = Ingresante::firstOrFail();
    $servicio->perderCondicion($titular, CondicionIngresante::SinMatricula, 'No se matriculó en plazo.');

    expect(Ingresante::count())->toBe(2);

    $servicio->restaurarCondicion($titular);

    expect(Ingresante::count())->toBe(1)
        ->and($titular->refresh()->condicion_ing)->toBe(CondicionIngresante::Vigente);
});

it('exige resolver la jornada antes de generar el padrón', function () {
    $proceso = Proceso::factory()->codigo('2027-III')->create(['convocatoria_pro' => Convocatoria::Tercera]);
    $examen = Examen::factory()->create(['id_pro' => $proceso->id_pro, 'resuelto_en_exa' => null]);

    app(IngresanteService::class)->generar($examen);
})->throws(RuntimeException::class, 'Genera primero los resultados de la jornada.');
