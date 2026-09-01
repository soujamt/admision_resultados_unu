<?php

use App\Models\Examen;
use App\Models\Proceso;
use App\Models\Usuario;
use Livewire\Livewire;

it('muestra el indicador de carga al cambiar de jornada', function () {
    $usuario = Usuario::factory()->administrador()->create();

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->assertSee('Cargando jornada y distribución');
});

it('permite crear una jornada de examen para el proceso seleccionado', function () {
    $proceso = Proceso::factory()->create();
    $usuario = Usuario::factory()->administrador()->create();

    Livewire::actingAs($usuario)
        ->test('pages::resultados.aulas')
        ->set('procesoSeleccionado', (string) $proceso->id_pro)
        ->set('formExamen.nombre', 'CEPRE 2027-I')
        ->set('formExamen.fecha', '2027-03-21')
        ->call('crearExamen')
        ->assertHasNoErrors();

    expect(Examen::query()->where('id_pro', $proceso->id_pro)->value('nombre_exa'))->toBe('CEPRE 2027-I');
});
