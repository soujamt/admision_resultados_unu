<?php

use App\Enums\Permiso;
use App\Models\Area;
use App\Models\AsignacionExamen;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\ExamenAula;
use App\Models\Inscripcion;
use App\Models\Rol;
use App\Models\Usuario;
use Livewire\Livewire;

/**
 * Deja lista una jornada con un aula habilitada y un área, que es el punto de
 * partida de casi todas las comprobaciones de esta pantalla.
 *
 * @return array{examen: Examen, aula: Aula, area: Area, usuario: Usuario}
 */
function jornadaConAula(int $capacidadAula = 40): array
{
    $examen = Examen::factory()->create();
    $aula = Aula::factory()->create(['capacidad_aul' => $capacidadAula]);
    $area = Area::factory()->create();
    $usuario = Usuario::factory()->administrador()->create();

    return compact('examen', 'aula', 'area', 'usuario');
}

it('agrega un aula a la distribución de la jornada', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula();

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.aula', $aula->id_aul)
        ->set('formAula.area', $area->id_are)
        ->set('formAula.capacidad', 24)
        ->call('agregarAula')
        ->assertHasNoErrors();

    expect(ExamenAula::where('id_exa', $examen->id_exa)->count())->toBe(1);
});

it('deja el formulario vacío después de agregar, para que el aula anterior no quede seleccionada', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula();

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.aula', $aula->id_aul)
        ->set('formAula.area', $area->id_are)
        ->set('formAula.capacidad', 24)
        ->call('agregarAula')
        ->assertSet('formAula.aula', null)
        ->assertSet('formAula.area', null)
        ->assertSet('formAula.capacidad', null);
});

it('mantiene las aulas en el desplegable y marca las asignadas', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula();
    Aula::factory()->create();

    $componente = Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa);

    $componente->set('formAula.aula', $aula->id_aul)
        ->set('formAula.area', $area->id_are)
        ->set('formAula.capacidad', 24)
        ->call('agregarAula');

    expect($componente->viewData('aulas')->pluck('id_aul'))->toContain($aula->id_aul)
        ->and($componente->viewData('aulasAsignadas'))->toHaveKey($aula->id_aul);
});

it('no impone una capacidad fija antes de que el usuario distribuya el aula', function () {
    ['examen' => $examen, 'aula' => $aula, 'usuario' => $usuario] = jornadaConAula(capacidadAula: 24);

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.aula', $aula->id_aul)
        ->assertSet('formAula.capacidad', null);
});

it('permite más de cuarenta si esa es la capacidad registrada del aula', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula(capacidadAula: 50);

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.aula', $aula->id_aul)
        ->set('formAula.area', $area->id_are)
        ->set('formAula.capacidad', 42)
        ->call('agregarAula')
        ->assertHasNoErrors();

    expect(ExamenAula::query()
        ->where('id_exa', $examen->id_exa)
        ->value('capacidad_eau'))->toBe(42);
});

it('señala el campo de postulantes cuando se pide más de lo que cabe en el aula', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula(capacidadAula: 24);

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.aula', $aula->id_aul)
        ->set('formAula.area', $area->id_are)
        ->set('formAula.capacidad', 40)
        ->call('agregarAula')
        ->assertHasErrors('formAula.capacidad')
        ->assertHasNoErrors('formAula.aula')
        ->assertSee('permite hasta 24 postulantes');

    expect(ExamenAula::where('id_exa', $examen->id_exa)->count())->toBe(0);
});

it('señala el campo del aula cuando ya está en la distribución', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula();

    ExamenAula::create([
        'id_exa' => $examen->id_exa,
        'id_aul' => $aula->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 24,
    ]);

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.aula', $aula->id_aul)
        ->set('formAula.area', $area->id_are)
        ->set('formAula.capacidad', 10)
        ->call('agregarAula')
        ->assertHasErrors('formAula.aula');

    expect(ExamenAula::where('id_exa', $examen->id_exa)->count())->toBe(1);
});

it('deshabilita en el desplegable las aulas que ya están asignadas', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula();

    ExamenAula::create([
        'id_exa' => $examen->id_exa,
        'id_aul' => $aula->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 24,
    ]);

    $componente = Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa);

    expect($componente->viewData('aulas')->pluck('id_aul'))->toContain($aula->id_aul)
        ->and($componente->viewData('aulasAsignadas'))->toHaveKey($aula->id_aul);
});

it('mantiene una opción vacía real aunque las primeras aulas estén asignadas', function () {
    ['examen' => $examen, 'aula' => $primeraAula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula();
    $primeraAula->update(['orden_aul' => 1]);
    $aulas = collect([$primeraAula])->concat(Aula::factory()
        ->count(2)
        ->sequence(['orden_aul' => 2], ['orden_aul' => 3])
        ->create());

    foreach ($aulas->take(2) as $aula) {
        ExamenAula::create([
            'id_exa' => $examen->id_exa,
            'id_aul' => $aula->id_aul,
            'id_are' => $area->id_are,
            'capacidad_eau' => 24,
        ]);
    }

    $html = Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->html();

    $encontrada = preg_match(
        '/<option\s+[^>]*value=""[^>]*>\s*Elige un aula\s*<\/option>/s',
        $html,
        $opcionVacia,
    );

    expect($encontrada)->toBe(1)
        ->and($opcionVacia[0])->not->toContain('disabled');
});

it('limpia el formulario al retirar un aula, porque vuelve al desplegable', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula();

    $fila = ExamenAula::create([
        'id_exa' => $examen->id_exa,
        'id_aul' => $aula->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 24,
    ]);

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.aula', $aula->id_aul)
        ->call('retirarAula', $fila->id_eau)
        ->assertSet('formAula.aula', null);

    expect(ExamenAula::where('id_exa', $examen->id_exa)->count())->toBe(0);
});

it('vacía el formulario al cambiar de jornada', function () {
    ['examen' => $examen, 'aula' => $aula, 'usuario' => $usuario] = jornadaConAula();
    $otra = Examen::factory()->create(['id_pro' => $examen->id_pro]);

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.aula', $aula->id_aul)
        ->set('examenSeleccionado', (string) $otra->id_exa)
        ->assertSet('formAula.aula', null);
});

it('muestra el nombre del área en los totales, no su id', function () {
    $examen = Examen::factory()->create();
    $area = Area::factory()->create(['numero_are' => 2, 'nombre_are' => 'Ciencias de la Salud']);
    $carrera = Carrera::factory()->create(['id_are' => $area->id_are]);
    Inscripcion::factory()->count(3)->create(['id_pro' => $examen->id_pro, 'id_car' => $carrera->id_car]);

    Livewire::actingAs(Usuario::factory()->administrador()->create())
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->assertSee('Área 2: Ciencias de la Salud');
});

it('explica qué área está descuadrada en vez de dejar sortear', function () {
    $examen = Examen::factory()->create();
    $area = Area::factory()->create(['numero_are' => 1, 'nombre_are' => 'Ciencias Agrarias y del Ambiente']);
    $carrera = Carrera::factory()->create(['id_are' => $area->id_are]);
    Inscripcion::factory()->count(10)->create(['id_pro' => $examen->id_pro, 'id_car' => $carrera->id_car]);

    ExamenAula::create([
        'id_exa' => $examen->id_exa,
        'id_aul' => Aula::factory()->create()->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 4,
    ]);

    Livewire::actingAs(Usuario::factory()->administrador()->create())
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->assertSee('faltan 6 cupos');
});

it('sortea desde la pantalla antes de importar el padrón TXT', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula(capacidadAula: 3);
    $carrera = Carrera::factory()->create(['id_are' => $area->id_are]);
    Inscripcion::factory()->count(3)->create([
        'id_pro' => $examen->id_pro,
        'id_car' => $carrera->id_car,
    ]);
    ExamenAula::create([
        'id_exa' => $examen->id_exa,
        'id_aul' => $aula->id_aul,
        'id_are' => $area->id_are,
        'capacidad_eau' => 3,
    ]);

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->call('sortear');

    expect(AsignacionExamen::query()->count())->toBe(3);
});

it('pide elegir una jornada antes de agregar aulas', function () {
    ['aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula();

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', '')
        ->set('formAula.aula', $aula->id_aul)
        ->set('formAula.area', $area->id_are)
        ->set('formAula.capacidad', 24)
        ->call('agregarAula')
        ->assertHasErrors('examenSeleccionado');
});

it('exige el aula y el área antes de guardar', function () {
    ['examen' => $examen, 'usuario' => $usuario] = jornadaConAula();

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->call('agregarAula')
        ->assertHasErrors(['formAula.aula' => 'required', 'formAula.area' => 'required']);
});

it('no deja configurar aulas a quien solo puede mirar', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area] = jornadaConAula();
    $mirón = Usuario::factory()->for(Rol::factory()->con([Permiso::ResultadosVer]), 'rol')->create();

    Livewire::actingAs($mirón)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.aula', $aula->id_aul)
        ->set('formAula.area', $area->id_are)
        ->set('formAula.capacidad', 24)
        ->call('agregarAula')
        ->assertForbidden();
});

it('borra el error del aula en cuanto se elige una', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula();

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.area', $area->id_are)
        ->set('formAula.capacidad', 24)
        /* Sin aula: falla y deja el mensaje en pantalla. */
        ->call('agregarAula')
        ->assertHasErrors('formAula.aula')
        /* Al corregirlo el mensaje tiene que desaparecer solo. */
        ->set('formAula.aula', $aula->id_aul)
        ->assertHasNoErrors('formAula.aula');
});

it('borra el error del área en cuanto se elige una', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula();

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.aula', $aula->id_aul)
        ->call('agregarAula')
        ->assertHasErrors('formAula.area')
        ->set('formAula.area', $area->id_are)
        ->assertHasNoErrors('formAula.area');
});

it('guarda a la primera después de corregir el campo que faltaba', function () {
    ['examen' => $examen, 'aula' => $aula, 'area' => $area, 'usuario' => $usuario] = jornadaConAula();

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.area', $area->id_are)
        ->call('agregarAula')
        ->assertHasErrors('formAula.aula')
        ->set('formAula.aula', $aula->id_aul)
        ->set('formAula.capacidad', 24)
        ->call('agregarAula')
        ->assertHasNoErrors();

    expect(ExamenAula::where('id_exa', $examen->id_exa)->count())->toBe(1);
});

it('respeta la cantidad que el usuario escribió antes de elegir el aula', function () {
    ['examen' => $examen, 'aula' => $aula, 'usuario' => $usuario] = jornadaConAula(capacidadAula: 50);

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.capacidad', 33)
        ->set('formAula.aula', $aula->id_aul)
        ->assertSet('formAula.capacidad', 33);
});

it('rechaza la cantidad cuando no cabe en el aula elegida', function () {
    ['examen' => $examen, 'aula' => $aula, 'usuario' => $usuario] = jornadaConAula(capacidadAula: 24);

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('examenSeleccionado', (string) $examen->id_exa)
        ->set('formAula.capacidad', 40)
        ->set('formAula.aula', $aula->id_aul)
        ->set('formAula.area', Area::factory()->create()->id_are)
        ->call('agregarAula')
        ->assertHasErrors('formAula.capacidad');
});
