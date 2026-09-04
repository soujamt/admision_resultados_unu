<?php

use App\Models\Aula;

it('deja solo el numeral romano del pabellón', function (?string $pabellon, ?string $esperado) {
    $aula = new Aula(['pabellon_aul' => $pabellon]);

    expect($aula->numeroDePabellon())->toBe($esperado);
})->with([
    'con piso' => ['PAB I - Piso 2', 'I'],
    'con piso y numeral doble' => ['PAB II - Piso 1', 'II'],
    'sin piso' => ['PAB III', 'III'],
    'solo el numeral' => ['I', 'I'],
    'con letra en vez de numeral' => ['PABELLON C', 'C'],
    'sin numeral reconocible' => ['Bloque Norte', 'Bloque Norte'],
    'sin pabellón' => [null, null],
]);

it('quita la palabra «Aula» del nombre, que la columna ya dice', function (string $nombre, string $esperado) {
    $aula = new Aula(['nombre_aul' => $nombre]);

    expect($aula->numeroDeAula())->toBe($esperado);
})->with([
    'con prefijo' => ['Aula 8', '8'],
    'con prefijo en mayúsculas' => ['AULA 12', '12'],
    'con sufijo de letra' => ['Aula 8B', '8B'],
    'sin prefijo' => ['9', '9'],
    'solo la palabra' => ['Aula', 'Aula'],
]);
