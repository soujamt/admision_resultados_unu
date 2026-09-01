@props([
    'alt' => 'Escudo de la Universidad Nacional de Ucayali',
])

{{--
    El escudo es una elipse blanca opaca con el texto en negro, asi que se lee
    igual sobre fondo claro y oscuro: no hace falta una variante para cada tema.
--}}
<img
    src="{{ asset('img/isologo-unu.png') }}"
    alt="{{ $alt }}"
    {{ $attributes->class('w-auto object-contain') }}
/>
