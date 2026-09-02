<?php

namespace App\Enums;

/**
 * En que situacion esta un ingresante despues de publicado el padron.
 *
 * Perder la condicion de ingresante libera la vacante, y segun el motivo el
 * reglamento la trata distinto: el expediente incompleto y la constancia no
 * recogida arrastran la plaza a la tercera convocatoria por el Art. 18, y la
 * falta de matricula llama al inmediato inferior por el Art. 93.
 */
enum CondicionIngresante: string
{
    case Vigente = 'vigente';
    case SinExpediente = 'sin_expediente';
    case SinConstancia = 'sin_constancia';
    case SinMatricula = 'sin_matricula';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Vigente => 'Vigente',
            self::SinExpediente => 'Expediente incompleto',
            self::SinConstancia => 'No recogió constancia',
            self::SinMatricula => 'No se matriculó',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Vigente => 'green',
            self::SinExpediente => 'amber',
            self::SinConstancia => 'orange',
            self::SinMatricula => 'red',
        };
    }

    /**
     * Articulo que sustenta la perdida de la condicion de ingresante.
     */
    public function articulo(): string
    {
        return match ($this) {
            self::Vigente => 'Art. 85',
            self::SinExpediente => 'Art. 86 y 1.ª Disposición Complementaria',
            self::SinConstancia => 'Art. 92',
            self::SinMatricula => 'Art. 93',
        };
    }

    public function perdioCondicion(): bool
    {
        return $this !== self::Vigente;
    }

    /**
     * Art. 18: solo las plazas liberadas por la Primera Disposicion
     * Complementaria y por el Art. 92 se arrastran a la tercera convocatoria.
     * La falta de matricula no arrastra porque el Art. 93 la resuelve
     * llamando al inmediato inferior.
     */
    public function generaArrastre(): bool
    {
        return match ($this) {
            self::SinExpediente, self::SinConstancia => true,
            self::Vigente, self::SinMatricula => false,
        };
    }

    /**
     * @return list<self>
     */
    public static function perdidas(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $condicion): bool => $condicion->perdioCondicion(),
        ));
    }
}
