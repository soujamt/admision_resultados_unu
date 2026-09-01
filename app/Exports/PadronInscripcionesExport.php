<?php

namespace App\Exports;

use App\Models\Inscripcion;
use App\Models\Vacante;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Devuelve las inscripciones en las mismas 34 columnas del formato oficial.
 *
 * Sirve para dos cosas: revisar en Excel lo que quedo cargado, y regenerar el
 * archivo que se reporta despues de corregir datos en el sistema. Por eso las
 * cabeceras y el orden son identicos a los del archivo de entrada, y los
 * codigos que salen son los externos (ubigeo, colegio, carrera), no los ids.
 *
 * @implements FromQuery<Inscripcion>
 * @implements WithMapping<Inscripcion>
 */
class PadronInscripcionesExport implements FromQuery, ShouldAutoSize, WithChunkReading, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    /** @var array<int, ?int> id de la vacante => codigo externo de carrera */
    private array $codigosDeCarrera = [];

    /** @var array<int, ?int> id de la modalidad => codigo de lugar de inscripcion */
    private array $lugaresDeInscripcion = [];

    /**
     * @param  Builder<Inscripcion>  $consulta  el listado ya filtrado en pantalla
     */
    public function __construct(private readonly Builder $consulta) {}

    /**
     * @return Builder<Inscripcion>
     */
    public function query(): Builder
    {
        return $this->consulta
            ->clone()
            ->with(['postulante.identidadEtnica', 'postulante.lenguaNativa', 'postulante.lenguaExtranjera', 'postulante.paisNacimiento', 'postulante.nacionalidad', 'postulante.tipoDiscapacidad', 'paisColegio', 'proceso'])
            ->reorder('id_ins');
    }

    public function title(): string
    {
        return 'FORMATO';
    }

    public function chunkSize(): int
    {
        return 200;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'CODIGO_TIPO_DOCUMENTO', 'NUMERO_DOCUMENTO', 'CODIGO_SOLO_UN_APELLIDO',
            'PRIMER_APELLIDO', 'SEGUNDO_APELLIDO', 'NOMBRES', 'ESTADO_CIVIL',
            'APELLIDO_CASADA', 'SEXO', 'CODIGO_NACIMIENTO_PAIS', 'CODIGO_NACIONALIDAD',
            'CODIGO_NACIMIENTO_UBIGEO', 'FECHA_NACIMIENTO', 'CONDICION_DISCAPACIDAD',
            'TIPO_DISCAPACIDAD', 'CELULAR', 'TELEFONO', 'CORREO_ELECTRONICO',
            'CODIGO_UBIGEO_DIRECCION', 'DIRECCION', 'GRADUACION', 'CODIGO_PAIS_COLEGIO',
            'CODIGO_COLEGIO', 'NOMBRE_COLEGIO', 'TIPO_COLEGIO', 'VECES_POST_UNU',
            'VECES_POST_OTROS', 'LENGUA_MATERNA', 'IDENTIDAD_ETNICA', 'LENGUA_NATIVA',
            'LENGUA_EXTRANJERA', 'CODIGO_LUGAR_INSCRIPCION', 'CODIGO_CARRERA', 'OBSERVACION',
        ];
    }

    /**
     * @param  Inscripcion  $fila
     * @return list<mixed>
     */
    public function map(mixed $fila): array
    {
        $postulante = $fila->postulante;

        return [
            $postulante->tipo_documento_pos->value,
            $postulante->numero_documento_pos,
            $postulante->solo_un_apellido_pos ? 1 : 0,
            $postulante->primer_apellido_pos,
            $postulante->segundo_apellido_pos,
            $postulante->nombres_pos,
            $postulante->estado_civil_pos->value,
            $postulante->apellido_casada_pos,
            $postulante->sexo_pos->value,
            $postulante->paisNacimiento?->codigo_pai,
            $postulante->nacionalidad?->codigo_nac,
            $postulante->ubigeo_nacimiento_pos,
            $postulante->fecha_nacimiento_pos->format('d/m/Y'),
            $postulante->condicion_discapacidad_pos ? 1 : 0,
            $postulante->tipoDiscapacidad?->codigo_tdi,
            $postulante->celular_pos,
            $postulante->telefono_pos,
            $postulante->correo_pos,
            $postulante->ubigeo_direccion_pos,
            $postulante->direccion_pos,
            $fila->anio_graduacion_ins,
            $fila->paisColegio?->codigo_pai,
            $fila->codigo_colegio_ins,
            $fila->nombre_colegio_ins,
            $fila->tipo_colegio_ins?->value,
            $fila->veces_unu_ins,
            $fila->veces_otros_ins,
            $postulante->lengua_materna_pos,
            $postulante->identidadEtnica?->codigo_ide,
            $postulante->lenguaNativa?->codigo_lna,
            $postulante->lenguaExtranjera?->codigo_lex,
            $this->lugarDeInscripcion($fila),
            $this->codigoDeCarrera($fila),
            $fila->observacion_ins,
        ];
    }

    /**
     * El codigo de carrera que exige el formato no vive en la ficha: es el de
     * la fila del cuadro de vacantes a la que corresponde. Se resuelve una vez
     * por proceso y se recuerda, porque el export recorre miles de filas.
     */
    private function codigoDeCarrera(Inscripcion $fila): ?int
    {
        $this->cargarCodigos($fila);

        return $this->codigosDeCarrera[$fila->id_pro.'-'.$fila->id_mod.'-'.$fila->id_car.'-'.$fila->id_sed] ?? null;
    }

    private function lugarDeInscripcion(Inscripcion $fila): ?int
    {
        $this->cargarCodigos($fila);

        return $this->lugaresDeInscripcion[$fila->id_pro.'-'.$fila->id_mod] ?? null;
    }

    private function cargarCodigos(Inscripcion $fila): void
    {
        if (isset($this->codigosDeCarrera[$fila->id_pro])) {
            return;
        }

        foreach (Vacante::where('id_pro', $fila->id_pro)->get() as $vacante) {
            $clave = $vacante->id_pro.'-'.$vacante->id_mod.'-'.$vacante->id_car.'-'.$vacante->id_sed;
            $this->codigosDeCarrera[$clave] = $vacante->codigo_externo_vac;
        }

        foreach ($fila->proceso->modalidades as $modalidad) {
            $this->lugaresDeInscripcion[$fila->id_pro.'-'.$modalidad->id_mod] = $modalidad->pivot->codigo_lugar_prm;
        }

        /* Marca de que este proceso ya se resolvio, aunque no tuviera filas. */
        $this->codigosDeCarrera[$fila->id_pro] = null;
    }
}
