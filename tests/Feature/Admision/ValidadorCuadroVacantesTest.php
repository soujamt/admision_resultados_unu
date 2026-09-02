<?php

use App\Enums\EstadoRegistro;
use App\Enums\GrupoModalidad;
use App\Models\Carrera;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Vacante;
use App\Services\Admision\ValidadorCuadroVacantes;

/**
 * Crea las tres convocatorias de un anio con una vacante ordinaria por
 * convocatoria y las cantidades que se le pasen.
 *
 * @param  array{1:int, 2:int, 3:int}  $cantidades
 * @return array{carrera:Carrera, sede:Sede, modalidad:Modalidad, procesos:array<int, Proceso>}
 */
function cuadroGeneralDelAnio(array $cantidades, ?Carrera $carrera = null, ?Sede $sede = null): array
{
    $carrera ??= Carrera::factory()->create();
    $sede ??= Sede::factory()->create();
    $modalidad = Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario]);
    $procesos = [];

    foreach (['I', 'II', 'III'] as $indice => $romano) {
        $proceso = Proceso::factory()->codigo('2027-'.$romano)->create();
        $procesos[$indice + 1] = $proceso;
        Vacante::factory()->create([
            'id_pro' => $proceso->id_pro,
            'id_mod' => $modalidad->id_mod,
            'id_car' => $carrera->id_car,
            'id_sed' => $sede->id_sed,
            'cantidad_vac' => $cantidades[$indice + 1],
        ]);
    }

    return ['carrera' => $carrera, 'sede' => $sede, 'modalidad' => $modalidad, 'procesos' => $procesos];
}

it('acepta el cuadro general repartido 25, 25 y 50 por ciento', function () {
    cuadroGeneralDelAnio([1 => 25, 2 => 25, 3 => 50]);

    $revision = app(ValidadorCuadroVacantes::class)->revisar(2027);

    expect($revision['cumple'])->toBeTrue()
        ->and($revision['total'])->toBe(100)
        ->and($revision['completo'])->toBeTrue()
        ->and($revision['art14'][0]['porcentaje'])->toBe(25.0)
        ->and($revision['art14'][2]['porcentaje'])->toBe(50.0);
});

it('observa la convocatoria que se aparta del reparto del Art. 14', function () {
    cuadroGeneralDelAnio([1 => 50, 2 => 25, 3 => 25]);

    $revision = app(ValidadorCuadroVacantes::class)->revisar(2027);
    $articulos = array_column($revision['observaciones'], 'articulo');

    expect($revision['cumple'])->toBeFalse()
        ->and($articulos)->toContain('Art. 14')
        ->and($revision['art14'][0]['cumple'])->toBeFalse()
        ->and($revision['art14'][0]['desvio'])->toBe(25)
        ->and($revision['art14'][1]['cumple'])->toBeTrue()
        ->and($revision['observaciones'][0]['mensaje'])->toContain('Primera convocatoria');
});

it('no juzga el reparto mientras falte configurar una convocatoria', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    Vacante::factory()->create(['id_pro' => $proceso->id_pro, 'cantidad_vac' => 80]);

    $revision = app(ValidadorCuadroVacantes::class)->revisar(2027);

    expect($revision['completo'])->toBeFalse()
        ->and($revision['observaciones'])->toHaveCount(1)
        ->and($revision['observaciones'][0]['mensaje'])->toContain('todavía no está completo')
        ->and($revision['observaciones'][0]['mensaje'])->toContain('Segunda convocatoria');
});

it('observa a la Escuela Profesional que pasa del 30 por ciento al CEPREUNU', function () {
    $escenario = cuadroGeneralDelAnio([1 => 25, 2 => 25, 3 => 50]);
    $cepreunu = Modalidad::factory()->create([
        'grupo_mod' => GrupoModalidad::Exoneracion,
        'codigo_mod' => 'EXO_CEPREUNU',
    ]);
    Vacante::factory()->create([
        'id_pro' => $escenario['procesos'][1]->id_pro,
        'id_mod' => $cepreunu->id_mod,
        'id_car' => $escenario['carrera']->id_car,
        'id_sed' => $escenario['sede']->id_sed,
        'cantidad_vac' => 60,
    ]);

    $revision = app(ValidadorCuadroVacantes::class)->revisar(2027);
    $art16 = $revision['art16'][0];

    expect($art16['cepreunu'])->toBe(60)
        ->and($art16['total'])->toBe(160)
        ->and($art16['porcentaje'])->toBe(37.5)
        ->and($art16['excede'])->toBeTrue()
        ->and(array_column($revision['observaciones'], 'articulo'))->toContain('Art. 16');
});

it('suma la reserva CEPREUNU al mismo cupo del Art. 16', function () {
    $escenario = cuadroGeneralDelAnio([1 => 25, 2 => 25, 3 => 50]);
    $reserva = Modalidad::factory()->create([
        'grupo_mod' => GrupoModalidad::Reserva,
        'codigo_mod' => 'RES_CEPREUNU',
    ]);
    Vacante::factory()->create([
        'id_pro' => $escenario['procesos'][2]->id_pro,
        'id_mod' => $reserva->id_mod,
        'id_car' => $escenario['carrera']->id_car,
        'id_sed' => $escenario['sede']->id_sed,
        'cantidad_vac' => 20,
    ]);

    $revision = app(ValidadorCuadroVacantes::class)->revisar(2027);

    expect($revision['art16'][0]['cepreunu'])->toBe(20)
        ->and($revision['art16'][0]['porcentaje'])->toBe(round(20 / 120 * 100, 2))
        ->and($revision['art16'][0]['excede'])->toBeFalse();
});

it('no cuenta el arrastre de los Arts. 17, 18 y 19 en el reparto del Art. 14', function () {
    $escenario = cuadroGeneralDelAnio([1 => 25, 2 => 25, 3 => 50]);
    Vacante::query()
        ->where('id_pro', $escenario['procesos'][3]->id_pro)
        ->update(['cantidad_arrastre_vac' => 40]);

    $revision = app(ValidadorCuadroVacantes::class)->revisar(2027);

    expect($revision['total'])->toBe(100)
        ->and($revision['art14'][2]['vacantes'])->toBe(50)
        ->and($revision['cumple'])->toBeTrue();
});

it('ignora las vacantes deshabilitadas del cuadro', function () {
    $escenario = cuadroGeneralDelAnio([1 => 25, 2 => 25, 3 => 50]);
    Vacante::factory()->create([
        'id_pro' => $escenario['procesos'][1]->id_pro,
        'id_car' => $escenario['carrera']->id_car,
        'id_sed' => $escenario['sede']->id_sed,
        'cantidad_vac' => 500,
        'estado_vac' => EstadoRegistro::Deshabilitado,
    ]);

    $revision = app(ValidadorCuadroVacantes::class)->revisar(2027);

    expect($revision['total'])->toBe(100)
        ->and($revision['cumple'])->toBeTrue();
});
