<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Cuadro de vacantes"
        bajada="Las vacantes las aprueban las Escuelas Profesionales (Art. 15). Aquí se registran por proceso, modalidad y sede."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::VacantesEditar->value)
                @if ($proceso)
                    <flux:button wire:click="abrirAgregar" icon="plus">Agregar carrera</flux:button>
                    <flux:button wire:click="guardar" variant="primary" icon="check">Guardar cambios</flux:button>
                @endif
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <flux:select wire:model.live="codigoProceso" label="Proceso" class="max-w-sm">
        <flux:select.option value="">Elige un proceso</flux:select.option>
        @foreach ($procesos as $opcion)
            <flux:select.option :value="$opcion->codigo_pro">
                {{ $opcion->codigo_pro }} — {{ $opcion->nombre_pro }}
            </flux:select.option>
        @endforeach
    </flux:select>

    @if (! $proceso)
        <flux:callout icon="calendar-days" variant="secondary">
            <flux:callout.heading>Elige un proceso</flux:callout.heading>
            <flux:callout.text>
                El cuadro de vacantes es distinto en cada convocatoria. Selecciona una arriba para configurarla.
            </flux:callout.text>
        </flux:callout>
    @else
        <div class="grid gap-4 sm:grid-cols-3">
            <flux:card class="space-y-1">
                <flux:text size="sm">Vacantes ofertadas</flux:text>
                <flux:heading size="xl">{{ number_format($resumen['ofertadas']) }}</flux:heading>
            </flux:card>

            <flux:card class="space-y-1">
                <flux:text size="sm">Postulantes inscritos</flux:text>
                <flux:heading size="xl">{{ number_format($resumen['inscritos']) }}</flux:heading>
            </flux:card>

            <flux:card class="space-y-1">
                <flux:text size="sm">Carreras sin configurar</flux:text>
                <flux:heading size="xl" @class(['text-amber-600 dark:text-amber-400' => $resumen['sin_configurar'] > 0])>
                    {{ $resumen['sin_configurar'] }}
                </flux:heading>
            </flux:card>
        </div>

        @if ($resumen['sin_configurar'] > 0)
            <flux:callout icon="exclamation-triangle" variant="warning">
                <flux:callout.text>
                    Hay {{ $resumen['sin_configurar'] }} carrera(s) en cero. La importación de la oferta las crea así:
                    escribe la cantidad aprobada antes de publicar el cuadro.
                </flux:callout.text>
            </flux:callout>
        @endif

        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">Cuadro general {{ $reglamento['anio'] }} · Arts. 14 y 16</flux:heading>
                <flux:text class="mt-1">
                    El Art. 14 reparte el cuadro general en 25%, 25% y 50%, y el Art. 16 deja hasta el 30% de cada
                    Escuela Profesional al CEPREUNU. Se mide sobre la cifra aprobada, sin contar el arrastre de los
                    Arts. 17, 18 y 19. Son topes de referencia: la cifra final la ratifica el Consejo Universitario.
                </flux:text>
            </div>

            @if ($reglamento['observaciones'] === [])
                <flux:callout icon="check-circle" variant="success">
                    <flux:callout.text>
                        Las {{ number_format($reglamento['total']) }} vacantes del año respetan ambos artículos.
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:callout icon="exclamation-triangle" variant="warning">
                    <flux:callout.heading>Revisar antes de publicar el cuadro</flux:callout.heading>
                    <flux:callout.text>
                        <ul class="list-disc space-y-1 ps-4">
                            @foreach ($reglamento['observaciones'] as $observacion)
                                <li><strong>{{ $observacion['articulo'] }}:</strong> {{ $observacion['mensaje'] }}</li>
                            @endforeach
                        </ul>
                    </flux:callout.text>
                </flux:callout>
            @endif

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-100 text-left dark:bg-zinc-800">
                            <tr>
                                <th class="px-3 py-2">Convocatoria (Art. 14)</th>
                                <th class="w-24 px-3 py-2 text-right">Vacantes</th>
                                <th class="w-28 px-3 py-2 text-right">Reparto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($reglamento['art14'] as $fila)
                                <tr wire:key="art14-{{ $fila['convocatoria']->value }}">
                                    <td class="px-3 py-2">
                                        <div class="font-medium">{{ $fila['convocatoria']->etiqueta() }}</div>
                                        <div class="text-xs text-zinc-500">
                                            {{ $fila['proceso']?->codigo_pro ?? 'Sin configurar' }} ·
                                            le corresponde {{ $fila['porcentaje_esperado'] }}% ({{ $fila['esperadas'] }})
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ number_format($fila['vacantes']) }}</td>
                                    <td @class([
                                        'px-3 py-2 text-right font-semibold',
                                        'text-amber-600 dark:text-amber-400' => $reglamento['completo'] && ! $fila['cumple'],
                                    ])>
                                        {{ number_format($fila['porcentaje'], 2) }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="max-h-72 overflow-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-zinc-100 text-left dark:bg-zinc-800">
                            <tr>
                                <th class="px-3 py-2">Escuela Profesional (Art. 16)</th>
                                <th class="w-28 px-3 py-2 text-right">CEPREUNU</th>
                                <th class="w-24 px-3 py-2 text-right">Cupo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($reglamento['art16'] as $fila)
                                <tr wire:key="art16-{{ $fila['carrera']->id_car }}">
                                    <td class="px-3 py-2">{{ $fila['carrera']->nombre_corto_car }}</td>
                                    <td class="px-3 py-2 text-right">
                                        {{ $fila['cepreunu'] }} de {{ $fila['total'] }}
                                    </td>
                                    <td @class([
                                        'px-3 py-2 text-right font-semibold',
                                        'text-amber-600 dark:text-amber-400' => $fila['excede'],
                                    ])>
                                        {{ number_format($fila['porcentaje'], 2) }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-4 text-center text-zinc-500">
                                        Todavía no hay vacantes cargadas en el año.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </flux:card>

        @forelse ($cuadro as $grupo => $filas)
            <div class="space-y-2">
                <div class="flex items-baseline justify-between gap-3">
                    <flux:heading size="lg">{{ $grupo }}</flux:heading>
                    <flux:text size="sm">
                        {{ $filas->sum('cantidad_vac') }} vacantes · {{ $filas->sum('inscritos') }} inscritos
                    </flux:text>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Carrera</flux:table.column>
                        <flux:table.column align="center">Área</flux:table.column>
                        <flux:table.column align="center">Cód. formato</flux:table.column>
                        <flux:table.column align="center">Vacantes</flux:table.column>
                        <flux:table.column align="center">Inscritos</flux:table.column>
                        <flux:table.column align="end">Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($filas as $vacante)
                            <flux:table.row :key="$vacante->id_vac">
                                <flux:table.cell class="max-w-90">
                                    <span class="block truncate font-medium text-zinc-800 dark:text-white">
                                        {{ $vacante->carrera->nombre_car }}
                                    </span>
                                    <span class="text-xs text-zinc-500">{{ $vacante->carrera->facultad->nombre_fac }}</span>
                                </flux:table.cell>

                                <flux:table.cell align="center">
                                    <flux:tooltip :content="$vacante->carrera->area->nombre_are">
                                        <flux:badge color="zinc" size="sm">{{ $vacante->carrera->area->numero_are }}</flux:badge>
                                    </flux:tooltip>
                                </flux:table.cell>

                                <flux:table.cell align="center" class="font-mono text-xs text-zinc-500">
                                    {{ $vacante->codigo_externo_vac ?? '—' }}
                                </flux:table.cell>

                                <flux:table.cell align="center">
                                    @can(App\Enums\Permiso::VacantesEditar->value)
                                        <flux:input
                                            wire:model="cantidades.{{ $vacante->id_vac }}"
                                            type="number"
                                            min="0"
                                            max="9999"
                                            size="sm"
                                            class="mx-auto max-w-24 text-center"
                                        />
                                    @else
                                        {{ $vacante->cantidad_vac }}
                                    @endcan
                                </flux:table.cell>

                                <flux:table.cell align="center">
                                    @if ($vacante->inscritos > $vacante->cantidad_vac)
                                        <flux:tooltip content="Hay más inscritos que vacantes ofertadas">
                                            <flux:badge color="red" size="sm">{{ $vacante->inscritos }}</flux:badge>
                                        </flux:tooltip>
                                    @else
                                        {{ $vacante->inscritos }}
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    @can(App\Enums\Permiso::VacantesEditar->value)
                                        <x-tabla.accion
                                            wire:click="eliminar({{ $vacante->id_vac }})"
                                            wire:confirm="¿Quitar «{{ $vacante->carrera->nombre_car }}» del cuadro?"
                                            icon="trash"
                                            tooltip="Quitar del cuadro"
                                        />
                                    @endcan
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @empty
            <flux:callout icon="table-cells" variant="secondary">
                <flux:callout.heading>Este proceso todavía no tiene cuadro</flux:callout.heading>
                <flux:callout.text>
                    Agrega las carreras a mano, o cárgalas desde el archivo del formato con
                    <code class="font-mono text-xs">php artisan admision:importar-oferta</code>.
                </flux:callout.text>
            </flux:callout>
        @endforelse
    @endif

    <flux:modal name="agregar-vacante" class="w-full md:max-w-lg">
        <form wire:submit="agregar" class="space-y-6">
            <div>
                <flux:heading size="lg">Agregar carrera al cuadro</flux:heading>
                <flux:subheading>Una carrera puede estar varias veces si se oferta en distintas modalidades o sedes.</flux:subheading>
            </div>

            <flux:select wire:model="nuevaModalidad" label="Modalidad" placeholder="Elige una modalidad">
                @foreach ($modalidades as $modalidad)
                    <flux:select.option :value="$modalidad->id_mod">{{ $modalidad->nombre_mod }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="nuevaCarrera" label="Carrera" placeholder="Elige una carrera">
                @foreach ($carreras as $carrera)
                    <flux:select.option :value="$carrera->id_car">{{ $carrera->nombre_car }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="nuevaSede" label="Sede" placeholder="Elige una sede">
                @foreach ($sedes as $sede)
                    <flux:select.option :value="$sede->id_sed">{{ $sede->nombre_sed }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="nuevaCantidad" type="number" min="0" max="9999" label="Vacantes" />

                <flux:input
                    wire:model="nuevoCodigoExterno"
                    type="number"
                    min="1"
                    label="Código del formato"
                    description="El de la hoja CARRERAS_PROFESIONALES (2555, 2556…)."
                />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Agregar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
