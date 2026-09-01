<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Inscripciones"
        bajada="Las fichas llegan en el archivo del formato oficial; aquí se cargan y se consultan."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::InscripcionesExportar->value)
                <flux:button wire:click="exportar" icon="arrow-down-tray">Exportar</flux:button>
            @endcan

            @can(App\Enums\Permiso::InscripcionesImportar->value)
                <flux:button wire:click="abrirImportacion" variant="primary" icon="arrow-up-tray">
                    Importar Excel
                </flux:button>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    @if ($ultimaImportacion)
        <flux:callout
            :icon="$ultimaImportacion['errores_totales'] > 0 ? 'exclamation-triangle' : 'check-circle'"
            :variant="$ultimaImportacion['errores_totales'] > 0 ? 'warning' : 'success'"
        >
            <flux:callout.heading>
                Carga de {{ $ultimaImportacion['proceso'] }}:
                {{ $ultimaImportacion['creados'] }} nuevas,
                {{ $ultimaImportacion['actualizados'] }} actualizadas,
                {{ $ultimaImportacion['omitidos'] }} omitidas
            </flux:callout.heading>

            @if ($ultimaImportacion['errores_totales'] > 0)
                <flux:callout.text>
                    <div class="mt-2 max-h-56 space-y-1 overflow-y-auto pr-2 text-xs">
                        @foreach ($ultimaImportacion['errores'] as $error)
                            <div>
                                <span class="font-mono">fila {{ $error['fila'] }}</span>
                                @if ($error['referencia'])
                                    <span class="font-mono">[{{ $error['referencia'] }}]</span>
                                @endif
                                {{ $error['mensaje'] }}
                            </div>
                        @endforeach

                        @if ($ultimaImportacion['errores_totales'] > count($ultimaImportacion['errores']))
                            <div class="text-zinc-500">
                                … y {{ $ultimaImportacion['errores_totales'] - count($ultimaImportacion['errores']) }} más.
                            </div>
                        @endif
                    </div>
                </flux:callout.text>
            @endif
        </flux:callout>
    @endif

    @if ($resumen)
        <div class="grid gap-4 sm:grid-cols-4">
            <flux:card class="space-y-1">
                <flux:text size="sm">Total de fichas</flux:text>
                <flux:heading size="xl">{{ number_format($resumen['total']) }}</flux:heading>
            </flux:card>

            <flux:card class="space-y-1">
                <flux:text size="sm">Inscritas</flux:text>
                <flux:heading size="xl">{{ number_format($resumen['inscritos']) }}</flux:heading>
            </flux:card>

            <flux:card class="space-y-1">
                <flux:text size="sm">Observadas</flux:text>
                <flux:heading size="xl" @class(['text-amber-600 dark:text-amber-400' => $resumen['observados'] > 0])>
                    {{ number_format($resumen['observados']) }}
                </flux:heading>
            </flux:card>

            <flux:card class="space-y-1">
                <flux:text size="sm">Con foto</flux:text>
                <flux:heading size="xl">{{ number_format($resumen['con_foto']) }}</flux:heading>
            </flux:card>
        </div>
    @endif

    <div class="flex flex-wrap items-end gap-3">
        <flux:select wire:model.live="codigoProceso" class="max-w-45">
            <flux:select.option value="">Todos los procesos</flux:select.option>
            @foreach ($procesos as $opcion)
                <flux:select.option :value="$opcion->codigo_pro">{{ $opcion->codigo_pro }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input
            wire:model.live.debounce.400ms="busqueda"
            placeholder="Documento, apellidos, nombres o código"
            icon="magnifying-glass"
            clearable
            class="max-w-xs"
        />

        <flux:select wire:model.live="filtroModalidad" class="max-w-56">
            <flux:select.option value="">Todas las modalidades</flux:select.option>
            @foreach ($modalidades as $modalidad)
                <flux:select.option :value="$modalidad->id_mod">{{ $modalidad->nombre_mod }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filtroCarrera" class="max-w-64">
            <flux:select.option value="">Todas las carreras</flux:select.option>
            @foreach ($carreras as $carrera)
                <flux:select.option :value="$carrera->id_car">{{ $carrera->nombre_corto_car }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filtroEstado" class="max-w-45">
            <flux:select.option value="">Todos los estados</flux:select.option>
            @foreach ($estadosInscripcion as $estado)
                <flux:select.option :value="$estado->value">{{ $estado->etiqueta() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:button wire:click="limpiarFiltros" variant="subtle" size="sm" icon="x-mark">Limpiar</flux:button>
    </div>

    <flux:table :paginate="$inscripciones">
        <flux:table.columns>
            <flux:table.column>Código</flux:table.column>
            <flux:table.column>Postulante</flux:table.column>
            <flux:table.column>Carrera</flux:table.column>
            <flux:table.column>Modalidad</flux:table.column>
            <flux:table.column align="center">Foto</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($inscripciones as $inscripcion)
                <flux:table.row :key="$inscripcion->id_ins">
                    <flux:table.cell class="font-mono text-xs">
                        {{ $inscripcion->codigo_ins ?? '—' }}
                        <div class="text-zinc-500">{{ $inscripcion->proceso->codigo_pro }}</div>
                    </flux:table.cell>

                    <flux:table.cell class="max-w-72">
                        <span class="block truncate font-medium text-zinc-800 dark:text-white">
                            {{ $inscripcion->postulante->nombreCompleto() }}
                        </span>
                        <span class="font-mono text-xs text-zinc-500">
                            {{ $inscripcion->postulante->tipo_documento_pos->abreviatura() }}
                            {{ $inscripcion->postulante->numero_documento_pos }}
                        </span>
                    </flux:table.cell>

                    <flux:table.cell class="max-w-56">
                        <span class="block truncate">{{ $inscripcion->carrera->nombre_corto_car }}</span>
                        <span class="text-xs text-zinc-500">{{ $inscripcion->sede->nombre_sed }}</span>
                    </flux:table.cell>

                    <flux:table.cell class="max-w-45">
                        <span class="block truncate text-sm">{{ $inscripcion->modalidad->nombre_mod }}</span>
                    </flux:table.cell>

                    <flux:table.cell align="center">
                        @if ($inscripcion->tieneFoto())
                            <flux:icon.check-circle variant="mini" class="mx-auto size-5 text-green-600 dark:text-green-400" />
                        @else
                            <flux:icon.minus-circle variant="mini" class="mx-auto size-5 text-zinc-300 dark:text-zinc-600" />
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge :color="$inscripcion->estado_ins->color()" size="sm">
                            {{ $inscripcion->estado_ins->etiqueta() }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            <x-tabla.accion
                                wire:click="verFicha({{ $inscripcion->id_ins }})"
                                icon="eye"
                                tooltip="Ver ficha"
                            />

                            @can(App\Enums\Permiso::InscripcionesEliminar->value)
                                @if ($inscripcion->estado_ins !== App\Enums\EstadoInscripcion::Anulado)
                                    <x-tabla.accion
                                        wire:click="anular({{ $inscripcion->id_ins }})"
                                        wire:confirm="¿Anular la ficha de {{ $inscripcion->postulante->nombreCompleto() }}?"
                                        icon="no-symbol"
                                        tooltip="Anular"
                                    />
                                @endif
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <x-tabla.vacia :columnas="7" mensaje="No hay inscripciones que coincidan con los filtros." icono="clipboard-document-list">
                    @can(App\Enums\Permiso::InscripcionesImportar->value)
                        <flux:button wire:click="abrirImportacion" size="sm" variant="primary" icon="arrow-up-tray" class="mt-2">
                            Importar el Excel del formato
                        </flux:button>
                    @endcan
                </x-tabla.vacia>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Carga del archivo del formato oficial --}}
    <flux:modal name="importar" class="w-full md:max-w-lg">
        <form wire:submit="importar" class="space-y-6">
            <div>
                <flux:heading size="lg">Importar inscripciones</flux:heading>
                <flux:subheading>
                    Sube el .xlsx del formato oficial. Se lee la hoja <strong>FORMATO</strong> y cada fila se
                    guarda como una ficha del proceso elegido.
                </flux:subheading>
            </div>

            <flux:select wire:model="procesoDestino" label="Proceso de destino" placeholder="Elige un proceso">
                @foreach ($procesos as $opcion)
                    <flux:select.option :value="$opcion->codigo_pro">
                        {{ $opcion->codigo_pro }} — {{ $opcion->nombre_pro }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:field>
                <flux:label>Archivo</flux:label>

                <x-form.upload-dropzone
                    model="archivo"
                    accept=".xlsx"
                    titulo="Click para elegir el archivo del formato"
                    subtitulo="Excel (.xlsx), hasta 20 MB"
                >
                    @if ($archivo)
                        <div class="mt-3 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <flux:icon.document-check variant="mini" class="size-4 text-green-600" />
                            <span class="truncate">{{ $archivo->getClientOriginalName() }}</span>
                        </div>
                    @endif
                </x-form.upload-dropzone>

                <flux:error name="archivo" />
            </flux:field>

            <flux:callout icon="light-bulb" variant="secondary">
                <flux:callout.text class="text-xs">
                    El proceso necesita su cuadro de vacantes cargado: es el que traduce el CODIGO_CARRERA del
                    archivo a la carrera, la modalidad y la sede. Volver a subir el mismo archivo actualiza las
                    fichas en vez de duplicarlas.
                </flux:callout.text>
            </flux:callout>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" icon="arrow-up-tray">Cargar</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Detalle de una ficha --}}
    <flux:modal name="ficha" variant="flyout" class="w-full md:max-w-xl">
        @if ($ficha)
            <div class="space-y-6">
                <div class="flex items-start gap-4">
                    @if ($ficha->tieneFoto())
                        <img
                            src="{{ route('inscripciones.foto', ['inscripcion' => encode_id($ficha->id_ins)]) }}"
                            alt="Foto de {{ $ficha->postulante->nombreCompleto() }}"
                            class="size-24 shrink-0 rounded-lg object-cover ring-1 ring-zinc-200 dark:ring-zinc-700"
                        />
                    @else
                        <div class="flex size-24 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                            <flux:icon.user class="size-10 text-zinc-400" />
                        </div>
                    @endif

                    <div class="min-w-0">
                        <flux:heading size="lg">{{ $ficha->postulante->nombreCompleto() }}</flux:heading>

                        <flux:text size="sm" class="mt-1 font-mono">
                            {{ $ficha->postulante->tipo_documento_pos->abreviatura() }}
                            {{ $ficha->postulante->numero_documento_pos }}
                        </flux:text>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <flux:badge color="blue" size="sm">{{ $ficha->proceso->codigo_pro }}</flux:badge>
                            <flux:badge :color="$ficha->estado_ins->color()" size="sm">
                                {{ $ficha->estado_ins->etiqueta() }}
                            </flux:badge>
                            @if ($ficha->codigo_ins)
                                <span class="font-mono text-xs text-zinc-500">{{ $ficha->codigo_ins }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <flux:separator />

                <div class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    @foreach ([
                        'Carrera' => $ficha->carrera->nombre_car,
                        'Facultad' => $ficha->carrera->facultad->nombre_fac,
                        'Área' => $ficha->carrera->area->etiqueta(),
                        'Modalidad' => $ficha->modalidad->nombre_mod,
                        'Sede' => $ficha->sede->nombre_sed,
                        'Nacimiento' => $ficha->postulante->fecha_nacimiento_pos->format('d/m/Y'),
                        'Lugar de nacimiento' => $ficha->postulante->ubigeoNacimiento?->descripcion(),
                        'Sexo' => $ficha->postulante->sexo_pos->etiqueta(),
                        'Estado civil' => $ficha->postulante->estado_civil_pos->etiqueta(),
                        'Celular' => $ficha->postulante->celular_pos,
                        'Correo' => $ficha->postulante->correo_pos,
                        'Dirección' => $ficha->postulante->direccion_pos,
                        'Distrito' => $ficha->postulante->ubigeoDireccion?->descripcion(),
                        'Colegio' => $ficha->colegio?->nombre_col ?? $ficha->nombre_colegio_ins,
                        'Tipo de colegio' => $ficha->tipo_colegio_ins?->etiqueta(),
                        'Año de egreso' => $ficha->anio_graduacion_ins,
                        'Postulaciones a la UNU' => $ficha->veces_unu_ins,
                        'Postulaciones a otras' => $ficha->veces_otros_ins,
                        'Lengua materna' => $ficha->postulante->lengua_materna_pos,
                        'Observación' => $ficha->observacion_ins,
                    ] as $etiqueta => $valor)
                        <div>
                            <div class="text-xs text-zinc-500">{{ $etiqueta }}</div>
                            <div class="break-words text-zinc-800 dark:text-zinc-200">
                                {{ filled($valor) ? $valor : '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </flux:modal>
</div>
