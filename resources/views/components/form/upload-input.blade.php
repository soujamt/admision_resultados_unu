{{-- Input de archivo nativo con barra de progreso (eventos de Livewire). --}}
{{-- Para formularios que muestran el input tal cual, sin dropzone: ahi va
     x-form.upload-dropzone. --}}
@props ([
    'model',              // propiedad wire del archivo (ej: 'form.firma')
    'accept' => null,
    'multiple' => false,
    'etiqueta' => 'Subiendo',
])

{{-- Los eventos de subida se disparan sobre el propio input y suben por el
     DOM, asi que con un contenedor por campo cada barra refleja solo lo suyo
     aunque el formulario tenga varios archivos. --}}
<div
    x-data="{ subiendo: false, progreso: 0 }"
    x-on:livewire-upload-start="
        subiendo = true;
        progreso = 0;
    "
    x-on:livewire-upload-finish="subiendo = false"
    x-on:livewire-upload-error="subiendo = false"
    x-on:livewire-upload-cancel="subiendo = false"
    x-on:livewire-upload-progress="progreso = $event.detail.progress"
>
    <input
        type="file"
        wire:model="{{ $model }}"
        @if ($accept) accept="{{ $accept }}" @endif
        @if ($multiple) multiple @endif
        x-bind:disabled="subiendo"
        {{ $attributes->merge(['class' => 'block w-full text-sm text-zinc-500 dark:text-zinc-400 file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-700 dark:file:text-zinc-200 dark:hover:file:bg-zinc-600']) }}
    />

    <div x-cloak x-show="subiendo" class="mt-2 flex items-center gap-3">
        <flux:icon.loading class="size-4 shrink-0 text-blue-600 dark:text-blue-300" />
        <span class="shrink-0 text-xs font-medium text-zinc-600 dark:text-zinc-300">
            {{ $etiqueta }}... <span x-text="progreso + '%'"></span>
        </span>
        <div class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
            <div
                class="h-full rounded-full bg-linear-to-r from-blue-500 to-cyan-400 transition-all duration-150"
                x-bind:style="`width: ${progreso}%`"
            ></div>
        </div>
    </div>
</div>
