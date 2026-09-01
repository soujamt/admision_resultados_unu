<?php

namespace App\Services\Admision;

use App\Services\Excel\LectorXlsx;
use Illuminate\Support\Facades\DB;

/**
 * Carga los maestros del formato oficial de inscripcion: paises,
 * nacionalidades, ubigeo, colegios y lenguas.
 *
 * Son catalogos que la universidad no administra (los publica el MINEDU con
 * cada proceso), asi que la importacion es idempotente: se vuelve a correr
 * cuando llega un formato nuevo y solo actualiza las descripciones que
 * cambiaron.
 */
class ImportadorCatalogos
{
    /**
     * Filas por sentencia. El padron de colegios pasa de veinte mil, y
     * mandarlo en un solo upsert revienta el limite de paquete de MySQL.
     */
    private const TAMANIO_LOTE = 500;

    /**
     * @return array<string, int> catalogo => filas procesadas
     */
    public function importar(LectorXlsx $lector): array
    {
        return [
            'paises' => $this->importarCodificado($lector, 'PAISES', 'tbl_pais', 'codigo_pai', 'nombre_pai'),
            'nacionalidades' => $this->importarCodificado($lector, 'NACIONALIDADES', 'tbl_nacionalidad', 'codigo_nac', 'nombre_nac'),
            'ubigeos' => $this->importarUbigeo($lector),
            'colegios' => $this->importarColegios($lector),
            'lenguas_nativas' => $this->importarCodificado($lector, 'LENGUA NATIVA', 'tbl_lengua_nativa', 'codigo_lna', 'nombre_lna'),
            'lenguas_extranjeras' => $this->importarCodificado($lector, 'LENGUA EXTRANJERA', 'tbl_lengua_extranjera', 'codigo_lex', 'nombre_lex'),
            'identidades_etnicas' => $this->importarIdentidadEtnica($lector),
        ];
    }

    /**
     * Hojas con la forma CÓDIGO / DESCRIPCIÓN y codigo numerico.
     */
    private function importarCodificado(
        LectorXlsx $lector,
        string $hoja,
        string $tabla,
        string $columnaCodigo,
        string $columnaNombre,
    ): int {
        if (! $lector->tieneHoja($hoja)) {
            return 0;
        }

        $ahora = now();
        $lote = [];
        $total = 0;

        foreach ($lector->filasCrudas($hoja) as $numero => $fila) {
            if ($numero === 1 || ! ctype_digit($fila[0] ?? '')) {
                continue;
            }

            $lote[] = [
                $columnaCodigo => (int) $fila[0],
                $columnaNombre => $fila[1] ?? '',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];

            $total++;

            if (count($lote) >= self::TAMANIO_LOTE) {
                DB::table($tabla)->upsert($lote, [$columnaCodigo], [$columnaNombre, 'updated_at']);
                $lote = [];
            }
        }

        if ($lote !== []) {
            DB::table($tabla)->upsert($lote, [$columnaCodigo], [$columnaNombre, 'updated_at']);
        }

        return $total;
    }

    /**
     * La hoja UBIGEO trae la descripcion como DEPARTAMENTO/PROVINCIA/DISTRITO
     * en una sola celda; aqui se parte en las tres columnas.
     */
    private function importarUbigeo(LectorXlsx $lector): int
    {
        if (! $lector->tieneHoja('UBIGEO')) {
            return 0;
        }

        $ahora = now();
        $lote = [];
        $total = 0;

        foreach ($lector->filasCrudas('UBIGEO') as $numero => $fila) {
            $codigo = $fila[0] ?? '';

            if ($numero === 1 || ! ctype_digit($codigo)) {
                continue;
            }

            $partes = array_pad(explode('/', $fila[1] ?? '', 3), 3, '');

            $lote[] = [
                'codigo_ubi' => str_pad($codigo, 6, '0', STR_PAD_LEFT),
                'departamento_ubi' => trim($partes[0]),
                'provincia_ubi' => trim($partes[1]),
                'distrito_ubi' => trim($partes[2]),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];

            $total++;

            if (count($lote) >= self::TAMANIO_LOTE) {
                $this->guardarUbigeo($lote);
                $lote = [];
            }
        }

        if ($lote !== []) {
            $this->guardarUbigeo($lote);
        }

        return $total;
    }

    /**
     * @param  list<array<string, mixed>>  $lote
     */
    private function guardarUbigeo(array $lote): void
    {
        DB::table('tbl_ubigeo')->upsert(
            $lote,
            ['codigo_ubi'],
            ['departamento_ubi', 'provincia_ubi', 'distrito_ubi', 'updated_at'],
        );
    }

    private function importarColegios(LectorXlsx $lector): int
    {
        if (! $lector->tieneHoja('COLEGIOS')) {
            return 0;
        }

        $ahora = now();
        $lote = [];
        $total = 0;

        foreach ($lector->filasCrudas('COLEGIOS') as $numero => $fila) {
            $codigo = $fila[0] ?? '';

            if ($numero === 1 || ! ctype_digit($codigo)) {
                continue;
            }

            $lote[] = [
                'codigo_modular_col' => str_pad($codigo, 7, '0', STR_PAD_LEFT),
                'nombre_col' => mb_substr($fila[1] ?? '', 0, 200),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];

            $total++;

            if (count($lote) >= self::TAMANIO_LOTE) {
                DB::table('tbl_colegio')->upsert($lote, ['codigo_modular_col'], ['nombre_col', 'updated_at']);
                $lote = [];
            }
        }

        if ($lote !== []) {
            DB::table('tbl_colegio')->upsert($lote, ['codigo_modular_col'], ['nombre_col', 'updated_at']);
        }

        return $total;
    }

    /**
     * En esta hoja el codigo es el mismo texto de la descripcion.
     */
    private function importarIdentidadEtnica(LectorXlsx $lector): int
    {
        if (! $lector->tieneHoja('IDENTIDAD ETNICA')) {
            return 0;
        }

        $ahora = now();
        $lote = [];

        foreach ($lector->filasCrudas('IDENTIDAD ETNICA') as $numero => $fila) {
            $codigo = trim($fila[0] ?? '');

            if ($numero === 1 || $codigo === '' || $codigo === 'CÓDIGO') {
                continue;
            }

            $lote[] = [
                'codigo_ide' => mb_substr($codigo, 0, 120),
                'nombre_ide' => mb_substr(trim($fila[1] ?? '') ?: $codigo, 0, 120),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        if ($lote !== []) {
            DB::table('tbl_identidad_etnica')->upsert($lote, ['codigo_ide'], ['nombre_ide', 'updated_at']);
        }

        return count($lote);
    }
}
