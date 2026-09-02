<?php

namespace Database\Seeders;

use App\Enums\EstadoRegistro;
use App\Models\Aula;
use App\Models\Sede;
use Illuminate\Database\Seeder;

/**
 * Quince aulas iniciales de la sede Coronel Portillo. La capacidad indicada
 * aqui es solo el valor inicial y puede modificarse desde Configuracion > Aulas.
 */
class AulasInicialesSeeder extends Seeder
{
    private const CAPACIDAD = 50;

    private const AULAS = 15;

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
            $ubicacion = match (true) {
                $numero <= 5 => 'PAB I - Piso 1',
                $numero <= 10 => 'PAB I - Piso 2',
                $numero <= 15 => 'PAB I - Piso 3',
                default => 'PAB II - Piso 1',
            };

            return [
                'codigo_aul' => 'A-'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
                'nombre_aul' => 'Aula '.$numero,
                'pabellon_aul' => $ubicacion,
                'capacidad_aul' => self::CAPACIDAD,
                'orden_aul' => $numero,
            ];
        })->all();
    }
}
