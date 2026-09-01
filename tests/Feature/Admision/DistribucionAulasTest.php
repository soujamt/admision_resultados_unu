<?php

use App\Models\Area;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\ExamenAula;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Services\Admision\DistribucionAulasService;

it('no permite asignar mas postulantes que carpetas tiene el aula', function () {
    $examen = Examen::create(['id_pro' => Proceso::factory()->create()->id_pro, 'nombre_exa' => 'Jornada 1']);
    $aula = Aula::factory()->create(['capacidad_aul' => 40]);
    $area = Area::factory()->create();

    expect(fn () => app(DistribucionAulasService::class)->guardar($examen, [[
        'id_aul' => $aula->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 41,
    ]]))->toThrow(RuntimeException::class);
});

/*
 * El reglamento no fija un maximo por aula: el reparto real de 2027-I pone 42
 * postulantes en un aula de 50 carpetas porque es el remanente de su area.
 */
it('permite pasar de cuarenta cuando el aula tiene carpetas de sobra', function () {
    $examen = Examen::factory()->create();

    app(DistribucionAulasService::class)->guardar($examen, [[
        'id_aul' => Aula::factory()->create(['capacidad_aul' => 50])->id_aul,
        'id_are' => Area::factory()->create()->id_are,
        'capacidad_eau' => 42,
    ]]);

    expect(ExamenAula::where('id_exa', $examen->id_exa)->value('capacidad_eau'))->toBe(42);
});

it('no permite repetir un aula en dos áreas', function () {
    $examen = Examen::create(['id_pro' => Proceso::factory()->create()->id_pro, 'nombre_exa' => 'Jornada 1']);
    $aula = Aula::factory()->create();

    expect(fn () => app(DistribucionAulasService::class)->guardar($examen, [
        ['id_aul' => $aula->id_aul, 'id_are' => Area::factory()->create()->id_are, 'capacidad_eau' => 24],
        ['id_aul' => $aula->id_aul, 'id_are' => Area::factory()->create()->id_are, 'capacidad_eau' => 16],
    ]))->toThrow(RuntimeException::class);
});

it('guarda una distribucion valida con un area por aula', function () {
    $examen = Examen::factory()->create();

    app(DistribucionAulasService::class)->guardar($examen, [
        ['id_aul' => Aula::factory()->create()->id_aul, 'id_are' => Area::factory()->create()->id_are, 'capacidad_eau' => 40],
        ['id_aul' => Aula::factory()->create()->id_aul, 'id_are' => Area::factory()->create()->id_are, 'capacidad_eau' => 24],
    ]);

    expect(ExamenAula::where('id_exa', $examen->id_exa)->count())->toBe(2);
});

it('impide asignar dos veces una aula a la misma jornada sin reconstruir la distribución', function () {
    $examen = Examen::factory()->create();
    $aula = Aula::factory()->create();
    $servicio = app(DistribucionAulasService::class);

    $servicio->agregar($examen, ['id_aul' => $aula->id_aul, 'id_are' => Area::factory()->create()->id_are, 'capacidad_eau' => 24]);

    expect(fn () => $servicio->agregar($examen, [
        'id_aul' => $aula->id_aul,
        'id_are' => Area::factory()->create()->id_are,
        'capacidad_eau' => 16,
    ]))->toThrow(RuntimeException::class)
        ->and(ExamenAula::query()->where('id_exa', $examen->id_exa)->count())->toBe(1);
});

it('compara la capacidad de cada area con los inscritos del proceso', function () {
    $examen = Examen::factory()->create();
    $area = Area::factory()->create();
    $carrera = Carrera::factory()->create(['id_are' => $area->id_are]);
    Inscripcion::factory()->count(24)->create(['id_pro' => $examen->id_pro, 'id_car' => $carrera->id_car]);

    app(DistribucionAulasService::class)->guardar($examen, [[
        'id_aul' => Aula::factory()->create()->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 24,
    ]]);

    expect(app(DistribucionAulasService::class)->totalesPorArea($examen)->all())->toBe([
        ['id_are' => $area->id_are, 'inscritos' => 24, 'capacidad' => 24, 'diferencia' => 0],
    ])->and(app(DistribucionAulasService::class)->distribucionEstaCompleta($examen))->toBeTrue();
});

/*
 * El reparto real de 2027-I: 379 inscritos en diez aulas de cincuenta
 * carpetas. Los numeros por aula son los remanentes de cada area, no el tamano
 * del aula, y por eso van de 24 a 42.
 */
it('acepta el reparto completo de 2027-I y lo da por cuadrado', function () {
    $examen = Examen::factory()->create();

    $plan = [
        1 => [24],
        2 => [40, 40, 33],
        3 => [40],
        4 => [42],
        5 => [40, 40, 40, 40],
    ];

    $filas = [];

    foreach ($plan as $numeroArea => $capacidades) {
        $area = Area::factory()->create(['numero_are' => $numeroArea]);
        $carrera = Carrera::factory()->create(['id_are' => $area->id_are]);

        Inscripcion::factory()->count(array_sum($capacidades))->create([
            'id_pro' => $examen->id_pro,
            'id_car' => $carrera->id_car,
        ]);

        foreach ($capacidades as $capacidad) {
            $filas[] = [
                'id_aul' => Aula::factory()->create(['capacidad_aul' => 50])->id_aul,
                'id_are' => $area->id_are,
                'capacidad_eau' => $capacidad,
            ];
        }
    }

    $servicio = app(DistribucionAulasService::class);
    $servicio->guardar($examen, $filas);

    expect(ExamenAula::where('id_exa', $examen->id_exa)->count())->toBe(10)
        ->and(ExamenAula::where('id_exa', $examen->id_exa)->sum('capacidad_eau'))->toBe(379)
        ->and($servicio->distribucionEstaCompleta($examen))->toBeTrue()
        ->and($servicio->motivoParaNoSortear($examen))->toBeNull();
});
