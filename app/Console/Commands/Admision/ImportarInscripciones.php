<?php

namespace App\Console\Commands\Admision;

use App\Services\Admision\AlmacenFotos;
use App\Services\Admision\ImportadorInscripciones;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Carga la hoja FORMATO: un postulante y su ficha de inscripcion por fila.
 *
 * Requiere que la oferta del proceso ya este importada, porque cada fila llega
 * con el codigo externo de carrera y es el cuadro de vacantes el que lo traduce
 * a carrera, modalidad y sede.
 */
#[AsCommand(name: 'admision:importar-inscripciones', description: 'Carga las inscripciones de un proceso desde el formato oficial.')]
class ImportarInscripciones extends ComandoDeAdmision
{
    protected $signature = 'admision:importar-inscripciones
        {archivo : Ruta al .xlsx del formato oficial}
        {--proceso= : Código del proceso, por ejemplo 2027-I}';

    public function handle(ImportadorInscripciones $importador, AlmacenFotos $fotos): int
    {
        $lector = $this->abrirArchivo($this->argument('archivo'));

        if ($lector === null) {
            return self::FAILURE;
        }

        $proceso = $this->resolverProceso($this->option('proceso'));

        if ($proceso === null) {
            return self::FAILURE;
        }

        if ($proceso->vacantes()->whereNotNull('codigo_externo_vac')->doesntExist()) {
            $this->components->error("El proceso {$proceso->codigo_pro} no tiene oferta cargada. Corre primero admision:importar-oferta.");

            return self::FAILURE;
        }

        $resultado = $importador->importar($lector, $proceso);

        $this->components->info("Inscripciones del proceso {$proceso->codigo_pro}");
        $this->mostrarResultado($resultado, 'Inscripciones');

        $this->newLine();
        $this->components->info('Copia las fotos de los postulantes en:');
        $this->line('  <fg=cyan>'.$fotos->prepararCarpeta($proceso).'</>');
        $this->line('  <fg=gray>Un archivo por postulante, nombrado con su documento: 72155069.jpg</>');
        $this->line('  <fg=gray>Luego corre: php artisan admision:vincular-fotos --proceso='.$proceso->codigo_pro.'</>');

        return $resultado->tieneErrores() ? self::INVALID : self::SUCCESS;
    }
}
