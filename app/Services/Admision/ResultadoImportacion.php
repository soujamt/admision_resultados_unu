<?php

namespace App\Services\Admision;

/**
 * Recuento de lo que hizo una importacion.
 *
 * Los importadores no abortan cuando una fila viene mal: la saltan y la anotan
 * aqui, para que el operador vea de golpe todo lo que tiene que corregir en el
 * Excel en vez de descubrirlo error por error.
 */
class ResultadoImportacion
{
    public int $creados = 0;

    public int $actualizados = 0;

    public int $omitidos = 0;

    /** @var list<array{fila: int, mensaje: string, referencia: ?string}> */
    public array $errores = [];

    public function crear(): void
    {
        $this->creados++;
    }

    public function actualizar(): void
    {
        $this->actualizados++;
    }

    public function fallar(int $fila, string $mensaje, ?string $referencia = null): void
    {
        $this->omitidos++;
        $this->errores[] = ['fila' => $fila, 'mensaje' => $mensaje, 'referencia' => $referencia];
    }

    public function procesados(): int
    {
        return $this->creados + $this->actualizados;
    }

    public function tieneErrores(): bool
    {
        return $this->errores !== [];
    }
}
