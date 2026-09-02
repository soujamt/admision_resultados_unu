<?php

use App\Models\Area;
use App\Models\AsignacionExamen;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\ExamenAula;
use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Usuario;

it('exporta el padrón del aula desde las inscripciones aunque no exista un TXT', function () {
    $examen = Examen::factory()->create();
    $area = Area::factory()->create();
    $carrera = Carrera::factory()->create(['id_are' => $area->id_are]);
    $inscripcion = Inscripcion::factory()->create([
        'id_pro' => $examen->id_pro,
        'id_car' => $carrera->id_car,
        'id_pos' => Postulante::factory()->create(['numero_documento_pos' => '87654321']),
        'codigo_ins' => '2027-I-0001',
    ]);
    $aulaExamen = ExamenAula::factory()->create([
        'id_exa' => $examen->id_exa,
        'id_are' => $area->id_are,
        'capacidad_eau' => 1,
    ]);
    AsignacionExamen::create([
        'id_ins' => $inscripcion->id_ins,
        'id_eau' => $aulaExamen->id_eau,
        'asiento_ase' => 1,
    ]);

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.aulas.padron', $aulaExamen))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
