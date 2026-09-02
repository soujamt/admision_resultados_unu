<?php

namespace App\Enums;

enum EstadoResultado: string
{
    case Pendiente = 'pendiente';
    case Ingreso = 'ingreso';
    case NoIngreso = 'no_ingreso';
    case Nsp = 'nsp';
    case Anulado = 'anulado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Ingreso => 'Ingresó',
            self::NoIngreso => 'No ingresó',
            self::Nsp => 'NSP',
            self::Anulado => 'Anulado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'zinc',
            self::Ingreso => 'green',
            self::NoIngreso => 'red',
            self::Nsp => 'amber',
            self::Anulado => 'rose',
        };
    }
}
