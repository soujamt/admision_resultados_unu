<?php

namespace Database\Seeders;

use App\Enums\EstadoRegistro;
use App\Models\Aula;
use App\Models\Sede;
use Illuminate\Database\Seeder;

/**
 * Aulas del pabellon I de la sede Coronel Portillo, que son las que se usan
 * para el examen.
 *
 * `capacidad_aul` es cuantas carpetas tiene el aula, no cuantos postulantes se
 * le van a poner en una jornada: eso ultimo sale del reparto por area y cambia
 * en cada examen (en 2027-I van desde 24 hasta 42 en la misma aula de 50).
 */
class AulasInicialesSeeder extends Seeder
{
    /** Carpetas por aula en el pabellon I. */
    private const CAPACIDAD = 50;

    /** Cuantas aulas tiene cada piso, en orden. */
    private const AULAS_POR_PISO = 5;

    private const AULAS = 10;

    public function run(): void
    {
        $sede = Sede::where('codigo_sed', 'CORONEL_PORTILLO')->first();

        if ($sede === null) {
            return;
        }

        foreach ($this->aulas() as $aula) {
            Aula::withTrashed()->updateOrCreate(
                ['id_sed' => $sede->id_sed, 'codigo_aul' => $aula['codigo_aul']],
                $aula + [
                    'id_sed' => $sede->id_sed,
                    'estado_aul' => EstadoRegistro::Habilitado,
                    'deleted_at' => null,
                ],
            );
        }
    }

    /**
     * @return list<array{codigo_aul:string, nombre_aul:string, pabellon_aul:string, capacidad_aul:int, orden_aul:int}>
     */
    private function aulas(): array
    {
        return collect(range(1, self::AULAS))->map(function (int $numero): array {
            $piso = (int) ceil($numero / self::AULAS_POR_PISO);

            return [
                'codigo_aul' => 'A-'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
                'nombre_aul' => 'Aula '.$numero,
                'pabellon_aul' => "PAB I - Piso {$piso}",
                'capacidad_aul' => self::CAPACIDAD,
                'orden_aul' => $numero,
            ];
        })->all();
    }
}
