<?php

namespace App\Enums;

/**
 * Como se ordena el padron de resultados que el Art. 84 manda publicar.
 *
 * El articulo pide el orden de merito general y por carrera profesional; el
 * alfabetico se agrega porque es con el que el postulante se busca a si mismo
 * cuando el padron se publica en la web y en el mural.
 */
enum OrdenPadronResultados: string
{
    case PorCarrera = 'carrera';
    case Alfabetico = 'alfabetico';
    case Merito = 'merito';

    public function titulo(): string
    {
        return match ($this) {
            self::PorCarrera => 'Por carrera profesional',
            self::Alfabetico => 'Por orden alfabético',
            self::Merito => 'Por orden de mérito',
        };
    }

    /**
     * Lo que se le agrega al nombre del archivo para distinguir las versiones.
     */
    public function sufijoArchivo(): string
    {
        return match ($this) {
            self::PorCarrera => '',
            self::Alfabetico => '-orden-alfabetico',
            self::Merito => '-orden-merito',
        };
    }

    /**
     * Si reparte el listado en una seccion por carrera o lo publica de corrido.
     */
    public function agrupaPorCarrera(): bool
    {
        return $this === self::PorCarrera;
    }
}
