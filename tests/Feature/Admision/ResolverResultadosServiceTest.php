<?php

use App\Enums\Convocatoria;
use App\Enums\EstadoRegistro;
use App\Enums\EstadoResultado;
use App\Enums\GrupoModalidad;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Vacante;
use App\Services\Admision\ResolverResultadosService;
use Tests\Support\PadronDeExamen;

it('genera órdenes, respeta el mínimo, publica NSP y admite empates en la última vacante', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $modalidad = Modalidad::factory()->create();
    $carrera = Carrera::factory()->create();
    $sede = Sede::factory()->create();
    $vacante = Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => $modalidad->id_mod,
        'id_car' => $carrera->id_car,
        'id_sed' => $sede->id_sed,
        'cantidad_vac' => 2,
    ]);
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => false,
    ]);
    PadronDeExamen::postulante($examen, $vacante, 1, 80);
    PadronDeExamen::postulante($examen, $vacante, 2, 70);
    PadronDeExamen::postulante($examen, $vacante, 3, 70);
    PadronDeExamen::postulante($examen, $vacante, 4, 40);
    PadronDeExamen::postulante($examen, $vacante, 5, null);

    $resumen = app(ResolverResultadosService::class)->resolver($examen);
    $resultados = $examen->resultados()->orderBy('id_exp')->get();

    expect($resultados->pluck('estado_res')->all())->toBe([
        EstadoResultado::Ingreso,
        EstadoResultado::Ingreso,
        EstadoResultado::Ingreso,
        EstadoResultado::NoIngreso,
        EstadoResultado::Nsp,
    ])->and($resultados->pluck('orden_general_res')->all())->toBe([1, 2, 2, 4, null])
        ->and($resumen['ingresantes'])->toBe(3)
        ->and($resumen['desiertas'])->toBe(0);
});

it('aplica el factor de dificultad y detecta el examen complementario en tercera convocatoria', function () {
    $proceso = Proceso::factory()->codigo('2027-III')->create(['convocatoria_pro' => Convocatoria::Tercera]);
    $vacante = Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'cantidad_vac' => 5,
    ]);
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'umbral_factor_dificultad_exa' => 40,
        'aplicar_factor_dificultad_exa' => true,
    ]);
    $postulante = PadronDeExamen::postulante($examen, $vacante, 11, 40);
    PadronDeExamen::postulante($examen, $vacante, 12, 30);

    $resumen = app(ResolverResultadosService::class)->resolver($examen);
    $resultado = $postulante->resultado;

    expect((float) $resultado->factor_dificultad_res)->toBe(1.6)
        ->and((float) $resultado->puntaje_res)->toBe(64.0)
        ->and($resultado->estado_res)->toBe(EstadoResultado::Ingreso)
        ->and($resumen['desiertas'])->toBe(4)
        ->and($resumen['porcentaje_desiertas'])->toBe(80.0)
        ->and($resumen['requiere_examen_complementario'])->toBeTrue();
});

it('comparte un solo factor de dificultad entre todas las modalidades de la carrera', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $carrera = Carrera::factory()->create();
    $sede = Sede::factory()->create();
    $comun = ['id_pro' => $proceso->id_pro, 'id_car' => $carrera->id_car, 'id_sed' => $sede->id_sed, 'cantidad_vac' => 5];
    $ordinaria = Vacante::factory()->create($comun + [
        'id_mod' => Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario]),
    ]);
    $reserva = Vacante::factory()->create($comun + [
        'id_mod' => Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Reserva]),
    ]);
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => true,
    ]);
    $mejor = PadronDeExamen::postulante($examen, $ordinaria, 21, 40);
    $peor = PadronDeExamen::postulante($examen, $reserva, 22, 20);

    app(ResolverResultadosService::class)->resolver($examen);

    expect((float) $mejor->resultado->factor_dificultad_res)->toBe(1.6)
        ->and((float) $peor->resultado->factor_dificultad_res)->toBe(1.6)
        ->and((float) $peor->resultado->puntaje_res)->toBe(32.0);
});

it('toma la nota mínima del Art. 81 de la carrera profesional', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $carrera = Carrera::factory()->create(['puntaje_minimo_car' => 60]);
    $vacante = Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_car' => $carrera->id_car,
        'cantidad_vac' => 5,
    ]);
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => false,
    ]);
    $bajo = PadronDeExamen::postulante($examen, $vacante, 31, 55);
    $alto = PadronDeExamen::postulante($examen, $vacante, 32, 65);

    app(ResolverResultadosService::class)->resolver($examen);

    expect($bajo->resultado->estado_res)->toBe(EstadoResultado::NoIngreso)
        ->and((float) $bajo->resultado->puntaje_minimo_res)->toBe(60.0)
        ->and($bajo->resultado->motivo_res)->toContain('Art. 81')
        ->and($alto->resultado->estado_res)->toBe(EstadoResultado::Ingreso);
});

it('deja fuera del concurso al postulante anulado y a la vacante deshabilitada', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $vacante = Vacante::factory()->create(['id_pro' => $proceso->id_pro, 'cantidad_vac' => 1]);
    Vacante::factory()->create(['id_pro' => $proceso->id_pro, 'cantidad_vac' => 9, 'estado_vac' => EstadoRegistro::Deshabilitado]);
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => false,
    ]);
    $anulado = PadronDeExamen::postulante($examen, $vacante, 41, 90);
    $anulado->update(['anulado_en_exp' => now(), 'motivo_anulacion_exp' => 'Suplantación, acta CCA 12.']);
    $limpio = PadronDeExamen::postulante($examen, $vacante, 42, 70);

    $resumen = app(ResolverResultadosService::class)->resolver($examen);

    expect($anulado->resultado->estado_res)->toBe(EstadoResultado::Anulado)
        ->and($anulado->resultado->puntaje_res)->toBeNull()
        ->and($anulado->resultado->orden_general_res)->toBeNull()
        ->and($anulado->resultado->motivo_res)->toBe('Suplantación, acta CCA 12.')
        ->and($limpio->resultado->estado_res)->toBe(EstadoResultado::Ingreso)
        ->and($resumen['vacantes'])->toBe(1)
        ->and($resumen['anulados'])->toBe(1);
});

it('pasa al examen ordinario a quien no logra vacante por exoneración, salvo el CEPREUNU', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $carrera = Carrera::factory()->create();
    $sede = Sede::factory()->create();
    $comun = ['id_pro' => $proceso->id_pro, 'id_car' => $carrera->id_car, 'id_sed' => $sede->id_sed];
    $ordinaria = Vacante::factory()->create($comun + [
        'id_mod' => Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario, 'codigo_mod' => 'ORDINARIO']),
        'cantidad_vac' => 2,
    ]);
    $exoneracion = Vacante::factory()->create($comun + [
        'id_mod' => Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Exoneracion, 'codigo_mod' => 'EXO_DEPORTISTA']),
        'cantidad_vac' => 1,
    ]);
    $cepreunu = Vacante::factory()->create($comun + [
        'id_mod' => Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Exoneracion, 'codigo_mod' => 'EXO_CEPREUNU']),
        'cantidad_vac' => 1,
    ]);
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => false,
    ]);
    PadronDeExamen::postulante($examen, $exoneracion, 51, 90);
    $repescado = PadronDeExamen::postulante($examen, $exoneracion, 52, 80);
    $ordinarioDentro = PadronDeExamen::postulante($examen, $ordinaria, 53, 70);
    $ordinarioFuera = PadronDeExamen::postulante($examen, $ordinaria, 54, 60);
    PadronDeExamen::postulante($examen, $cepreunu, 55, 85);
    $cepreunuFuera = PadronDeExamen::postulante($examen, $cepreunu, 56, 75);

    $resumen = app(ResolverResultadosService::class)->resolver($examen);

    expect($repescado->resultado->estado_res)->toBe(EstadoResultado::Ingreso)
        ->and($repescado->resultado->repesca_res)->toBeTrue()
        ->and($repescado->resultado->id_vac)->toBe($ordinaria->id_vac)
        ->and($repescado->resultado->motivo_res)->toContain('Art. 23')
        ->and($ordinarioDentro->resultado->estado_res)->toBe(EstadoResultado::Ingreso)
        ->and($ordinarioFuera->resultado->estado_res)->toBe(EstadoResultado::NoIngreso)
        ->and($cepreunuFuera->resultado->estado_res)->toBe(EstadoResultado::NoIngreso)
        ->and($cepreunuFuera->resultado->repesca_res)->toBeFalse()
        ->and($resumen['repescados'])->toBe(1);
});
