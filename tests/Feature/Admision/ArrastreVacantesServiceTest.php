<?php

use App\Enums\CondicionIngresante;
use App\Enums\GrupoModalidad;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\Ingresante;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Vacante;
use App\Services\Admision\ArrastreVacantesService;
use App\Services\Admision\IngresanteService;
use App\Services\Admision\ResolverResultadosService;
use Tests\Support\PadronDeExamen;

/**
 * Resuelve una jornada sobre las vacantes dadas y deja hecho el padron de
 * ingresantes, que es contra lo que el arrastre mide las plazas cubiertas.
 *
 * @param  list<array{vacante:Vacante, numero:int, aciertos:int}>  $postulantes
 */
function resolverConPadron(Proceso $proceso, array $postulantes): Examen
{
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => false,
    ]);

    foreach ($postulantes as $fila) {
        PadronDeExamen::postulante($examen, $fila['vacante'], $fila['numero'], $fila['aciertos']);
    }

    app(ResolverResultadosService::class)->resolver($examen);
    app(IngresanteService::class)->generar($examen);

    return $examen;
}

it('arrastra a la tercera convocatoria lo no cubierto y lo liberado en la primera', function () {
    $carrera = Carrera::factory()->create();
    $sede = Sede::factory()->create();
    $ordinario = Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario]);
    $comun = ['id_mod' => $ordinario->id_mod, 'id_car' => $carrera->id_car, 'id_sed' => $sede->id_sed];

    $primera = Proceso::factory()->codigo('2027-I')->create();
    $vacantePrimera = Vacante::factory()->create($comun + ['id_pro' => $primera->id_pro, 'cantidad_vac' => 5]);
    resolverConPadron($primera, [
        ['vacante' => $vacantePrimera, 'numero' => 201, 'aciertos' => 90],
        ['vacante' => $vacantePrimera, 'numero' => 202, 'aciertos' => 80],
    ]);

    $tercera = Proceso::factory()->codigo('2027-III')->create();
    $vacanteTercera = Vacante::factory()->create($comun + ['id_pro' => $tercera->id_pro, 'cantidad_vac' => 10]);

    $servicio = app(ArrastreVacantesService::class);
    $soloArt17 = $servicio->calcular($tercera);

    expect($soloArt17['total'])->toBe(3)
        ->and($soloArt17['lineas'][0]['art17'])->toBe(3)
        ->and($soloArt17['lineas'][0]['art18'])->toBe(0);

    app(IngresanteService::class)->perderCondicion(
        Ingresante::where('id_pro', $primera->id_pro)->firstOrFail(),
        CondicionIngresante::SinConstancia,
        'No recogió la constancia en el plazo del cronograma.',
    );
    $resumen = $servicio->aplicar($tercera);

    expect($resumen['total'])->toBe(4)
        ->and($resumen['lineas'][0]['art17'])->toBe(3)
        ->and($resumen['lineas'][0]['art18'])->toBe(1)
        ->and($vacanteTercera->refresh()->cantidad_arrastre_vac)->toBe(4)
        ->and($vacanteTercera->plazas())->toBe(14)
        ->and($vacanteTercera->motivo_arrastre_vac)->toContain('Art. 17: 3');
});

it('pasa al examen ordinario lo que no cubre la exoneración en la tercera convocatoria', function () {
    $carrera = Carrera::factory()->create();
    $sede = Sede::factory()->create();
    $tercera = Proceso::factory()->codigo('2027-III')->create();
    $comun = ['id_pro' => $tercera->id_pro, 'id_car' => $carrera->id_car, 'id_sed' => $sede->id_sed];
    $ordinaria = Vacante::factory()->create($comun + [
        'id_mod' => Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario]),
        'cantidad_vac' => 2,
    ]);
    $exoneracion = Vacante::factory()->create($comun + [
        'id_mod' => Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Exoneracion, 'codigo_mod' => 'EXO_DEPORTISTA']),
        'cantidad_vac' => 5,
    ]);
    resolverConPadron($tercera, [
        ['vacante' => $exoneracion, 'numero' => 211, 'aciertos' => 90],
        ['vacante' => $ordinaria, 'numero' => 212, 'aciertos' => 85],
        ['vacante' => $ordinaria, 'numero' => 213, 'aciertos' => 70],
    ]);

    $resumen = app(ArrastreVacantesService::class)->aplicar($tercera);

    expect($resumen['total'])->toBe(4)
        ->and($resumen['lineas'][0]['art19'])->toBe(4)
        ->and($resumen['lineas'][0]['vacante']->id_vac)->toBe($ordinaria->id_vac)
        ->and($ordinaria->refresh()->cantidad_arrastre_vac)->toBe(4)
        ->and($exoneracion->refresh()->cantidad_arrastre_vac)->toBe(0);
});

it('recalcula el arrastre entero, así que aplicarlo dos veces no duplica plazas', function () {
    $carrera = Carrera::factory()->create();
    $sede = Sede::factory()->create();
    $ordinario = Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario]);
    $comun = ['id_mod' => $ordinario->id_mod, 'id_car' => $carrera->id_car, 'id_sed' => $sede->id_sed];

    $segunda = Proceso::factory()->codigo('2027-II')->create();
    $vacanteSegunda = Vacante::factory()->create($comun + ['id_pro' => $segunda->id_pro, 'cantidad_vac' => 4]);
    resolverConPadron($segunda, [
        ['vacante' => $vacanteSegunda, 'numero' => 221, 'aciertos' => 90],
    ]);

    $tercera = Proceso::factory()->codigo('2027-III')->create();
    $vacanteTercera = Vacante::factory()->create($comun + ['id_pro' => $tercera->id_pro, 'cantidad_vac' => 6]);
    $servicio = app(ArrastreVacantesService::class);

    $servicio->aplicar($tercera);
    $servicio->aplicar($tercera);

    expect($vacanteTercera->refresh()->cantidad_arrastre_vac)->toBe(3)
        ->and($vacanteTercera->plazas())->toBe(9);
});

it('el resolver reparte también las plazas arrastradas', function () {
    $carrera = Carrera::factory()->create();
    $sede = Sede::factory()->create();
    $tercera = Proceso::factory()->codigo('2027-III')->create();
    $vacante = Vacante::factory()->create([
        'id_pro' => $tercera->id_pro,
        'id_mod' => Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario]),
        'id_car' => $carrera->id_car,
        'id_sed' => $sede->id_sed,
        'cantidad_vac' => 1,
        'cantidad_arrastre_vac' => 2,
    ]);
    $examen = Examen::factory()->create([
        'id_pro' => $tercera->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => false,
    ]);
    PadronDeExamen::postulante($examen, $vacante, 231, 90);
    PadronDeExamen::postulante($examen, $vacante, 232, 80);
    PadronDeExamen::postulante($examen, $vacante, 233, 70);
    PadronDeExamen::postulante($examen, $vacante, 234, 40);

    $resumen = app(ResolverResultadosService::class)->resolver($examen);

    expect($resumen['vacantes'])->toBe(3)
        ->and($resumen['ingresantes'])->toBe(3)
        ->and($resumen['desiertas'])->toBe(0);
});

it('exige el padrón de ingresantes de la convocatoria de origen', function () {
    $carrera = Carrera::factory()->create();
    $sede = Sede::factory()->create();
    $ordinario = Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario]);
    $comun = ['id_mod' => $ordinario->id_mod, 'id_car' => $carrera->id_car, 'id_sed' => $sede->id_sed];

    $primera = Proceso::factory()->codigo('2027-I')->create();
    $vacantePrimera = Vacante::factory()->create($comun + ['id_pro' => $primera->id_pro, 'cantidad_vac' => 5]);
    $examen = Examen::factory()->create([
        'id_pro' => $primera->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => false,
    ]);
    PadronDeExamen::postulante($examen, $vacantePrimera, 241, 90);
    app(ResolverResultadosService::class)->resolver($examen);

    $tercera = Proceso::factory()->codigo('2027-III')->create();
    Vacante::factory()->create($comun + ['id_pro' => $tercera->id_pro, 'cantidad_vac' => 10]);

    app(ArrastreVacantesService::class)->calcular($tercera);
})->throws(RuntimeException::class, 'no tiene padrón de ingresantes generado');

it('solo arrastra hacia la tercera convocatoria', function () {
    $primera = Proceso::factory()->codigo('2027-I')->create();

    app(ArrastreVacantesService::class)->calcular($primera);
})->throws(RuntimeException::class, 'solo corresponde a la tercera convocatoria');
