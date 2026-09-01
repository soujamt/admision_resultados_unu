{{-- Cabecera comun de las pantallas de configuracion: titulo, bajada y acciones. --}}
@props([
    'titulo',
    'bajada' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-4']) }}>
    <div class="min-w-0">
        <flux:heading size="xl" level="1">{{ $titulo }}</flux:heading>

        @if ($bajada)
            <flux:subheading class="mt-1">{{ $bajada }}</flux:subheading>
        @endif
    </div>

    @if (isset($acciones))
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $acciones }}
        </div>
    @endif
</div>
