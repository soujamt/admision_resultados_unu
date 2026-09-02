<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Padrón de ingresantes"
        bajada="Registra quién conserva su condición de ingresante y arrastra al cuadro de vacantes lo que liberan los Arts. 17, 18 y 19."
    />

    <div class="flex flex-wrap items-end gap-3">
        <flux:select wire:model.live="procesoSeleccionado" label="Proceso" class="min-w-44">
            @foreach ($procesos as $opcion)
                <flux:select.option :value="$opcion->id_pro">{{ $opcion->codigo_pro }}</flux:select.option>
            @endforeach
        </flux:select>

        @can(App\Enums\Permiso::IngresantesGenerar->value)
            <flux:select wire:model="examenSeleccionado" label="Jornada resuelta" class="min-w-72">
                <flux:select.option value="">Elige una jornada</flux:select.option>
                @foreach ($examenes as $opcion)
                    <flux:select.option :value="$opcion->id_exa">
                        {{ $opcion->nombre_exa }}{{ $opcion->fecha_exa ? ' · '.$opcion->fecha_exa->format('d/m/Y') : '' }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:button wire:click="generarPadron" icon="clipboard-document-check" variant="primary">
                Generar padrón
            </flux:button>
        @endcan
    </div>

    <div wire:loading.flex wire:target="procesoSeleccionado" class="items-center gap-2 text-sm text-zinc-600">
        <flux:icon.arrow-path class="size-4 animate-spin text-blue-600" />
        Cargando el padrón…
    </div>

    @if (! $proceso)
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.heading>Elige un proceso</flux:callout.heading>
            <flux:callout.text>El padrón de ingresantes se lleva por convocatoria.</flux:callout.text>
        </flux:callout>
    @else
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Ingresantes', $estadisticas['total'], 'user-group'],
                ['Vigentes', $estadisticas['vigentes'], 'check-circle'],
                ['Perdieron la condición', $estadisticas['perdidas'], 'exclamation-triangle'],
                ['Entraron por el Art. 93', $estadisticas['sustitutos'], 'arrows-right-left'],
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

        @can(App\Enums\Permiso::IngresantesArrastrar->value)
            <flux:card class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Arrastre de vacantes</flux:heading>
                        <flux:text class="mt-1">
                            Art. 17 y 18: lo que la primera y la segunda convocatoria no cubrieron o liberaron pasa a la
                            tercera en su misma modalidad. Art. 19: lo que no cubren exoneración, convenios, PRONABEC y
                            traslados incrementa el examen ordinario.
                        </flux:text>
                    </div>

                    @if ($esTercera)
                        <div class="flex gap-2">
                            <flux:button wire:click="previsualizarArrastre" icon="calculator" variant="filled">
                                Previsualizar
                            </flux:button>
                            <flux:button wire:click="aplicarArrastre" icon="arrow-down-tray" variant="primary">
                                Aplicar al cuadro
                            </flux:button>
                        </div>
                    @endif
                </div>

                @if (! $esTercera)
                    <flux:callout icon="information-circle" variant="secondary">
                        <flux:callout.text>
                            El arrastre solo corresponde a la tercera convocatoria. Marca aquí quién pierde la condición
                            de ingresante y aplícalo desde el proceso de la tercera.
                        </flux:callout.text>
                    </flux:callout>
                @elseif ($arrastre !== [])
                    @if ($arrastre['sin_sustituto'] > 0)
                        <flux:callout icon="exclamation-triangle" variant="warning">
                            <flux:callout.text>
                                Hay {{ $arrastre['sin_sustituto'] }} plaza(s) de ingresantes que no se matricularon y para
                                las que el Art. 93 no encontró inmediato inferior. El reglamento no las arrastra: decídelo
                                en comisión.
                            </flux:callout.text>
                        </flux:callout>
                    @endif

                    @if ($arrastre['lineas'] === [])
                        <flux:text>No hay plazas que arrastrar con el padrón actual.</flux:text>
                    @else
                        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-100 text-left dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-3 py-2">Vacante que recibe</th>
                                        <th class="w-20 px-3 py-2 text-right">Art. 17</th>
                                        <th class="w-20 px-3 py-2 text-right">Art. 18</th>
                                        <th class="w-20 px-3 py-2 text-right">Art. 19</th>
                                        <th class="w-20 px-3 py-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach ($arrastre['lineas'] as $linea)
                                        <tr wire:key="arrastre-{{ $linea['vacante']->id_vac }}">
                                            <td class="px-3 py-2">
                                                <div class="font-medium">{{ $linea['vacante']->carrera->nombre_car }}</div>
                                                <div class="text-xs text-zinc-500">
                                                    {{ $linea['vacante']->modalidad->nombre_mod }} ·
                                                    {{ $linea['vacante']->sede->abreviatura() }} ·
                                                    origen: {{ implode(', ', $linea['origenes']) }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-right">{{ $linea['art17'] ?: '—' }}</td>
                                            <td class="px-3 py-2 text-right">{{ $linea['art18'] ?: '—' }}</td>
                                            <td class="px-3 py-2 text-right">{{ $linea['art19'] ?: '—' }}</td>
                                            <td class="px-3 py-2 text-right font-semibold">{{ $linea['total'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <flux:text>
                            Total a arrastrar: <strong>{{ $arrastre['total'] }}</strong> plaza(s).
                            Después de aplicarlo hay que volver a generar los resultados para repartirlas.
                        </flux:text>
                    @endif
                @endif
            </flux:card>
        @endcan

        <flux:card class="space-y-4">
            <div class="flex flex-wrap items-end gap-3">
                <flux:input wire:model.live.debounce.400ms="buscar" label="Buscar" placeholder="Documento o apellidos" class="min-w-64" />

                <flux:select wire:model.live="condicion" label="Condición" class="min-w-52">
                    <flux:select.option value="">Todas</flux:select.option>
                    @foreach (App\Enums\CondicionIngresante::cases() as $opcion)
                        <flux:select.option :value="$opcion->value">{{ $opcion->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if ($ingresantes?->isNotEmpty())
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column align="center">O. carrera</flux:table.column>
                            <flux:table.column>Ingresante</flux:table.column>
                            <flux:table.column>Carrera y modalidad</flux:table.column>
                            <flux:table.column align="end">Puntaje</flux:table.column>
                            <flux:table.column align="center">Condición</flux:table.column>
                            @can(App\Enums\Permiso::IngresantesCondicion->value)
                                <flux:table.column align="end">Acciones</flux:table.column>
                            @endcan
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($ingresantes as $ingresante)
                                <flux:table.row :key="$ingresante->id_ing">
                                    <flux:table.cell align="center">{{ $ingresante->orden_carrera_ing ?? '—' }}</flux:table.cell>
                                    <flux:table.cell>
                                        <div class="font-medium">{{ $ingresante->inscripcion->postulante->nombreCompleto() }}</div>
                                        <div class="text-xs text-zinc-500">
                                            {{ $ingresante->inscripcion->postulante->numero_documento_pos }}
                                            @if ($ingresante->sustituido)
                                                · Art. 93, reemplaza a {{ $ingresante->sustituido->inscripcion->postulante->nombreCompleto() }}
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div>{{ $ingresante->inscripcion->carrera->nombre_corto_car }}</div>
                                        <div class="text-xs text-zinc-500">{{ $ingresante->vacante->modalidad->nombre_mod }}</div>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        {{ $ingresante->puntaje_ing === null ? '—' : number_format((float) $ingresante->puntaje_ing, 4) }}
                                    </flux:table.cell>
                                    <flux:table.cell align="center">
                                        <flux:badge :color="$ingresante->condicion_ing->color()" :tooltip="$ingresante->motivo_ing">
                                            {{ $ingresante->condicion_ing->etiqueta() }}
                                        </flux:badge>
                                        @if ($ingresante->condicion_ing->perdioCondicion())
                                            <div class="mt-1 text-xs text-zinc-500">
                                                {{ $ingresante->condicion_ing->articulo() }}
                                                @if ($ingresante->sustituto)
                                                    · reemplazado
                                                @endif
                                            </div>
                                        @endif
                                    </flux:table.cell>
                                    @can(App\Enums\Permiso::IngresantesCondicion->value)
                                        <flux:table.cell align="end">
                                            @if ($ingresante->estaVigente())
                                                <flux:button
                                                    wire:click="prepararCondicion({{ $ingresante->id_ing }})"
                                                    icon="user-minus"
                                                    variant="subtle"
                                                    size="sm"
                                                    square
                                                    tooltip="Registrar pérdida de la condición"
                                                />
                                            @else
                                                <flux:button
                                                    wire:click="restaurar({{ $ingresante->id_ing }})"
                                                    icon="arrow-uturn-left"
                                                    variant="subtle"
                                                    size="sm"
                                                    square
                                                    tooltip="Devolver la condición de ingresante"
                                                />
                                            @endif
                                        </flux:table.cell>
                                    @endcan
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{ $ingresantes->links() }}
            @else
                <x-tabla.vacia :columnas="6" mensaje="Todavía no hay padrón de ingresantes para este proceso." icono="user-group" />
            @endif
        </flux:card>

        @can(App\Enums\Permiso::IngresantesCondicion->value)
            <flux:modal name="perder-condicion" class="md:w-96">
                <form wire:submit="registrarCondicion" class="space-y-6">
                    <div>
                        <flux:heading size="lg">Pérdida de la condición de ingresante</flux:heading>
                        <flux:text class="mt-2">
                            La vacante queda libre. Si el motivo es la falta de matrícula, el Art. 93 llama en el acto al
                            inmediato inferior de la misma modalidad en la tercera convocatoria.
                        </flux:text>
                    </div>

                    <flux:select wire:model="nuevaCondicion" label="Motivo reglamentario">
                        <flux:select.option value="">Elige el motivo</flux:select.option>
                        @foreach (App\Enums\CondicionIngresante::perdidas() as $opcion)
                            <flux:select.option :value="$opcion->value">
                                {{ $opcion->etiqueta() }} · {{ $opcion->articulo() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:textarea
                        wire:model="motivoCondicion"
                        label="Sustento"
                        placeholder="Ej.: No se matriculó en el plazo del cronograma, informe DA N.º 45-2027."
                        rows="3"
                    />

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancelar</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="danger" icon="user-minus">Registrar</flux:button>
                    </div>
                </form>
            </flux:modal>
        @endcan
    @endif
</div>
