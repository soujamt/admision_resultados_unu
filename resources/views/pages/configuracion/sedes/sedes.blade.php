<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Sedes y filiales"
        bajada="Los locales donde se dicta cada carrera y donde se rinde el examen."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::SedesCrear->value)
                <flux:button wire:click="nueva" variant="primary" icon="plus">Nueva sede</flux:button>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Sede</flux:table.column>
            <flux:table.column>Distrito</flux:table.column>
            <flux:table.column align="center">Aulas</flux:table.column>
            <flux:table.column align="center">Capacidad</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($sedes as $sede)
                <flux:table.row :key="$sede->id_sed">
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-zinc-800 dark:text-white">{{ $sede->nombre_sed }}</span>

                            @if ($sede->es_filial_sed)
                                <flux:badge color="amber" size="sm">Filial</flux:badge>
                            @endif
                        </div>
                        <div class="font-mono text-xs text-zinc-500">{{ $sede->codigo_sed }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($sede->ubigeo)
                            <span class="text-sm">{{ $sede->ubigeo->distrito_ubi }}</span>
                            <div class="text-xs text-zinc-500">
                                {{ $sede->ubigeo->departamento_ubi }} / {{ $sede->ubigeo->provincia_ubi }}
                            </div>
                        @else
                            <flux:text size="sm" class="text-zinc-400">Sin asignar</flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="center">{{ $sede->aulas_count }}</flux:table.cell>

                    <flux:table.cell align="center">{{ (int) $sede->aulas_sum_capacidad_aul }}</flux:table.cell>

                    <flux:table.cell>
                        <x-estado.badge :estado="$sede->estado_sed" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            @can(App\Enums\Permiso::SedesEditar->value)
                                <x-tabla.accion wire:click="editar({{ $sede->id_sed }})" icon="pencil-square" tooltip="Editar" />

                                <x-tabla.accion
                                    wire:click="alternarEstado({{ $sede->id_sed }})"
                                    :icon="$sede->estaHabilitado() ? 'eye-slash' : 'eye'"
                                    :tooltip="$sede->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                />
                            @endcan

                            @can(App\Enums\Permiso::SedesEliminar->value)
                                <x-tabla.accion
                                    wire:click="eliminar({{ $sede->id_sed }})"
                                    wire:confirm="¿Eliminar la sede «{{ $sede->nombre_sed }}»?"
                                    icon="trash"
                                    tooltip="Eliminar"
                                />
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <x-tabla.vacia :columnas="6" mensaje="Todavía no hay sedes registradas." icono="map-pin" />
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="sede" class="w-full md:max-w-lg">
        <form wire:submit="guardar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->id ? 'Editar sede' : 'Nueva sede' }}</flux:heading>
                <flux:subheading>El nombre se usa al importar la oferta para separar la sede de la carrera.</flux:subheading>
            </div>

            <flux:input
                wire:model="form.nombre"
                label="Nombre"
                description="Como aparece en el archivo del formato: «Sede Coronel Portillo - Callería»."
                placeholder="Sede Coronel Portillo - Callería"
            />

            <flux:input wire:model="form.codigo" label="Código" placeholder="CORONEL_PORTILLO" />

            <flux:field>
                <flux:label>Distrito</flux:label>
                <flux:description>Busca por distrito, provincia o departamento.</flux:description>

                <flux:input
                    wire:model.live.debounce.300ms="busquedaUbigeo"
                    placeholder="Escribe para buscar…"
                    icon="magnifying-glass"
                    size="sm"
                    clearable
                />

                <flux:select wire:model="form.ubigeo" class="mt-2">
                    <flux:select.option value="">Sin asignar</flux:select.option>
                    @foreach ($ubigeos as $ubigeo)
                        <flux:select.option :value="$ubigeo->codigo_ubi">
                            {{ $ubigeo->distrito_ubi }} — {{ $ubigeo->departamento_ubi }}/{{ $ubigeo->provincia_ubi }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:error name="form.ubigeo" />
            </flux:field>

            <flux:switch wire:model="form.esFilial" label="Es filial" description="Las filiales se listan aparte en los reportes." />

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
