<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Aulas"
        bajada="Locales de examen de cada sede. Sobre su capacidad se hará el sorteo que ubica a cada postulante."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::AulasCrear->value)
                <flux:button wire:click="nueva" variant="primary" icon="plus">Nueva aula</flux:button>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <flux:callout icon="information-circle" variant="secondary">
        <flux:callout.text>
            Por ahora esto es el catálogo de aulas. La asignación por proceso y el sorteo de ubicaciones
            llegan después y se apoyarán en la capacidad que registres aquí.
        </flux:callout.text>
    </flux:callout>

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="flex flex-wrap items-end gap-3">
            <flux:input
                wire:model.live.debounce.300ms="busqueda"
                placeholder="Buscar por aula, código o pabellón"
                icon="magnifying-glass"
                clearable
                class="max-w-xs"
            />

            <flux:select wire:model.live="filtroSede" class="max-w-64">
                <flux:select.option value="">Todas las sedes</flux:select.option>
                @foreach ($sedes as $sede)
                    <flux:select.option :value="$sede->id_sed">{{ $sede->nombre_sed }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:badge color="lime" size="lg" icon="users">
            Capacidad habilitada: {{ number_format($capacidadTotal) }}
        </flux:badge>
    </div>

    <flux:table :paginate="$aulas">
        <flux:table.columns>
            <flux:table.column>Aula</flux:table.column>
            <flux:table.column>Sede</flux:table.column>
            <flux:table.column align="center">Capacidad</flux:table.column>
            <flux:table.column align="center">Orden</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($aulas as $aula)
                <flux:table.row :key="$aula->id_aul">
                    <flux:table.cell>
                        <div class="font-medium text-zinc-800 dark:text-white">{{ $aula->etiqueta() }}</div>
                        <div class="font-mono text-xs text-zinc-500">{{ $aula->codigo_aul }}</div>
                    </flux:table.cell>

                    <flux:table.cell class="max-w-64">
                        <span class="block truncate">{{ $aula->sede->nombre_sed }}</span>
                    </flux:table.cell>

                    <flux:table.cell align="center">{{ $aula->capacidad_aul }}</flux:table.cell>

                    <flux:table.cell align="center" class="text-zinc-500">{{ $aula->orden_aul }}</flux:table.cell>

                    <flux:table.cell>
                        <x-estado.badge :estado="$aula->estado_aul" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            @can(App\Enums\Permiso::AulasEditar->value)
                                <x-tabla.accion wire:click="editar({{ $aula->id_aul }})" icon="pencil-square" tooltip="Editar" />

                                <x-tabla.accion
                                    wire:click="alternarEstado({{ $aula->id_aul }})"
                                    :icon="$aula->estaHabilitado() ? 'eye-slash' : 'eye'"
                                    :tooltip="$aula->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                />
                            @endcan

                            @can(App\Enums\Permiso::AulasEliminar->value)
                                <x-tabla.accion
                                    wire:click="eliminar({{ $aula->id_aul }})"
                                    wire:confirm="¿Eliminar el aula «{{ $aula->nombre_aul }}»?"
                                    icon="trash"
                                    tooltip="Eliminar"
                                />
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <x-tabla.vacia :columnas="6" mensaje="No hay aulas registradas para estos filtros." icono="building-office-2" />
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="aula" class="w-full md:max-w-lg">
        <form wire:submit="guardar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->id ? 'Editar aula' : 'Nueva aula' }}</flux:heading>
                <flux:subheading>El código solo tiene que ser único dentro de su sede.</flux:subheading>
            </div>

            <flux:select wire:model="form.sede" label="Sede" placeholder="Elige una sede">
                @foreach ($sedes as $sede)
                    <flux:select.option :value="$sede->id_sed">{{ $sede->nombre_sed }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.codigo" label="Código" placeholder="A-101" />
                <flux:input wire:model="form.nombre" label="Nombre" placeholder="Aula 101" />
            </div>

            <flux:input
                wire:model="form.pabellon"
                label="Pabellón"
                description="Opcional. Se antepone al nombre en los padrones."
                placeholder="Pabellón A"
            />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model="form.capacidad"
                    type="number"
                    min="0"
                    max="2000"
                    label="Capacidad"
                    description="Carpetas disponibles."
                />

                <flux:input
                    wire:model="form.orden"
                    type="number"
                    min="0"
                    max="999"
                    label="Orden"
                    description="Con qué prioridad se llena en el sorteo."
                />
            </div>

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
