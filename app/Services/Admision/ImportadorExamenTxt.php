<?php

namespace App\Services\Admision;

use App\Models\Examen;
use App\Models\ExamenPostulante;
use App\Models\ExamenRespuesta;
use App\Models\Inscripcion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportadorExamenTxt
{
    public function importarPadron(Examen $examen, string $archivo): int
    {
        return $this->leer($archivo, function (array $fila) use ($examen): void {
            $cartilla = trim($fila[0] ?? '');
            $documento = trim($fila[1] ?? '');

            if ($cartilla === '' || $documento === '') {
                throw new RuntimeException('El padrón tiene una fila sin cartilla o documento.');
            }

            $inscripcion = Inscripcion::query()->where('id_pro', $examen->id_pro)
                ->whereHas('postulante', fn ($query) => $query->where('numero_documento_pos', $documento))
                ->first();

            ExamenPostulante::updateOrCreate(
                ['id_exa' => $examen->id_exa, 'codigo_cartilla_exp' => $cartilla],
                ['id_ins' => $inscripcion?->id_ins, 'documento_exp' => $documento, 'nombre_exp' => trim($fila[2] ?? ''), 'codigo_carrera_exp' => trim($fila[3] ?? '') ?: null, 'codigo_modalidad_exp' => trim($fila[4] ?? '') ?: null, 'aula_origen_exp' => trim($fila[6] ?? '') ?: null],
            );
        });
    }

    public function importarRespuestas(Examen $examen, string $archivo): int
    {
        return $this->leer($archivo, function (array $fila) use ($examen): void {
            $cartilla = trim($fila[0] ?? '');
            $postulante = ExamenPostulante::where('id_exa', $examen->id_exa)->where('codigo_cartilla_exp', $cartilla)->first();
            if ($postulante === null) {
                throw new RuntimeException("La cartilla {$cartilla} no existe en el padrón.");
            }

            ExamenRespuesta::updateOrCreate(['id_exp' => $postulante->id_exp], ['nota_directa_exr' => $this->numero($fila[1] ?? null), 'nota_transformada_exr' => $this->numero($fila[2] ?? null), 'aciertos_exr' => (int) ($fila[3] ?? 0), 'errores_exr' => (int) ($fila[4] ?? 0), 'blancos_exr' => (int) ($fila[5] ?? 0), 'dobles_exr' => (int) ($fila[6] ?? 0), 'respuestas_exr' => array_values(array_slice($fila, 7, 100))]);
        });
    }

    private function leer(string $archivo, callable $guardar): int
    {
        $manejador = fopen($archivo, 'r');
        if ($manejador === false) {
            throw new RuntimeException('No se puede leer el TXT.');
        }
        $filas = 0;
        try {
            fgetcsv($manejador, 0, ';');
            while (($fila = fgetcsv($manejador, 0, ';')) !== false) {
                DB::transaction(fn () => $guardar($fila));
                $filas++;
            }
        } finally {
            fclose($manejador);
        }

        return $filas;
    }

    private function numero(?string $valor): ?float
    {
        return filled($valor) ? (float) str_replace(',', '.', trim($valor)) : null;
    }
}
