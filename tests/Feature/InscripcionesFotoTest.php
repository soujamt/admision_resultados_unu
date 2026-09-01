<?php

use App\Enums\Permiso;
use App\Models\Inscripcion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\Storage;

it('publica las rutas de configuración e inscripciones', function () {
    $usuario = Usuario::factory()->create([
        'id_rol' => Rol::factory()->con(Permiso::cases()),
    ]);

    $this->actingAs($usuario);

    foreach ([
        'configuracion.procesos',
        'configuracion.vacantes',
        'configuracion.facultades',
        'configuracion.areas',
        'configuracion.carreras',
        'configuracion.sedes',
        'configuracion.aulas',
        'inscripciones.index',
    ] as $ruta) {
        $this->get(route($ruta))->assertOk();
    }
});

it('sirve una foto privada a quien puede ver las inscripciones', function () {
    Storage::fake('local');
    $ruta = 'procesos/2027-I/fotos/72155069.jpg';
    Storage::disk('local')->put($ruta, 'foto-de-prueba');

    $usuario = Usuario::factory()->create([
        'id_rol' => Rol::factory()->con([Permiso::InscripcionesVer]),
    ]);
    $inscripcion = Inscripcion::factory()->conFoto($ruta)->create();

    $this->actingAs($usuario)
        ->get(route('inscripciones.foto', ['inscripcion' => encode_id($inscripcion->id_ins)]))
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=3600, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertSee('foto-de-prueba');
});

it('no expone una foto privada a un usuario sin permiso', function () {
    Storage::fake('local');
    $ruta = 'procesos/2027-I/fotos/72155069.jpg';
    Storage::disk('local')->put($ruta, 'foto-de-prueba');

    $usuario = Usuario::factory()->create();
    $inscripcion = Inscripcion::factory()->conFoto($ruta)->create();

    $this->actingAs($usuario)
        ->get(route('inscripciones.foto', ['inscripcion' => encode_id($inscripcion->id_ins)]))
        ->assertForbidden();
});
