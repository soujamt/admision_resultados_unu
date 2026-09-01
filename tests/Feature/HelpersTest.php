<?php

it('codifica y decodifica un id', function () {
    $hash = encode_id(42);

    expect($hash)->not->toBe('42')
        ->and(decode_id($hash))->toBe(42);
});

it('devuelve null ante un hash vacio o invalido', function () {
    expect(decode_id(null))->toBeNull()
        ->and(decode_id(''))->toBeNull()
        ->and(decode_id('no-es-un-hash'))->toBeNull();
});

it('normaliza un texto quitando tildes, signos y espacios sobrantes', function () {
    expect(normalizar_texto('Educación Secundaria: Especialidad Idioma Inglés'))
        ->toBe('EDUCACION SECUNDARIA ESPECIALIDAD IDIOMA INGLES');
});

it('lleva al mismo valor dos formas de escribir la misma carrera', function () {
    expect(normalizar_texto('Economía y Negocios Internacionales'))
        ->toBe(normalizar_texto('ECONOMIA  Y  NEGOCIOS - INTERNACIONALES'));
});

it('normaliza a cadena vacia lo que no tiene contenido', function () {
    expect(normalizar_texto(null))->toBe('')
        ->and(normalizar_texto('   '))->toBe('')
        ->and(normalizar_texto('---'))->toBe('');
});
