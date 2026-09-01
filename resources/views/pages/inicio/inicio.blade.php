<div class="space-y-6">
    <div>
        <flux:heading size="xl" level="1">Hola, {{ auth()->user()->nombre_usu }}</flux:heading>
        <flux:subheading class="mt-1">
            {{ auth()->user()->rol?->nombre_rol }} · Sistema de admisión de la Universidad Nacional de Ucayali
        </flux:subheading>
    </div>

    <flux:separator />

    <flux:callout icon="information-circle" variant="secondary">
        <flux:callout.heading>Sistema en construcción</flux:callout.heading>
        <flux:callout.text>
            La autenticación, los roles y los permisos ya están operativos. Los módulos de
            convocatorias, inscripciones y resultados se irán habilitando en el menú lateral.
        </flux:callout.text>
    </flux:callout>
</div>
