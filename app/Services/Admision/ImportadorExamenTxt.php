<?php

namespace App\Services\Admision;

use App\Models\Examen;
use App\Models\ExamenImportacion;
use App\Models\ExamenPostulante;
use App\Models\ExamenRespuesta;
use App\Models\Inscripcion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ImportadorExamenTxt
{
    public function importarPadron(
        Examen $examen,
        string $archivo,
        ?string $nombreArchivo = null,
        ?int $idUsuario = null,
    ): ResumenImportacionExamen {
        $filas = $this->leer($archivo);
        $errores = [];
        $cartillas = [];
        $documentos = [];
        $registros = [];
        $inscripciones = $this->inscripcionesPorDocumento($examen);

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2;
            $cartilla = trim($fila[0] ?? '');
            $documento = trim($fila[1] ?? '');

            if ($cartilla === '' || $documento === '') {
                $errores[] = "Fila {$numeroFila}: falta la cartilla o el documento.";

                continue;
            }

            if (isset($cartillas[$cartilla])) {
                $errores[] = "Fila {$numeroFila}: la cartilla {$cartilla} está repetida.";
            }

            if (isset($documentos[$documento])) {
                $errores[] = "Fila {$numeroFila}: el documento {$documento} está repetido.";
            }

            $cartillas[$cartilla] = true;
            $documentos[$documento] = true;
            $inscripcion = $inscripciones->get($documento);

            if ($inscripcion === null) {
                $errores[] = "Fila {$numeroFila}: el documento {$documento} no corresponde a una inscripción vigente del proceso.";
            }

            $registros[] = [
                'id_exa' => $examen->id_exa,
                'id_ins' => $inscripcion?->id_ins,
                'codigo_cartilla_exp' => $cartilla,
                'documento_exp' => $documento,
                'nombre_exp' => trim($fila[2] ?? ''),
                'codigo_carrera_exp' => trim($fila[3] ?? '') ?: null,
                'codigo_modalidad_exp' => trim($fila[4] ?? '') ?: null,
                'aula_origen_exp' => trim($fila[6] ?? '') ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $erroresEstructurales = array_filter(
            $errores,
            fn (string $error): bool => ! str_contains($error, 'no corresponde a una inscripción vigente'),
        );

        if ($registros === [] || $erroresEstructurales !== []) {
            $this->registrarImportacion($examen, 'padron', $archivo, $nombreArchivo, count($filas), $errores, $idUsuario);

            return new ResumenImportacionExamen(count($filas), $errores, false);
        }

        try {
            DB::transaction(function () use ($examen, $registros, $archivo, $nombreArchivo, $errores, $idUsuario): void {
                $examen->resultados()->delete();
                $examen->postulantes()->delete();

                foreach (array_chunk($registros, 500) as $lote) {
                    ExamenPostulante::insert($lote);
                }

                $examen->update(['resuelto_en_exa' => null]);
                $this->registrarImportacion($examen, 'padron', $archivo, $nombreArchivo, count($registros), $errores, $idUsuario);
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo guardar el padrón. No se modificó la información anterior.');
        }

        return new ResumenImportacionExamen(count($registros), $errores);
    }

    public function importarRespuestas(
        Examen $examen,
        string $archivo,
        ?string $nombreArchivo = null,
        ?int $idUsuario = null,
    ): ResumenImportacionExamen {
        $filas = $this->leer($archivo);
        $errores = [];
        $cartillasLeidas = [];
        $postulantes = $examen->postulantes()->pluck('id_exp', 'codigo_cartilla_exp');
        $registros = [];

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2;
            $cartilla = trim($fila[0] ?? '');

            if ($cartilla === '') {
                $errores[] = "Fila {$numeroFila}: falta la cartilla.";

                continue;
            }

            if (isset($cartillasLeidas[$cartilla])) {
                $errores[] = "Fila {$numeroFila}: la cartilla {$cartilla} está repetida en el archivo.";

                continue;
            }

            $cartillasLeidas[$cartilla] = true;
            $idPostulante = $postulantes->get($cartilla);

            if ($idPostulante === null) {
                $errores[] = "Fila {$numeroFila}: la cartilla {$cartilla} no existe en el padrón importado.";

                continue;
            }

            $aciertos = $this->entero($fila[3] ?? null);
            $falladas = $this->entero($fila[4] ?? null);
            $blancos = $this->entero($fila[5] ?? null);
            $dobles = $this->entero($fila[6] ?? null);

            if ($aciertos + $falladas + $blancos + $dobles !== 100) {
                $errores[] = "Fila {$numeroFila}: aciertos, errores, blancos y dobles deben sumar 100.";

                continue;
            }

            $respuestas = array_map(
                fn (mixed $respuesta): string => trim((string) $respuesta),
                array_slice($fila, 7, 100),
            );

            if (count($respuestas) !== 100) {
                $errores[] = "Fila {$numeroFila}: se esperaban 100 respuestas individuales.";

                continue;
            }

            $registros[] = [
                'id_exp' => $idPostulante,
                'nota_directa_exr' => $this->numero($fila[1] ?? null),
                'nota_transformada_exr' => $this->numero($fila[2] ?? null),
                'aciertos_exr' => $aciertos,
                'errores_exr' => $falladas,
                'blancos_exr' => $blancos,
                'dobles_exr' => $dobles,
                'respuestas_exr' => json_encode($respuestas, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($registros === [] || $errores !== []) {
            $this->registrarImportacion($examen, 'respuestas', $archivo, $nombreArchivo, count($filas), $errores, $idUsuario);

            return new ResumenImportacionExamen(count($filas), $errores, false);
        }

        try {
            DB::transaction(function () use ($examen, $registros, $archivo, $nombreArchivo, $idUsuario): void {
                foreach (array_chunk($registros, 500) as $lote) {
                    ExamenRespuesta::upsert(
                        $lote,
                        ['id_exp'],
                        ['nota_directa_exr', 'nota_transformada_exr', 'aciertos_exr', 'errores_exr', 'blancos_exr', 'dobles_exr', 'respuestas_exr', 'updated_at'],
                    );
                }

                $examen->resultados()->delete();
                $examen->update(['resuelto_en_exa' => null]);
                $this->registrarImportacion($examen, 'respuestas', $archivo, $nombreArchivo, count($registros), [], $idUsuario);
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudieron guardar las respuestas. No se modificó la información anterior.');
        }

        return new ResumenImportacionExamen(count($registros));
    }

    /** @return Collection<string, Inscripcion> */
    private function inscripcionesPorDocumento(Examen $examen): Collection
    {
        return Inscripcion::query()
            ->select(['id_ins', 'id_pro', 'id_pos'])
            ->delProceso($examen->id_pro)
            ->vigente()
            ->with('postulante:id_pos,numero_documento_pos')
            ->get()
            ->filter(fn (Inscripcion $inscripcion): bool => $inscripcion->postulante !== null)
            ->keyBy(fn (Inscripcion $inscripcion): string => $inscripcion->postulante->numero_documento_pos);
    }

    /** @return list<array<int, string>> */
    private function leer(string $archivo): array
    {
        $lineas = file($archivo, FILE_IGNORE_NEW_LINES);

        if ($lineas === false || $lineas === []) {
            throw new RuntimeException('No se puede leer el TXT o está vacío.');
        }

        $filas = [];

        foreach ($lineas as $indice => $linea) {
            $linea = $this->utf8($linea);

            if ($indice === 0) {
                continue;
            }

            if (trim($linea, " ;\t\r\n") === '') {
                continue;
            }

            $filas[] = str_getcsv($linea, ';');
        }

        return $filas;
    }

    private function utf8(string $texto): string
    {
        return mb_check_encoding($texto, 'UTF-8')
            ? $texto
            : mb_convert_encoding($texto, 'UTF-8', 'Windows-1252');
    }

    private function numero(?string $valor): ?float
    {
        return filled($valor) ? (float) str_replace(',', '.', trim($valor)) : null;
    }

    private function entero(?string $valor): int
    {
        return filled($valor) ? (int) trim($valor) : 0;
    }

    /** @param list<string> $errores */
    private function registrarImportacion(
        Examen $examen,
        string $tipo,
        string $archivo,
        ?string $nombreArchivo,
        int $filas,
        array $errores,
        ?int $idUsuario,
    ): void {
        ExamenImportacion::updateOrCreate(
            [
                'id_exa' => $examen->id_exa,
                'tipo_exi' => $tipo,
                'hash_exi' => hash_file('sha256', $archivo),
            ],
            [
                'archivo_exi' => $nombreArchivo ?? basename($archivo),
                'filas_exi' => $filas,
                'errores_exi' => $errores === [] ? null : array_slice($errores, 0, 100),
                'id_usu' => $idUsuario,
            ],
        );
    }
}
