<?php

namespace App\Services\Admision;

use App\Enums\EstadoInscripcion;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Services\Excel\LectorXlsx;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * Consulta y carga de las fichas de inscripcion.
 *
 * En este sistema las inscripciones no se capturan a mano: llegan en el
 * archivo del formato oficial que arma el CEPREUNU o la filial. Esta clase es
 * el punto por el que entra ese archivo desde la pantalla, reusando el mismo
 * importador que usa el comando de consola.
 */
class InscripcionService
{
    public function __construct(private readonly ImportadorInscripciones $importador) {}

    /**
     * Consulta base del listado, ya filtrada.
     *
     * @param  array{proceso?: ?int, modalidad?: ?int, carrera?: ?int, sede?: ?int, estado?: ?int, busqueda?: ?string}  $filtros
     * @return Builder<Inscripcion>
     */
    public function consulta(array $filtros): Builder
    {
        return Inscripcion::query()
            ->with(['postulante', 'carrera', 'modalidad', 'sede', 'proceso'])
            ->when($filtros['proceso'] ?? null, fn (Builder $q, $valor) => $q->where('id_pro', $valor))
            ->when($filtros['modalidad'] ?? null, fn (Builder $q, $valor) => $q->where('id_mod', $valor))
            ->when($filtros['carrera'] ?? null, fn (Builder $q, $valor) => $q->where('id_car', $valor))
            ->when($filtros['sede'] ?? null, fn (Builder $q, $valor) => $q->where('id_sed', $valor))
            ->when(
                ($filtros['estado'] ?? null) !== null && ($filtros['estado'] ?? '') !== '',
                fn (Builder $q) => $q->where('estado_ins', $filtros['estado']),
            )
            ->when(
                filled($filtros['busqueda'] ?? null),
                fn (Builder $q) => $q->where(function (Builder $q) use ($filtros): void {
                    $termino = '%'.trim((string) $filtros['busqueda']).'%';

                    $q->where('codigo_ins', 'like', $termino)
                        ->orWhereHas('postulante', function (Builder $p) use ($termino): void {
                            $p->where('numero_documento_pos', 'like', $termino)
                                ->orWhere('primer_apellido_pos', 'like', $termino)
                                ->orWhere('segundo_apellido_pos', 'like', $termino)
                                ->orWhere('nombres_pos', 'like', $termino)
                                ->orWhere('correo_pos', 'like', $termino);
                        });
                }),
            );
    }

    /**
     * Carga el archivo subido contra el proceso indicado.
     *
     * El proceso tiene que tener su cuadro de vacantes cargado: es el que
     * traduce el codigo de carrera del formato a la carrera, la modalidad y la
     * sede del sistema.
     */
    public function importar(Proceso $proceso, string $rutaArchivo): ResultadoImportacion
    {
        if ($proceso->vacantes()->whereNotNull('codigo_externo_vac')->doesntExist()) {
            throw new RuntimeException(
                "El proceso {$proceso->codigo_pro} todavía no tiene cuadro de vacantes. ".
                'Configúralo antes de cargar inscripciones.'
            );
        }

        $lector = new LectorXlsx($rutaArchivo);

        if (! $lector->tieneHoja('FORMATO')) {
            throw new RuntimeException(
                'El archivo no tiene la hoja «FORMATO». Sube el formato oficial de inscripción sin renombrar sus hojas.'
            );
        }

        return $this->importador->importar($lector, $proceso);
    }

    /**
     * Conteos de la cabecera del listado.
     *
     * @return array{total: int, inscritos: int, observados: int, con_foto: int}
     */
    public function resumen(Proceso $proceso): array
    {
        return [
            'total' => Inscripcion::where('id_pro', $proceso->id_pro)->count(),
            'inscritos' => Inscripcion::where('id_pro', $proceso->id_pro)->where('estado_ins', EstadoInscripcion::Inscrito)->count(),
            'observados' => Inscripcion::where('id_pro', $proceso->id_pro)->where('estado_ins', EstadoInscripcion::Observado)->count(),
            'con_foto' => Inscripcion::where('id_pro', $proceso->id_pro)->whereNotNull('foto_ins')->count(),
        ];
    }

    /**
     * Anula la ficha en vez de borrarla: el Art. 28 le da caracter de
     * declaracion jurada y el expediente tiene que poder reconstruirse.
     */
    public function anular(Inscripcion $inscripcion): Inscripcion
    {
        $inscripcion->estado_ins = EstadoInscripcion::Anulado;
        $inscripcion->save();

        return $inscripcion;
    }

    public function eliminar(Inscripcion $inscripcion): void
    {
        $inscripcion->delete();
    }
}
