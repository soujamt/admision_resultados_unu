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
use App\Services\Admision\ListaAsistenciaPdf;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Un aula con los postulantes que se le pasen, cada uno con su carrera y su
 * area, para mirar como se arma la tarjeta de asistencia.
 *
 * @param  list<array{apellido:string, nombres:string, area:int, carrera:string, asiento:int}>  $postulantes
 */
function aulaConAsistencia(array $postulantes): ExamenAula
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
    $areaAula = Area::factory()->create(['numero_are' => 5, 'nombre_are' => 'Ciencias Sociales']);
    $aulaExamen = ExamenAula::factory()->create([
        'id_exa' => $examen->id_exa,
        'id_aul' => Aula::factory()->create([
            'id_sed' => $sede->id_sed,
            'codigo_aul' => 'AULA-01',
            'nombre_aul' => '1',
            'pabellon_aul' => 'I',
            'capacidad_aul' => 40,
        ])->id_aul,
        'id_are' => $areaAula->id_are,
        'capacidad_eau' => 40,
    ]);

    foreach ($postulantes as $indice => $fila) {
        $area = Area::firstOrCreate(
            ['numero_are' => $fila['area']],
            ['nombre_are' => 'Área '.$fila['area']],
        );
        /* Varios postulantes comparten carrera, y el nombre es único. */
        $carrera = Carrera::where('nombre_car', $fila['carrera'])->first()
            ?? Carrera::factory()->llamada($fila['carrera'])->create(['id_are' => $area->id_are]);
        $inscripcion = Inscripcion::factory()->create([
            'id_pro' => $proceso->id_pro,
            'id_mod' => $modalidad->id_mod,
            'id_car' => $carrera->id_car,
            'id_sed' => $sede->id_sed,
            'id_pos' => Postulante::factory()->create([
                'numero_documento_pos' => (string) (61488570 + $indice),
                'primer_apellido_pos' => $fila['apellido'],
                'segundo_apellido_pos' => 'PISCO',
                'nombres_pos' => $fila['nombres'],
            ]),
            'codigo_ins' => '2027-I-'.str_pad((string) $indice, 4, '0', STR_PAD_LEFT),
            'foto_ins' => null,
        ]);
        AsignacionExamen::create([
            'id_ins' => $inscripcion->id_ins,
            'id_eau' => $aulaExamen->id_eau,
            'asiento_ase' => $fila['asiento'],
        ]);
    }

    return $aulaExamen;
}

it('arma la tarjeta con el área de la carrera y el código de barras del documento', function () {
    $aulaExamen = aulaConAsistencia([
        ['apellido' => 'VENTOCILLA', 'nombres' => 'WILLIAMS', 'area' => 5, 'carrera' => 'Derecho', 'asiento' => 1],
        ['apellido' => 'ÁLVAREZ', 'nombres' => 'ANA', 'area' => 1, 'carrera' => 'Ingeniería Ambiental', 'asiento' => 2],
    ]);

    $datos = app(ListaAsistenciaPdf::class)->datos($aulaExamen);
    $primera = $datos['paginas'][0][0]['izquierda'];

    expect($datos['total'])->toBe(2)
        ->and($datos['codigoProceso'])->toBe('2027-I')
        ->and($datos['ubicacion'])->toBe('PUCALLPA')
        /* Manda la carpeta, no el apellido: VENTOCILLA ocupa la 1. */
        ->and($primera['nombre'])->toBe('VENTOCILLA PISCO, WILLIAMS')
        ->and($primera['carpeta'])->toBe(1)
        ->and($primera['numero'])->toBe(1)
        ->and($primera['procedencia'])->toBe('AREA 5: SCP-C - DERECHO')
        ->and($primera['documento'])->toBe('61488570')
        ->and($primera['foto'])->toBeNull()
        ->and($primera['barras'])->toStartWith('data:image/png;base64,');
});

it('ordena las tarjetas por carpeta, no por apellido', function () {
    $aulaExamen = aulaConAsistencia([
        ['apellido' => 'ÁLVAREZ', 'nombres' => 'ANA', 'area' => 5, 'carrera' => 'Derecho', 'asiento' => 30],
        ['apellido' => 'ZÚÑIGA', 'nombres' => 'ZOILA', 'area' => 5, 'carrera' => 'Educación Inicial', 'asiento' => 2],
        ['apellido' => 'BENITES', 'nombres' => 'BRUNO', 'area' => 5, 'carrera' => 'Enfermería', 'asiento' => 11],
    ]);

    $tarjetas = collect(app(ListaAsistenciaPdf::class)->datos($aulaExamen)['paginas'][0])
        ->pluck('izquierda');

    expect($tarjetas->pluck('carpeta')->all())->toBe([2, 11, 30])
        ->and($tarjetas->pluck('nombre')->all())->toBe([
            'ZÚÑIGA PISCO, ZOILA',
            'BENITES PISCO, BRUNO',
            'ÁLVAREZ PISCO, ANA',
        ]);
});

it('el código de barras codifica el documento y se puede volver a leer', function () {
    $aulaExamen = aulaConAsistencia([
        ['apellido' => 'VENTOCILLA', 'nombres' => 'WILLIAMS', 'area' => 5, 'carrera' => 'Derecho', 'asiento' => 1],
    ]);

    $tarjeta = app(ListaAsistenciaPdf::class)->datos($aulaExamen)['paginas'][0][0]['izquierda'];
    $png = base64_decode(Str::after($tarjeta['barras'], 'base64,'));
    $esperado = (new BarcodeGeneratorPNG)->getBarcode(
        '61488570',
        BarcodeGeneratorPNG::TYPE_CODE_128,
        2,
        45,
    );

    expect($tarjeta['documento'])->toBe('61488570')
        ->and($png)->toBe($esperado)
        ->and($png)->toStartWith(chr(137).'PNG');
});

it('incrusta la foto del postulante desde el disco privado', function () {
    Storage::fake('local');
    $aulaExamen = aulaConAsistencia([
        ['apellido' => 'VENTOCILLA', 'nombres' => 'WILLIAMS', 'area' => 5, 'carrera' => 'Derecho', 'asiento' => 1],
    ]);
    $inscripcion = $aulaExamen->asignaciones->first()->inscripcion;
    $ruta = 'procesos/2027-I/fotos/61488570.jpg';
    Storage::disk('local')->put($ruta, base64_decode(
        'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
    ));
    $inscripcion->update(['foto_ins' => $ruta]);

    $tarjeta = app(ListaAsistenciaPdf::class)->datos($aulaExamen->fresh())['paginas'][0][0]['izquierda'];

    expect($tarjeta['foto'])->not->toBeNull()
        ->and($tarjeta['foto'])->toStartWith('data:image/')
        ->and($tarjeta['foto'])->toContain(';base64,');
});

it('reparte las tarjetas en columnas de cinco, bajando por la izquierda', function () {
    $postulantes = [];

    foreach (range(1, 12) as $numero) {
        $postulantes[] = [
            'apellido' => 'APELLIDO'.str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
            'nombres' => 'NOMBRE',
            'area' => 5,
            'carrera' => 'Carrera '.$numero,
            'asiento' => $numero,
        ];
    }

    $datos = app(ListaAsistenciaPdf::class)->datos(aulaConAsistencia($postulantes));
    $html = view('pdf.lista-asistencia', $datos)->render();

    expect($datos['paginas'])->toHaveCount(2)
        ->and($datos['paginas'][0])->toHaveCount(5)
        /* Primera página: la izquierda lleva 1 al 5 y la derecha 6 al 10. */
        ->and($datos['paginas'][0][0]['izquierda']['numero'])->toBe(1)
        ->and($datos['paginas'][0][0]['derecha']['numero'])->toBe(6)
        ->and($datos['paginas'][0][4]['izquierda']['numero'])->toBe(5)
        ->and($datos['paginas'][0][4]['derecha']['numero'])->toBe(10)
        /* La última página solo tiene las dos que sobran, sin columna derecha. */
        ->and($datos['paginas'][1])->toHaveCount(2)
        ->and($datos['paginas'][1][0]['izquierda']['numero'])->toBe(11)
        ->and($datos['paginas'][1][0]['derecha'])->toBeNull()
        /* Cada bloque posterior fuerza una hoja nueva sin separar su cabecera. */
        ->and(substr_count($html, 'style="page-break-before: always;"'))->toBe(1);
});

it('exporta la lista de asistencia del aula en pdf', function () {
    $aulaExamen = aulaConAsistencia([
        ['apellido' => 'VENTOCILLA', 'nombres' => 'WILLIAMS', 'area' => 5, 'carrera' => 'Derecho', 'asiento' => 1],
    ]);

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.aulas.asistencia', $aulaExamen))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('asistencia-2027-i-1.pdf');
});

it('imprime la cabecera y los rótulos del formato de asistencia', function () {
    $aulaExamen = aulaConAsistencia([
        ['apellido' => 'VENTOCILLA', 'nombres' => 'WILLIAMS', 'area' => 5, 'carrera' => 'Derecho', 'asiento' => 1],
    ]);

    $html = view('pdf.lista-asistencia', app(ListaAsistenciaPdf::class)->datos($aulaExamen))->render();

    expect($html)->toContain(
        'UNIVERSIDAD NACIONAL DE UCAYALI',
        'COMISIÓN CENTRAL DE ADMISIÓN',
        'MODALIDAD DE ADMISIÓN POR EXONERACIÓN - CEPREUNU',
        '2027-I',
        'Lista de asistencia de postulantes',
        'N° Pabellón',
        'N° Aula',
        'N° Postulantes',
        'class="celda-huella" rowspan="3"',
        'class="celda-datos" rowspan="2"',
        'class="celda-foto" rowspan="2"',
        'class="celda-firma" colspan="2"',
        'HUELLA<br>DACTILAR',
        'FIRMA',
        'Arial, Helvetica, sans-serif',
        'OBSERVACIONES',
        'FOR-PM01.02.004-V1.1',
        'SCP-C',
    );
});

it('repite cabecera, observaciones y pie en todas las hojas', function () {
    $postulantes = [];

    /* Doce tarjetas: dos hojas, la primera llena y la segunda con dos. */
    foreach (range(1, 12) as $numero) {
        $postulantes[] = [
            'apellido' => 'APELLIDO'.str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
            'nombres' => 'NOMBRE',
            'area' => 5,
            'carrera' => 'Carrera '.$numero,
            'asiento' => $numero,
        ];
    }

    $aulaExamen = aulaConAsistencia($postulantes);
    $datos = app(ListaAsistenciaPdf::class)->datos($aulaExamen);
    $html = view('pdf.lista-asistencia', $datos)->render();

    expect($datos['paginas'])->toHaveCount(2)
        /* La cabecera se emite por sección, una por hoja. */
        ->and(substr_count($html, 'class="cabecera"'))->toBe(2)
        /* Observaciones y pie van fijos: se emiten una vez y DomPDF los
           repite en cada hoja, pegados entre sí al fondo. */
        ->and(substr_count($html, 'OBSERVACIONES'))->toBe(1)
        ->and(substr_count($html, 'class="pie"'))->toBe(1)
        /* La caja vive dentro del bloque fijo, pegada al pie. */
        ->and($html)->toMatch('/\.pie \{[^}]*position: fixed/')
        ->and($html)->toMatch('/<footer class="pie">\s*<table class="observaciones">/')
        ->and($html)->toMatch('/\.pie-linea \{[^}]*border-top:/')
        ->and($html)->toContain('de 2');
});

it('deja las cinco tarjetas en una sola hoja aunque los nombres sean largos', function () {
    Storage::fake('local');
    $postulantes = [];

    foreach (range(1, 10) as $numero) {
        $postulantes[] = [
            /* Lo más largo que se ha visto en un padrón: el nombre ocupa dos
               líneas y la carrera otras dos. */
            'apellido' => 'MONTALVAN VILLANUEVA '.$numero,
            'nombres' => 'MARIA FERNANDA DEL CARMEN GUADALUPE',
            'area' => 1,
            'carrera' => 'Ingeniería en Industrias Alimentarias y Agroindustriales '.$numero,
            'asiento' => $numero,
        ];
    }

    $aulaExamen = aulaConAsistencia($postulantes);
    $lienzo = imagecreatetruecolor(300, 400);
    ob_start();
    imagejpeg($lienzo);
    $retrato = (string) ob_get_clean();

    foreach ($aulaExamen->asignaciones as $asignacion) {
        $ruta = 'procesos/2027-I/fotos/'.$asignacion->inscripcion->postulante->numero_documento_pos.'.jpg';
        Storage::disk('local')->put($ruta, $retrato);
        $asignacion->inscripcion->update(['foto_ins' => $ruta]);
    }

    $datos = app(ListaAsistenciaPdf::class)->datos($aulaExamen->fresh());
    $documento = app(ListaAsistenciaPdf::class)->documento($aulaExamen->fresh());
    $documento->output();

    /* Las diez tarjetas son una sola hoja para el servicio, y el pie anuncia
       ese total. Si la maqueta se pasa del alto útil DomPDF parte la hoja en
       dos y el pie queda mintiendo («Página 2 de 1»). */
    expect($datos['paginas'])->toHaveCount(1)
        ->and($documento->getDomPDF()->getCanvas()->get_page_count())->toBe(1);

    /* La otra mitad del trato: la celda de datos lleva altura fija para que la
       tarjeta mida igual con nombres cortos que largos. Sin ella hay que dejar
       de reserva el alto de las dos líneas de más, y las tarjetas vuelven a
       quedarse a media hoja del recuadro de observaciones. */
    expect(view('pdf.lista-asistencia', $datos)->render())
        ->toMatch('/\.celda-datos \{[^}]*height: \d/');
});
