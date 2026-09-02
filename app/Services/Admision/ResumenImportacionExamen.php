<?php

namespace App\Services\Admision;

class ResumenImportacionExamen
{
    /** @param list<string> $observaciones */
    public function __construct(
        public readonly int $filas,
        public readonly array $observaciones = [],
        public readonly bool $importado = true,
    ) {}

    public function mensaje(string $entidad): string
    {
        $mensaje = $this->importado
            ? "Se importaron {$this->filas} {$entidad}."
            : "El archivo no fue importado: {$this->filas} filas revisadas.";

        return $this->observaciones === []
            ? $mensaje
            : $mensaje.' '.count($this->observaciones).' observación(es) requieren revisión.';
    }
}
