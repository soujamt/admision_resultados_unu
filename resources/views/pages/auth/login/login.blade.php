<div>
    <div class="mb-8">
        <flux:heading size="xl" level="1" class="text-2xl! tracking-tight">Iniciar sesión</flux:heading>
        <flux:subheading class="mt-2">
            Ingresa con el correo asociado a tu cuenta institucional.
        </flux:subheading>
    </div>

    @if (session('acceso'))
        <flux:callout icon="check-circle" variant="success" class="mb-6">
            <flux:callout.text>{{ session('acceso') }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="autenticar" class="flex flex-col gap-5">
        <flux:input
            wire:model="form.usuario"
            type="email"
            label="Correo"
            placeholder="correo@ejemplo.pe"
            autocomplete="username"
            icon="envelope"
            autofocus
        />

        <flux:field>
            <div class="mb-2 flex items-baseline justify-between gap-3">
                <flux:label>Contraseña</flux:label>

                <flux:link :href="route('auth.recuperar')" wire:navigate variant="subtle" class="text-xs">
                    ¿Olvidaste tu contraseña?
                </flux:link>
            </div>

            <flux:input
                wire:model="form.clave"
                type="password"
                placeholder="••••••••"
                autocomplete="current-password"
                icon="lock-closed"
                viewable
            />

            <flux:error name="form.clave" />
        </flux:field>

        <flux:checkbox wire:model="form.recordarme" label="Mantener la sesión iniciada" />

        <flux:button type="submit" variant="primary" class="mt-1 w-full">Ingresar</flux:button>
    </form>

    {{--
        No hay registro publico: las cuentas las crea la administracion. Esta
        nota evita que quien no tiene cuenta se quede buscando el boton.
    --}}
    <div class="mt-8 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900">
        <p class="text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
            Las cuentas de acceso las genera la Oficina de Admisión. Si aún no tienes una o
            perdiste el acceso, comunícate con la oficina.
        </p>
    </div>
</div>
