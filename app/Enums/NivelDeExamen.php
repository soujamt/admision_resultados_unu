<?php

namespace App\Enums;

/**
 * Que tan duro salio el examen que se esta simulando. Solo mueve la campana de
 * aciertos con la que GeneradorLecturaOptica llena las tarjetas opticas: el
 * nivel dificil deja la mayoria de puntajes debajo del minimo del Art. 81 y es
 * el que sirve para ensayar las vacantes desiertas y el factor de dificultad
 * del Art. 80.
 */
enum NivelDeExamen: string
{
    case Facil = 'facil';
    case Normal = 'normal';
    case Dificil = 'dificil';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Facil => 'Fácil',
            self::Normal => 'Normal',
            self::Dificil => 'Difícil',
        };
    }

    /**
     * Aciertos promedio sobre las 100 preguntas de la prueba.
     */
    public function promedioDeAciertos(): float
    {
        return match ($this) {
            self::Facil => 58.0,
            self::Normal => 43.0,
            self::Dificil => 29.0,
        };
    }

    public function desviacion(): float
    {
        return match ($this) {
            self::Facil => 15.0,
            self::Normal => 13.0,
            self::Dificil => 10.0,
        };
    }
}
