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
<body class="min-h-screen bg-white antialiased dark:bg-zinc-950">
    <div class="flex min-h-screen">
        {{--
            Panel institucional. Solo desde lg: por debajo de ese ancho el
            formulario ocupa la pantalla completa, que es lo unico que importa
            cuando se entra desde el telefono.
        --}}
        <aside class="relative hidden w-[46%] max-w-3xl shrink-0 flex-col justify-between overflow-hidden bg-unu-900 p-14 lg:flex">
            {{-- Profundidad: dos focos de luz sobre el verde plano. --}}
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0"
                style="
                    background:
                        radial-gradient(70rem 55rem at 100% -15%, var(--color-unu-600) 0%, transparent 58%),
                        radial-gradient(50rem 50rem at -25% 115%, var(--color-unu-950) 0%, transparent 62%);
                "
            ></div>

            {{-- Trama fina, apenas perceptible: evita que el fondo se vea plano. --}}
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 opacity-[0.07]"
                style="
                    background-image: repeating-linear-gradient(
                        135deg,
                        #fff 0px,
                        #fff 1px,
                        transparent 1px,
                        transparent 11px
                    );
                "
            ></div>

            {{-- El escudo como sello al margen, desbordado a proposito. --}}
            <x-marca.isologo
                aria-hidden="true"
                alt=""
                class="pointer-events-none absolute -right-32 -bottom-32 h-[34rem] opacity-[0.05] grayscale"
            />

            <div class="relative flex items-center gap-4">
                <div class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-white p-2 shadow-xl shadow-black/20">
                    <x-marca.isologo class="h-full" />
                </div>

                <div class="text-white">
                    <div class="text-sm leading-tight font-semibold tracking-tight">
                        Universidad Nacional<br />de Ucayali
                    </div>
                    <div class="mt-1 text-xs text-white/60">Pucallpa · Perú</div>
                </div>
            </div>

            {{--
                El titular y el credito van juntos abajo: con tres bloques
                repartidos, en pantallas altas quedaba un vacio de 400px entre
                la marca y el texto.
            --}}
            <div class="relative max-w-md">
                <div class="mb-5 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white/90 ring-1 ring-white/15">
                    <span class="size-1.5 rounded-full bg-unu-300"></span>
                    Plataforma institucional
                </div>

                <h1 class="text-4xl leading-[1.1] font-semibold tracking-tight text-balance text-white">
                    Sistema de Admisión
                </h1>

                <p class="mt-5 text-sm leading-relaxed text-pretty text-white/70">
                    Postula a las convocatorias, haz seguimiento a tu inscripción y consulta
                    los resultados del proceso desde un solo lugar.
                </p>

                <p class="mt-12 text-xs text-white/45">
                    &copy; {{ now()->year }} Universidad Nacional de Ucayali
                </p>
            </div>
        </aside>

        {{-- Formulario --}}
        <main class="relative flex flex-1 flex-col px-6 py-8 sm:px-10">
            <div class="flex justify-end">
                <flux:button
                    x-data
                    x-on:click="$flux.dark = !$flux.dark"
                    icon="moon"
                    icon:variant="outline"
                    variant="ghost"
                    size="sm"
                    class="text-zinc-500 dark:text-zinc-400"
                    aria-label="Cambiar entre modo claro y oscuro"
                />
            </div>

            <div class="flex flex-1 items-center justify-center py-8">
                <div class="w-full max-w-sm">
                    {{-- La marca se repite aqui solo donde el panel no se ve. --}}
                    <div class="mb-10 flex flex-col items-center gap-3 lg:hidden">
                        <x-marca.isologo class="h-20" />
                        <div class="text-center">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                Universidad Nacional de Ucayali
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">Sistema de Admisión</div>
                        </div>
                    </div>

                    {{ $slot }}
                </div>
            </div>

            <p class="text-center text-xs text-zinc-400 lg:hidden dark:text-zinc-500">
                &copy; {{ now()->year }} Universidad Nacional de Ucayali
            </p>
        </main>
    </div>

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
