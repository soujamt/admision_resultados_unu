<?php

namespace App\Console\Commands\Admision;

use App\Models\Sede;
use App\Services\Admision\ImportadorOferta;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Arma el cuadro de vacantes de un proceso a partir de la hoja
 * CARRERAS_PROFESIONALES del formato oficial.
 *
 * Las filas nacen con cero vacantes: la cantidad la aprueban las Escuelas
 * Profesionales (Art. 15) y se configura despues desde el sistema.
 */
#[AsCommand(name: 'admision:importar-oferta', description: 'Carga las modalidades y los códigos de carrera de un proceso.')]
class ImportarOferta extends ComandoDeAdmision
{
    protected $signature = 'admision:importar-oferta
        {archivo : Ruta al .xlsx del formato oficial}
        {--proceso= : Código del proceso, por ejemplo 2027-I}
        {--sede=CORONEL_PORTILLO : Sede que se asume cuando la carrera no la indica}';

    public function handle(ImportadorOferta $importador): int
    {
        $lector = $this->abrirArchivo($this->argument('archivo'));

        if ($lector === null) {
            return self::FAILURE;
        }

        $proceso = $this->resolverProceso($this->option('proceso'), crearSiFalta: true);

        if ($proceso === null) {
            return self::FAILURE;
        }

        $sede = Sede::where('codigo_sed', $this->option('sede'))->first();

        if ($sede === null) {
            $this->components->error("No existe la sede «{$this->option('sede')}». ¿Corriste el seeder de la estructura académica?");

            return self::FAILURE;
        }

        $resultado = $importador->importar($lector, $proceso, $sede);

        $this->components->info("Oferta del proceso {$proceso->codigo_pro}");
        $this->mostrarResultado($resultado, 'Vacantes');

        $this->newLine();
        $this->components->warn('Las vacantes se crean en cero: configura la cantidad de cada carrera antes de publicar el cuadro.');

        return $resultado->tieneErrores() ? self::INVALID : self::SUCCESS;
    }
}
