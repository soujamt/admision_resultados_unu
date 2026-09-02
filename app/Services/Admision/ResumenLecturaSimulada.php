<?php

namespace App\Services\Admision;

/**
 * Lo que dejo una corrida de GeneradorLecturaOptica: donde quedaron los TXT y
 * que hay dentro, para que el comando lo muestre sin volver a abrirlos.
 */
class ResumenLecturaSimulada
{
    /** @param list<string> $advertencias */
    public function __construct(
        public readonly string $padron,
        public readonly string $respuestas,
        public readonly int $filasPadron,
        public readonly int $filasRespuestas,
        public readonly int $ausentes,
        public readonly int $intrusos,
        public readonly int $semilla,
        public readonly array $advertencias = [],
    ) {}
}
