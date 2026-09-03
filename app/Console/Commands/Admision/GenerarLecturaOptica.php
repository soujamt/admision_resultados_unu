<?php

namespace App\Console\Commands\Admision;

use App\Enums\NivelDeExamen;
use App\Models\Examen;
use App\Services\Admision\GeneradorLecturaOptica;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Escribe un padron y una hoja de respuestas ficticios con el formato del
 * lector optico, para probar la importacion y los resultados sin esperar a que
 * el CEPRE entregue los TXT de una jornada real.
 *
 * Los postulantes son los que ya estan inscritos en el proceso, porque el
 * importador cruza por documento; lo inventado son las marcas de la tarjeta.
 */
#[AsCommand(name: 'admision:generar-lectura-optica', description: 'Genera TXT ficticios del lector óptico para probar la importación y los resultados.')]
class GenerarLecturaOptica extends ComandoDeAdmision
{
    protected $signature = 'admision:generar-lectura-optica
        {--proceso= : Código del proceso, por ejemplo 2027-I}
        {--examen= : ID de la jornada; si se omite, la última del proceso}
        {--carpeta= : Carpeta donde escribir los TXT; por defecto la del proceso}
        {--nivel=normal : Qué tan difícil salió el examen: facil, normal o dificil}
        {--ausentes=8 : Porcentaje de inscritos que no rinde y queda fuera de los dos TXT}
        {--intrusos=0 : Filas con documentos ajenos al proceso, para probar el rechazo}
        {--limite= : Cuántos postulantes escribir; por defecto todo el padrón}
        {--semilla= : Semilla para repetir exactamente la misma corrida}
        {--utf8 : Escribe en UTF-8 en vez del Windows-1252 que entrega el lector}';

    public function handle(GeneradorLecturaOptica $generador): int
    {
        $proceso = $this->resolverProceso($this->option('proceso'));

        if ($proceso === null) {
            return self::FAILURE;
        }

        $examen = $this->resolverExamen($proceso->id_pro);

        if ($examen === null) {
            return self::FAILURE;
        }

        $nivel = NivelDeExamen::tryFrom(mb_strtolower(trim((string) $this->option('nivel'))));

        if ($nivel === null) {
            $this->components->error('El nivel debe ser facil, normal o dificil.');

            return self::FAILURE;
        }

        try {
            $resumen = $generador->generar(
                examen: $examen,
                carpeta: $this->option('carpeta') ?: null,
                nivel: $nivel,
                ausentes: (int) $this->option('ausentes'),
                intrusos: (int) $this->option('intrusos'),
                limite: $this->option('limite') !== null ? (int) $this->option('limite') : null,
                semilla: $this->option('semilla') !== null ? (int) $this->option('semilla') : null,
                utf8: (bool) $this->option('utf8'),
            );
        } catch (RuntimeException $error) {
            $this->components->error($error->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Lectura óptica simulada de «{$examen->nombre_exa}» ({$proceso->codigo_pro})");
        $this->components->twoColumnDetail('Nivel del examen', $nivel->etiqueta());
        $this->components->twoColumnDetail('Filas del padrón', (string) $resumen->filasPadron);
        $this->components->twoColumnDetail('Tarjetas leídas', (string) $resumen->filasRespuestas);
        $this->components->twoColumnDetail('Inscritos que no rindieron', (string) $resumen->ausentes);
        $this->components->twoColumnDetail('Semilla', (string) $resumen->semilla);

        $this->newLine();
        $this->line('  <fg=cyan>'.$resumen->padron.'</>');
        $this->line('  <fg=cyan>'.$resumen->respuestas.'</>');

        if ($resumen->advertencias !== []) {
            $this->newLine();
            $this->components->warn('Ten en cuenta:');

            foreach ($resumen->advertencias as $advertencia) {
                $this->line("  <fg=yellow>{$advertencia}</>");
            }
        }

        $this->newLine();
        $this->components->info('Súbelos en Resultados › Importación y resultados: primero el padrón, después las respuestas.');

        return self::SUCCESS;
    }

    /**
     * Sin --examen se toma la ultima jornada del proceso, que es la que se esta
     * preparando cuando uno corre esto.
     */
    private function resolverExamen(int $idProceso): ?Examen
    {
        $consulta = Examen::where('id_pro', $idProceso);
        $id = $this->option('examen');

        $examen = $id === null
            ? $consulta->orderByDesc('fecha_exa')->orderByDesc('id_exa')->first()
            : $consulta->find((int) $id);

        if ($examen !== null) {
            return $examen;
        }

        $this->components->error($id === null
            ? 'El proceso no tiene ninguna jornada de examen: créala en Resultados › Examen y aulas.'
            : "No existe la jornada {$id} en este proceso.");

        return null;
    }
}
