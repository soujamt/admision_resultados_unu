<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Procesos de admisión"
        bajada="Cada convocatoria es un proceso independiente: 2027-I, 2027-II, 2027-III."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::ProcesosCrear->value)
                <flux:button wire:click="nuevo" variant="primary" icon="plus">Nuevo proceso</flux:button>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Proceso</flux:table.column>
            <flux:table.column>Inscripciones</flux:table.column>
            <flux:table.column>Examen</flux:table.column>
            <flux:table.column align="center">Vacantes</flux:table.column>
            <flux:table.column align="center">Inscritos</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($procesos as $proceso)
                <flux:table.row :key="$proceso->id_pro">
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:badge color="blue" size="sm">{{ $proceso->codigo_pro }}</flux:badge>
                            <span class="text-xs text-zinc-500">{{ $proceso->convocatoria_pro->etiqueta() }}</span>
                        </div>
                        <div class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $proceso->nombre_pro }}</div>

                        @if ($proceso->resolucion_pro)
                            <div class="text-xs text-zinc-500">{{ $proceso->resolucion_pro }}</div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-sm">
                        @if ($proceso->fecha_inicio_inscripcion_pro || $proceso->fecha_fin_inscripcion_pro)
                            {{ $proceso->fecha_inicio_inscripcion_pro?->format('d/m/Y') ?? '—' }}
                            &rarr;
                            {{ $proceso->fecha_fin_inscripcion_pro?->format('d/m/Y') ?? '—' }}
                        @else
                            <span class="text-zinc-400">Sin fechas</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-sm">
                        {{ $proceso->fecha_examen_pro?->format('d/m/Y') ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell align="center">
                        <div>{{ (int) $proceso->vacantes_sum_cantidad_vac }}</div>
                        <div class="text-xs text-zinc-500">{{ $proceso->vacantes_count }} carrera(s)</div>
                    </flux:table.cell>

                    <flux:table.cell align="center">{{ $proceso->inscripciones_count }}</flux:table.cell>

                    <flux:table.cell>
                        <x-estado.badge :estado="$proceso->estado_pro" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            @can(App\Enums\Permiso::VacantesVer->value)
                                <x-tabla.accion
                                    :href="route('configuracion.vacantes', ['proceso' => $proceso->codigo_pro])"
                                    wire:navigate
                                    icon="table-cells"
                                    tooltip="Cuadro de vacantes"
                                />
                            @endcan

                            @can(App\Enums\Permiso::ProcesosEditar->value)
                                <x-tabla.accion wire:click="editar({{ $proceso->id_pro }})" icon="pencil-square" tooltip="Editar" />

                                <x-tabla.accion
                                    wire:click="alternarEstado({{ $proceso->id_pro }})"
                                    :icon="$proceso->estaHabilitado() ? 'eye-slash' : 'eye'"
                                    :tooltip="$proceso->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                />
                            @endcan

                            @can(App\Enums\Permiso::ProcesosEliminar->value)
                                <x-tabla.accion
                                    wire:click="eliminar({{ $proceso->id_pro }})"
                                    wire:confirm="¿Eliminar el proceso {{ $proceso->codigo_pro }}?"
                                    icon="trash"
                                    tooltip="Eliminar"
                                />
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <x-tabla.vacia :columnas="7" mensaje="Todavía no hay procesos registrados." icono="calendar-days" />
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="proceso" class="w-full md:max-w-xl">
        <form wire:submit="guardar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->id ? 'Editar proceso' : 'Nuevo proceso' }}</flux:heading>
                <flux:subheading>
                    El código sale del año y la convocatoria.
                    @if ($form->codigoPrevisto())
                        Este proceso será <strong>{{ $form->codigoPrevisto() }}</strong>.
                    @endif
                </flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model.live="form.anio"
                    type="number"
                    min="2000"
                    max="2100"
                    label="Año"
                />

                <flux:select wire:model.live="form.convocatoria" label="Convocatoria">
                    @foreach ($convocatorias as $convocatoria)
                        <flux:select.option :value="$convocatoria->value">
                            {{ $convocatoria->etiqueta() }} ({{ $convocatoria->porcentajeVacantes() }}%)
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input
                wire:model="form.nombre"
                label="Nombre"
                description="Si lo dejas vacío se arma solo: «Proceso de Admisión 2027-I»."
                placeholder="Proceso de Admisión 2027-I"
            />

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="form.inicioInscripcion" type="date" label="Inicio de inscripciones" />
                <flux:input wire:model="form.finInscripcion" type="date" label="Cierre de inscripciones" />
                <flux:input wire:model="form.fechaExamen" type="date" label="Fecha del examen" />
            </div>

            <flux:input
                wire:model="form.resolucion"
                label="Resolución"
                description="La que aprueba el cuadro de vacantes (Art. 15)."
                placeholder="Resolución N° 783-2025-UNU-CU-R"
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
