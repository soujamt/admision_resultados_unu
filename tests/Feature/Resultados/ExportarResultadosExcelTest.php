<?php

use App\Enums\CondicionIngresante;
use App\Enums\GrupoModalidad;
use App\Exports\ResultadosExport;
use App\Models\Area;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\Ingresante;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Usuario;
use App\Models\Vacante;
use App\Services\Admision\IngresanteService;
use App\Services\Admision\ResolverResultadosService;
use App\Services\Excel\LectorXlsx;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Support\PadronDeExamen;

/**
 * Una jornada resuelta con un ingresante, un no ingresante y un NSP, que es lo
 * que el archivo tiene que mostrar junto.
 *
 * @return array{examen:Examen, vacante:Vacante}
 */
function jornadaParaExcel(): array
{
    $proceso = Proceso::factory()->codigo('2027-III')->create();
    $area = Area::factory()->create(['numero_are' => 2, 'nombre_are' => 'Ciencias de la Salud']);
    $vacante = Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => Modalidad::factory()->create([
            'grupo_mod' => GrupoModalidad::Ordinario,
            'nombre_mod' => 'Examen ordinario',
        ]),
        'id_car' => Carrera::factory()->llamada('Enfermería')->create(['id_are' => $area->id_are])->id_car,
        'id_sed' => Sede::factory()->create(['codigo_sed' => 'CORONEL_PORTILLO'])->id_sed,
        'cantidad_vac' => 1,
    ]);
    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        'nombre_exa' => 'Examen general',
        'fecha_exa' => '2027-03-21',
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'aplicar_factor_dificultad_exa' => false,
    ]);

    /* Zúñiga entra, Álvarez no llega al mínimo y Benites no se presenta. */
    foreach ([['ZÚÑIGA', 90], ['ÁLVAREZ', 40], ['BENITES', null]] as $indice => [$apellido, $aciertos]) {
        $postulante = PadronDeExamen::postulante($examen, $vacante, 500 + $indice, $aciertos);
        $postulante->inscripcion->postulante->update([
            'primer_apellido_pos' => $apellido,
            'segundo_apellido_pos' => 'PISCO',
            'nombres_pos' => 'NOMBRE '.($indice + 1),
        ]);
    }

    app(ResolverResultadosService::class)->resolver($examen);

    return ['examen' => $examen, 'vacante' => $vacante];
}

/**
 * @return list<array<int, string>>
 */
function filasDelExcel(Examen $examen): array
{
    /* Se escribe en disco y se lee de vuelta con el lector propio del proyecto. */
    $nombre = 'resultados-'.$examen->id_exa.'.xlsx';
    Excel::store(new ResultadosExport($examen), $nombre, 'local');

    $filas = [];

    foreach ((new LectorXlsx(Storage::disk('local')->path($nombre)))->filasCrudas('RESULTADOS') as $valores) {
        $filas[] = $valores;
    }

    return $filas;
}

it('exporta todos los resultados en las 64 columnas del formato', function () {
    $escenario = jornadaParaExcel();

    $filas = filasDelExcel($escenario['examen']);
    $cabecera = $filas[0];

    expect($cabecera)->toHaveCount(64)
        ->and($cabecera[0])->toBe('N°')
        ->and($cabecera[56])->toBe('PUNTAJE')
        ->and($cabecera[57])->toBe('ESTADO')
        ->and($cabecera[58])->toBe('ESTADO_EVALUACIÓN')
        ->and($cabecera[59])->toBe('ESTADO_REGLAMENTO')
        ->and($cabecera[61])->toBe('ORDEN_MERITO_GENERAL')
        ->and($cabecera[63])->toBe('ORDEN_MERITO_AREA')
        /* Los tres van en el archivo: ingresante, no ingresante y NSP. */
        ->and($filas)->toHaveCount(4);
});

it('ordena alfabéticamente y trae a los tres estados', function () {
    $escenario = jornadaParaExcel();

    $filas = array_slice(filasDelExcel($escenario['examen']), 1);
    $apellidos = array_column($filas, 5);
    $estados = array_column($filas, 58);

    /* El lector recorta las celdas vacías del final de cada fila. */
    $celda = fn (int $fila, int $columna): string => $filas[$fila][$columna] ?? '';

    expect($apellidos)->toBe(['ÁLVAREZ', 'BENITES', 'ZÚÑIGA'])
        ->and($estados)->toBe(['NO INGRESÓ', 'NSP', 'INGRESÓ'])
        /* El NSP va sin puntaje y sin orden de mérito. */
        ->and($celda(1, 56))->toBe('')
        ->and($celda(1, 61))->toBe('')
        /* El ingresante trae sus tres órdenes. */
        ->and($celda(2, 61))->toBe('1')
        ->and($celda(2, 62))->toBe('1')
        ->and($celda(2, 63))->toBe('1');
});

it('distingue al que perdió la condición de ingresante', function () {
    $escenario = jornadaParaExcel();
    app(IngresanteService::class)->generar($escenario['examen']);
    app(IngresanteService::class)->perderCondicion(
        Ingresante::firstOrFail(),
        CondicionIngresante::SinConstancia,
        'No recogió la constancia en el plazo del cronograma.',
    );

    $filas = array_slice(filasDelExcel($escenario['examen']), 1);
    $zuniga = $filas[2];

    /* La evaluación lo declaró ingresante; la condición ya no. */
    expect($zuniga[5])->toBe('ZÚÑIGA')
        ->and($zuniga[58])->toBe('INGRESÓ')
        ->and($zuniga[57])->toStartWith('NO INGRESANTE');
});

it('descarga el excel desde la pantalla', function () {
    $escenario = jornadaParaExcel();

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.excel', $escenario['examen']))
        ->assertOk()
        ->assertDownload('resultados-2027-iii-examen-general.xlsx');
});

it('responde 404 cuando la jornada no tiene resultados', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $examen = Examen::factory()->create(['id_pro' => $proceso->id_pro]);

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.excel', $examen))
        ->assertNotFound();
});
