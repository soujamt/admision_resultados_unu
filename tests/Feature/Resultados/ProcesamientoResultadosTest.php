<?php

use App\Enums\EstadoResultado;
use App\Models\Examen;
use App\Models\ExamenPostulante;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Resultado;
use App\Models\Usuario;
use App\Models\Vacante;
use Livewire\Livewire;
use Tests\Support\PadronDeExamen;

it('muestra el módulo de importación y resultados al administrador', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    Examen::factory()->create(['id_pro' => $proceso->id_pro]);

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.procesamiento'))
        ->assertOk()
        ->assertSee('Importación y resultados')
        ->assertSee('Importar archivos del lector óptico')
        ->assertSee('Configurar calificación')
        ->assertSee('Puntaje por respuesta')
        ->assertSee('Factor de dificultad')
        ->assertSee('El umbral del Art. 80 se evalúa por carrera profesional.')
        ->assertSee('Excepciones por carrera');
});

it('exporta el pdf general con orden de mérito y estado', function () {
    $inscripcion = Inscripcion::factory()->create();
    $proceso = $inscripcion->proceso;
    $examen = Examen::factory()->create(['id_pro' => $proceso->id_pro]);
    $vacante = Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => $inscripcion->id_mod,
        'id_car' => $inscripcion->id_car,
        'id_sed' => $inscripcion->id_sed,
    ]);
    $postulante = ExamenPostulante::factory()->create([
        'id_exa' => $examen->id_exa,
        'id_ins' => $inscripcion->id_ins,
        'documento_exp' => $inscripcion->postulante->numero_documento_pos,
        'nombre_exp' => 'PÉREZ ROJAS, ANA MARÍA',
    ]);
    Resultado::factory()->create([
        'id_exa' => $examen->id_exa,
        'id_exp' => $postulante->id_exp,
        'id_vac' => $vacante->id_vac,
        'estado_res' => EstadoResultado::Ingreso,
    ]);

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.pdf', $examen))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload();
});

it('anula y restaura una postulación, e invalida la resolución vigente', function () {
    $inscripcion = Inscripcion::factory()->create();
    $examen = Examen::factory()->create(['id_pro' => $inscripcion->id_pro, 'resuelto_en_exa' => now()]);
    $vacante = Vacante::factory()->create([
        'id_pro' => $inscripcion->id_pro,
        'id_mod' => $inscripcion->id_mod,
        'id_car' => $inscripcion->id_car,
        'id_sed' => $inscripcion->id_sed,
    ]);
    $postulante = ExamenPostulante::factory()->create([
        'id_exa' => $examen->id_exa,
        'id_ins' => $inscripcion->id_ins,
        'documento_exp' => $inscripcion->postulante->numero_documento_pos,
    ]);
    Resultado::factory()->create([
        'id_exa' => $examen->id_exa,
        'id_exp' => $postulante->id_exp,
        'id_vac' => $vacante->id_vac,
        'estado_res' => EstadoResultado::Ingreso,
    ]);

    $componente = Livewire::actingAs(Usuario::factory()->administrador()->create())
        ->test('pages::resultados.procesamiento', ['proceso' => $inscripcion->id_pro, 'jornada' => $examen->id_exa])
        ->call('prepararAnulacion', $postulante->id_exp)
        ->set('motivoAnulacion', 'Retirado del aula por suplantación, acta CCA 12.')
        ->call('anular')
        ->assertHasNoErrors();

    expect($postulante->refresh()->estaAnulado())->toBeTrue()
        ->and($postulante->motivo_anulacion_exp)->toBe('Retirado del aula por suplantación, acta CCA 12.')
        ->and($examen->refresh()->resuelto_en_exa)->toBeNull()
        ->and($examen->resultados()->count())->toBe(0);

    $componente->call('restaurar', $postulante->id_exp)->assertHasNoErrors();

    expect($postulante->refresh()->estaAnulado())->toBeFalse();
});

it('guarda la nota mínima del Art. 81 en la carrera profesional', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $examen = Examen::factory()->create(['id_pro' => $proceso->id_pro, 'resuelto_en_exa' => now()]);
    $vacante = Vacante::factory()->create(['id_pro' => $proceso->id_pro]);

    Livewire::actingAs(Usuario::factory()->administrador()->create())
        ->test('pages::resultados.procesamiento', ['proceso' => $proceso->id_pro, 'jornada' => $examen->id_exa])
        ->set('minimosCarreras.'.$vacante->id_car, '55')
        ->call('guardarConfiguracion')
        ->assertHasNoErrors();

    expect((float) $vacante->carrera->refresh()->puntaje_minimo_car)->toBe(55.0)
        ->and($examen->refresh()->resuelto_en_exa)->toBeNull();
});

it('muestra la vista previa del factor antes de confirmar la generación', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $vacante = Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'cantidad_vac' => 1,
    ]);
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'umbral_factor_dificultad_exa' => 40,
        'aplicar_factor_dificultad_exa' => true,
    ]);
    PadronDeExamen::postulante($examen, $vacante, 81, 40);
    PadronDeExamen::postulante($examen, $vacante, 82, 40);

    $componente = Livewire::actingAs(Usuario::factory()->administrador()->create())
        ->test('pages::resultados.procesamiento', ['proceso' => $proceso->id_pro, 'jornada' => $examen->id_exa])
        ->call('prepararGeneracion')
        ->assertSet('previsualizacion.factor_aplicado', true)
        ->assertSet('previsualizacion.carreras_con_factor', 1)
        ->assertSet('previsualizacion.ingresantes_adicionales_empate', 1)
        ->assertSee('Se aplicará el factor de dificultad')
        ->assertSee('Se detectó empate en el último puesto (Art. 85)')
        ->assertSee('Evaluación del Art. 80 por carrera');

    expect($examen->resultados()->count())->toBe(0);

    $componente->call('generar')->assertHasNoErrors();

    expect($examen->resultados()->count())->toBe(2)
        ->and((float) $examen->resultados()->first()->factor_dificultad_res)->toBe(1.6);
});
