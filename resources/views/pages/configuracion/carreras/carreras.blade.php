<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Carreras profesionales"
        bajada="Cada carrera pertenece a una facultad y se evalúa dentro de un área académica."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::CarrerasCrear->value)
                <flux:button wire:click="nueva" variant="primary" icon="plus">Nueva carrera</flux:button>
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

        <flux:select wire:model.live="filtroFacultad" class="max-w-64">
            <flux:select.option value="">Todas las facultades</flux:select.option>
            @foreach ($facultades as $facultad)
                <flux:select.option :value="$facultad->id_fac">{{ $facultad->nombre_fac }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filtroArea" class="max-w-56">
            <flux:select.option value="">Todas las áreas</flux:select.option>
            @foreach ($areas as $area)
                <flux:select.option :value="$area->id_are">{{ $area->etiqueta() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$carreras">
        <flux:table.columns>
            <flux:table.column>Carrera</flux:table.column>
            <flux:table.column>Facultad</flux:table.column>
            <flux:table.column align="center">Área</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($carreras as $carrera)
                <flux:table.row :key="$carrera->id_car">
                    <flux:table.cell class="max-w-90">
                        <div class="truncate font-medium text-zinc-800 dark:text-white">
                            {{ $carrera->nombre_car }}
                        </div>
                        <div class="font-mono text-xs text-zinc-500">{{ $carrera->codigo_car }}</div>
                    </flux:table.cell>

                    <flux:table.cell class="max-w-64">
                        <span class="block truncate">{{ $carrera->facultad->nombre_fac }}</span>
                    </flux:table.cell>

                    <flux:table.cell align="center">
                        <flux:tooltip :content="$carrera->area->nombre_are">
                            <flux:badge color="zinc" size="sm">{{ $carrera->area->numero_are }}</flux:badge>
                        </flux:tooltip>
                    </flux:table.cell>

                    <flux:table.cell>
                        <x-estado.badge :estado="$carrera->estado_car" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            @can(App\Enums\Permiso::CarrerasEditar->value)
                                <x-tabla.accion wire:click="editar({{ $carrera->id_car }})" icon="pencil-square" tooltip="Editar" />

                                <x-tabla.accion
                                    wire:click="alternarEstado({{ $carrera->id_car }})"
                                    :icon="$carrera->estaHabilitado() ? 'eye-slash' : 'eye'"
                                    :tooltip="$carrera->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                />
                            @endcan

                            @can(App\Enums\Permiso::CarrerasEliminar->value)
                                <x-tabla.accion
                                    wire:click="eliminar({{ $carrera->id_car }})"
                                    wire:confirm="¿Eliminar la carrera «{{ $carrera->nombre_car }}»?"
                                    icon="trash"
                                    tooltip="Eliminar"
                                />
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <x-tabla.vacia :columnas="5" mensaje="No hay carreras que coincidan con los filtros." icono="academic-cap" />
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="carrera" class="w-full md:max-w-xl">
        <form wire:submit="guardar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->id ? 'Editar carrera' : 'Nueva carrera' }}</flux:heading>
                <flux:subheading>El nombre debe coincidir con el del reglamento: es el que se cruza al importar la oferta.</flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="form.facultad" label="Facultad" placeholder="Elige una facultad">
                    @foreach ($facultades as $facultad)
                        <flux:select.option :value="$facultad->id_fac">{{ $facultad->nombre_fac }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form.area" label="Área académica" placeholder="Elige un área">
                    @foreach ($areas as $area)
                        <flux:select.option :value="$area->id_are">{{ $area->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input
                wire:model="form.nombre"
                label="Nombre"
                description="Tal como aparece en el reglamento, con tildes y todo."
                placeholder="Educación Secundaria: Especialidad Idioma Inglés"
            />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model="form.codigo"
                    label="Código"
                    placeholder="EDU_SEC_INGLES"
                />

                <flux:input
                    wire:model="form.nombreCorto"
                    label="Nombre corto"
                    description="Para las tablas y reportes."
                    placeholder="Edu. Sec. Inglés"
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
