<?php

use App\Enums\GrupoModalidad;
use App\Models\Carrera;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\Sede;
use App\Models\Usuario;
use App\Models\Vacante;

it('avisa en el cuadro cuando el año se aparta de los Arts. 14 y 16', function () {
    $carrera = Carrera::factory()->llamada('Medicina Humana')->create();
    $sede = Sede::factory()->create();
    $ordinario = Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario]);
    $cepreunu = Modalidad::factory()->create([
        'grupo_mod' => GrupoModalidad::Exoneracion,
        'codigo_mod' => 'EXO_CEPREUNU',
    ]);
    $comun = ['id_car' => $carrera->id_car, 'id_sed' => $sede->id_sed];
    $primera = Proceso::factory()->codigo('2027-I')->create();

    foreach (['I' => 60, 'II' => 20, 'III' => 20] as $romano => $cantidad) {
        $proceso = $romano === 'I' ? $primera : Proceso::factory()->codigo('2027-'.$romano)->create();
        Vacante::factory()->create($comun + [
            'id_pro' => $proceso->id_pro,
            'id_mod' => $ordinario->id_mod,
            'cantidad_vac' => $cantidad,
        ]);
    }

    Vacante::factory()->create($comun + [
        'id_pro' => $primera->id_pro,
        'id_mod' => $cepreunu->id_mod,
        'cantidad_vac' => 50,
    ]);

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('configuracion.vacantes', ['proceso' => $primera->codigo_pro]))
        ->assertOk()
        ->assertSee('Cuadro general 2027')
        ->assertSee('Revisar antes de publicar el cuadro')
        ->assertSee('Primera convocatoria')
        ->assertSee('Medicina Humana');
});

it('confirma el cumplimiento cuando el reparto y el cupo CEPREUNU están en regla', function () {
    $carrera = Carrera::factory()->create();
    $sede = Sede::factory()->create();
    $ordinario = Modalidad::factory()->create(['grupo_mod' => GrupoModalidad::Ordinario]);
    $primera = Proceso::factory()->codigo('2027-I')->create();

    foreach (['I' => 25, 'II' => 25, 'III' => 50] as $romano => $cantidad) {
        $proceso = $romano === 'I' ? $primera : Proceso::factory()->codigo('2027-'.$romano)->create();
        Vacante::factory()->create([
            'id_pro' => $proceso->id_pro,
            'id_mod' => $ordinario->id_mod,
            'id_car' => $carrera->id_car,
            'id_sed' => $sede->id_sed,
            'cantidad_vac' => $cantidad,
        ]);
    }

    $this->actingAs(Usuario::factory()->administrador()->create())
        ->get(route('configuracion.vacantes', ['proceso' => $primera->codigo_pro]))
        ->assertOk()
        ->assertSee('respetan ambos artículos')
        ->assertDontSee('Revisar antes de publicar el cuadro');
});
