<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Áreas académicas"
        bajada="El examen de admisión se aplica agrupando las carreras en áreas (Art. 4), no por facultad."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::AreasCrear->value)
                <flux:button wire:click="nueva" variant="primary" icon="plus">Nueva área</flux:button>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($areas as $area)
            <flux:card :key="$area->id_are" class="space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:badge color="zinc" size="sm">Área {{ $area->numero_are }}</flux:badge>
                            <x-estado.badge :estado="$area->estado_are" />
                        </div>

                        <flux:heading size="lg" class="mt-2">{{ $area->nombre_are }}</flux:heading>

                        <flux:text size="sm" class="mt-1">
                            {{ $area->carreras->count() }} carrera(s)
                        </flux:text>
                    </div>

                    <div class="flex shrink-0 gap-1">
                        @can(App\Enums\Permiso::AreasEditar->value)
                            <x-tabla.accion wire:click="editar({{ $area->id_are }})" icon="pencil-square" tooltip="Editar" />

                            <x-tabla.accion
                                wire:click="alternarEstado({{ $area->id_are }})"
                                :icon="$area->estaHabilitado() ? 'eye-slash' : 'eye'"
                                :tooltip="$area->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                            />
                        @endcan

                        @can(App\Enums\Permiso::AreasEliminar->value)
                            <x-tabla.accion
                                wire:click="eliminar({{ $area->id_are }})"
                                wire:confirm="¿Eliminar el área «{{ $area->nombre_are }}»?"
                                icon="trash"
                                tooltip="Eliminar"
                            />
                        @endcan
                    </div>
                </div>

                @if ($area->carreras->isNotEmpty())
                    <flux:separator variant="subtle" />

                    <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                        @foreach ($area->carreras as $carrera)
                            <li class="flex items-center gap-2">
                                <flux:icon.chevron-right variant="micro" class="size-3 shrink-0 text-zinc-400" />
                                <span class="truncate">{{ $carrera->nombre_car }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </flux:card>
        @empty
            <flux:callout icon="squares-2x2" variant="secondary" class="lg:col-span-2">
                <flux:callout.heading>Todavía no hay áreas</flux:callout.heading>
                <flux:callout.text>
                    El reglamento define cinco. Créalas para poder asignar cada carrera a la suya.
                </flux:callout.text>
            </flux:callout>
        @endforelse
    </div>

    <flux:modal name="area" class="w-full md:max-w-lg">
        <form wire:submit="guardar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->id ? 'Editar área' : 'Nueva área' }}</flux:heading>
                <flux:subheading>Decide con qué examen se evalúa a los postulantes de sus carreras.</flux:subheading>
            </div>

            <flux:input
                wire:model="form.numero"
                type="number"
                min="1"
                max="99"
                label="Número"
                description="El orden con el que se nombra: Área 1, Área 2…"
            />

            <flux:input
                wire:model="form.nombre"
                label="Denominación"
                placeholder="Ciencias Agrarias y del Ambiente"
            />

            <flux:select wire:model="form.estado" label="Estado">
                @foreach ($estados as $estado)
                    <flux:select.option :value="$estado->value">{{ $estado->etiqueta() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
