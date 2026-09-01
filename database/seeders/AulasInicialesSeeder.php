<?php

namespace Database\Seeders;

use App\Enums\EstadoRegistro;
use App\Models\Aula;
use Illuminate\Database\Seeder;

class AulasInicialesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->aulas() as $aula) {
            Aula::withTrashed()->updateOrCreate(
                ['id_sed' => 1, 'codigo_aul' => $aula['codigo_aul']],
                $aula + ['id_sed' => 1, 'estado_aul' => EstadoRegistro::Habilitado, 'deleted_at' => null],
            );
        }
    }

    /**
     * @return list<array{codigo_aul:string, nombre_aul:string, pabellon_aul:string, capacidad_aul:int, orden_aul:int}>
     */
    private function aulas(): array
    {
        return collect(range(1, 15))->map(function (int $numero): array {
            $pabellon = match (true) {
                $numero <= 3 => 'PAB I - Piso 1',
                $numero <= 6 => 'PAB I - Piso 2',
                $numero <= 9 => 'PAB I - Piso 3',
                default => 'PAB II - Piso 1',
            };

            return [
                'codigo_aul' => 'A-'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
                'nombre_aul' => 'Aula '.$numero,
                'pabellon_aul' => $pabellon,
                'capacidad_aul' => 40,
                'orden_aul' => $numero,
            ];
        })->all();
    }
}
