
<div class="space-y-6">
    <x-pagina.encabezado titulo="Examen y aulas" bajada="Define la capacidad y el área de cada aula antes de sortear las ubicaciones.">
        <x-slot:acciones>
            @can(App\Enums\Permiso::ResultadosConfigurarAulas->value)
                <flux:button wire:click="nuevoExamen" variant="primary" icon="plus">Nueva jornada</flux:button>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <flux:callout icon="information-circle" variant="secondary">
        <flux:callout.text>Una aula pertenece a una sola área en una jornada y admite como máximo 40 postulantes. La suma de capacidades debe coincidir exactamente con los inscritos de cada área.</flux:callout.text>
    </flux:callout>

    <div class="flex flex-wrap items-end gap-3">
        <flux:select wire:model.live="procesoSeleccionado" label="Proceso" class="min-w-48">
            @foreach ($procesos as $proceso)
                <flux:select.option :value="$proceso->id_pro">{{ $proceso->codigo_pro }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="procesoSeleccionado" />

        <flux:select wire:model.live="examenSeleccionado" label="Jornada de examen" class="min-w-64">
            <flux:select.option value="">Elige una jornada</flux:select.option>
            @foreach ($examenes as $opcion)
                <flux:select.option :value="$opcion->id_exa">{{ $opcion->nombre_exa }}{{ $opcion->fecha_exa ? ' · '.$opcion->fecha_exa->format('d/m/Y') : '' }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div
        wire:loading.flex
        wire:target="procesoSeleccionado,examenSeleccionado"
        class="items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300"
        role="status"
        aria-live="polite"
    >
        <flux:icon.arrow-path class="size-4 animate-spin text-blue-600" />
        Cargando jornada y distribución…
    </div>

    @if ($examen)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($totales as $total)
                <flux:card class="space-y-1" wire:key="total-{{ $total['id_are'] }}">
                    <flux:text size="sm">Área {{ $total['id_are'] }}</flux:text>
                    <flux:heading size="lg">{{ $total['capacidad'] }} / {{ $total['inscritos'] }}</flux:heading>
                    <flux:text size="sm" @class(['text-green-600' => $total['diferencia'] === 0, 'text-amber-600' => $total['diferencia'] !== 0])>
                        {{ $total['diferencia'] === 0 ? 'Distribución completa' : ($total['diferencia'] > 0 ? '+'.$total['diferencia'].' cupos' : $total['diferencia'].' cupos') }}
                    </flux:text>
                </flux:card>
            @empty
                <flux:card class="sm:col-span-2 lg:col-span-4"><flux:text>Aún no hay inscritos ni aulas configuradas para esta jornada.</flux:text></flux:card>
            @endforelse
        </div>

        @can(App\Enums\Permiso::ResultadosConfigurarAulas->value)
            <flux:card>
                <form wire:submit="agregarAula" class="grid items-end gap-4 lg:grid-cols-4">
                    <flux:select wire:model.live="formAula.aula" label="Aula" placeholder="Elige un aula">
                        @foreach ($aulas as $aula)
                            <flux:select.option :value="$aula->id_aul">{{ $aula->etiqueta() }} · {{ $aula->sede->nombre_sed }} (máx. {{ min(40, $aula->capacidad_aul) }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="formAula.area" label="Área" placeholder="Elige un área">
                        @foreach ($areas as $area)
                            <flux:select.option :value="$area->id_are">{{ $area->etiqueta() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="formAula.capacidad" type="number" min="1" max="40" label="Postulantes" />
                    <flux:button type="submit" variant="primary" icon="plus" wire:loading.attr="disabled" wire:target="agregarAula">
                        <span wire:loading.remove wire:target="agregarAula">Agregar aula</span>
                        <span wire:loading wire:target="agregarAula">Agregando…</span>
                    </flux:button>
                </form>
                <flux:error name="formAula.aula" />
            </flux:card>
        @endcan

        <flux:table>
            <flux:table.columns><flux:table.column>Aula</flux:table.column><flux:table.column>Área</flux:table.column><flux:table.column align="center">Capacidad</flux:table.column><flux:table.column align="end">Acciones</flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse ($distribucion as $fila)
                    <flux:table.row :key="$fila->id_eau">
                        <flux:table.cell><div class="font-medium">{{ $fila->aula->etiqueta() }}</div><div class="text-xs text-zinc-500">{{ $fila->aula->sede->nombre_sed }}</div></flux:table.cell>
                        <flux:table.cell>{{ $fila->area->etiqueta() }}</flux:table.cell>
                        <flux:table.cell align="center">{{ $fila->capacidad_eau }}</flux:table.cell>
                        <flux:table.cell align="end"><div class="flex justify-end gap-1"><a href="{{ route('resultados.aulas.padron', $fila) }}"><flux:button size="sm" variant="subtle" icon="document-arrow-down">PDF</flux:button></a>@can(App\Enums\Permiso::ResultadosConfigurarAulas->value)<x-tabla.accion wire:click="retirarAula({{ $fila->id_eau }})" wire:confirm="¿Retirar esta aula de la distribución?" icon="trash" tooltip="Retirar" />@endcan</div></flux:table.cell>
                    </flux:table.row>
                @empty
                    <x-tabla.vacia :columnas="4" mensaje="Agrega las aulas que se usarán en esta jornada." icono="building-office-2" />
                @endforelse
            </flux:table.rows>
        </flux:table>

        @can(App\Enums\Permiso::ResultadosSortearAulas->value)
            <div class="flex justify-end"><flux:button wire:click="sortear" wire:confirm="¿Generar nuevamente el sorteo de aula y asiento? La asignación anterior será reemplazada." variant="primary" icon="arrows-right-left">Sortear aulas y asientos</flux:button></div>
        @endcan
    @endif

    <flux:modal name="examen" class="w-full md:max-w-lg">
        <form wire:submit="crearExamen" class="space-y-5">
            <div><flux:heading size="lg">Nueva jornada de examen</flux:heading><flux:subheading>La distribución y el sorteo se guardan dentro de esta jornada.</flux:subheading></div>
            <flux:input wire:model="formExamen.nombre" label="Nombre" placeholder="Examen CEPRE 2027-I" />
            <flux:input wire:model="formExamen.fecha" type="date" label="Fecha" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Crear jornada</flux:button></div>
        </form>
    </flux:modal>
</div>
