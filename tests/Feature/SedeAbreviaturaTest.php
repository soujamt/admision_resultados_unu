<?php

use App\Models\Sede;

it('usa la sigla oficial de la sede Coronel Portillo - Callería', function () {
    $sede = Sede::factory()->create(['codigo_sed' => 'CORONEL_PORTILLO']);

    expect($sede->abreviatura())->toBe('SCP-C');
});
