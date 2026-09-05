<?php

use App\Models\Area;
use App\Models\AsignacionExamen;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\ExamenAula;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\Postulante;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Usuario;
use App\Services\Admision\PadronAulaPdf;

/**
 * Una jornada con dos aulas sorteadas, para comprobar que el padrón por aula
 * se queda con la suya y no arrastra a la vecina.
 *
 * @param  array<int, list<array{apellido:string, nombres:string, asiento:int}>>  $porAula
 * @return array<int, ExamenAula>
 */
function jornadaConDosAulas(array $porAula): array
{
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'nombre_exa' => 'Examen CEPREUNU',
        'fecha_exa' => '2027-03-21',
    ]);
    $sede = Sede::factory()->create([
        'codigo_sed' => 'CORONEL_PORTILLO',
        'nombre_sed' => 'Sede Coronel Portillo - Callería',
    ]);
    $modalidad = Modalidad::factory()->create(['nombre_mod' => 'Exoneración - CEPREUNU']);
    $area = Area::factory()->create(['numero_are' => 1, 'nombre_are' => 'Ingenierías']);
    $carrera = Carrera::factory()->llamada('Ingeniería de Sistemas')->create(['id_are' => $area->id_are]);
    $aulas = [];
    $documento = 87654320;

    foreach ($porAula as $numero => $postulantes) {
        $aulas[$numero] = ExamenAula::factory()->create([
            'id_exa' => $examen->id_exa,
            'id_aul' => Aula::factory()->create([
                'id_sed' => $sede->id_sed,
                'codigo_aul' => 'AULA-0'.$numero,
                'nombre_aul' => 'Aula '.$numero,
                'pabellon_aul' => 'PAB I - Piso '.$numero,
                'capacidad_aul' => 40,
            ])->id_aul,
            'id_are' => $area->id_are,
            'capacidad_eau' => 40,
        ]);

        foreach ($postulantes as $fila) {
            $inscripcion = Inscripcion::factory()->create([
                'id_pro' => $proceso->id_pro,
                'id_mod' => $modalidad->id_mod,
                'id_car' => $carrera->id_car,
                'id_sed' => $sede->id_sed,
                'id_pos' => Postulante::factory()->create([
                    'numero_documento_pos' => (string) $documento++,
                    'primer_apellido_pos' => $fila['apellido'],
                    'segundo_apellido_pos' => 'PISCO',
                    'nombres_pos' => $fila['nombres'],
                ]),
                'codigo_ins' => '2027-I-'.str_pad((string) $documento, 4, '0', STR_PAD_LEFT),
                'foto_ins' => null,
            ]);
            AsignacionExamen::create([
                'id_ins' => $inscripcion->id_ins,
                'id_eau' => $aulas[$numero]->id_eau,
                'asiento_ase' => $fila['asiento'],
            ]);
        }
    }

    return $aulas;
}

it('lista solo a los postulantes del aula, en orden alfabético', function () {
    $aulas = jornadaConDosAulas([
        1 => [
            /* Sentados en desorden a propósito: el padrón va por apellido, no
               por carpeta, que es lo contrario de la lista de asistencia. */
            ['apellido' => 'ZÚÑIGA', 'nombres' => 'ZOILA', 'asiento' => 2],
            ['apellido' => 'ÁLVAREZ', 'nombres' => 'ANA', 'asiento' => 30],
            ['apellido' => 'BENITES', 'nombres' => 'BRUNO', 'asiento' => 11],
        ],
        2 => [
            ['apellido' => 'CASTILLO', 'nombres' => 'CARLOS', 'asiento' => 1],
        ],
    ]);

    $datos = app(PadronAulaPdf::class)->datos($aulas[1]);

    expect($datos['asignaciones']->pluck('inscripcion.postulante.primer_apellido_pos')->all())
        /* «ÁLVAREZ» primero pese a la tilde, y «ZÚÑIGA» al final pese a la eñe
           de más arriba en UTF-8. */
        ->toBe(['ÁLVAREZ', 'BENITES', 'ZÚÑIGA'])
        ->and($datos['asignaciones']->pluck('asiento_ase')->all())->toBe([30, 11, 2]);

    /* El aula vecina no se cuela. */
    expect(app(PadronAulaPdf::class)->datos($aulas[2])['asignaciones'])->toHaveCount(1);
});

it('conserva el formato del padrón general y añade el aula en la cabecera', function () {
    $aulas = jornadaConDosAulas([
        1 => [['apellido' => 'ÁLVAREZ', 'nombres' => 'ANA', 'asiento' => 30]],
    ]);

    $datos = app(PadronAulaPdf::class)->datos($aulas[1]);
    $html = view('pdf.padron-postulantes', $datos)->render();

    expect($datos['ubicacion'])->toBe('PUCALLPA');

    expect($html)->toContain(
        'UNIVERSIDAD NACIONAL DE UCAYALI',
        'DIRECCIÓN DE ADMISIÓN',
        'COMISIÓN CENTRAL DE ADMISIÓN',
        'MODALIDAD DE ADMISIÓN POR EXONERACIÓN - CEPREUNU',
        '2027-I',
        'Padrón de postulantes por aula',
        /* Identifica el juego de hojas, que impreso no se distingue del de
           otra aula. */
        'PAB I - Piso 1 · Aula 1',
        '21 de marzo de 2027',
    );

    /* Las mismas siete columnas del general, en el mismo orden. */
    expect($html)->toMatch('/N°<\/th>\s*<th class="col-codigo">Código<\/th>\s*<th class="col-postulante">Apellidos y nombres<\/th>\s*<th class="col-carrera">Carrera profesional<\/th>\s*<th class="col-pabellon">Pabellón<\/th>\s*<th class="col-aula">Aula<\/th>\s*<th class="col-carpeta">Carpeta<\/th>/');

    /* En las filas el pabellón y el aula van en corto —la etiqueta larga solo
       vale para la cabecera—, y la carpeta es la que salió del sorteo. */
    expect($html)->toMatch('/<td class="centro col-pabellon">I<\/td>/')
        ->and($html)->toMatch('/<td class="centro col-aula">1<\/td>/')
        ->and($html)->toMatch('/<td class="centro col-carpeta">30<\/td>/');
});

it('exporta el padrón del aula en pdf', function () {
    $aulas = jornadaConDosAulas([
        1 => [['apellido' => 'ÁLVAREZ', 'nombres' => 'ANA', 'asiento' => 30]],
    ]);

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.aulas.padron', $aulas[1]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('padron-aula-2027-i-aula-1.pdf');
});

it('exige sesión y permiso para descargar el padrón del aula', function () {
    $aulas = jornadaConDosAulas([
        1 => [['apellido' => 'ÁLVAREZ', 'nombres' => 'ANA', 'asiento' => 30]],
    ]);

    $this->get(route('resultados.aulas.padron', $aulas[1]))->assertRedirect(route('auth.login'));

    $this->actingAs(Usuario::factory()->create())
        ->get(route('resultados.aulas.padron', $aulas[1]))
        ->assertForbidden();
});

it('avisa cuando el aula todavía no tiene sorteo', function () {
    $aulas = jornadaConDosAulas([1 => []]);

    $html = view('pdf.padron-postulantes', app(PadronAulaPdf::class)->datos($aulas[1]))->render();

    expect($html)->toContain('Todavía no se ha ejecutado el sorteo de aulas para esta jornada.');
});
