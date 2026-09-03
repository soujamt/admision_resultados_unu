<?php

namespace App\Console\Commands;

use App\Models\Examen;
use App\Models\ExamenRespuesta;
use App\Models\Ingresante;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('admision:limpiar-pruebas {examen : ID de la jornada de examen} {--force : Ejecuta la limpieza sin pedir confirmación}')]
#[Description('Limpia los datos de prueba importados y los resultados de una jornada')]
class LimpiarPruebas extends Command
{
    public function handle(): int
    {
        $examen = Examen::query()->find((int) $this->argument('examen'));

        if ($examen === null) {
            $this->components->error('No existe la jornada de examen indicada.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Se eliminarán las importaciones, postulantes, respuestas, resultados e ingresantes. ¿Continuar?')) {
            $this->components->warn('No se realizó ninguna limpieza.');

            return self::FAILURE;
        }

        $conteos = [
            'Importaciones' => $examen->importaciones()->count(),
            'Postulantes' => $examen->postulantes()->count(),
            'Respuestas' => ExamenRespuesta::query()
                ->whereIn('id_exp', $examen->postulantes()->select('id_exp'))
                ->count(),
            'Resultados' => $examen->resultados()->count(),
            'Ingresantes' => Ingresante::query()->where('id_exa', $examen->id_exa)->count(),
        ];

        DB::transaction(function () use ($examen): void {
            Ingresante::query()->where('id_exa', $examen->id_exa)->delete();
            $examen->resultados()->delete();
            $examen->postulantes()->delete();
            $examen->importaciones()->delete();
            $examen->update(['resuelto_en_exa' => null]);
        }, attempts: 3);

        $this->components->info('Jornada de prueba limpiada.');

        foreach ($conteos as $concepto => $cantidad) {
            $this->components->twoColumnDetail($concepto.' eliminados', (string) $cantidad);
        }

        return self::SUCCESS;
    }
}
