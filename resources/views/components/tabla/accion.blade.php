{{-- Boton de icono para la columna de acciones, con su tooltip. --}}
{{-- Los atributos sueltos (wire:click, wire:confirm) bajan al boton. --}}
@props([
    'tooltip',
    'icon',
    'variant' => 'subtle',
])

<flux:tooltip :content="$tooltip">
    <flux:button
        size="sm"
        :variant="$variant"
        :icon="$icon"
        :aria-label="$tooltip"
        {{ $attributes }}
    />
</flux:tooltip>
