<?php

namespace App\Enums;

/**
 * Agrupacion de las modalidades segun el Art. 5 del reglamento. Sirve para
 * ordenar los cuadros de vacantes y para aplicar las reglas que valen para
 * todo un grupo (por ejemplo, el Art. 23 sobre el pase al examen ordinario).
 */
enum GrupoModalidad: string
{
    case Ordinario = 'ordinario';
    case Exoneracion = 'exoneracion';
    case Reserva = 'reserva';
    case Convenio = 'convenio';
    case Pronabec = 'pronabec';
    case Traslado = 'traslado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Ordinario => 'Examen ordinario',
            self::Exoneracion => 'Exoneración',
            self::Reserva => 'Reserva',
            self::Convenio => 'Convenio',
            self::Pronabec => 'PRONABEC - Beca 18',
            self::Traslado => 'Traslado',
        };
    }
}
