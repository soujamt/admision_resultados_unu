<?php

namespace App\Enums;

enum TipoColegio: int
{
    case Nacional = 1;
    case Particular = 2;

    public function etiqueta(): string
    {
        return match ($this) {
            self::Nacional => 'Colegio nacional',
            self::Particular => 'Colegio particular',
        };
    }
}
