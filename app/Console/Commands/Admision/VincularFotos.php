<?php

namespace App\Console\Commands\Admision;

use App\Services\Admision\AlmacenFotos;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Asocia a cada inscripcion el archivo de foto que lleva su numero de
 * documento por nombre.
 *
 * Sin `--origen` solo vincula lo que ya este dentro de la carpeta del proceso;
 * con `--origen` copia primero el lote desde donde lo dejo el CEPREUNU.
 */
#[AsCommand(name: 'admision:vincular-fotos', description: 'Vincula las fotos de los postulantes a sus inscripciones.')]
class VincularFotos extends ComandoDeAdmision
{
    protected $signature = 'admision:vincular-fotos
        {--proceso= : Código del proceso, por ejemplo 2027-I}
        {--origen= : Carpeta desde la que copiar las fotos; si se omite, se usan las que ya están en el proceso}';

    public function handle(AlmacenFotos $fotos): int
    {
        $proceso = $this->resolverProceso($this->option('proceso'));

        if ($proceso === null) {
            return self::FAILURE;
        }

        $carpeta = $fotos->prepararCarpeta($proceso);

        $this->components->info("Fotos del proceso {$proceso->codigo_pro}");
        $this->components->twoColumnDetail('Carpeta del proceso', $carpeta);

        try {
            $resultado = $fotos->vincular($proceso, $this->option('origen') ?: null);
        } catch (RuntimeException $error) {
            $this->components->error($error->getMessage());

            return self::FAILURE;
        }

        $this->mostrarResultado($resultado, 'Fotos');

        return $resultado->tieneErrores() ? self::INVALID : self::SUCCESS;
    }
}
