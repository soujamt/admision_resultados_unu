<?php

use App\Enums\Convocatoria;
use App\Models\Carrera;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\ProcesoModalidad;
use App\Models\Sede;
use App\Models\Vacante;
use Tests\Support\ConstructorXlsx;

/**
 * Reproduce la hoja CARRERAS_PROFESIONALES: tres filas de cabecera y luego un
 * bloque por modalidad, donde solo la primera fila del bloque repite el codigo
 * de la modalidad y el del lugar de inscripcion.
 *
 * @param  list<list<string>>  $filas
 */
function archivoDeOferta(array $filas): string
{
    return (new ConstructorXlsx)
        ->hoja('CARRERAS_PROFESIONALES', array_merge([
            ['PROCESO DE ADMISIÓN 2027 - PRIMERA CONVOCATORIA'],
            ['MODALIDAD DE ADMISIÓN', '', 'LUGARES DE INSCRIPCIÓN', '', 'CARRERAS PROFESIONALES'],
            ['Código', 'Descripción', 'Código', 'Descripción', 'Código', 'Descripción'],
        ], $filas))
        ->escribir();
}

function prepararEstructura(): array
{
    $sede = Sede::factory()->create([
        'codigo_sed' => 'CORONEL_PORTILLO',
        'nombre_sed' => 'Sede Coronel Portillo - Callería',
    ]);

    $modalidad = Modalidad::factory()->conCodigoExterno(2)->create([
        'nombre_mod' => 'Exoneración - CEPREUNU',
    ]);

    $derecho = Carrera::factory()->llamada('Derecho')->create();
    $enfermeria = Carrera::factory()->llamada('Enfermería')->create();

    return compact('sede', 'modalidad', 'derecho', 'enfermeria');
}

it('crea el proceso cuando todavia no existe', function () {
    prepararEstructura();

    $archivo = archivoDeOferta([
        ['2', 'EXONERACIÓN - CEPREUNU', '593', 'PUCALLPA', '2567', 'SEDE CORONEL PORTILLO - CALLERIA - DERECHO'],
    ]);

    $this->artisan('admision:importar-oferta', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->assertSuccessful();

    $proceso = Proceso::where('codigo_pro', '2027-I')->sole();

    expect($proceso->anio_pro)->toBe(2027)
        ->and($proceso->convocatoria_pro)->toBe(Convocatoria::Primera);
});

it('hereda la modalidad y el lugar en las filas que no los repiten', function () {
    ['modalidad' => $modalidad, 'derecho' => $derecho, 'enfermeria' => $enfermeria] = prepararEstructura();

    $archivo = archivoDeOferta([
        ['2', 'EXONERACIÓN - CEPREUNU', '593', 'PUCALLPA', '2567', 'SEDE CORONEL PORTILLO - CALLERIA - DERECHO'],
        ['', '', '', '', '2560', 'SEDE CORONEL PORTILLO - CALLERIA - ENFERMERÍA'],
    ]);

    $this->artisan('admision:importar-oferta', ['archivo' => $archivo, '--proceso' => '2027-I']);

    expect(Vacante::count())->toBe(2)
        ->and(Vacante::where('codigo_externo_vac', 2560)->value('id_car'))->toBe($enfermeria->id_car)
        ->and(Vacante::where('codigo_externo_vac', 2560)->value('id_mod'))->toBe($modalidad->id_mod)
        ->and(Vacante::where('codigo_externo_vac', 2567)->value('id_car'))->toBe($derecho->id_car);
});

it('guarda el codigo de lugar de inscripcion de la modalidad', function () {
    ['modalidad' => $modalidad] = prepararEstructura();

    $archivo = archivoDeOferta([
        ['2', 'EXONERACIÓN - CEPREUNU', '593', 'PUCALLPA', '2567', 'SEDE CORONEL PORTILLO - CALLERIA - DERECHO'],
    ]);

    $this->artisan('admision:importar-oferta', ['archivo' => $archivo, '--proceso' => '2027-I']);

    $abierta = ProcesoModalidad::where('id_mod', $modalidad->id_mod)->sole();

    expect($abierta->codigo_lugar_prm)->toBe(593)
        ->and($abierta->nombre_lugar_prm)->toBe('PUCALLPA');
});

it('reconoce la carrera aunque el nombre venga sin tildes ni con la misma puntuacion', function () {
    prepararEstructura();
    $educacion = Carrera::factory()->llamada('Educación Secundaria: Especialidad Idioma Inglés')->create();

    $archivo = archivoDeOferta([
        ['2', 'EXONERACIÓN - CEPREUNU', '593', 'PUCALLPA', '2570', 'SEDE CORONEL PORTILLO - CALLERIA - EDUCACION SECUNDARIA - ESPECIALIDAD IDIOMA INGLES'],
    ]);

    $this->artisan('admision:importar-oferta', ['archivo' => $archivo, '--proceso' => '2027-I']);

    expect(Vacante::where('codigo_externo_vac', 2570)->value('id_car'))->toBe($educacion->id_car);
});

it('no pisa la cantidad de vacantes ya configurada al reimportar', function () {
    prepararEstructura();

    $archivo = archivoDeOferta([
        ['2', 'EXONERACIÓN - CEPREUNU', '593', 'PUCALLPA', '2567', 'SEDE CORONEL PORTILLO - CALLERIA - DERECHO'],
    ]);

    $this->artisan('admision:importar-oferta', ['archivo' => $archivo, '--proceso' => '2027-I']);

    Vacante::query()->update(['cantidad_vac' => 40]);

    $this->artisan('admision:importar-oferta', ['archivo' => $archivo, '--proceso' => '2027-I']);

    expect(Vacante::count())->toBe(1)
        ->and(Vacante::sole()->cantidad_vac)->toBe(40);
});

it('reporta la carrera que no esta registrada en vez de abortar la carga', function () {
    prepararEstructura();

    $archivo = archivoDeOferta([
        ['2', 'EXONERACIÓN - CEPREUNU', '593', 'PUCALLPA', '2567', 'SEDE CORONEL PORTILLO - CALLERIA - DERECHO'],
        ['', '', '', '', '2999', 'SEDE CORONEL PORTILLO - CALLERIA - ODONTOLOGÍA'],
    ]);

    $this->artisan('admision:importar-oferta', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->expectsOutputToContain('No hay una carrera registrada con ese nombre.');

    expect(Vacante::count())->toBe(1);
});

it('rechaza un codigo de proceso mal formado', function () {
    prepararEstructura();

    $archivo = archivoDeOferta([
        ['2', 'EXONERACIÓN - CEPREUNU', '593', 'PUCALLPA', '2567', 'SEDE CORONEL PORTILLO - CALLERIA - DERECHO'],
    ]);

    $this->artisan('admision:importar-oferta', ['archivo' => $archivo, '--proceso' => '2027-PRIMERA'])
        ->assertFailed();

    expect(Proceso::count())->toBe(0);
});
