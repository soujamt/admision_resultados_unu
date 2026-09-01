<?php

namespace App\Enums;

/**
 * Las tres convocatorias en que el Art. 14 del reglamento divide el año, con
 * el porcentaje del cuadro general de vacantes que le toca a cada una.
 */
enum Convocatoria: int
{
    case Primera = 1;
    case Segunda = 2;
    case Tercera = 3;

    public function etiqueta(): string
    {
        return match ($this) {
            self::Primera => 'Primera convocatoria',
            self::Segunda => 'Segunda convocatoria',
            self::Tercera => 'Tercera convocatoria',
        };
    }

    /**
     * Numeral romano con el que se nombra el proceso (2027-I, 2027-II...).
     */
    public function romano(): string
    {
        return match ($this) {
            self::Primera => 'I',
            self::Segunda => 'II',
            self::Tercera => 'III',
        };
    }

    /**
     * Porcentaje del cuadro general de vacantes que corresponde a la
     * convocatoria segun el Art. 14.
     */
    public function porcentajeVacantes(): int
    {
        return match ($this) {
            self::Primera, self::Segunda => 25,
            self::Tercera => 50,
        };
    }
}
