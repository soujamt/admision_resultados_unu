@props ([
    'name' => null,
    'iconVariant' => 'mini',
    'variant' => 'outline',
    'icon' => 'magnifying-glass',
    'placeholder' => 'Seleccionar...',
    'invalid' => null,
    'size' => null,
])

@php
    $name ??= $attributes->whereStartsWith('wire:model')->first();
    $invalid ??= ($name && $errors->has($name));

    // Tus estilos se mantienen igual...
    $controlClasses = \App\Helpers\Flux::classes()
        ->add('w-full border rounded-lg block disabled:shadow-none dark:shadow-none')
        ->add('appearance-none bg-transparent')
        ->add(match ($size) {
            default => 'text-base sm:text-sm py-2 h-10 leading-5.5',
            'sm' => 'text-sm py-1.5 h-8 leading-4.5',
            'xs' => 'text-xs py-1.5 h-6 leading-4.5',
        })
        ->add('ps-10 pe-10')
        ->add(match ($variant) {
            'outline' => 'bg-white dark:bg-white/10 dark:disabled:bg-white/7 text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500',
            'filled'  => 'bg-zinc-800/5 dark:bg-white/10 dark:disabled:bg-white/7 text-zinc-700 placeholder-zinc-500 disabled:placeholder-zinc-400 dark:text-zinc-200 dark:placeholder-white/60 dark:disabled:placeholder-white/40',
        })
        ->add(match ($variant) {
            'outline' => $invalid ? 'border-red-500' : 'shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5',
            'filled'  => $invalid ? 'border-red-500' : 'border-0',
        })
        ->add('focus:outline-hidden focus:ring-1 focus:ring-accent focus:ring-offset-1 focus:ring-offset-background')
        ->add('truncate');

    $iconClasses = \App\Helpers\Flux::classes()->add($iconVariant === 'outline' ? 'size-5' : '');
@endphp

<flux:with-field :$attributes :$name>
    <div
        x-data="{
            show: false,
            allItems: [],
            filteredItems: [],
            value: @entangle($attributes->wire('model')),
            search: '',

            init() {
                this.refreshOptions();

                // Re-sync after entangle resolves its initial value (wire:navigate timing fix)
                this.$nextTick(() => this.syncLabelFromValue());

                const observer = new MutationObserver(() => {
                    this.refreshOptions();
                });

                observer.observe(this.$refs.realSelect, {
                    childList: true,
                    subtree: true,
                    attributes: true // CAMBIO: Observar cambios en atributos (data-description)
                });

                this.$watch('value', (val) => {
                    this.syncLabelFromValue();
                    if (!val) this.search = '';
                });
            },

            refreshOptions() {
                const select = this.$refs.realSelect;

                this.allItems = Array.from(select.options)
                    .filter(o => o.value !== '')
                    .map(o => ({
                        value: o.value,
                        label: o.text,
                        // CAMBIO: Leemos el atributo data-description
                        description: o.getAttribute('data-description')
                    }));

                this.filteredItems = this.allItems;
                this.syncLabelFromValue();
            },

            filterOptions() {
                if (this.search === '') {
                    this.filteredItems = this.allItems;
                    return;
                }
                const term = this.search.toLowerCase();
                this.filteredItems = this.allItems.filter(item =>
                    item.label.toLowerCase().includes(term) ||
                    // CAMBIO: Permitimos buscar también en la descripción
                    (item.description && item.description.toLowerCase().includes(term))
                );
                this.show = true;
            },

            selectOption(item) {
                this.value = item.value;
                this.search = item.label; // En el input solo mostramos el título principal
                this.show = false;
                this.filteredItems = this.allItems;
            },

            clear() {
                this.value = null;
                this.search = '';
                this.filteredItems = this.allItems;
                this.show = false;
                $refs.searchInput.focus();
            },

            syncLabelFromValue() {
                if (!this.value) return;
                const found = this.allItems.find(i => i.value == this.value);
                if (found) {
                    this.search = found.label;
                }
            },

            openMenu() {
                this.show = true;
                if(this.filteredItems.length === 0) this.filteredItems = this.allItems;
            }
        }"
        @click.outside="show = false"
        class="group/select relative block w-full"
    >
        <select x-ref="realSelect" class="hidden">
            {{ $slot }}
        </select>

        <div class="relative grid w-full grid-cols-1">
            <div
                class="pointer-events-none absolute inset-s-0 top-0 bottom-0 z-10 flex items-center justify-center border-s border-transparent ps-3 text-xs text-zinc-400/75 dark:text-white/60"
            >
                <flux:icon :icon="$icon" :variant="$iconVariant" :class="$iconClasses" />
            </div>

            <input
                x-ref="searchInput"
                type="text"
                x-model="search"
                @input="filterOptions()"
                @focus="openMenu()"
                @click="$el.select()"
                @keydown.escape="show = false"
                @keydown.enter.prevent="if (filteredItems.length > 0) selectOption(filteredItems[0]);"
                placeholder="{{ $placeholder }}"
                class="{{ $controlClasses }}"
                autocomplete="off"
            />

            <div class="absolute inset-e-0 top-0 bottom-0 flex items-center gap-x-1.5 pe-3 text-xs text-zinc-400">
                <button
                    x-show="value"
                    @click="clear()"
                    type="button"
                    class="text-zinc-400 transition-colors hover:text-red-500 focus:outline-none"
                    x-cloak
                >
                    <flux:icon name="x-mark" :variant="$iconVariant" :class="$iconClasses" />
                </button>
                <div x-show="!value" x-cloak class="pointer-events-none">
                    <flux:icon
                        icon="chevron-down"
                        :variant="$iconVariant"
                        class="size-5 text-zinc-400/75 dark:text-white/60"
                    />
                </div>
            </div>
        </div>

        {{-- DROPDOWN --}}
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
                <template x-if="filteredItems.length === 0">
                    <div class="p-2 text-sm text-zinc-500 dark:text-zinc-400">Sin resultados.</div>
                </template>

                <template x-for="item in filteredItems" :key="item.value">
                    <button
                        type="button"
                        @click="selectOption(item)"
                        :class="{
                            'bg-zinc-100 dark:bg-zinc-900/50 text-zinc-900 dark:text-white': value == item.value,
                            'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/50':
                                value != item.value,
                        }"
                        class="flex w-full cursor-pointer items-start justify-between rounded-md p-2 text-left text-sm transition duration-150 ease-in-out focus:outline-none"
                    >
                        {{-- CAMBIO: Estructura Flex vertical para título y descripción --}}
                        <div class="flex flex-col overflow-hidden">
                            <span
                                class="truncate"
                                x-text="item.label"
                                :class="{
                                    'font-medium': item.description,
                                    'font-normal': !item.description,
                                }"
                            ></span>

                            {{-- Renderizado condicional de la descripción --}}
                            <template x-if="item.description">
                                <span
                                    class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400"
                                    x-text="item.description"
                                ></span>
                            </template>
                        </div>

                        <template x-if="value == item.value">
                            <flux:icon
                                name="check"
                                :variant="$iconVariant"
                                class="text-primary-600 dark:text-primary-400 mt-1 h-4 w-4 shrink-0"
                            />
                        </template>
                    </button>
                </template>
            </div>
        </div>
    </div>
</flux:with-field>
