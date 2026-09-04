<?php

namespace App\Exports;

use App\Enums\CondicionIngresante;
use App\Enums\EstadoResultado;
use App\Models\Examen;
use App\Models\Ingresante;
use App\Models\Resultado;
use App\Models\Ubigeo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Todos los resultados de una jornada en el formato ancho que revisa la
 * Direccion de Admision: la ficha completa del postulante seguida de su
 * puntaje, sus tres estados y sus ordenes de merito.
 *
 * Salen todos, no solo los ingresantes: el no ingresante y el NSP tambien
 * figuran, porque el archivo se usa para cuadrar el padron completo.
 *
 * @implements FromCollection<int, Resultado>
 * @implements WithMapping<Resultado>
 */
class ResultadosExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    /** @var Collection<string, Ubigeo> */
    private Collection $ubigeos;

    /** @var Collection<int, Ingresante> por id de inscripcion */
    private Collection $ingresantes;

    private int $numero = 0;

    public function __construct(private readonly Examen $examen)
    {
        $this->ubigeos = collect();
        $this->ingresantes = collect();
    }

    /**
     * Se resuelve en memoria y no por lotes a proposito: el orden alfabetico
     * tiene que salir igual en cualquier motor, y ordenar por SQL lo deja a
     * merced de la colacion. SQLite compara bytes y manda «ALVAREZ» con tilde
     * detras de «ZUNIGA». Una jornada son cientos o pocos miles de filas, asi
     * que cabe; el padron de colegios, que son 26 mil, es otra historia.
     *
     * @return Collection<int, Resultado>
     */
    public function collection(): Collection
    {
        $this->cargarApoyo();

        return Resultado::query()
            ->where('id_exa', $this->examen->id_exa)
            ->with([
                'postulante.inscripcion.postulante.paisNacimiento',
                'postulante.inscripcion.postulante.nacionalidad',
                'postulante.inscripcion.postulante.tipoDiscapacidad',
                'postulante.inscripcion.postulante.identidadEtnica',
                'postulante.inscripcion.postulante.lenguaNativa',
                'postulante.inscripcion.postulante.lenguaExtranjera',
                'postulante.inscripcion.paisColegio',
                'postulante.inscripcion.carrera',
                'postulante.inscripcion.modalidad',
                'postulante.inscripcion.sede',
                'postulante.inscripcion.proceso',
            ])
            ->get()
            ->sortBy(
                fn (Resultado $resultado): string => Str::ascii(mb_strtoupper(
                    $resultado->postulante->inscripcion->postulante->nombreCompleto(),
                )).'|'.$resultado->postulante->inscripcion->postulante->numero_documento_pos,
                SORT_NATURAL,
            )
            ->values();
    }

    public function title(): string
    {
        return 'RESULTADOS';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'N°', 'CODIGO_INSCRIPCION', 'TIPO_DOCUMENTO', 'NUMERO_DOCUMENTO',
            'TIENE_SOLO_UN_APELLIDO', 'PRIMER_APELLIDO', 'SEGUNDO_APELLIDO', 'NOMBRES',
            'CELULAR', 'TELEFONO', 'CORREO_ELECTRÓNICO', 'ESTADO_CIVIL', 'APELLIDO_CASADA',
            'SEXO', 'PAIS_NACIMIENTO', 'NACIONALIDAD', 'UBIGEO_NACIMIENTO',
            'DEPARTAMENTO_NACIMIENTO', 'PROVINCIA_NACIMIENTO', 'DISTRITO_NACIMIENTO',
            'FECHA_NACIMIENTO', 'CONDICIÓN_DISCAPACIDAD', 'DISCAPACIDAD', 'UBIGEO_DOMICILIO',
            'DEPARTAMENTO_DOMICILIO', 'PROVINCIA_DOMICILIO', 'DISTRITO_DOMICILIO', 'DIRECCIÓN',
            'AÑO_GRADUACIÓN', 'PAIS_COLEGIO', 'CODIGO_MODULAR_COLEGIO', 'NOMBRE_COLEGIO',
            'TIPO_COLEGIO', 'CODIGO_UBIGEO_COLEGIO', 'DEPARTAMENTO_COLEGIO', 'PROVINCIA_COLEGIO',
            'DISTRITO_COLEGIO', 'VECES_POSTULÓ_UNU', 'VECES_POSTULÓ_OTRAS_UNIVERSIDADES',
            'LENGUA_MATERNA', 'IDENTIDAD_ETNICA', 'LENGUA_NATIVA', 'LENGUA_EXTRANJERA',
            'PROCESO_ADMISIÓN', 'CONVOCATORIA', 'TIPO DE EXAMEN', 'LUGAR_EXAMEN',
            'SEDE_O_FILIAL', 'CARRERA', 'TIPO_PAGO', 'ESTUDIÓ_CARRERAS_PROFESIONALES',
            'PREPARACIÓN', 'UNIVERSIDAD', 'TIPO_UNIVERSIDAD', 'CICLO_ACADÉMICO',
            'FECHA_EXAMEN', 'PUNTAJE', 'ESTADO', 'ESTADO_EVALUACIÓN', 'ESTADO_REGLAMENTO',
            'FECHA_INSCRIPCIÓN', 'ORDEN_MERITO_GENERAL', 'ORDEN_MERITO_CARRERA',
            'ORDEN_MERITO_AREA',
        ];
    }

    /**
     * @param  Resultado  $fila
     * @return list<mixed>
     */
    public function map(mixed $fila): array
    {
        $inscripcion = $fila->postulante->inscripcion;
        $postulante = $inscripcion->postulante;
        $nacimiento = $this->ubigeos->get((string) $postulante->ubigeo_nacimiento_pos);
        $domicilio = $this->ubigeos->get((string) $postulante->ubigeo_direccion_pos);

        return [
            ++$this->numero,
            $inscripcion->codigo_ins,
            $postulante->tipo_documento_pos->abreviatura(),
            $postulante->numero_documento_pos,
            $postulante->solo_un_apellido_pos ? 'SI' : 'NO',
            $postulante->primer_apellido_pos,
            $postulante->segundo_apellido_pos,
            $postulante->nombres_pos,
            $postulante->celular_pos,
            $postulante->telefono_pos,
            $postulante->correo_pos,
            $postulante->estado_civil_pos->value,
            $postulante->apellido_casada_pos,
            $postulante->sexo_pos->value,
            $postulante->paisNacimiento?->nombre_pai,
            $postulante->nacionalidad?->nombre_nac,
            $postulante->ubigeo_nacimiento_pos,
            $nacimiento?->departamento_ubi,
            $nacimiento?->provincia_ubi,
            $nacimiento?->distrito_ubi,
            $postulante->fecha_nacimiento_pos?->format('d/m/Y'),
            $postulante->condicion_discapacidad_pos ? 'SI' : 'NO',
            $postulante->tipoDiscapacidad?->nombre_tdi,
            $postulante->ubigeo_direccion_pos,
            $domicilio?->departamento_ubi,
            $domicilio?->provincia_ubi,
            $domicilio?->distrito_ubi,
            $postulante->direccion_pos,
            $inscripcion->anio_graduacion_ins,
            $inscripcion->paisColegio?->nombre_pai,
            $inscripcion->codigo_colegio_ins,
            $inscripcion->nombre_colegio_ins,
            $inscripcion->tipo_colegio_ins === null ? null : mb_strtoupper($inscripcion->tipo_colegio_ins->etiqueta()),
            /* El padrón del MINEDU no trae el ubigeo del colegio. */
            null, null, null, null,
            $inscripcion->veces_unu_ins,
            $inscripcion->veces_otros_ins,
            $postulante->lengua_materna_pos,
            $postulante->identidadEtnica?->nombre_ide,
            $postulante->lenguaNativa?->nombre_lna,
            $postulante->lenguaExtranjera?->nombre_lex,
            $inscripcion->proceso->anio_pro,
            mb_strtoupper($inscripcion->proceso->convocatoria_pro->etiqueta()),
            mb_strtoupper($inscripcion->modalidad->nombre_mod),
            $inscripcion->sede->ubicacionCabecera(),
            mb_strtoupper($inscripcion->sede->nombre_sed),
            mb_strtoupper($inscripcion->carrera->nombre_car),
            /* La ficha no registra pago, preparación ni estudios previos. */
            null, null, null, null, null, null,
            $this->examen->fecha_exa?->format('d/m/Y'),
            $fila->puntaje_res === null ? null : (float) $fila->puntaje_res,
            $this->estadoIngresante($inscripcion->id_ins, $fila),
            mb_strtoupper($fila->estado_res->etiqueta()),
            $this->estadoReglamento($fila),
            $inscripcion->fecha_ins?->format('d/m/Y H:i:s'),
            $fila->orden_general_res,
            $fila->orden_carrera_res,
            $fila->orden_area_res,
        ];
    }

    /**
     * ESTADO: si conserva la condicion de ingresante del Art. 85. Quien perdio
     * la condicion por los Arts. 86, 92 o 93 deja de ser ingresante aunque el
     * examen lo haya declarado tal.
     */
    private function estadoIngresante(int $idInscripcion, Resultado $fila): string
    {
        $ingresante = $this->ingresantes->get($idInscripcion);

        if ($ingresante !== null) {
            return $ingresante->condicion_ing === CondicionIngresante::Vigente
                ? 'INGRESANTE'
                : 'NO INGRESANTE · '.mb_strtoupper($ingresante->condicion_ing->etiqueta());
        }

        return $fila->estado_res === EstadoResultado::Ingreso ? 'INGRESANTE' : 'NO INGRESANTE';
    }

    /**
     * ESTADO_REGLAMENTO: el resultado despues de aplicar el reglamento, que se
     * separa de la evaluacion en el repescado del Art. 23 y en el sustituto del
     * Art. 93, que no ingresaron por su propia modalidad.
     */
    private function estadoReglamento(Resultado $fila): string
    {
        if ($fila->repesca_res) {
            return 'INGRESO POR ART. 23';
        }

        $ingresante = $this->ingresantes->get($fila->postulante->id_ins);

        if ($ingresante?->id_sustituido_ing !== null) {
            return 'INGRESO POR ART. 93';
        }

        return mb_strtoupper($fila->estado_res->etiqueta());
    }

    /**
     * Ubigeos e ingresantes se resuelven una vez y se recuerdan: el export
     * recorre miles de filas y no puede consultarlos uno por uno.
     */
    private function cargarApoyo(): void
    {
        $this->ubigeos = Ubigeo::query()->get()->keyBy('codigo_ubi');
        $this->ingresantes = Ingresante::query()
            ->where('id_pro', $this->examen->id_pro)
            ->get()
            ->keyBy('id_ins');
    }

    public function nombreArchivo(): string
    {
        return Str::slug(
            'resultados-'.$this->examen->proceso->codigo_pro.'-'.$this->examen->nombre_exa,
        ).'.xlsx';
    }
}
