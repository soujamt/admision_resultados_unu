<?php

use App\Models\Area;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\ExamenAula;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Services\Admision\DistribucionAulasService;

it('no permite superar cuarenta postulantes en un aula', function () {
    $examen = Examen::create(['id_pro' => Proceso::factory()->create()->id_pro, 'nombre_exa' => 'Jornada 1']);
    $aula = Aula::factory()->create();
    $area = Area::factory()->create();

    expect(fn () => app(DistribucionAulasService::class)->guardar($examen, [[
        'id_aul' => $aula->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 41,
    ]]))->toThrow(RuntimeException::class);
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
