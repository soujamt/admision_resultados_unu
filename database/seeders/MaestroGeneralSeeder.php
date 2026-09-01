<?php

namespace Database\Seeders;

use App\Models\IdentidadEtnica;
use App\Models\TipoDiscapacidad;
use Illuminate\Database\Seeder;

/**
 * Los dos catalogos cortos de la hoja MAESTRO GENERAL del formato oficial.
 *
 * Los demas maestros (paises, nacionalidades, ubigeo, colegios, lenguas) son
 * demasiado extensos para escribirlos aqui y se cargan con
 * `admision:importar-catalogos` desde el mismo archivo del formato.
 */
class MaestroGeneralSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->identidadesEtnicas() as $nombre) {
            IdentidadEtnica::updateOrCreate(
                ['codigo_ide' => $nombre],
                ['nombre_ide' => $nombre],
            );
        }

        foreach ($this->tiposDiscapacidad() as $codigo => $nombre) {
            TipoDiscapacidad::updateOrCreate(
                ['codigo_tdi' => $codigo],
                ['nombre_tdi' => $nombre],
            );
        }
    }

    /**
     * El maestro no numera las identidades etnicas: el codigo es el texto.
     *
     * @return list<string>
     */
    private function identidadesEtnicas(): array
    {
        return [
            'QUECHUA',
            'AYMARA',
            'NATIVO O INDÍGENA DE LA AMAZONIA',
            'PERTENECIENTE O PARTE DE OTRO PUEBLO INDÍGENA U ORIGINARIO',
            'NEGRO/MORENO/ZAMBO/MULATO/PUEBLO AFROPERUANO O AFRODESCENDIENTE',
            'BLANCO',
            'MESTIZO',
            'OTRO',
            'NO SABE / NO RESPONDE',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function tiposDiscapacidad(): array
    {
        return [
            1 => 'Discapacidad Motriz',
            2 => 'Discapacidad Visual',
            3 => 'Visual y Esquema Corporal',
            4 => 'Disminuidos Visuales',
            5 => 'Discapacidad Auditiva',
            6 => 'Autismo',
            7 => 'Discapacidad Mental',
            8 => 'Parálisis Cerebral',
            9 => 'Discapacidad Intelectual',
            10 => 'Sordoceguera',
            11 => 'No Cuenta con Información',
            12 => 'Otros',
            13 => 'Síndrome de Asperger',
            14 => 'Hemiplejia no Identificada',
            15 => 'Estenosis Congénita de la Válvula Aórtica',
            16 => 'Multidiscapacidad',
            17 => 'Discapacidad Física',
            18 => 'Trastorno del Espectro Autista',
            19 => 'T. por Déficit de Atención con Hiperactividad',
            20 => 'T. Específico del Aprendizaje',
            21 => 'T. Mentales y del Comportamiento',
            22 => 'Enfermedades Raras',
            23 => 'Talla Baja',
            24 => 'Talento',
            25 => 'Superdotación',
        ];
    }
}
