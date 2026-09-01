<?php

use App\Enums\EstadoCivil;
use App\Enums\Sexo;
use App\Enums\TipoColegio;
use App\Enums\TipoDocumento;
use App\Models\Carrera;
use App\Models\Colegio;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Postulante;
use App\Models\Proceso;
use App\Models\ProcesoModalidad;
use App\Models\Sede;
use App\Models\Ubigeo;
use App\Models\Vacante;
use Tests\Support\ConstructorXlsx;
use Tests\Support\FormatoOficial;

/**
 * Deja montado lo minimo que la carga necesita encontrar: los maestros, la
 * estructura academica y el cuadro de vacantes del proceso.
 *
 * @return array<string, mixed>
 */
function prepararProceso(): array
{
    Pais::factory()->peru()->create();
    Nacionalidad::factory()->peruana()->create();
    Ubigeo::create([
        'codigo_ubi' => '250101',
        'departamento_ubi' => 'UCAYALI',
        'provincia_ubi' => 'CORONEL PORTILLO',
        'distrito_ubi' => 'CALLERIA',
    ]);
    Colegio::create(['codigo_modular_col' => '0238808', 'nombre_col' => '64035 AGROPECUARIO']);

    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $sede = Sede::factory()->create(['nombre_sed' => 'Sede Coronel Portillo - Callería']);
    $modalidad = Modalidad::factory()->conCodigoExterno(2)->create(['nombre_mod' => 'Exoneración - CEPREUNU']);
    $derecho = Carrera::factory()->llamada('Derecho')->create();
    $enfermeria = Carrera::factory()->llamada('Enfermería')->create();

    ProcesoModalidad::create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => $modalidad->id_mod,
        'codigo_lugar_prm' => 593,
        'nombre_lugar_prm' => 'PUCALLPA',
    ]);

    Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => $modalidad->id_mod,
        'id_car' => $derecho->id_car,
        'id_sed' => $sede->id_sed,
        'codigo_externo_vac' => 2567,
    ]);

    Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => $modalidad->id_mod,
        'id_car' => $enfermeria->id_car,
        'id_sed' => $sede->id_sed,
        'codigo_externo_vac' => 2560,
    ]);

    return compact('proceso', 'sede', 'modalidad', 'derecho', 'enfermeria');
}

/**
 * @param  list<list<string>>  $filas
 */
function archivoDeInscripciones(array $filas): string
{
    return (new ConstructorXlsx)
        ->hoja('FORMATO', FormatoOficial::hoja($filas))
        ->escribir();
}

it('crea el postulante y su ficha con los datos de la fila', function () {
    ['derecho' => $derecho, 'modalidad' => $modalidad, 'sede' => $sede] = prepararProceso();

    $archivo = archivoDeInscripciones([FormatoOficial::fila()]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->assertSuccessful();

    $postulante = Postulante::sole();

    expect($postulante->tipo_documento_pos)->toBe(TipoDocumento::Dni)
        ->and($postulante->numero_documento_pos)->toBe('62035505')
        ->and($postulante->primer_apellido_pos)->toBe('SHUPINGAHUA')
        ->and($postulante->nombres_pos)->toBe('YOJANA VALENTINA')
        ->and($postulante->estado_civil_pos)->toBe(EstadoCivil::Soltero)
        ->and($postulante->sexo_pos)->toBe(Sexo::Femenino)
        ->and($postulante->fecha_nacimiento_pos->format('Y-m-d'))->toBe('2009-02-02')
        ->and($postulante->ubigeo_nacimiento_pos)->toBe('250101')
        ->and($postulante->correo_pos)->toBe('yojana@example.com');

    $inscripcion = Inscripcion::sole();

    expect($inscripcion->id_car)->toBe($derecho->id_car)
        ->and($inscripcion->id_mod)->toBe($modalidad->id_mod)
        ->and($inscripcion->id_sed)->toBe($sede->id_sed)
        ->and($inscripcion->codigo_colegio_ins)->toBe('0238808')
        ->and($inscripcion->tipo_colegio_ins)->toBe(TipoColegio::Nacional)
        ->and($inscripcion->anio_graduacion_ins)->toBe(2025)
        ->and($inscripcion->veces_unu_ins)->toBe(1);
});

it('traduce el codigo externo de carrera usando el cuadro de vacantes', function () {
    ['enfermeria' => $enfermeria] = prepararProceso();

    $archivo = archivoDeInscripciones([
        FormatoOficial::fila(['CODIGO_CARRERA' => '2560']),
    ]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I']);

    expect(Inscripcion::sole()->id_car)->toBe($enfermeria->id_car);
});

it('numera cada ficha con un correlativo del proceso', function () {
    prepararProceso();

    $archivo = archivoDeInscripciones([
        FormatoOficial::fila(),
        FormatoOficial::fila(['NUMERO_DOCUMENTO' => '61549383', 'CORREO_ELECTRONICO' => 'otro@example.com']),
    ]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I']);

    expect(Inscripcion::orderBy('id_ins')->pluck('codigo_ins')->all())
        ->toBe(['2027I-00001', '2027I-00002']);
});

it('vuelve a correrse sin duplicar postulantes ni fichas', function () {
    prepararProceso();

    $archivo = archivoDeInscripciones([FormatoOficial::fila()]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I']);
    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->expectsOutputToContain('Inscripciones actualizados');

    expect(Postulante::count())->toBe(1)
        ->and(Inscripcion::count())->toBe(1)
        ->and(Inscripcion::sole()->codigo_ins)->toBe('2027I-00001');
});

it('salta la fila con datos invalidos y carga el resto', function () {
    prepararProceso();

    $archivo = archivoDeInscripciones([
        FormatoOficial::fila(['SEXO' => 'X']),
        FormatoOficial::fila(['NUMERO_DOCUMENTO' => '61549383', 'CORREO_ELECTRONICO' => 'otro@example.com']),
    ]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->expectsOutputToContain('se espera M o F');

    expect(Inscripcion::count())->toBe(1)
        ->and(Postulante::sole()->numero_documento_pos)->toBe('61549383');
});

it('rechaza la fila cuyo codigo de carrera no esta en el cuadro de vacantes', function () {
    prepararProceso();

    $archivo = archivoDeInscripciones([
        FormatoOficial::fila(['CODIGO_CARRERA' => '9999']),
    ]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->expectsOutputToContain('no está en el cuadro de vacantes');

    expect(Inscripcion::count())->toBe(0);
});

it('rechaza la fila cuyo lugar de inscripcion no corresponde a la modalidad de la carrera', function () {
    ['proceso' => $proceso] = prepararProceso();

    $reserva = Modalidad::factory()->conCodigoExterno(8)->create(['nombre_mod' => 'Reserva - CEPREUNU']);

    ProcesoModalidad::create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => $reserva->id_mod,
        'codigo_lugar_prm' => 594,
        'nombre_lugar_prm' => 'PUCALLPA',
    ]);

    $archivo = archivoDeInscripciones([
        FormatoOficial::fila(['CODIGO_LUGAR_INSCRIPCION' => '594']),
    ]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->expectsOutputToContain('no corresponde a la modalidad');

    expect(Inscripcion::count())->toBe(0);
});

it('rechaza el DNI que no tiene ocho digitos', function () {
    prepararProceso();

    $archivo = archivoDeInscripciones([
        FormatoOficial::fila(['NUMERO_DOCUMENTO' => '6203550']),
    ]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->expectsOutputToContain('no tiene 8 dígitos');

    expect(Postulante::count())->toBe(0);
});

it('rechaza el ubigeo que no esta en el padron', function () {
    prepararProceso();

    $archivo = archivoDeInscripciones([
        FormatoOficial::fila(['CODIGO_NACIMIENTO_UBIGEO' => '999999']),
    ]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->expectsOutputToContain('no está en el padrón de ubigeos');

    expect(Postulante::count())->toBe(0);
});

it('conserva el nombre del colegio cuando el codigo modular no esta en el padron', function () {
    prepararProceso();

    $archivo = archivoDeInscripciones([
        FormatoOficial::fila(['CODIGO_COLEGIO' => '9999999', 'NOMBRE_COLEGIO' => 'COLEGIO NUEVO']),
    ]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->assertSuccessful();

    $inscripcion = Inscripcion::sole();

    expect($inscripcion->codigo_colegio_ins)->toBeNull()
        ->and($inscripcion->nombre_colegio_ins)->toBe('COLEGIO NUEVO');
});

it('ignora las filas en blanco que arrastra el formato', function () {
    prepararProceso();

    $archivo = archivoDeInscripciones([
        FormatoOficial::fila(),
        array_fill(0, count(FormatoOficial::cabecera()), ''),
    ]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->assertSuccessful();

    expect(Inscripcion::count())->toBe(1);
});

it('exige que la oferta del proceso este cargada antes de las inscripciones', function () {
    Proceso::factory()->codigo('2027-I')->create();

    $archivo = archivoDeInscripciones([FormatoOficial::fila()]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I'])
        ->expectsOutputToContain('no tiene oferta cargada')
        ->assertFailed();
});

it('no acepta dos fichas del mismo postulante en el mismo proceso', function () {
    prepararProceso();

    $archivo = archivoDeInscripciones([
        FormatoOficial::fila(['CODIGO_CARRERA' => '2567']),
        FormatoOficial::fila(['CODIGO_CARRERA' => '2560']),
    ]);

    $this->artisan('admision:importar-inscripciones', ['archivo' => $archivo, '--proceso' => '2027-I']);

    expect(Inscripcion::count())->toBe(1);
});
