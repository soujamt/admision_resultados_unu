<div>
    <div class="mb-8">
        <flux:heading size="xl" level="1" class="text-2xl! tracking-tight">Recuperar contraseña</flux:heading>
        <flux:subheading class="mt-2">
            Te enviaremos un enlace para crear una contraseña nueva.
        </flux:subheading>
    </div>

    @if ($enviado)
        <flux:callout icon="envelope" variant="success">
            <flux:callout.heading>Revisa tu correo</flux:callout.heading>
            <flux:callout.text>
                Si existe una cuenta con ese correo, en unos minutos recibirás el enlace para
                restablecer tu contraseña.
            </flux:callout.text>
        </flux:callout>
    @else
        <form wire:submit="enviarEnlace" class="flex flex-col gap-5">
            <flux:input
                wire:model="correo"
                type="email"
                label="Correo"
                placeholder="correo@ejemplo.pe"
                autocomplete="username"
                icon="envelope"
                autofocus
            />

            <flux:button type="submit" variant="primary" class="mt-1 w-full">Enviar enlace</flux:button>
        </form>
    @endif

    <flux:separator class="my-8" />

    <flux:button
        :href="route('auth.login')"
        wire:navigate
        variant="subtle"
        icon="arrow-left"
        class="w-full"
    >
        Volver al inicio de sesión
    </flux:button>
</div>
