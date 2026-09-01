@php
    $iconTrailing ??= $attributes->pluck('icon:trailing');
    $iconLeading ??= $attributes->pluck('icon:leading');
    $iconVariant ??= $attributes->pluck('icon:variant');
    $maskDynamic ??= $attributes->pluck('mask:dynamic');
@endphp

@props ([
    'name' => null,
    'iconVariant' => 'mini',
    'variant' => 'outline',
    'iconTrailing' => 'chevron-down',
    'iconLeading' => null,
    'maskDynamic' => null,
    'expandable' => null,
    'clearable' => null,
    'copyable' => null,
    'viewable' => null,
    'invalid' => null,
    'loading' => null,
    'type' => 'text',
    'mask' => null,
    'size' => null,
    'icon' => null,
    'kbd' => null,
    'options' => [],
    'multiple' => false,
])

@php
    // Detectamos si hay wire:model
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $name ??= $wireModel;

    // --- Lógica de Loading ---
    $wireObject = $attributes->wire('model');
    $wireTarget = null;

    if ($loading !== false) {
        if ($loading === true) {
            $loading = true;
        } elseif ($wireObject?->directive) {
            $loading = $wireObject->hasModifier('live');
            $wireTarget = $loading ? $wireObject->value() : null;
        } else {
            $wireTarget = $loading;
            $loading = (bool) $loading;
        }
    }

    $invalid ??= ($name && $errors->has($name));
    $iconLeading ??= $icon;
    $hasLeadingIcon = (bool) ($iconLeading);
    $isMultiSelect = (bool) $multiple;

    // ... (El resto de tus clases PHP se mantienen igual) ...
    $countOfTrailingIcons = collect([
        (bool) $iconTrailing, (bool) $kbd, (bool) $clearable,
        (bool) $copyable, (bool) $viewable, (bool) $expandable,
    ])->filter()->count();

    $iconClasses = \App\Helpers\Flux::classes()->add($iconVariant === 'outline' ? 'size-5' : '');

    $controlClasses = \App\Helpers\Flux::classes()
        ->add('w-full grid grid-cols-1 rounded-lg border disabled:shadow-none dark:shadow-none')
        ->add('appearance-none')
        ->add(match ($size) {
            default => 'text-base sm:text-sm py-2 ' . ($isMultiSelect ? 'min-h-10' : 'h-10 leading-5.5'),
            'sm' => 'text-sm py-1.5 ' . ($isMultiSelect ? 'min-h-8' : 'h-8 leading-4.5'),
            'xs' => 'text-xs py-1.5 ' . ($isMultiSelect ? 'min-h-6' : 'h-6 leading-4.5'),
        })
        ->add($hasLeadingIcon ? 'ps-10' : 'ps-3')
        ->add(match ($countOfTrailingIcons) {
            0 => 'pe-3', 1 => 'pe-10', 2 => 'pe-16', 3 => 'pe-23', 4 => 'pe-30', 5 => 'pe-37', 6 => 'pe-44',
        })
        ->add(match ($variant) {
            'outline' => 'bg-white dark:bg-white/10 dark:disabled:bg-white/7 text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500',
            'filled'  => 'bg-zinc-800/5 dark:bg-white/10 dark:disabled:bg-white/7 text-zinc-700 placeholder-zinc-500 disabled:placeholder-zinc-400 dark:text-zinc-200 dark:placeholder-white/60 dark:disabled:placeholder-white/40',
        })
        ->add(match ($variant) {
            'outline' => $invalid ? 'border-red-500' : 'shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5',
            'filled'  => $invalid ? 'border-red-500' : 'border-0',
        })
        // El contorno de foco lo pinta app.css para todos los controles a la
        // vez, con el selector [data-select-trigger].
        ->add('focus:outline-hidden')
        ->add($attributes->pluck('class:input'))
        ->add('text-left cursor-pointer overflow-hidden relative');

    $placeholder = $attributes->get('placeholder') ?? 'Seleccionar...';

    /*
     * Los desplegables de filtro van sin etiqueta visible, solo con su
     * placeholder («Toda convocatoria»). Un lector de pantalla los anunciaría
     * como «botón» a secas, así que cuando no hay etiqueta ni aria-label
     * propio, el placeholder hace de nombre accesible.
     */
    if (blank($attributes->get('label')) && blank($attributes->get('aria-label'))) {
        $attributes = $attributes->merge(['aria-label' => $placeholder]);
    }
@endphp

<flux:with-field :$attributes :$name>
    <div
        x-data="{
            open: false,
            multiple: {{ $multiple ? 'true' : 'false' }},
            options: [],
            placeholder: @js($placeholder),

            {{--
                SOLUCIÓN CLAVE:
                Usamos @entangle si existe wire:model. Esto conecta JS con PHP directamente.
                Si no hay wire:model, usamos 'null' y funcionará como select normal.
            --}}
            selected: @if($wireModel) @entangle($attributes->wire('model')) @else null @endif,

            init() {
                // Solo cargamos las OPCIONES del DOM, no el valor (el valor lo maneja entangle)
                this.syncOptionsFromDOM();

                // Si no hay wire:model, intentamos cargar el valor inicial del select HTML
                if (this.selected === null && !this.$wire) {
                   this.loadValueFromSelect();
                }

                // Sincronizar el select oculto cuando cambia 'selected' (para que funcione el submit nativo)
                this.$watch('selected', (value) => {
                    this.syncSelectVisuals();
                });

                // Si Livewire actualiza el DOM (ej: cambian las opciones dependientes), recargamos la lista
                Livewire.hook('morph.updated', () => {
                    this.$nextTick(() => {
                        this.syncOptionsFromDOM();

                        // Al elegir opciones el trigger crece (muestra las
                        // etiquetas), así que el panel abierto hay que
                        // reubicarlo con la nueva medida.
                        if (this.open) this.posicionar();
                    });
                });
            },

            syncOptionsFromDOM() {
                const select = this.$refs.realSelect;
                if (!select) return;

                this.options = Array.from(select.options)
                    .filter(o => o.value !== '' && (this.multiple || o.text.trim() !== ''))
                    .map((o) => ({ value: o.value, label: o.text }));
            },

            loadValueFromSelect() {
                const select = this.$refs.realSelect;
                if (!select) return;

                if (this.multiple) {
                    const selectedValues = Array.from(select.selectedOptions).map(o => o.value).filter(v => v !== '');
                    this.selected = selectedValues;
                } else {
                    this.selected = select.value === '' ? null : select.value;
                }
            },

            syncSelectVisuals() {
                const select = this.$refs.realSelect;
                if (!select) return;

                // Actualizamos el select oculto solo para consistencia visual/submit tradicional
                if (this.multiple) {
                    const values = Array.isArray(this.selected) ? this.selected : [];
                    Array.from(select.options).forEach(o => o.selected = values.includes(o.value));
                } else {
                    select.value = this.selected ?? '';
                }
            },

            get selectedLabels() {
                if (this.multiple) {
                    if (!Array.isArray(this.selected) || this.selected.length === 0) return this.placeholder;

                    const labels = this.options
                        .filter(opt => this.selected.some(s => s == opt.value))
                        .map(opt => opt.label || opt.value);

                    return labels.length ? labels.join(', ') : this.placeholder;
                }

                if (this.selected === null || this.selected === '') return this.placeholder;

                const opt = this.options.find(o => o.value == this.selected);
                return opt ? (opt.label || opt.value) : this.placeholder;
            },

            selectedIsEmpty() {
                if (this.multiple) return !Array.isArray(this.selected) || this.selected.length === 0;
                return this.selected === null || this.selected === '' || this.selected === undefined;
            },

            toggleOption(value) {
                if (this.multiple) {
                    // Copiamos el array para reactividad de Alpine
                    let values = Array.isArray(this.selected) ? [...this.selected] : [];
                    const index = values.indexOf(value);

                    if (index !== -1) values.splice(index, 1);
                    else values.push(value);

                    this.selected = values; // Entangle actualiza Livewire automáticamente aquí
                } else {
                    this.selected = value; // Entangle actualiza Livewire automáticamente aquí
                    this.open = false;
                }
            },

            isSelected(value) {
                if (this.multiple) {
                    return Array.isArray(this.selected) && this.selected.some(s => s == value);
                }
                return this.selected == value;
            },

            removeTag(value, event) {
                event.stopPropagation();
                if (!Array.isArray(this.selected)) return;
                this.selected = this.selected.filter(v => v != value);
            },

            /*
                Muestra u oculta el panel usando la Popover API.

                Los modales de Flux son <dialog> nativos abiertos con
                showModal(), lo que dispara TRES obstáculos a la vez para un
                dropdown corriente:

                  1. El dialog vive en el TOP LAYER del navegador, así que
                     cualquier cosa del DOM normal (incluso teletransportada a
                     <body>) se pinta por debajo, sin importar el z-index.
                  2. El dialog lleva una transform por su animación, lo que lo
                     convierte en bloque contenedor de sus descendientes
                     position:fixed y desplaza sus coordenadas.
                  3. El dialog tiene overflow:auto, así que recorta todo lo que
                     se salga de su caja.

                Un popover resuelve los tres: sube al top layer (queda encima
                del dialog), no lo recorta ningún ancestro, y su bloque
                contenedor es el viewport.

                Si el navegador no soporta la API se cae a mostrar/ocultar con
                display, que es el comportamiento previo.
            */
            get soportaPopover() {
                return typeof this.$refs.panel?.showPopover === 'function';
            },


            alternarPanel() {
                const panel = this.$refs.panel;
                if (!panel) return;

                if (this.open) {
                    if (this.soportaPopover) {
                        try { panel.showPopover(); } catch (e) { /* ya estaba abierto */ }
                    } else {
                        panel.style.display = 'block';
                    }
                    this.$nextTick(() => this.posicionar());
                } else {
                    if (this.soportaPopover) {
                        try { panel.hidePopover(); } catch (e) { /* ya estaba cerrado */ }
                    } else {
                        panel.style.display = 'none';
                    }
                }
            },

            /*
                Origen del bloque contenedor. Como popover el panel se posiciona
                contra el viewport; en el camino de respaldo (sin popover) hay
                que descontar el ancestro que cree bloque contenedor, si existe.
            */
            origenDelContenedor() {
                if (this.soportaPopover) return { x: 0, y: 0 };

                let padre = this.$refs.panel?.parentElement;

                while (padre && padre !== document.documentElement) {
                    const cs = getComputedStyle(padre);
                    const creaContenedor = cs.transform !== 'none'
                        || cs.filter !== 'none'
                        || cs.perspective !== 'none'
                        || /paint|layout|strict|content/.test(cs.contain || '')
                        || /transform|filter|perspective/.test(cs.willChange || '');

                    if (creaContenedor) {
                        // El bloque contenedor es la caja de padding: se descuentan los bordes
                        const r = padre.getBoundingClientRect();
                        return {
                            x: r.left + (parseFloat(cs.borderLeftWidth) || 0),
                            y: r.top + (parseFloat(cs.borderTopWidth) || 0),
                        };
                    }

                    padre = padre.parentElement;
                }

                return { x: 0, y: 0 };
            },

            posicionar() {
                const panel = this.$refs.panel;
                const trigger = this.$refs.trigger;
                if (!panel || !trigger) return;

                const r = trigger.getBoundingClientRect();
                const alto = panel.offsetHeight || 0;

                // Si no entra abajo pero sí arriba, se despliega hacia arriba
                const arriba = (window.innerHeight - r.bottom) < alto + 8 && r.top > alto + 8;
                const destinoY = arriba ? r.top - alto - 4 : r.bottom + 4;

                const base = this.origenDelContenedor();

                // La hoja de estilos del navegador da a los popover
                // inset:0 y margin:auto para centrarlos: hay que anularlo.
                panel.style.position = 'fixed';
                panel.style.inset = 'auto';
                panel.style.margin = '0';

                // Ancho mínimo el del disparador, pero puede crecer: si el
                // control es angosto, las etiquetas largas se leen igual.
                panel.style.width = 'auto';
                panel.style.minWidth = r.width + 'px';
                panel.style.maxWidth = Math.max(r.width, Math.min(320, window.innerWidth - r.left - 16)) + 'px';
                panel.style.left = (r.left - base.x) + 'px';
                panel.style.top = (destinoY - base.y) + 'px';
            }
        }"
        x-init="init()"
        class="group/select relative block w-full"
    >
        {{--
            SELECT REAL (oculto)
            Nota: Quitamos wire:model de aquí para evitar conflictos dobles con @entangle.
            El @entangle en x-data ya maneja la comunicación.
        --}}
        <select x-ref="realSelect" @if ($multiple) multiple @endif class="hidden">
            @if (!$multiple)
                <option value="">{{ $placeholder }}</option>
            @endif
            {{ $slot }}
        </select>

        {{-- BOTÓN TRIGGER --}}
        <button
            type="button"
            x-ref="trigger"
            data-select-trigger
            @if ($invalid) data-invalid @endif
            @click="open = !open"
            x-bind:class="{ 'border-accent!': open }"
            {{ $attributes->except(['class', 'wire:model', 'wire:model.live', 'placeholder'])->class([$controlClasses]) }}
        >
            @if (is_string($iconLeading))
                <div
                    class="pointer-events-none absolute inset-s-0 top-0 bottom-0 flex items-center justify-center ps-3 text-xs text-zinc-400/75"
                >
                    <flux:icon :icon="$iconLeading" :variant="$iconVariant" :class="$iconClasses" />
                </div>
            @elseif ($iconLeading)
                <div
                    {{ $iconLeading->attributes->class('pointer-events-none absolute top-0 bottom-0 flex items-center justify-center text-xs text-zinc-400/75 ps-3 inset-s-0') }}
                >
                    {{ $iconLeading }}
                </div>
            @endif

            {{-- MODO MULTIPLE --}}
            <template x-if="multiple">
                <div class="flex w-full flex-wrap gap-1.5 overflow-hidden py-0">
                    <template x-for="value in (Array.isArray(selected) ? selected : [])" :key="value">
                        <span
                            class="inline-flex max-w-full items-center rounded-full bg-zinc-100 py-px pe-1.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            <span
                                class="max-w-25 truncate ps-2"
                                x-text="options.find((o) => o.value == value)?.label || value"
                            ></span>
                            <button
                                type="button"
                                @click="removeTag(value, $event)"
                                class="ml-1 shrink-0 rounded-full p-0.5 hover:bg-zinc-200 focus:outline-none dark:hover:bg-zinc-600"
                                aria-label="Quitar"
                            >
                                <flux:icon.x-mark variant="micro" class="size-3" />
                            </button>
                        </span>
                    </template>
                    <template x-if="selectedIsEmpty()">
                        <span
                            class="block truncate align-middle leading-5.5 text-zinc-400 sm:leading-4.5 dark:text-white/60"
                            x-text="placeholder"
                        ></span>
                    </template>
                </div>
            </template>

            {{-- MODO SIMPLE --}}
            <template x-if="!multiple">
                <span
                    x-text="selectedLabels"
                    :class="{ 'text-zinc-400 dark:text-white/60': selectedIsEmpty() }"
                    class="block w-full truncate"
                ></span>
            </template>

            {{-- ICONOS --}}
            @if ($loading || $countOfTrailingIcons > 0)
                <div
                    class="pointer-events-none absolute inset-e-0 top-0 bottom-0 flex h-full items-center justify-end gap-x-1.5 pe-3"
                >
                    @if ($loading)
                        <flux:icon
                            name="loading"
                            :variant="$iconVariant"
                            :class="$iconClasses"
                            wire:loading
                            :wire:target="$wireTarget"
                        />
                    @endif
                    @if ($clearable)
                        <div class="pointer-events-auto"><flux:input.clearable inset="left right" :$size /></div>
                    @endif
                    @if ($kbd)
                        <span class="pointer-events-none">{{ $kbd }}</span>
                    @endif
                    @if ($expandable)
                        <div class="pointer-events-auto"><flux:input.expandable inset="left right" :$size /></div>
                    @endif
                    @if ($copyable)
                        <div class="pointer-events-auto"><flux:input.copyable inset="left right" :$size /></div>
                    @endif
                    @if ($viewable)
                        <div class="pointer-events-auto"><flux:input.viewable inset="left right" :$size /></div>
                    @endif
                    @if (is_string($iconTrailing))
                        <flux:icon
                            :icon="$iconTrailing"
                            :variant="$iconVariant"
                            class="text-zinc-400/75 transition-transform duration-200 dark:text-white/60"
                            x-bind:class="{ 'rotate-180': open }"
                        />
                    @elseif ($iconTrailing)
                        {{ $iconTrailing }}
                    @endif
                </div>
            @endif
        </button>

        {{--
            DROPDOWN

            Queda donde está en el DOM, sin x-teleport: al declararlo popover el
            navegador ya lo sube al top layer al abrirlo, así que ningún ancestro
            con overflow lo recorta y no hace falta moverlo a ninguna parte.

            Es importante NO teletransportarlo: Livewire remorfa el DOM en cada
            petición (wire:model.live, acciones que abren el modal...) y un panel
            movido fuera del componente queda huérfano en ese morph, con lo que
            el dropdown deja de existir. Ver los comentarios en x-data.
        --}}
        <div
            x-ref="panel"
            popover="manual"
            x-effect="alternarPanel()"
            @click.outside="if (! $refs.trigger.contains($event.target)) open = false"
            @scroll.window="open && posicionar()"
            @resize.window="open && posicionar()"
            class="fixed z-50 max-h-45 overflow-y-auto rounded-lg border bg-white p-0 shadow-lg dark:border-white/10 dark:bg-zinc-800"
        >
            <div class="p-1">
                <template x-if="options.length === 0">
                    <div class="p-2 text-sm text-zinc-500 dark:text-zinc-400">Sin opciones disponibles.</div>
                </template>

                <template x-for="option in options" :key="option.value">
                    <button
                        type="button"
                        @click="toggleOption(option.value)"
                        :class="{
                            'bg-zinc-100 dark:bg-zinc-900/50 text-zinc-900 dark:text-white': isSelected(option.value),
                            'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/50': !isSelected(
                                option.value,
                            ),
                        }"
                        class="flex w-full cursor-pointer items-center justify-between rounded-md p-2 text-left text-sm transition duration-150 ease-in-out focus:outline-none"
                    >
                        <span class="truncate" x-text="option.label || option.value"></span>
                        <template x-if="isSelected(option.value)">
                            <flux:icon.check variant="micro" class="size-4 shrink-0 text-accent" />
                        </template>
                    </button>
                </template>
            </div>
        </div>
    </div>
</flux:with-field>
