<?php

use App\Enums\Convocatoria;
use App\Models\Area;
use App\Models\AsignacionExamen;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\ExamenAula;
use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Usuario;

it('exporta el padrón del aula desde las inscripciones aunque no exista un TXT', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'nombre_exa' => 'Examen CEPREUNU',
        'fecha_exa' => '2027-03-21',
    ]);
    $area = Area::factory()->create([
        'numero_are' => 2,
        'nombre_are' => 'Ciencias de la Salud',
    ]);
    $carrera = Carrera::factory()->create(['id_are' => $area->id_are]);
    $sede = Sede::factory()->create([
        'codigo_sed' => 'CORONEL_PORTILLO',
        'nombre_sed' => 'Sede Coronel Portillo - Callería',
    ]);
    $aulaExamen = ExamenAula::factory()->create([
        'id_exa' => $examen->id_exa,
        'id_aul' => Aula::factory()->create([
            'id_sed' => $sede->id_sed,
            'codigo_aul' => 'AULA-01',
            'nombre_aul' => 'Aula 1',
            'pabellon_aul' => 'PAB I - Piso 1',
            'capacidad_aul' => 40,
        ])->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 40,
    ]);

    foreach (range(1, 40) as $asiento) {
        $inscripcion = Inscripcion::factory()->create([
            'id_pro' => $examen->id_pro,
            'id_car' => $carrera->id_car,
            'id_sed' => $sede->id_sed,
            'id_pos' => Postulante::factory()->create([
                'numero_documento_pos' => (string) (87654320 + $asiento),
            ]),
            'codigo_ins' => '2027-I-'.str_pad((string) $asiento, 4, '0', STR_PAD_LEFT),
        ]);
        AsignacionExamen::create([
            'id_ins' => $inscripcion->id_ins,
            'id_eau' => $aulaExamen->id_eau,
            'asiento_ase' => $asiento,
        ]);
    }

    $aulaExamen->load(['examen.proceso', 'area', 'aula.sede', 'asignaciones.inscripcion.postulante']);
    $asignaciones = $aulaExamen->asignaciones->sortBy('asiento_ase');
    $html = view('pdf.padron-aula', [
        'aulaExamen' => $aulaExamen,
        'asignaciones' => $asignaciones,
    ])->render();

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.aulas.padron', $aulaExamen))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($proceso->convocatoria_pro)->toBe(Convocatoria::Primera);
    expect($html)->toContain(
        'UNIVERSIDAD NACIONAL DE UCAYALI',
        '2027 - PRIMERA CONVOCATORIA',
        'Padrón de postulantes por aula',
        'isologo-unu.png',
        'PUCALLPA',
        '87654321',
    );
});
