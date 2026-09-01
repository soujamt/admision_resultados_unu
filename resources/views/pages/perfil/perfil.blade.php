<div class="max-w-2xl space-y-10">
    <div>
        <flux:heading size="xl" level="1">Mi perfil</flux:heading>
        <flux:subheading class="mt-1">Datos de tu cuenta de acceso.</flux:subheading>
    </div>

    <form wire:submit="guardarContacto" class="space-y-6">
        <flux:heading size="lg" level="2">Datos de contacto</flux:heading>

        <flux:input wire:model="nombre" label="Nombres y apellidos" icon="user" />

        <flux:input
            wire:model="correo"
            type="email"
            label="Correo"
            description="Es tambien el usuario con el que ingresas al sistema."
            icon="envelope"
        />

        <flux:button type="submit" variant="primary">Guardar cambios</flux:button>
    </form>

    <flux:separator />

    <form wire:submit="guardarClave" class="space-y-6">
        <flux:heading size="lg" level="2">Cambiar contraseña</flux:heading>

        <flux:input
            wire:model="claveActual"
            type="password"
            label="Contraseña actual"
            autocomplete="current-password"
            icon="lock-closed"
            viewable
        />

        <flux:input
            wire:model="claveNueva"
            type="password"
            label="Contraseña nueva"
            placeholder="Mínimo 8 caracteres"
            autocomplete="new-password"
            icon="lock-closed"
            viewable
        />

        <flux:input
            wire:model="claveConfirmacion"
            type="password"
            label="Repetir contraseña nueva"
            autocomplete="new-password"
            icon="lock-closed"
            viewable
        />

        <flux:button type="submit" variant="primary">Actualizar contraseña</flux:button>
    </form>
</div>
