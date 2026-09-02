<?php

use App\Enums\CondicionIngresante;
use App\Enums\GrupoModalidad;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\Ingresante;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Usuario;
use App\Models\Vacante;
use App\Services\Admision\ResolverResultadosService;
use Livewire\Livewire;
use Tests\Support\PadronDeExamen;

/**
 * Tercera convocatoria con una vacante ordinaria de una plaza y dos
 * postulantes aptos: entra el mejor y queda uno en cola para el Art. 93.
 *
 * @return array{proceso:Proceso, examen:Examen, vacante:Vacante}
 */
function terceraConvocatoriaResuelta(int $plazas = 1): array
{
    $proceso = Proceso::factory()->codigo('2027-III')->create();
    $vacante = Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario]),
        'id_car' => Carrera::factory()->create()->id_car,
        'id_sed' => Sede::factory()->create()->id_sed,
        'cantidad_vac' => $plazas,
    ]);
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => false,
    ]);
    PadronDeExamen::postulante($examen, $vacante, 301, 90);
    PadronDeExamen::postulante($examen, $vacante, 302, 70);
    app(ResolverResultadosService::class)->resolver($examen);

    return ['proceso' => $proceso, 'examen' => $examen, 'vacante' => $vacante];
}

it('muestra el padrón de ingresantes al administrador', function () {
    $escenario = terceraConvocatoriaResuelta();

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.ingresantes', ['proceso' => $escenario['proceso']->id_pro]))
        ->assertOk()
        ->assertSee('Padrón de ingresantes')
        ->assertSee('Arrastre de vacantes');
});

it('genera el padrón y registra la pérdida de condición llamando al Art. 93', function () {
    $escenario = terceraConvocatoriaResuelta();

    $componente = Livewire::actingAs(Usuario::factory()->administrador()->create())
        ->test('pages::resultados.ingresantes', [
            'proceso' => $escenario['proceso']->id_pro,
            'jornada' => $escenario['examen']->id_exa,
        ])
        ->call('generarPadron')
        ->assertHasNoErrors();

    expect(Ingresante::where('id_pro', $escenario['proceso']->id_pro)->count())->toBe(1);

    $titular = Ingresante::firstOrFail();

    $componente->call('prepararCondicion', $titular->id_ing)
        ->set('nuevaCondicion', CondicionIngresante::SinMatricula->value)
        ->set('motivoCondicion', 'No se matriculó en el plazo del cronograma.')
        ->call('registrarCondicion')
        ->assertHasNoErrors();

    $sustituto = Ingresante::whereNotNull('id_sustituido_ing')->first();

    expect($titular->refresh()->condicion_ing)->toBe(CondicionIngresante::SinMatricula)
        ->and($sustituto)->not->toBeNull()
        ->and((float) $sustituto->puntaje_ing)->toBe(70.0);
});

it('exige un sustento de al menos diez caracteres para quitar la condición', function () {
    $escenario = terceraConvocatoriaResuelta();

    Livewire::actingAs(Usuario::factory()->administrador()->create())
        ->test('pages::resultados.ingresantes', [
            'proceso' => $escenario['proceso']->id_pro,
            'jornada' => $escenario['examen']->id_exa,
        ])
        ->call('generarPadron')
        ->call('prepararCondicion', Ingresante::firstOrFail()->id_ing)
        ->set('nuevaCondicion', CondicionIngresante::SinConstancia->value)
        ->set('motivoCondicion', 'corto')
        ->call('registrarCondicion')
        ->assertHasErrors(['motivoCondicion']);

    expect(Ingresante::firstOrFail()->condicion_ing)->toBe(CondicionIngresante::Vigente);
});

it('aplica el arrastre al cuadro de vacantes desde la pantalla', function () {
    $escenario = terceraConvocatoriaResuelta(plazas: 3);

    Livewire::actingAs(Usuario::factory()->administrador()->create())
        ->test('pages::resultados.ingresantes', [
            'proceso' => $escenario['proceso']->id_pro,
            'jornada' => $escenario['examen']->id_exa,
        ])
        ->call('generarPadron')
        ->call('previsualizarArrastre')
        ->call('aplicarArrastre')
        ->assertHasNoErrors();

    /*
     * La plaza que sobra es de una vacante ordinaria, que el Art. 19 no mueve,
     * y no hay primera ni segunda convocatoria de la que arrastrar.
     */
    expect($escenario['vacante']->refresh()->cantidad_arrastre_vac)->toBe(0);
});
