<?php

namespace App\Enums;

/**
 * El formato de inscripcion no usa codigos numericos para el estado civil:
 * el valor que se reporta es el mismo texto, por eso el enum es de string.
 */
enum EstadoCivil: string
{
    case Soltero = 'SOLTERO(A)';
    case Casado = 'CASADO(A)';
    case Viudo = 'VIUDO(A)';
    case Divorciado = 'DIVORCIADO(A)';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Soltero => 'Soltero(a)',
            self::Casado => 'Casado(a)',
            self::Viudo => 'Viudo(a)',
            self::Divorciado => 'Divorciado(a)',
        };
    }
}
