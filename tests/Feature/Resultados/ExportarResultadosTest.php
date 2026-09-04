<?php

use App\Enums\GrupoModalidad;
use App\Enums\OrdenPadronResultados;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Usuario;
use App\Models\Vacante;
use App\Services\Admision\PadronResultadosPdf;
use App\Services\Admision\ResolverResultadosService;
use Tests\Support\PadronDeExamen;

/**
 * Una jornada con dos carreras, una de ellas con el examen tan duro que
 * dispara el factor del Art. 80, para poder mirar el corte y el factor.
 *
 * @return array{examen:Examen, dificil:Carrera, facil:Carrera, vacanteDificil:Vacante}
 */
function jornadaParaExportar(): array
{
    $proceso = Proceso::factory()->codigo('2027-III')->create();
    $sede = Sede::factory()->create();
    $ordinario = Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario]);
    $dificil = Carrera::factory()->llamada('Medicina Humana')->create(['puntaje_minimo_car' => null]);
    $facil = Carrera::factory()->llamada('Agronomía')->create();

    $vacanteDificil = Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => $ordinario->id_mod,
        'id_car' => $dificil->id_car,
        'id_sed' => $sede->id_sed,
        'cantidad_vac' => 2,
    ]);
    $vacanteFacil = Vacante::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_mod' => $ordinario->id_mod,
        'id_car' => $facil->id_car,
        'id_sed' => $sede->id_sed,
        'cantidad_vac' => 8,
    ]);

    $examen = Examen::factory()->create([
        'id_pro' => $proceso->id_pro,
        /* Fijo, porque el nombre entra en el archivo que se descarga. */
        'nombre_exa' => 'Examen general',
        'puntaje_error_exa' => 0,
        'puntaje_blanco_exa' => 0,
        'puntaje_minimo_exa' => 50,
        'umbral_factor_dificultad_exa' => 40,
        'aplicar_factor_dificultad_exa' => true,
    ]);

    PadronDeExamen::postulante($examen, $vacanteDificil, 401, 40);
    PadronDeExamen::postulante($examen, $vacanteDificil, 402, 38);
    PadronDeExamen::postulante($examen, $vacanteDificil, 403, 20);
    PadronDeExamen::postulante($examen, $vacanteFacil, 404, 60);
    PadronDeExamen::postulante($examen, $vacanteFacil, 405, null);

    app(ResolverResultadosService::class)->resolver($examen);

    return compact('examen', 'dificil', 'facil', 'vacanteDificil');
}

it('lista solo la carrera pedida, en su orden de mérito', function () {
    $escenario = jornadaParaExportar();

    $datos = app(PadronResultadosPdf::class)->datos($escenario['examen'], $escenario['dificil']->id_car);

    expect($datos['resultados']->count())->toBe(3)
        ->and($datos['resultados']->pluck('orden_carrera_res')->all())->toBe([1, 2, 3])
        ->and($datos['esPorCarrera'])->toBeTrue()
        ->and($datos['tituloListado'])->toBe('Por carrera profesional');
});

it('el listado general separa las carreras y conserva los NSP en su carrera', function () {
    $escenario = jornadaParaExportar();

    $datos = app(PadronResultadosPdf::class)->datos($escenario['examen']);
    $seccionDificil = $datos['secciones']->first(
        fn (array $seccion) => $seccion['resultados']->first()->postulante->inscripcion->id_car === $escenario['dificil']->id_car,
    );
    $seccionFacil = $datos['secciones']->first(
        fn (array $seccion) => $seccion['resultados']->first()->postulante->inscripcion->id_car === $escenario['facil']->id_car,
    );
    $documento = app(PadronResultadosPdf::class)->documento($escenario['examen']);
    $documento->render();

    expect($datos['resultados']->count())->toBe(5)
        ->and($datos['secciones'])->toHaveCount(2)
        ->and($seccionDificil['resultados']->pluck('orden_carrera_res')->all())->toBe([1, 2, 3])
        ->and($seccionFacil['resultados']->last()->orden_general_res)->toBeNull()
        ->and($documento->getDomPDF()->getCanvas()->get_page_count())->toBe(2)
        ->and($datos['esPorCarrera'])->toBeFalse()
        ->and($datos['tituloListado'])->toBe('Por carrera profesional');
});

it('publica un solo listado alfabético con todas las carreras', function () {
    $escenario = jornadaParaExportar();

    $datos = app(PadronResultadosPdf::class)->datos(
        $escenario['examen'],
        orden: OrdenPadronResultados::Alfabetico,
    );
    $nombres = $datos['resultados']->pluck('postulante.nombre_exp');

    expect($datos['tituloListado'])->toBe('Por orden alfabético')
        /* Una sola sección: no se corta por carrera. */
        ->and($datos['secciones'])->toHaveCount(1)
        ->and($datos['secciones'][0]['resultados'])->toHaveCount(5)
        /* Alfabético de verdad, con las tildes en su sitio. */
        ->and($nombres->all())->toBe($nombres->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all())
        /* Los NSP también figuran, con su puntaje vacío. */
        ->and($datos['resultados']->whereNull('puntaje_res'))->toHaveCount(1);
});

it('publica un solo listado por orden de mérito de todas las carreras', function () {
    $escenario = jornadaParaExportar();

    $datos = app(PadronResultadosPdf::class)->datos(
        $escenario['examen'],
        orden: OrdenPadronResultados::Merito,
    );
    $ordenes = $datos['secciones'][0]['resultados']
        ->pluck('orden_general_res')
        ->filter()
        ->values();

    expect($datos['tituloListado'])->toBe('Por orden de mérito')
        ->and($datos['secciones'])->toHaveCount(1)
        ->and($datos['secciones'][0]['resultados'])->toHaveCount(5)
        /* De mejor a peor, y el NSP sin orden queda al final. */
        ->and($ordenes->all())->toBe($ordenes->sort()->values()->all())
        ->and($datos['secciones'][0]['resultados']->last()->orden_general_res)->toBeNull();
});

it('exporta cada orden con su propio nombre de archivo', function () {
    $escenario = jornadaParaExportar();
    $usuario = Usuario::factory()->administrador()->create();

    $this->actingAs($usuario)
        ->get(route('resultados.pdf', ['examen' => $escenario['examen'], 'orden' => 'alfabetico']))
        ->assertOk()
        ->assertDownload('resultados-2027-iii-examen-general-orden-alfabetico.pdf');

    $this->actingAs($usuario)
        ->get(route('resultados.pdf', ['examen' => $escenario['examen'], 'orden' => 'merito']))
        ->assertOk()
        ->assertDownload('resultados-2027-iii-examen-general-orden-merito.pdf');
});

it('exporta el pdf de una sola carrera', function () {
    $escenario = jornadaParaExportar();

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.pdf', ['examen' => $escenario['examen'], 'carrera' => $escenario['dificil']->id_car]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload();
});

it('entrega el juego del Art. 84 con el general y un pdf por carrera', function () {
    $escenario = jornadaParaExportar();

    $respuesta = $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.pdf.juego', $escenario['examen']))
        ->assertOk()
        ->assertHeader('content-type', 'application/zip');

    $zip = new ZipArchive;
    $zip->open($respuesta->baseResponse->getFile()->getPathname());
    $nombres = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $nombres[] = $zip->getNameIndex($i);
    }

    $zip->close();

    expect($nombres)->toHaveCount(3)
        ->and($nombres[0])->toBe('00-general.pdf')
        ->and(implode(' ', $nombres))->toContain('agronomia')
        ->and(implode(' ', $nombres))->toContain('medicina-humana');
});

it('responde 404 cuando la jornada no tiene resultados', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    $examen = Examen::factory()->create(['id_pro' => $proceso->id_pro]);

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('resultados.pdf.juego', $examen))
        ->assertNotFound();
});
