<?php

use App\Models\Area;
use App\Models\AsignacionExamen;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\ExamenPostulante;
use App\Models\Inscripcion;
use App\Services\Admision\DistribucionAulasService;
use App\Services\Admision\SorteadorAulasService;

it('asigna todos los postulantes a una sola area y numera sus asientos', function () {
    $examen = Examen::factory()->create();
    $area = Area::factory()->create();
    $carrera = Carrera::factory()->create(['id_are' => $area->id_are]);
    $inscripciones = Inscripcion::factory()->count(3)->create(['id_pro' => $examen->id_pro, 'id_car' => $carrera->id_car]);

    foreach ($inscripciones as $indice => $inscripcion) {
        ExamenPostulante::factory()->create([
            'id_exa' => $examen->id_exa,
            'id_ins' => $inscripcion->id_ins,
            'codigo_cartilla_exp' => 'CARTILLA-'.($indice + 1),
        ]);
    }

    app(DistribucionAulasService::class)->guardar($examen, [[
        'id_aul' => Aula::factory()->create()->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 3,
    ]]);

    expect(app(SorteadorAulasService::class)->sortear($examen, app(DistribucionAulasService::class)))->toBe(3)
        ->and(AsignacionExamen::query()->count())->toBe(3)
        ->and(AsignacionExamen::query()->pluck('asiento_ase')->sort()->values()->all())->toBe([1, 2, 3]);
});
