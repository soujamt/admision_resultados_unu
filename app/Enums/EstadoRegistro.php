<?php

namespace App\Enums;

/**
 * Estado logico de cualquier registro del sistema. Se guarda como entero
 * porque asi lo esperan los reportes que ya existen en la universidad.
 */
enum EstadoRegistro: int
{
    case Deshabilitado = 0;
    case Habilitado = 1;

    public function etiqueta(): string
    {
        return match ($this) {
            self::Habilitado => 'Habilitado',
            self::Deshabilitado => 'Deshabilitado',
        };
    }

    /**
     * Color de Flux con el que se pinta el badge del estado.
     */
    public function color(): string
    {
        return match ($this) {
            self::Habilitado => 'green',
            self::Deshabilitado => 'zinc',
        };
    }
}
