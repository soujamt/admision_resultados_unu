<?php

namespace App\Console\Commands\Admision;

use App\Services\Admision\ImportadorCatalogos;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Carga los maestros del formato oficial: paises, nacionalidades, ubigeo,
 * colegios, lenguas e identidades etnicas.
 *
 * Se corre una vez al preparar el sistema y otra vez cada vez que el MINEDU
 * publica un formato con los padrones actualizados.
 */
#[AsCommand(name: 'admision:importar-catalogos', description: 'Carga los catálogos oficiales desde el archivo del formato de inscripción.')]
class ImportarCatalogos extends ComandoDeAdmision
{
    protected $signature = 'admision:importar-catalogos {archivo : Ruta al .xlsx del formato oficial}';

    public function handle(ImportadorCatalogos $importador): int
    {
        $lector = $this->abrirArchivo($this->argument('archivo'));

        if ($lector === null) {
            return self::FAILURE;
        }

        $this->components->info('Cargando catálogos. El padrón de colegios puede tardar un momento.');

        $totales = $importador->importar($lector);

        foreach ($totales as $catalogo => $filas) {
            $this->components->twoColumnDetail(str_replace('_', ' ', $catalogo), (string) $filas);
        }

        $vacios = array_keys(array_filter($totales, static fn (int $filas): bool => $filas === 0));

        if ($vacios !== []) {
            $this->components->warn('Sin datos (¿falta la hoja en el archivo?): '.implode(', ', $vacios));
        }

        $this->components->info('Catálogos actualizados.');

        return self::SUCCESS;
    }
}
