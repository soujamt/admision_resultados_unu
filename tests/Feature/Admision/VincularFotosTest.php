<?php

use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Proceso;
use App\Services\Admision\AlmacenFotos;
use Illuminate\Support\Facades\Storage;

/**
 * Crea una ficha con el documento indicado dentro del proceso.
 */
function fichaCon(Proceso $proceso, string $documento): Inscripcion
{
    return Inscripcion::factory()->create([
        'id_pro' => $proceso->id_pro,
        'id_pos' => Postulante::factory()->create(['numero_documento_pos' => $documento]),
    ]);
}

/**
 * Deja un archivo de imagen simulado en una carpeta temporal y la devuelve.
 *
 * @param  array<string, string>  $archivos  nombre => contenido
 */
function carpetaConFotos(array $archivos): string
{
    $carpeta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fotos-'.uniqid();
    mkdir($carpeta);

    foreach ($archivos as $nombre => $contenido) {
        file_put_contents($carpeta.DIRECTORY_SEPARATOR.$nombre, $contenido);
    }

    return $carpeta;
}

beforeEach(function () {
    Storage::fake('local');
});

it('guarda las fotos en la carpeta del proceso, separadas por convocatoria', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    fichaCon($proceso, '72155069');

    $origen = carpetaConFotos(['72155069.jpg' => 'imagen']);

    $this->artisan('admision:vincular-fotos', ['--proceso' => '2027-I', '--origen' => $origen])
        ->assertSuccessful();

    Storage::disk('local')->assertExists('procesos/2027-I/fotos/72155069.jpg');

    expect(Inscripcion::sole()->foto_ins)->toBe('procesos/2027-I/fotos/72155069.jpg');
});

it('no mezcla las fotos de dos convocatorias del mismo postulante', function () {
    $primera = Proceso::factory()->codigo('2027-I')->create();
    $segunda = Proceso::factory()->codigo('2027-II')->create();

    $postulante = Postulante::factory()->create(['numero_documento_pos' => '72155069']);

    Inscripcion::factory()->create(['id_pro' => $primera->id_pro, 'id_pos' => $postulante->id_pos]);
    Inscripcion::factory()->create(['id_pro' => $segunda->id_pro, 'id_pos' => $postulante->id_pos]);

    $this->artisan('admision:vincular-fotos', [
        '--proceso' => '2027-I',
        '--origen' => carpetaConFotos(['72155069.jpg' => 'primera']),
    ]);

    $this->artisan('admision:vincular-fotos', [
        '--proceso' => '2027-II',
        '--origen' => carpetaConFotos(['72155069.jpg' => 'segunda']),
    ]);

    expect(Storage::disk('local')->get('procesos/2027-I/fotos/72155069.jpg'))->toBe('primera')
        ->and(Storage::disk('local')->get('procesos/2027-II/fotos/72155069.jpg'))->toBe('segunda');
});

it('reporta los postulantes que se quedaron sin foto', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    fichaCon($proceso, '72155069');
    fichaCon($proceso, '61549383');

    $origen = carpetaConFotos(['72155069.jpg' => 'imagen']);

    $this->artisan('admision:vincular-fotos', ['--proceso' => '2027-I', '--origen' => $origen])
        ->expectsOutputToContain('61549383');

    expect(Inscripcion::whereNotNull('foto_ins')->count())->toBe(1);
});

it('vincula lo que ya esta copiado dentro del proceso cuando no se indica origen', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    fichaCon($proceso, '72155069');

    Storage::disk('local')->put('procesos/2027-I/fotos/72155069.png', 'imagen');

    $this->artisan('admision:vincular-fotos', ['--proceso' => '2027-I'])
        ->assertSuccessful();

    expect(Inscripcion::sole()->foto_ins)->toBe('procesos/2027-I/fotos/72155069.png');
});

it('ignora los archivos que no son imagenes', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();
    fichaCon($proceso, '72155069');

    $origen = carpetaConFotos(['72155069.pdf' => 'documento']);

    $this->artisan('admision:vincular-fotos', ['--proceso' => '2027-I', '--origen' => $origen]);

    expect(Inscripcion::sole()->foto_ins)->toBeNull();
});

it('falla cuando la carpeta de origen no existe', function () {
    Proceso::factory()->codigo('2027-I')->create();

    $this->artisan('admision:vincular-fotos', ['--proceso' => '2027-I', '--origen' => 'D:\\no-existe'])
        ->assertFailed();
});

it('deja la carpeta del proceso creada y la muestra', function () {
    $proceso = Proceso::factory()->codigo('2027-I')->create();

    $carpeta = app(AlmacenFotos::class)->prepararCarpeta($proceso);

    expect($carpeta)->toContain('2027-I')
        ->and($carpeta)->toContain('fotos');

    Storage::disk('local')->assertExists('procesos/2027-I/fotos');
});
