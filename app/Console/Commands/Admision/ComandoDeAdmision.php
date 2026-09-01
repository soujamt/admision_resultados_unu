<?php

namespace App\Console\Commands\Admision;

use App\Models\Proceso;
use App\Services\Admision\ResultadoImportacion;
use App\Services\Excel\LectorXlsx;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Base de los comandos de carga: resuelve el proceso y el archivo, y presenta
 * el resultado siempre igual para que el operador lea lo mismo en todos.
 */
abstract class ComandoDeAdmision extends Command
{
    /** Cuantos errores se listan antes de resumir el resto. */
    private const ERRORES_A_MOSTRAR = 25;

    protected function abrirArchivo(string $ruta): ?LectorXlsx
    {
        try {
            return new LectorXlsx($ruta);
        } catch (RuntimeException $error) {
            $this->components->error($error->getMessage());

            return null;
        }
    }

    /**
     * Busca el proceso por su codigo. Si no existe y se pide crearlo, lo deduce
     * del propio codigo (2027-I => año 2027, primera convocatoria).
     */
    protected function resolverProceso(?string $codigo, bool $crearSiFalta = false): ?Proceso
    {
        if (blank($codigo)) {
            $this->components->error('Indica el proceso con --proceso=2027-I.');

            return null;
        }

        $codigo = mb_strtoupper(trim($codigo));
        $proceso = Proceso::where('codigo_pro', $codigo)->first();

        if ($proceso !== null) {
            return $proceso;
        }

        if (! $crearSiFalta) {
            $this->components->error("No existe el proceso «{$codigo}».");

            return null;
        }

        $partes = Proceso::interpretarCodigo($codigo);

        if ($partes === null) {
            $this->components->error("El código «{$codigo}» no tiene la forma AÑO-CONVOCATORIA, por ejemplo 2027-I.");

            return null;
        }

        $proceso = Proceso::create([
            'codigo_pro' => $codigo,
            'nombre_pro' => 'Proceso de Admisión '.$codigo,
            'anio_pro' => $partes['anio'],
            'convocatoria_pro' => $partes['convocatoria'],
        ]);

        $this->components->info("Se creó el proceso «{$codigo}» ({$partes['convocatoria']->etiqueta()}).");

        return $proceso;
    }

    protected function mostrarResultado(ResultadoImportacion $resultado, string $sustantivo): void
    {
        $this->components->twoColumnDetail("{$sustantivo} creados", (string) $resultado->creados);
        $this->components->twoColumnDetail("{$sustantivo} actualizados", (string) $resultado->actualizados);
        $this->components->twoColumnDetail("{$sustantivo} omitidos", (string) $resultado->omitidos);

        if (! $resultado->tieneErrores()) {
            return;
        }

        $this->newLine();
        $this->components->warn('Filas que no se pudieron cargar:');

        foreach (array_slice($resultado->errores, 0, self::ERRORES_A_MOSTRAR) as $error) {
            $ubicacion = $error['fila'] > 0 ? "fila {$error['fila']}" : 'sin fila';
            $referencia = $error['referencia'] !== null ? " [{$error['referencia']}]" : '';

            $this->line("  <fg=yellow>{$ubicacion}</>{$referencia} {$error['mensaje']}");
        }

        $restantes = count($resultado->errores) - self::ERRORES_A_MOSTRAR;

        if ($restantes > 0) {
            $this->line("  <fg=gray>… y {$restantes} más.</>");
        }
    }
}
