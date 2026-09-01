<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Facultades"
        bajada="Las facultades del Art. 1 del reglamento. Cada carrera profesional pertenece a una."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::FacultadesCrear->value)
                <flux:button wire:click="nueva" variant="primary" icon="plus">Nueva facultad</flux:button>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <div class="flex flex-wrap items-end gap-3">
        <flux:input
            wire:model.live.debounce.300ms="busqueda"
            placeholder="Buscar por nombre o código"
            icon="magnifying-glass"
            clearable
            class="max-w-xs"
        />

        <flux:select wire:model.live="filtroEstado" placeholder="Todos los estados" class="max-w-45">
            <flux:select.option value="">Todos los estados</flux:select.option>
            @foreach ($estados as $estado)
                <flux:select.option :value="$estado->value">{{ $estado->etiqueta() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$facultades">
        <flux:table.columns>
            <flux:table.column>Código</flux:table.column>
            <flux:table.column>Facultad</flux:table.column>
            <flux:table.column align="center">Carreras</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($facultades as $facultad)
                <flux:table.row :key="$facultad->id_fac">
                    <flux:table.cell class="font-mono text-xs">{{ $facultad->codigo_fac }}</flux:table.cell>

                    <flux:table.cell variant="strong">{{ $facultad->nombre_fac }}</flux:table.cell>

                    <flux:table.cell align="center">{{ $facultad->carreras_count }}</flux:table.cell>

                    <flux:table.cell>
                        <x-estado.badge :estado="$facultad->estado_fac" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            @can(App\Enums\Permiso::FacultadesEditar->value)
                                <x-tabla.accion
                                    wire:click="editar({{ $facultad->id_fac }})"
                                    icon="pencil-square"
                                    tooltip="Editar"
                                />

                                <x-tabla.accion
                                    wire:click="alternarEstado({{ $facultad->id_fac }})"
                                    :icon="$facultad->estaHabilitado() ? 'eye-slash' : 'eye'"
                                    :tooltip="$facultad->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                />
                            @endcan

                            @can(App\Enums\Permiso::FacultadesEliminar->value)
                                <x-tabla.accion
                                    wire:click="eliminar({{ $facultad->id_fac }})"
                                    wire:confirm="¿Eliminar la facultad «{{ $facultad->nombre_fac }}»?"
                                    icon="trash"
                                    tooltip="Eliminar"
                                />
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <x-tabla.vacia :columnas="5" mensaje="No hay facultades que coincidan con la búsqueda." icono="building-library" />
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="facultad" class="w-full md:max-w-lg">
        <form wire:submit="guardar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->id ? 'Editar facultad' : 'Nueva facultad' }}</flux:heading>
                <flux:subheading>Se usa para agrupar las carreras profesionales.</flux:subheading>
            </div>

            <flux:input
                wire:model="form.codigo"
                label="Código"
                description="Identificador corto en mayúsculas, por ejemplo EDUCACION."
                placeholder="EDUCACION"
            />

            <flux:input
                wire:model="form.nombre"
                label="Nombre"
                placeholder="Facultad de Educación y Ciencias Sociales"
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
