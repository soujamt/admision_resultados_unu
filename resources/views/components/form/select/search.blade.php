@props ([
    'name' => null,
    'action', // URL de la API
    'label' => null, // Texto inicial (ej: nombre del programa al editar)
    'iconVariant' => 'mini',
    'variant' => 'outline',
    'icon' => 'magnifying-glass', // Icono por defecto (lupa)
    'placeholder' => 'Buscar...',
    'invalid' => null,
    'size' => null,
    'apiKey' => null, // <--- NUEVO PROP
])

@php
    $name ??= $attributes->whereStartsWith('wire:model')->first();
    $invalid ??= ($name && $errors->has($name));

    // --- 1. REUTILIZACIÓN EXACTA DE ESTILOS DEL SELECT NORMAL ---
    
    // Clases para el INPUT (que reemplaza al botón del select normal)
    $controlClasses = \App\Helpers\Flux::classes()
        ->add('w-full border rounded-lg block disabled:shadow-none dark:shadow-none')
        ->add('appearance-none') // Vital para que parezca un div y no un input nativo
        ->add('bg-transparent') // El fondo lo maneja el wrapper para consistencia
        ->add(match ($size) {
            default => 'text-base sm:text-sm py-2 h-10 leading-5.5',
            'sm' => 'text-sm py-1.5 h-8 leading-4.5',
            'xs' => 'text-xs py-1.5 h-6 leading-4.5',
        })
        ->add('ps-10') // Espacio para el icono de la lupa
        ->add('pe-10') // Espacio para el loading/chevron/clear
        ->add(match ($variant) {
            'outline' => 'bg-white dark:bg-white/10 dark:disabled:bg-white/7 text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500',
            'filled'  => 'bg-zinc-800/5 dark:bg-white/10 dark:disabled:bg-white/7 text-zinc-700 placeholder-zinc-500 disabled:placeholder-zinc-400 dark:text-zinc-200 dark:placeholder-white/60 dark:disabled:placeholder-white/40',
        })
        ->add(match ($variant) {
            'outline' => $invalid ? 'border-red-500' : 'shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5',
            'filled'  => $invalid ? 'border-red-500' : 'border-0',
        })
        ->add('focus:outline-hidden focus:ring-1 focus:ring-accent focus:ring-offset-1 focus:ring-offset-background')
        ->add('truncate') // Importante para que el texto largo no rompa el input
        ;

    $iconClasses = \App\Helpers\Flux::classes()->add($iconVariant === 'outline' ? 'size-5' : '');
@endphp

<flux:with-field :$attributes :$name>
    <div
        x-data="{
            show: false,
            options: [],
            value: @entangle($attributes->wire('model')), 
            search: @js($label), // Texto visible
            searching: false,
            url: @js($action),
            apiKey: @js($apiKey), // Pasamos la key a Alpine
            
            init() {
                // Si el modelo cambia externamente a null (ej. limpiar formulario), limpiamos el texto
                this.$watch('value', (val) => {
                    if (!val) this.search = '';
                });
            },

            fetchOptions() {
                if (!this.search || this.search.length < 2) {
                    this.options = [];
                    return;
                }
                this.searching = true;
                
                // Preparamos los headers
                let headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                };

                // Si hay API Key, la agregamos al header
                if (this.apiKey) {
                    headers['X-API-KEY'] = this.apiKey;
                }
                
                // Fetch a la API con headers
                fetch(this.url + '?query=' + encodeURIComponent(this.search), {
                    method: 'GET',
                    headers: headers
                })
                .then(response => {
                    if (!response.ok) throw new Error('Error en la API');
                    return response.json();
                })
                .then(data => {
                    this.options = data;
                    // Se abre incluso sin resultados: si no, buscar a alguien
                    // inexistente no daba ninguna señal en pantalla.
                    this.show = true;
                    this.searching = false;
                })
                .catch(error => {
                    console.error(error);
                    this.searching = false;
                    this.options = [];
                });
            },

            selectOption(option) {
                this.value = option.value;     // ID para el backend
                this.search = option.label;    // Texto para el usuario
                this.show = false;
            },

            clear() {
                this.value = null;
                this.search = '';
                this.options = [];
                this.show = false;
                $refs.searchInput.focus();
            },

            openSearch() {
                if (this.search && this.search.length >= 2 && this.options.length > 0) {
                    this.show = true;
                }
            }
        }"
        @click.outside="show = false"
        class="group/select relative block w-full"
    >
        {{-- WRAPPER DEL INPUT (Mantiene la estructura Grid/Relative del Select Normal) --}}
        <div class="relative grid w-full grid-cols-1">
            {{-- 1. Icono Izquierdo (Lupa) --}}
            <div
                class="pointer-events-none absolute inset-s-0 top-0 bottom-0 z-10 flex items-center justify-center border-s border-transparent ps-3 text-xs text-zinc-400/75 dark:text-white/60"
            >
                <flux:icon :icon="$icon" :variant="$iconVariant" :class="$iconClasses" />
            </div>

            {{-- 2. El Input (Actúa visualmente igual que el Botón del Select) --}}
            <input
                x-ref="searchInput"
                type="text"
                x-model="search"
                @input.debounce.400ms="fetchOptions()"
                @focus="openSearch()"
                @keydown.escape="show = false"
                placeholder="{{ $placeholder }}"
                {{-- Aplicamos las mismas clases calculadas que en el select normal --}}
                class="{{ $controlClasses }}"
                autocomplete="off"
            />

            {{-- 3. Iconos Derechos (Loading / Clear / Chevron) --}}
            <div class="absolute inset-e-0 top-0 bottom-0 flex items-center gap-x-1.5 pe-3 text-xs text-zinc-400">
                {{-- Spinner de carga --}}
                <div x-show="searching" x-cloak>
                    <flux:icon name="loading" :variant="$iconVariant" :class="$iconClasses" class="animate-spin" />
                </div>

                {{-- Botón de Limpiar (Solo si hay texto y no está cargando) --}}
                <button
                    x-show="search && !searching"
                    @click="clear()"
                    type="button"
                    class="text-zinc-400 transition-colors hover:text-red-500 focus:outline-none"
                    x-cloak
                >
                    <flux:icon name="x-mark" :variant="$iconVariant" :class="$iconClasses" />
                </button>

                {{-- Chevron (Solo decorativo para que parezca un Select, se oculta si limpiamos) --}}
                <div x-show="!search && !searching" x-cloak class="pointer-events-none">
                    <flux:icon
                        icon="chevron-down"
                        :variant="$iconVariant"
                        class="size-5 text-zinc-400/75 dark:text-white/60"
                    />
                </div>
            </div>
        </div>

        {{-- DROPDOWN DE RESULTADOS (Estilo idéntico al Select Normal) --}}
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border bg-white shadow-lg dark:border-white/10 dark:bg-zinc-800"
            x-cloak
        >
            <div class="p-1">
                {{-- Caso: Sin Resultados --}}
                <template x-if="options.length === 0 && !searching">
                    <div class="p-2 text-sm text-zinc-500 dark:text-zinc-400">No se encontraron coincidencias.</div>
                </template>

                {{-- Lista de Opciones --}}
                <template x-for="option in options" :key="option.value">
                    <button
                        type="button"
                        @click="selectOption(option)"
                        :class="{
                            'bg-zinc-100 dark:bg-zinc-900/50 text-zinc-900 dark:text-white': value == option.value,
                            'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/50':
                                value != option.value,
                        }"
                        class="flex w-full cursor-pointer items-center justify-between rounded-md p-2 text-left text-sm transition duration-150 ease-in-out focus:outline-none"
                    >
                        {{-- Truncate aquí también para seguridad --}}
                        <span class="truncate" x-text="option.label"></span>

                        <template x-if="value == option.value">
                            <flux:icon
                                name="check"
                                :variant="$iconVariant"
                                class="text-primary-600 dark:text-primary-400 h-4 w-4 shrink-0"
                            />
                        </template>
                    </button>
                </template>
            </div>
        </div>
    </div>
</flux:with-field>
