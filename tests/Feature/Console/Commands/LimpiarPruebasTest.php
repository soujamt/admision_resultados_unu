<?php

use App\Models\AsignacionExamen;
use App\Models\Examen;
use App\Models\ExamenAula;
use App\Models\ExamenImportacion;
use App\Models\ExamenPostulante;
use App\Models\ExamenRespuesta;
use App\Models\Ingresante;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Resultado;
use App\Models\Vacante;

test('cleans test imports and results while preserving vacancies and classroom assignments', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $inscripcion = Inscripcion::factory()->create(['id_pro' => $proceso->id_pro]);
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'resuelto_en_exa' => now(),
    ]);
    $vacante = Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => $inscripcion->id_mod,
        'id_car' => $inscripcion->id_car,
        'id_sed' => $inscripcion->id_sed,
    ]);
    $postulante = ExamenPostulante::factory()->create([
        'id_exa' => $examen->id_exa,
        'id_ins' => $inscripcion->id_ins,
    ]);
    $respuesta = ExamenRespuesta::factory()->create(['id_exp' => $postulante->id_exp]);
    $resultado = Resultado::factory()->create([
        'id_exa' => $examen->id_exa,
        'id_exp' => $postulante->id_exp,
        'id_vac' => $vacante->id_vac,
    ]);
    $ingresante = Ingresante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_ins' => $inscripcion->id_ins,
        'id_vac' => $vacante->id_vac,
        'id_exa' => $examen->id_exa,
        'id_res' => $resultado->id_res,
    ]);
    $importacion = ExamenImportacion::factory()->create(['id_exa' => $examen->id_exa]);
    $aula = ExamenAula::factory()->create(['id_exa' => $examen->id_exa]);
    $asignacion = AsignacionExamen::factory()->create([
        'id_ins' => $inscripcion->id_ins,
        'id_eau' => $aula->id_eau,
    ]);
    $otroExamen = Examen::factory()->create(['id_pro' => $proceso->id_pro]);
    $otraImportacion = ExamenImportacion::factory()->create(['id_exa' => $otroExamen->id_exa]);

    $this->artisan('admision:limpiar-pruebas', [
        'examen' => $examen->id_exa,
        '--force' => true,
    ])
        ->expectsOutputToContain('Jornada de prueba limpiada.')
        ->assertExitCode(0);

    $this->assertModelMissing($postulante);
    $this->assertModelMissing($respuesta);
    $this->assertModelMissing($resultado);
    $this->assertModelMissing($ingresante);
    $this->assertModelMissing($importacion);
    $this->assertModelExists($examen);
    $this->assertModelExists($vacante);
    $this->assertModelExists($inscripcion);
    $this->assertModelExists($aula);
    $this->assertModelExists($asignacion);
    $this->assertModelExists($otraImportacion);
    expect($examen->refresh()->resuelto_en_exa)->toBeNull();
});
