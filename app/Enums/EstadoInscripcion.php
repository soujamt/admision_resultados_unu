<?php

namespace App\Enums;

/**
 * Situacion de una ficha de inscripcion dentro de un proceso.
 *
 * `Preinscrito` cubre la primera etapa de la inscripcion virtual del Art. 26,
 * cuando el postulante ya envio sus datos pero aun no valida en ventanilla.
 */
enum EstadoInscripcion: int
{
    case Preinscrito = 0;
    case Inscrito = 1;
    case Observado = 2;
    case Anulado = 3;

    public function etiqueta(): string
    {
        return match ($this) {
            self::Preinscrito => 'Preinscrito',
            self::Inscrito => 'Inscrito',
            self::Observado => 'Observado',
            self::Anulado => 'Anulado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Preinscrito => 'amber',
            self::Inscrito => 'green',
            self::Observado => 'orange',
            self::Anulado => 'red',
        };
    }
}
