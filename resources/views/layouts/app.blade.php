<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('img/isologo-unu.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('img/isologo-unu.png') }}" />

    @vite (['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    @fluxAppearance
</head>
<body class="min-h-screen bg-white antialiased dark:bg-zinc-800">
    <flux:sidebar sticky collapsible class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            {{--
                El contenedor por defecto del logo mide 24px y recorta lo que
                sobra, y a ese tamano el escudo es una mancha. Las clases del
                slot lo agrandan: las de Flux van con :where(), asi que pierden
                sin necesidad de !important.
            --}}
            <flux:sidebar.brand :href="route('inicio.dashboard')" name="Admisión UNU">
                <x-slot:logo
                    class="h-9 min-w-9 rounded-lg bg-white p-1 ring-1 ring-zinc-200 dark:ring-zinc-700"
                >
                    <x-marca.isologo class="h-full" />
                </x-slot:logo>
            </flux:sidebar.brand>

            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2"
            />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item
                icon="home"
                :href="route('inicio.dashboard')"
                :current="request()->routeIs('inicio.dashboard')"
                wire:navigate
            >
                Inicio
            </flux:sidebar.item>

            @can(App\Enums\Permiso::InscripcionesVer->value)
                <flux:sidebar.item
                    icon="clipboard-document-list"
                    :href="route('inscripciones.index')"
                    :current="request()->routeIs('inscripciones.*')"
                    wire:navigate
                >
                    Inscripciones
                </flux:sidebar.item>
            @endcan

            @can(App\Enums\Permiso::ResultadosVer->value)
                <flux:sidebar.item
                    icon="building-library"
                    :href="route('resultados.aulas')"
                    :current="request()->routeIs('resultados.aulas*')"
                    wire:navigate
                >
                    Examen y aulas
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="chart-bar-square"
                    :href="route('resultados.procesamiento')"
                    :current="request()->routeIs('resultados.procesamiento*')"
                    wire:navigate
                >
                    Resultados
                </flux:sidebar.item>
            @endcan

            @can(App\Enums\Permiso::IngresantesVer->value)
                <flux:sidebar.item
                    icon="user-group"
                    :href="route('resultados.ingresantes')"
                    :current="request()->routeIs('resultados.ingresantes*')"
                    wire:navigate
                >
                    Ingresantes
                </flux:sidebar.item>
            @endcan

            @if (app(App\Services\Auth\AccesoService::class)->puedeAlguno(auth()->user(), [
                App\Enums\Permiso::ProcesosVer,
                App\Enums\Permiso::VacantesVer,
                App\Enums\Permiso::FacultadesVer,
                App\Enums\Permiso::AreasVer,
                App\Enums\Permiso::CarrerasVer,
                App\Enums\Permiso::SedesVer,
                App\Enums\Permiso::AulasVer,
            ]))
                <flux:separator class="my-2" />

                <div class="px-3 pb-1 text-xs font-medium uppercase tracking-wide text-zinc-500">Configuración</div>
            @endif

            @can(App\Enums\Permiso::ProcesosVer->value)
                <flux:sidebar.item icon="calendar-days" :href="route('configuracion.procesos')" :current="request()->routeIs('configuracion.procesos')" wire:navigate>Procesos</flux:sidebar.item>
            @endcan
            @can(App\Enums\Permiso::VacantesVer->value)
                <flux:sidebar.item icon="table-cells" :href="route('configuracion.vacantes')" :current="request()->routeIs('configuracion.vacantes')" wire:navigate>Vacantes</flux:sidebar.item>
            @endcan
            @can(App\Enums\Permiso::FacultadesVer->value)
                <flux:sidebar.item icon="building-office-2" :href="route('configuracion.facultades')" :current="request()->routeIs('configuracion.facultades')" wire:navigate>Facultades</flux:sidebar.item>
            @endcan
            @can(App\Enums\Permiso::AreasVer->value)
                <flux:sidebar.item icon="squares-2x2" :href="route('configuracion.areas')" :current="request()->routeIs('configuracion.areas')" wire:navigate>Áreas</flux:sidebar.item>
            @endcan
            @can(App\Enums\Permiso::CarrerasVer->value)
                <flux:sidebar.item icon="academic-cap" :href="route('configuracion.carreras')" :current="request()->routeIs('configuracion.carreras')" wire:navigate>Carreras</flux:sidebar.item>
            @endcan
            @can(App\Enums\Permiso::SedesVer->value)
                <flux:sidebar.item icon="map-pin" :href="route('configuracion.sedes')" :current="request()->routeIs('configuracion.sedes')" wire:navigate>Sedes</flux:sidebar.item>
            @endcan
            @can(App\Enums\Permiso::AulasVer->value)
                <flux:sidebar.item icon="rectangle-group" :href="route('configuracion.aulas')" :current="request()->routeIs('configuracion.aulas')" wire:navigate>Aulas</flux:sidebar.item>
            @endcan
        </flux:sidebar.nav>
    </flux:sidebar>

    <flux:header
        container
        class="border-b border-zinc-200 bg-white lg:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900"
    >
        <flux:sidebar.toggle class="me-4 lg:hidden" icon="bars-2" inset="left" />

        {{ $migas ?? '' }}

        <flux:spacer />

        <flux:navbar class="me-4">
            <flux:button
                x-data
                x-on:click="$flux.dark = !$flux.dark"
                icon="moon"
                icon:variant="outline"
                variant="subtle"
                aria-label="Cambiar entre modo claro y oscuro"
            />
        </flux:navbar>

        @php($usuario = auth()->user())

        <flux:dropdown position="top" align="start">
            <flux:profile
                :name="$usuario?->nombre_usu"
                :initials="$usuario?->iniciales()"
                aria-label="Menu de la cuenta"
            />

            <flux:menu>
                <div class="px-2 py-1.5">
                    <div class="text-sm font-medium">{{ $usuario?->nombre_usu }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $usuario?->rol?->nombre_rol }}</div>
                </div>

                <flux:menu.separator />

                <flux:menu.item icon="user" :href="route('perfil')" wire:navigate>Mi perfil</flux:menu.item>

                <flux:menu.separator />

                <form method="POST" action="{{ route('auth.salir') }}">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        Salir
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    <flux:main container>
        {{ $slot }}
    </flux:main>

    <x-alert />

    @persist ('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @livewireScripts
    @fluxScripts
</body>
</html>
