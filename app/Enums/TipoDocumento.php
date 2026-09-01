<?php

namespace App\Enums;

/**
 * Documentos con los que un postulante puede identificarse.
 *
 * Los valores son los codigos que exige el formato de inscripcion que la
 * universidad reporta (hoja MAESTRO GENERAL), asi que no se renumeran.
 */
enum TipoDocumento: int
{
    case Dni = 1;
    case CarnetExtranjeria = 2;
    case CedulaIdentidad = 3;

    public function etiqueta(): string
    {
        return match ($this) {
            self::Dni => 'Documento Nacional de Identidad',
            self::CarnetExtranjeria => 'Carné de extranjería',
            self::CedulaIdentidad => 'Cédula de identidad',
        };
    }

    public function abreviatura(): string
    {
        return match ($this) {
            self::Dni => 'DNI',
            self::CarnetExtranjeria => 'CE',
            self::CedulaIdentidad => 'CI',
        };
    }

    /**
     * Longitud exacta que debe tener el numero de documento, o null cuando el
     * tipo admite longitudes variables.
     */
    public function longitud(): ?int
    {
        return match ($this) {
            self::Dni => 8,
            default => null,
        };
    }
}
