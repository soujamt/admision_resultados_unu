<?php

use App\Models\Area;
use App\Models\AsignacionExamen;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\ExamenPostulante;
use App\Models\Inscripcion;
use App\Models\Postulante;
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

it('separa a los postulantes que comparten primer apellido', function () {
    $examen = Examen::factory()->create();
    $area = Area::factory()->create();
    $carrera = Carrera::factory()->create(['id_are' => $area->id_are]);

    /*
     * Doce postulantes repartidos en cuatro apellidos: ninguno llega a la
     * mitad del aula, asi que el sorteo puede separarlos a todos.
     */
    foreach (['QUISPE', 'MAMANI', 'FLORES', 'RAMOS'] as $apellido) {
        foreach (range(1, 3) as $numero) {
            $inscripcion = Inscripcion::factory()->create([
                'id_pro' => $examen->id_pro,
                'id_car' => $carrera->id_car,
                'id_pos' => Postulante::factory()->create(['primer_apellido_pos' => $apellido]),
            ]);

            ExamenPostulante::factory()->create([
                'id_exa' => $examen->id_exa,
                'id_ins' => $inscripcion->id_ins,
            ]);
        }
    }

    app(DistribucionAulasService::class)->guardar($examen, [[
        'id_aul' => Aula::factory()->create()->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 12,
    ]]);

    app(SorteadorAulasService::class)->sortear($examen, app(DistribucionAulasService::class));

    $apellidos = AsignacionExamen::query()
        ->with('postulante.inscripcion.postulante')
        ->orderBy('asiento_ase')
        ->get()
        ->map(fn (AsignacionExamen $fila): string => $fila->postulante->inscripcion->postulante->primer_apellido_pos)
        ->all();

    $contiguos = collect($apellidos)->sliding(2)->filter(
        fn ($par): bool => $par->first() === $par->last(),
    );

    expect($apellidos)->toHaveCount(12)
        ->and($contiguos)->toBeEmpty();
});

it('deja al final a los del apellido que no se puede separar', function () {
    $examen = Examen::factory()->create();
    $area = Area::factory()->create();
    $carrera = Carrera::factory()->create(['id_are' => $area->id_are]);

    /* Cuatro de cinco comparten apellido: repetir es inevitable. */
    foreach (['SILVA', 'SILVA', 'SILVA', 'SILVA', 'VEGA'] as $apellido) {
        $inscripcion = Inscripcion::factory()->create([
            'id_pro' => $examen->id_pro,
            'id_car' => $carrera->id_car,
            'id_pos' => Postulante::factory()->create(['primer_apellido_pos' => $apellido]),
        ]);

        ExamenPostulante::factory()->create([
            'id_exa' => $examen->id_exa,
            'id_ins' => $inscripcion->id_ins,
        ]);
    }

    app(DistribucionAulasService::class)->guardar($examen, [[
        'id_aul' => Aula::factory()->create()->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 5,
    ]]);

    expect(app(SorteadorAulasService::class)->sortear($examen, app(DistribucionAulasService::class)))->toBe(5)
        ->and(AsignacionExamen::query()->pluck('asiento_ase')->sort()->values()->all())->toBe([1, 2, 3, 4, 5]);
});

it('nombra el área descuadrada al negarse a sortear', function () {
    $examen = Examen::factory()->create();
    $area = Area::factory()->create(['numero_are' => 3, 'nombre_are' => 'Negocios']);
    $carrera = Carrera::factory()->create(['id_are' => $area->id_are]);
    Inscripcion::factory()->count(5)->create(['id_pro' => $examen->id_pro, 'id_car' => $carrera->id_car]);

    app(DistribucionAulasService::class)->guardar($examen, [[
        'id_aul' => Aula::factory()->create()->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 2,
    ]]);

    expect(fn () => app(SorteadorAulasService::class)->sortear($examen, app(DistribucionAulasService::class)))
        ->toThrow(RuntimeException::class, 'Área 3: Negocios');
});
