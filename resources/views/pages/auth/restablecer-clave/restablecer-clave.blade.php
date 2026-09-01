<div>
    <div class="mb-8">
        <flux:heading size="xl" level="1" class="text-2xl! tracking-tight">Nueva contraseña</flux:heading>
        <flux:subheading class="mt-2">Elige una contraseña para volver a entrar.</flux:subheading>
    </div>

    <form wire:submit="restablecer" class="flex flex-col gap-5">
        <flux:input
            wire:model="correo"
            type="email"
            label="Correo"
            autocomplete="username"
            icon="envelope"
            readonly
        />

        <flux:input
            wire:model="clave"
            type="password"
            label="Contraseña nueva"
            placeholder="Mínimo 8 caracteres"
            autocomplete="new-password"
            icon="lock-closed"
            viewable
            autofocus
        />

        <flux:input
            wire:model="claveConfirmacion"
            type="password"
            label="Repetir contraseña"
            placeholder="••••••••"
            autocomplete="new-password"
            icon="lock-closed"
            viewable
        />

        <flux:button type="submit" variant="primary" class="mt-1 w-full">Guardar contraseña</flux:button>
    </form>
</div>
