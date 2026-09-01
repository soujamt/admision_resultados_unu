{{-- Badge de EstadoRegistro. El color y el texto salen del propio enum. --}}
@props([
    'estado',
    'size' => 'sm',
])

<flux:badge :color="$estado->color()" :size="$size" inset="top bottom">
    {{ $estado->etiqueta() }}
</flux:badge>
