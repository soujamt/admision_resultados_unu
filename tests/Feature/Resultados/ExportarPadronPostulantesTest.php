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
use App\Services\Admision\PadronPostulantesPdf;

/**
 * Una jornada con dos aulas de pabellones distintos, para comprobar que el
 * padrón las mezcla en una sola lista alfabética en vez de agruparlas.
 *
 * @param  list<array{apellido:string, nombres:string, aula:int, asiento:int}>  $postulantes
 * @return array{examen:Examen, modalidad:Modalidad}
 */
function jornadaConSorteo(array $postulantes): array
{
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'nombre_exa' => 'Examen CEPREUNU',
        'fecha_exa' => '2027-03-21',
    ]);
    $area = Area::factory()->create(['numero_are' => 2, 'nombre_are' => 'Ciencias de la Salud']);
    $modalidad = Modalidad::factory()->create(['nombre_mod' => 'Exoneración - CEPREUNU']);
    $carrera = Carrera::factory()->llamada('Ingeniería de Sistemas')->create(['id_are' => $area->id_are]);
    $sede = Sede::factory()->create([
        'codigo_sed' => 'CORONEL_PORTILLO',
        'nombre_sed' => 'Sede Coronel Portillo - Callería',
    ]);

    $aulas = [];

    /* Los pabellones llegan con prefijo y piso, como en el maestro real. */
    foreach ([1 => 'PAB I - Piso 2', 2 => 'PAB II - Piso 1'] as $numero => $pabellon) {
        $aulas[$numero] = ExamenAula::factory()->create([
            'id_exa' => $examen->id_exa,
            'id_aul' => Aula::factory()->create([
                'id_sed' => $sede->id_sed,
                'codigo_aul' => 'AULA-0'.$numero,
                'nombre_aul' => 'Aula '.$numero,
                'pabellon_aul' => $pabellon,
                'capacidad_aul' => 40,
            ])->id_aul,
            'id_are' => $area->id_are,
            'capacidad_eau' => 40,
        ]);
    }

    foreach ($postulantes as $indice => $fila) {
        $inscripcion = Inscripcion::factory()->create([
            'id_pro' => $examen->id_pro,
            'id_mod' => $modalidad->id_mod,
            'id_car' => $carrera->id_car,
            'id_sed' => $sede->id_sed,
            'id_pos' => Postulante::factory()->create([
                'numero_documento_pos' => (string) (87654320 + $indice),
                'primer_apellido_pos' => $fila['apellido'],
                'segundo_apellido_pos' => 'ROJAS',
                'nombres_pos' => $fila['nombres'],
            ]),
            'codigo_ins' => '2027-I-'.str_pad((string) $indice, 4, '0', STR_PAD_LEFT),
        ]);
        AsignacionExamen::create([
            'id_ins' => $inscripcion->id_ins,
            'id_eau' => $aulas[$fila['aula']]->id_eau,
            'asiento_ase' => $fila['asiento'],
        ]);
    }

    return ['examen' => $examen, 'modalidad' => $modalidad];
}

it('lista a todos los postulantes de la jornada en un solo orden alfabético', function () {
    $escenario = jornadaConSorteo([
        ['apellido' => 'ZÚÑIGA', 'nombres' => 'ZOILA', 'aula' => 1, 'asiento' => 5],
        ['apellido' => 'ÁLVAREZ', 'nombres' => 'ANA', 'aula' => 2, 'asiento' => 12],
        ['apellido' => 'BENITES', 'nombres' => 'BRUNO', 'aula' => 1, 'asiento' => 3],
    ]);

    $respuesta = $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.padron', $escenario['examen']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('padron-2027-i-examen-cepreunu.pdf');

    expect($respuesta)->not->toBeNull();
});

it('arma la cabecera y las columnas del formato oficial', function () {
    $escenario = jornadaConSorteo([
        ['apellido' => 'ZÚÑIGA', 'nombres' => 'ZOILA', 'aula' => 1, 'asiento' => 5],
        ['apellido' => 'ÁLVAREZ', 'nombres' => 'ANA', 'aula' => 2, 'asiento' => 12],
        ['apellido' => 'BENITES', 'nombres' => 'BRUNO', 'aula' => 1, 'asiento' => 3],
    ]);
    $datos = app(PadronPostulantesPdf::class)->datos($escenario['examen']);
    $html = view('pdf.padron-postulantes', $datos)->render();

    expect($datos['ubicacion'])->toBe('PUCALLPA');

    expect($html)->toContain(
        'UNIVERSIDAD NACIONAL DE UCAYALI',
        'VICERRECTORADO ACADÉMICO',
        'DIRECCIÓN DE ADMISIÓN',
        'COMISIÓN CENTRAL DE ADMISIÓN',
        /* Cierra con la modalidad y el código del proceso. */
        'MODALIDAD DE ADMISIÓN POR EXONERACIÓN - CEPREUNU',
        '2027-I',
        'Padrón general de postulantes',
        'isologo-unu.png',
    );

    /* La cabecera va fija para repetirse en todas las hojas. */
    expect($html)->toMatch('/\.cabecera \{[^}]*position: fixed/');

    /* Las cinco columnas del formato, en orden y sin la de documento. */
    expect($html)->toMatch('/N°<\/th>\s*<th class="col-codigo">Código<\/th>\s*<th class="col-postulante">Apellidos y nombres<\/th>\s*<th class="col-carrera">Carrera profesional<\/th>\s*<th class="col-pabellon">Pabellón<\/th>\s*<th class="col-aula">Aula<\/th>\s*<th class="col-carpeta">Carpeta<\/th>/');

    /* El código es el documento y la carrera va con la sede y el nombre corto. */
    expect($html)->toContain('87654320', 'SCP-C -', 'INGENIERÍA DE SISTEMAS')
        ->and($html)->toContain('Arial, Helvetica, sans-serif');

    /* Fecha de la jornada, con la línea colgando de ella. En el HTML el
       Blade la parte en dos líneas; el PDF la junta al maquetar. */
    expect($html)->toMatch('/class="fecha">\s*Pucallpa,\s*21 de marzo de 2027\s*<\/div>/')
        ->and($html)->toMatch('/\.fecha \{[^}]*border-bottom:[^}]*\}/');

    /* Sombreado de la cabecera y de la columna del correlativo. No se fija el
       tono: lo que importa es que ambas sigan sombreadas. */
    expect($html)->toMatch('/\.listado th \{[^}]*background: #[0-9A-Fa-f]{3,6}/')
        ->and($html)->toMatch('/\.col-numero \{[^}]*background: #[0-9A-Fa-f]{3,6}/');

    /* Alfabético entre aulas: Álvarez (Aula 2) va antes que Benites (Aula 1). */
    expect(mb_strpos($html, 'ÁLVAREZ'))->toBeLessThan(mb_strpos($html, 'BENITES'))
        ->and(mb_strpos($html, 'BENITES'))->toBeLessThan(mb_strpos($html, 'ZÚÑIGA'));

    /* El pabellón se reduce al numeral y el aula pierde la palabra «Aula». */
    expect($html)->toContain('>I<', '>II<', '>1<', '>2<')
        ->and($html)->not->toContain('PAB I - Piso 2', 'Aula 1');
});

it('escribe «setiembre» a la peruana en la fecha de la cabecera', function () {
    $escenario = jornadaConSorteo([
        ['apellido' => 'ÁLVAREZ', 'nombres' => 'ANA', 'aula' => 1, 'asiento' => 3],
    ]);
    $escenario['examen']->update(['fecha_exa' => '2027-09-19']);

    $html = view('pdf.padron-postulantes', app(PadronPostulantesPdf::class)->datos($escenario['examen']->fresh()))->render();

    expect($html)->toContain('19 de setiembre de 2027')
        ->and($html)->not->toContain('septiembre');
});

it('avisa cuando la jornada todavía no tiene sorteo', function () {
    $escenario = jornadaConSorteo([]);

    $html = view('pdf.padron-postulantes', [
        'examen' => $escenario['examen']->load('proceso'),
        'asignaciones' => collect(),
        'modalidadCabecera' => $escenario['examen']->nombre_exa,
        'ubicacion' => 'PUCALLPA',
    ])->render();

    expect($html)->toContain('Todavía no se ha ejecutado el sorteo de aulas para esta jornada.');
});
