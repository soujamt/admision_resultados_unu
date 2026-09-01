{{-- Fila de "no hay nada que mostrar" para las tablas de configuracion. --}}
@props([
    'columnas',
    'mensaje' => 'No hay registros que mostrar.',
    'icono' => 'inbox',
])

<flux:table.row>
    <flux:table.cell :colspan="$columnas" class="py-10 text-center">
        <div class="flex flex-col items-center gap-2 text-zinc-500 dark:text-zinc-400">
            <flux:icon :icon="$icono" class="size-8 text-zinc-300 dark:text-zinc-600" />
            <span class="text-sm">{{ $mensaje }}</span>
            {{ $slot }}
        </div>
    </flux:table.cell>
</flux:table.row>
