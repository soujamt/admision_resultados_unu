<?php

namespace App\Enums;

enum Sexo: string
{
    case Masculino = 'M';
    case Femenino = 'F';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Masculino => 'Masculino',
            self::Femenino => 'Femenino',
        };
    }
}
