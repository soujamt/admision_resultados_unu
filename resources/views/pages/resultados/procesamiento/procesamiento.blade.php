<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Importación y resultados"
        bajada="Cruza el padrón del lector óptico, importa respuestas por área y resuelve los ingresantes según vacantes y reglamento."
    >
        <x-slot:acciones>
            @if ($examen && $estadisticas['resultados'] > 0)
                @can(App\Enums\Permiso::ResultadosExportar->value)
                    <flux:button :href="route('resultados.pdf.juego', $examen)" icon="archive-box-arrow-down" variant="filled">
                        Juego por carrera (Art. 84)
                    </flux:button>
                    <flux:button :href="route('resultados.pdf', $examen)" icon="document-arrow-down" variant="primary">
                        Exportar PDF general
                    </flux:button>
                @endcan
            @endif
        </x-slot:acciones>
    </x-pagina.encabezado>

    <div class="flex flex-wrap items-end gap-3">
        <flux:select wire:model.live="procesoSeleccionado" label="Proceso" class="min-w-44">
            @foreach ($procesos as $proceso)
                <flux:select.option :value="$proceso->id_pro">{{ $proceso->codigo_pro }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="examenSeleccionado" label="Jornada de examen" class="min-w-72">
            <flux:select.option value="">Elige una jornada</flux:select.option>
            @foreach ($examenes as $opcion)
                <flux:select.option :value="$opcion->id_exa">
                    {{ $opcion->nombre_exa }}{{ $opcion->fecha_exa ? ' · '.$opcion->fecha_exa->format('d/m/Y') : '' }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div wire:loading.flex wire:target="procesoSeleccionado,examenSeleccionado" class="items-center gap-2 text-sm text-zinc-600">
        <flux:icon.arrow-path class="size-4 animate-spin text-blue-600" />
        Cargando datos de la jornada…
    </div>

    @if (! $examen)
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.text>Elige una jornada creada en “Examen y aulas” para comenzar.</flux:callout.text>
        </flux:callout>
    @else
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            @foreach ([
                ['Padrón', $estadisticas['padron'], 'users'],
                ['Respuestas', $estadisticas['respuestas'], 'document-check'],
                ['Sin cruce', $estadisticas['sin_cruce'], 'exclamation-triangle'],
                ['Resultados', $estadisticas['resultados'], 'chart-bar'],
                ['Ingresantes', $estadisticas['ingresantes'], 'check-circle'],
                ['NSP', $estadisticas['nsp'], 'minus-circle'],
            ] as [$etiqueta, $valor, $icono])
                <flux:card class="space-y-1">
                    <div class="flex items-center gap-2 text-zinc-500">
                        <flux:icon :name="$icono" class="size-4" />
                        <flux:text size="sm">{{ $etiqueta }}</flux:text>
                    </div>
                    <flux:heading size="xl">{{ number_format($valor) }}</flux:heading>
                </flux:card>
            @endforeach
        </div>

        @if ($observacionesImportacion !== [])
            <flux:callout icon="exclamation-triangle" variant="warning">
                <flux:callout.heading>Observaciones de la importación</flux:callout.heading>
                <flux:callout.text>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach (array_slice($observacionesImportacion, 0, 20) as $observacion)
                            <li>{{ $observacion }}</li>
                        @endforeach
                    </ul>
                    @if (count($observacionesImportacion) > 20)
                        <div class="mt-2">Se muestran las primeras 20 de {{ count($observacionesImportacion) }} observaciones.</div>
                    @endif
                </flux:callout.text>
            </flux:callout>
        @endif

        @if (($resumenResolucion['requiere_examen_complementario'] ?? false) === true)
            <flux:callout icon="exclamation-circle" variant="danger">
                <flux:callout.heading>Corresponde evaluar un examen complementario</flux:callout.heading>
                <flux:callout.text>
                    Quedó desierto el {{ number_format($resumenResolucion['porcentaje_desiertas'], 2) }}% de las vacantes de la tercera convocatoria,
                    superando el 20% previsto por el reglamento.
                </flux:callout.text>
            </flux:callout>
        @elseif ($examen->resuelto_en_exa && $resumenResolucion['desiertas'] > 0)
            <flux:callout icon="information-circle" variant="secondary">
                <flux:callout.heading>{{ $resumenResolucion['desiertas'] }} vacante(s) desierta(s)</flux:callout.heading>
                <flux:callout.text>
                    @if ($examen->proceso->convocatoria_pro !== App\Enums\Convocatoria::Tercera)
                        Deben acumularse a la tercera convocatoria en las modalidades que correspondan, conforme al reglamento.
                    @else
                        Representan el {{ number_format($resumenResolucion['porcentaje_desiertas'], 2) }}% de las {{ $resumenResolucion['ofrecidas'] }} vacantes ofertadas.
                    @endif
                </flux:callout.text>
            </flux:callout>
        @endif

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
            @can(App\Enums\Permiso::ResultadosImportar->value)
                <flux:card class="space-y-5">
                    <div>
                        <flux:heading size="lg">1. Importar archivos del lector óptico</flux:heading>
                        <flux:text class="mt-1">El padrón debe importarse primero. Luego puedes cargar uno o varios TXT de respuestas por área.</flux:text>
                    </div>

                    <form wire:submit="importarPadron" class="space-y-3">
                        <flux:input wire:model="archivoPadron" type="file" accept=".txt,text/plain" label="TXT de postulantes" />
                        <div class="flex justify-end">
                            <flux:button type="submit" icon="arrow-up-tray" variant="primary">Importar padrón</flux:button>
                        </div>
                    </form>

                    <flux:separator />

                    <form wire:submit="importarRespuestas" class="space-y-3">
                        <flux:input wire:model="archivosRespuestas" type="file" multiple accept=".txt,text/plain" label="TXT de respuestas por áreas" />
                        <div class="flex justify-end">
                            <flux:button type="submit" icon="arrow-up-tray" variant="primary" :disabled="$estadisticas['padron'] === 0">
                                Importar respuestas
                            </flux:button>
                        </div>
                    </form>

                    @if ($importaciones->isNotEmpty())
                        <div class="space-y-2">
                            <flux:text size="sm" class="font-medium">Últimas importaciones</flux:text>
                            @foreach ($importaciones as $importacion)
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                                    <div class="min-w-0">
                                        <div class="truncate font-medium">{{ $importacion->archivo_exi }}</div>
                                        <div class="text-xs text-zinc-500">{{ ucfirst($importacion->tipo_exi) }} · {{ $importacion->filas_exi }} filas</div>
                                    </div>
                                    <flux:badge :color="$importacion->errores_exi ? 'amber' : 'green'" size="sm">
                                        {{ $importacion->errores_exi ? 'Con observaciones' : 'Correcto' }}
                                    </flux:badge>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:card>
            @endcan

            @can(App\Enums\Permiso::ResultadosGenerar->value)
                <flux:card class="space-y-5">
                    <div>
                        <flux:heading size="lg">2. Configurar calificación</flux:heading>
                        <flux:text class="mt-1">Valores iniciales del reglamento: +1 acierto, −0.01 error/doble, +0.1 blanco y mínimo 50.</flux:text>
                    </div>

                    <form wire:submit="guardarConfiguracion" class="space-y-5">
                        <div class="space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <div>
                                <flux:heading size="sm">Puntaje por respuesta</flux:heading>
                                <flux:text class="mt-1 text-xs">Valores aplicados a cada pregunta durante el cálculo.</flux:text>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <flux:input wire:model="configuracion.puntajeAcierto" type="number" step="0.001" label="Acierto" />
                                <flux:input wire:model="configuracion.puntajeError" type="number" step="0.001" label="Error o doble marca" />
                                <flux:input wire:model="configuracion.puntajeBlanco" type="number" step="0.001" label="En blanco" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-4 rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200 dark:bg-zinc-800/50 dark:ring-zinc-700">
                                <div>
                                    <flux:heading size="sm">Puntaje mínimo general</flux:heading>
                                    <flux:text class="mt-1 text-xs">Se aplica cuando una carrera no tiene una excepción del Art. 81.</flux:text>
                                </div>

                                <flux:input wire:model="configuracion.puntajeMinimo" type="number" step="0.0001" min="0" max="100" label="Puntaje mínimo" />
                            </div>

                            <div class="space-y-4 rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200 dark:bg-zinc-800/50 dark:ring-zinc-700">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <flux:heading size="sm">Factor de dificultad</flux:heading>
                                        <flux:text class="mt-1 text-xs">El umbral del Art. 80 se evalúa por carrera profesional.</flux:text>
                                    </div>
                                    <flux:switch wire:model="configuracion.aplicarFactor" aria-label="Aplicar factor de dificultad" />
                                </div>

                                <flux:input wire:model="configuracion.umbralFactor" type="number" step="0.01" min="0" max="100" label="Vacantes desiertas (%)" />
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex flex-wrap items-end justify-between gap-3">
                                <div>
                                    <flux:heading size="sm">Excepciones por carrera</flux:heading>
                                    <flux:text class="mt-1 text-xs">Define únicamente los puntajes mínimos distintos al general.</flux:text>
                                </div>
                                <flux:badge size="sm">{{ $carreras->count() }} carrera(s)</flux:badge>
                            </div>

                            <div class="max-h-80 overflow-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                                <table class="w-full min-w-[36rem] text-sm">
                                    <thead class="sticky top-0 z-10 bg-zinc-100 text-left dark:bg-zinc-800">
                                        <tr>
                                            <th class="px-4 py-3 font-medium">Carrera profesional</th>
                                            <th class="w-24 px-3 py-3 text-center font-medium">Vacantes</th>
                                            <th class="w-44 px-4 py-3 font-medium">Mínimo Art. 81</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                        @foreach ($carreras as $grupo)
                                            <tr wire:key="minimo-carrera-{{ $grupo['carrera']->id_car }}" class="align-top">
                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $grupo['carrera']->nombre_car }}</div>
                                                    <div class="mt-1 text-xs leading-5 text-zinc-500">
                                                        @foreach ($grupo['vacantes'] as $vacante)
                                                            {{ $vacante->modalidad->nombre_mod }} · {{ $vacante->sede->abreviatura() }} ({{ $vacante->cantidad_vac }}@if ($vacante->cantidad_arrastre_vac > 0)<span title="Arrastre de los Arts. 17, 18 y 19"> + {{ $vacante->cantidad_arrastre_vac }}</span>@endif)@if (! $loop->last) · @endif
                                                        @endforeach
                                                    </div>
                                                    @if ($examen->resuelto_en_exa)
                                                        <div class="mt-1 text-xs text-zinc-500">
                                                            {{ $grupo['ingresantes'] }} ingresante(s) ·
                                                            {{ max(0, $grupo['ofrecidas'] - $grupo['ingresantes']) }} desierta(s)
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-3 text-center">
                                                    <flux:badge size="sm">{{ $grupo['ofrecidas'] }}</flux:badge>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <flux:input wire:model="minimosCarreras.{{ $grupo['carrera']->id_car }}" type="number" step="0.01" min="0" max="100" placeholder="Usar general" />
                                                        @if ($estadisticas['resultados'] > 0)
                                                            <flux:button
                                                                :href="route('resultados.pdf', ['examen' => $examen, 'carrera' => $grupo['carrera']->id_car])"
                                                                icon="document-arrow-down"
                                                                variant="subtle"
                                                                square
                                                                tooltip="PDF de esta carrera"
                                                            />
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-end border-t border-zinc-200 pt-5 dark:border-zinc-700">
                            <flux:button type="submit" icon="check" variant="primary">Guardar configuración</flux:button>
                        </div>
                    </form>
                </flux:card>
            @endcan
        </div>

        <flux:card class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg">3. Generar y revisar resultados</flux:heading>
                    <flux:text class="mt-1">
                        @if ($examen->resuelto_en_exa)
                            Última resolución: {{ $examen->resuelto_en_exa->format('d/m/Y H:i') }}.
                        @else
                            La jornada todavía no ha sido resuelta.
                        @endif
                    </flux:text>
                </div>

                @can(App\Enums\Permiso::ResultadosGenerar->value)
                    <flux:button
                        wire:click="prepararGeneracion"
                        icon="calculator"
                        variant="primary"
                        :disabled="$estadisticas['padron'] === 0 || $estadisticas['sin_cruce'] > 0"
                    >
                        Generar resultados
                    </flux:button>
                @endcan
            </div>

            <div class="flex flex-wrap gap-3">
                <flux:input wire:model.live.debounce.300ms="buscar" icon="magnifying-glass" placeholder="Documento o postulante" class="min-w-64" />
                <flux:select wire:model.live="estado" class="min-w-44">
                    <flux:select.option value="">Todos los estados</flux:select.option>
                    @foreach (App\Enums\EstadoResultado::cases() as $opcionEstado)
                        <flux:select.option :value="$opcionEstado->value">{{ $opcionEstado->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if ($resultados?->isNotEmpty())
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column align="center">O. general</flux:table.column>
                            <flux:table.column align="center">O. carrera</flux:table.column>
                            <flux:table.column>Postulante</flux:table.column>
                            <flux:table.column>Carrera</flux:table.column>
                            <flux:table.column align="end">Puntaje</flux:table.column>
                            <flux:table.column align="center">Estado</flux:table.column>
                            @can(App\Enums\Permiso::ResultadosAnular->value)
                                <flux:table.column align="end">Acciones</flux:table.column>
                            @endcan
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($resultados as $resultado)
                                <flux:table.row :key="$resultado->id_res">
                                    <flux:table.cell align="center">{{ $resultado->orden_general_res ?? '—' }}</flux:table.cell>
                                    <flux:table.cell align="center">{{ $resultado->orden_carrera_res ?? '—' }}</flux:table.cell>
                                    <flux:table.cell>
                                        <div class="font-medium">{{ $resultado->postulante->nombre_exp }}</div>
                                        <div class="text-xs text-zinc-500">{{ $resultado->postulante->documento_exp }}</div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $resultado->postulante->inscripcion->carrera->nombre_corto_car }}
                                        @if ($resultado->repesca_res)
                                            <flux:badge size="sm" color="sky">Art. 23</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell align="end">{{ $resultado->puntaje_res === null ? '—' : number_format((float) $resultado->puntaje_res, 4) }}</flux:table.cell>
                                    <flux:table.cell align="center">
                                        <flux:badge :color="$resultado->estado_res->color()" :tooltip="$resultado->motivo_res">
                                            {{ $resultado->estado_res->etiqueta() }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    @can(App\Enums\Permiso::ResultadosAnular->value)
                                        <flux:table.cell align="end">
                                            @if ($resultado->postulante->estaAnulado())
                                                <flux:button
                                                    wire:click="restaurar({{ $resultado->id_exp }})"
                                                    icon="arrow-uturn-left"
                                                    variant="subtle"
                                                    size="sm"
                                                    square
                                                    tooltip="Restaurar la postulación"
                                                />
                                            @else
                                                <flux:button
                                                    wire:click="prepararAnulacion({{ $resultado->id_exp }})"
                                                    icon="no-symbol"
                                                    variant="subtle"
                                                    size="sm"
                                                    square
                                                    tooltip="Anular la postulación"
                                                />
                                            @endif
                                        </flux:table.cell>
                                    @endcan
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{ $resultados->links() }}
            @else
                <x-tabla.vacia :columnas="6" mensaje="Todavía no hay resultados generados para esta jornada." icono="chart-bar" />
            @endif
        </flux:card>

        @can(App\Enums\Permiso::ResultadosGenerar->value)
            <flux:modal name="confirmar-generacion" class="w-full md:max-w-4xl">
                @if ($previsualizacion !== [])
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="xl">Vista previa de la generación</flux:heading>
                            <flux:text class="mt-2">
                                Revisa la proyección antes de reemplazar los resultados de esta jornada.
                            </flux:text>
                        </div>

                        @if (! $previsualizacion['factor_habilitado'])
                            <flux:callout icon="information-circle" variant="secondary">
                                <flux:callout.heading>El factor de dificultad está desactivado</flux:callout.heading>
                                <flux:callout.text>Todos los postulantes conservarán un FDE de 1.000000.</flux:callout.text>
                            </flux:callout>
                        @elseif ($previsualizacion['factor_aplicado'])
                            <flux:callout icon="check-circle" variant="success">
                                <flux:callout.heading>Se aplicará el factor de dificultad</flux:callout.heading>
                                <flux:callout.text>
                                    {{ $previsualizacion['carreras_con_factor'] }} carrera(s) alcanzan el umbral de
                                    {{ number_format($previsualizacion['umbral_factor'], 2) }}% de vacantes desiertas.
                                </flux:callout.text>
                            </flux:callout>
                        @else
                            <flux:callout icon="information-circle" variant="secondary">
                                <flux:callout.heading>No se aplicará el factor de dificultad</flux:callout.heading>
                                <flux:callout.text>
                                    Ninguna carrera cumple todas las condiciones del Art. 80 con el umbral de
                                    {{ number_format($previsualizacion['umbral_factor'], 2) }}%.
                                </flux:callout.text>
                            </flux:callout>
                        @endif

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200 dark:bg-zinc-800/50 dark:ring-zinc-700">
                                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Postulantes</div>
                                <div class="mt-1 text-2xl font-semibold">{{ $previsualizacion['postulantes'] }}</div>
                            </div>
                            <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200 dark:bg-zinc-800/50 dark:ring-zinc-700">
                                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Vacantes</div>
                                <div class="mt-1 text-2xl font-semibold">{{ $previsualizacion['vacantes'] }}</div>
                            </div>
                            <div class="rounded-xl bg-green-50 p-4 ring-1 ring-green-200 dark:bg-green-950/30 dark:ring-green-800">
                                <div class="text-xs font-medium uppercase tracking-wide text-green-700 dark:text-green-300">Ingresantes estimados</div>
                                <div class="mt-1 text-2xl font-semibold text-green-800 dark:text-green-200">{{ $previsualizacion['ingresantes'] }}</div>
                                @if ($previsualizacion['ingresantes_adicionales_empate'] > 0)
                                    <div class="text-xs text-green-700 dark:text-green-300">
                                        +{{ $previsualizacion['ingresantes_adicionales_empate'] }} por empate (Art. 85)
                                    </div>
                                @endif
                            </div>
                            <div class="rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:ring-amber-800">
                                <div class="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-300">Desiertas estimadas</div>
                                <div class="mt-1 text-2xl font-semibold text-amber-800 dark:text-amber-200">{{ $previsualizacion['desiertas'] }}</div>
                                <div class="text-xs text-amber-700 dark:text-amber-300">{{ number_format($previsualizacion['porcentaje_desiertas'], 2) }}%</div>
                            </div>
                        </div>

                        @if ($previsualizacion['ingresantes_adicionales_empate'] > 0)
                            <flux:callout icon="information-circle" variant="warning">
                                <flux:callout.heading>Se detectó empate en el último puesto (Art. 85)</flux:callout.heading>
                                <flux:callout.text>
                                    Hay {{ $previsualizacion['ingresantes_adicionales_empate'] }} ingreso(s) adicional(es) por empate.
                                    Por eso se proyectan {{ $previsualizacion['ingresantes'] }} ingresantes para
                                    {{ $previsualizacion['vacantes'] }} vacantes. El reglamento dispone admitir a todos los empatados.
                                </flux:callout.text>
                            </flux:callout>
                        @endif

                        <div class="space-y-3">
                            <div>
                                <flux:heading size="lg">Evaluación del Art. 80 por carrera</flux:heading>
                                <flux:text class="mt-1 text-xs">
                                    “Sin factor” determina si alcanza el umbral; “Proyección final” muestra el resultado después del FDE.
                                </flux:text>
                            </div>

                            <div class="max-h-80 overflow-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                                <table class="w-full min-w-[46rem] text-sm">
                                    <thead class="sticky top-0 z-10 bg-zinc-100 text-left dark:bg-zinc-800">
                                        <tr>
                                            <th class="px-4 py-3 font-medium">Carrera profesional</th>
                                            <th class="w-40 px-4 py-3 font-medium">Sin factor</th>
                                            <th class="w-44 px-4 py-3 font-medium">Decisión Art. 80</th>
                                            <th class="w-40 px-4 py-3 font-medium">Proyección final</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                        @foreach ($previsualizacion['carreras'] as $carrera)
                                            <tr wire:key="vista-previa-carrera-{{ $carrera['id_car'] }}" class="align-top">
                                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $carrera['carrera'] }}</td>
                                                <td class="px-4 py-3">
                                                    <div>{{ $carrera['ingresantes_sin_factor'] }} ingreso(s)</div>
                                                    <div class="mt-1 text-xs text-zinc-500">
                                                        {{ $carrera['desiertas_sin_factor'] }} de {{ $carrera['vacantes'] }} desiertas
                                                        ({{ number_format($carrera['porcentaje_desiertas_sin_factor'], 2) }}%)
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if (! $previsualizacion['factor_habilitado'])
                                                        <flux:badge size="sm">Desactivado</flux:badge>
                                                    @elseif ($carrera['aplica_factor'])
                                                        <flux:badge size="sm" color="green">Sí · FDE {{ number_format($carrera['factor'], 6) }}</flux:badge>
                                                    @elseif (! $carrera['alcanza_umbral'])
                                                        <flux:badge size="sm">No alcanza el umbral</flux:badge>
                                                    @else
                                                        <flux:badge size="sm" color="amber">Sin puntaje máximo válido</flux:badge>
                                                    @endif
                                                    <div class="mt-1 text-xs text-zinc-500">
                                                        PME: {{ $carrera['puntaje_maximo'] === null ? '—' : number_format($carrera['puntaje_maximo'], 4) }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div>{{ $carrera['ingresantes_estimados'] }} ingreso(s)</div>
                                                    <div class="mt-1 text-xs text-zinc-500">{{ $carrera['desiertas_estimadas'] }} desierta(s)</div>
                                                    @if ($carrera['ingresantes_adicionales_empate'] > 0)
                                                        <div class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                                                            +{{ $carrera['ingresantes_adicionales_empate'] }} por empate (Art. 85)
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                            <div class="font-medium">Configuración usada en esta proyección</div>
                            <div class="mt-1 text-zinc-500">
                                Mínimo general: {{ number_format($previsualizacion['puntaje_minimo'], 4) }} ·
                                Umbral del Art. 80: {{ number_format($previsualizacion['umbral_factor'], 2) }}% ·
                                Factor: {{ $previsualizacion['factor_habilitado'] ? 'habilitado' : 'desactivado' }}
                            </div>
                        </div>

                        @if ($examen->resuelto_en_exa)
                            <flux:callout icon="exclamation-triangle" variant="warning">
                                <flux:callout.text>Al confirmar se reemplazará la resolución generada anteriormente.</flux:callout.text>
                            </flux:callout>
                        @endif

                        <div class="flex justify-end gap-2">
                            <flux:modal.close>
                                <flux:button variant="ghost">Volver a revisar</flux:button>
                            </flux:modal.close>
                            <flux:button wire:click="generar" wire:loading.attr="disabled" wire:target="generar" variant="primary" icon="calculator">
                                Confirmar y generar resultados
                            </flux:button>
                        </div>
                    </div>
                @endif
            </flux:modal>
        @endcan

        @can(App\Enums\Permiso::ResultadosAnular->value)
            <flux:modal name="anular-postulacion" class="md:w-96">
                <form wire:submit="anular" class="space-y-6">
                    <div>
                        <flux:heading size="lg">Anular la postulación</flux:heading>
                        <flux:text class="mt-2">
                            La anulación de los Arts. 79, 96 y 105 al 108 deja al postulante fuera del concurso de
                            vacantes y se conserva aunque vuelvas a generar los resultados.
                        </flux:text>
                    </div>

                    <flux:textarea
                        wire:model="motivoAnulacion"
                        label="Motivo y acto que la sustenta"
                        placeholder="Ej.: Retirado del aula por suplantación, acta CCA N.º 12-2027."
                        rows="3"
                    />

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancelar</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="danger" icon="no-symbol">Anular</flux:button>
                    </div>
                </form>
            </flux:modal>
        @endcan
    @endif
</div>
