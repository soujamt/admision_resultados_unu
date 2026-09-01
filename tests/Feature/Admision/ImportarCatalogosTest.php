<?php

use App\Models\Colegio;
use App\Models\IdentidadEtnica;
use App\Models\LenguaNativa;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Ubigeo;
use Tests\Support\ConstructorXlsx;

function archivoDeCatalogos(): string
{
    return (new ConstructorXlsx)
        ->hoja('PAISES', [
            ['CÓDIGO', 'DESCRIPCIÓN'],
            ['1', 'PERÚ'],
            ['2', 'AFGANISTÁN'],
        ])
        ->hoja('NACIONALIDADES', [
            ['CÓDIGO', 'DESCRIPCIÓN'],
            ['1', 'PERUANA'],
        ])
        ->hoja('UBIGEO', [
            ['CÓDIGO', 'DESCRIPCIÓN'],
            ['250101', 'UCAYALI/CORONEL PORTILLO/CALLERIA'],
            ['010202', 'AMAZONAS/BAGUA/ARAMANGO'],
        ])
        ->hoja('COLEGIOS', [
            ['CÓDIGO MODULAR', 'NOMBRE DEL COLEGIO'],
            ['0238808', '64035 AGROPECUARIO'],
        ])
        ->hoja('LENGUA NATIVA', [
            ['CÓDIGO', 'DESCRIPCIÓN'],
            ['1', 'ACHUAR'],
        ])
        ->hoja('LENGUA EXTRANJERA', [
            ['CÓDIGO', 'DESCRIPCIÓN'],
            ['1', 'ABKHAZ'],
        ])
        ->hoja('IDENTIDAD ETNICA', [
            ['CÓDIGO', 'DESCRIPCIÓN'],
            ['QUECHUA', 'QUECHUA'],
        ])
        ->escribir();
}

it('carga los maestros oficiales del archivo', function () {
    $this->artisan('admision:importar-catalogos', ['archivo' => archivoDeCatalogos()])
        ->assertSuccessful();

    expect(Pais::count())->toBe(2)
        ->and(Nacionalidad::count())->toBe(1)
        ->and(Ubigeo::count())->toBe(2)
        ->and(Colegio::count())->toBe(1)
        ->and(LenguaNativa::count())->toBe(1)
        ->and(IdentidadEtnica::count())->toBe(1);
});

it('parte la descripcion del ubigeo en departamento, provincia y distrito', function () {
    $this->artisan('admision:importar-catalogos', ['archivo' => archivoDeCatalogos()]);

    $callería = Ubigeo::where('codigo_ubi', '250101')->sole();

    expect($callería->departamento_ubi)->toBe('UCAYALI')
        ->and($callería->provincia_ubi)->toBe('CORONEL PORTILLO')
        ->and($callería->distrito_ubi)->toBe('CALLERIA');
});

it('conserva el cero de la izquierda en los codigos de ubigeo y colegio', function () {
    $this->artisan('admision:importar-catalogos', ['archivo' => archivoDeCatalogos()]);

    expect(Ubigeo::where('codigo_ubi', '010202')->exists())->toBeTrue()
        ->and(Colegio::where('codigo_modular_col', '0238808')->exists())->toBeTrue();
});

it('actualiza las descripciones sin duplicar filas al reimportar', function () {
    $archivo = archivoDeCatalogos();

    $this->artisan('admision:importar-catalogos', ['archivo' => $archivo]);

    $renombrado = (new ConstructorXlsx)
        ->hoja('PAISES', [
            ['CÓDIGO', 'DESCRIPCIÓN'],
            ['1', 'PERU'],
            ['2', 'AFGANISTÁN'],
        ])
        ->escribir();

    $this->artisan('admision:importar-catalogos', ['archivo' => $renombrado]);

    expect(Pais::count())->toBe(2)
        ->and(Pais::where('codigo_pai', 1)->value('nombre_pai'))->toBe('PERU');
});

it('falla cuando el archivo no existe', function () {
    $this->artisan('admision:importar-catalogos', ['archivo' => 'no-existe.xlsx'])
        ->assertFailed();
});
