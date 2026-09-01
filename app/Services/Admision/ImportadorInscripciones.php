<?php

namespace App\Services\Admision;

use App\Enums\EstadoCivil;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoRegistro;
use App\Enums\Sexo;
use App\Enums\TipoColegio;
use App\Enums\TipoDocumento;
use App\Models\Colegio;
use App\Models\IdentidadEtnica;
use App\Models\Inscripcion;
use App\Models\LenguaExtranjera;
use App\Models\LenguaNativa;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Postulante;
use App\Models\Proceso;
use App\Models\ProcesoModalidad;
use App\Models\TipoDiscapacidad;
use App\Models\Ubigeo;
use App\Models\Vacante;
use App\Services\Excel\LectorXlsx;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Carga la hoja FORMATO del padron oficial: una fila por postulante inscrito.
 *
 * Cada fila se guarda dentro de su propia transaccion. Una fila mal formada no
 * puede tumbar la carga entera, porque el padron llega de un tercero (el
 * CEPREUNU, una filial) y siempre trae algo que corregir; lo que se espera es
 * cargar todo lo bueno y devolver la lista de lo que hay que arreglar.
 */
class ImportadorInscripciones
{
    private const HOJA = 'FORMATO';

    /** @var array<int, Pais> */
    private array $paises = [];

    /** @var array<int, Nacionalidad> */
    private array $nacionalidades = [];

    /** @var array<int, LenguaNativa> */
    private array $lenguasNativas = [];

    /** @var array<int, LenguaExtranjera> */
    private array $lenguasExtranjeras = [];

    /** @var array<int, TipoDiscapacidad> */
    private array $tiposDiscapacidad = [];

    /** @var array<string, IdentidadEtnica> */
    private array $identidadesEtnicas = [];

    /** @var array<int, Vacante> */
    private array $vacantesPorCodigo = [];

    /** @var array<int, int> codigo de lugar de inscripcion => id de modalidad */
    private array $modalidadPorLugar = [];

    /** @var array<string, bool> */
    private array $ubigeosVerificados = [];

    /** @var array<string, bool> */
    private array $colegiosVerificados = [];

    private int $siguienteCorrelativo = 1;

    public function importar(LectorXlsx $lector, Proceso $proceso): ResultadoImportacion
    {
        $this->cargarCatalogos($proceso);

        $resultado = new ResultadoImportacion;

        foreach ($lector->filas(self::HOJA) as $numero => $fila) {
            if ($this->estaVacia($fila)) {
                continue;
            }

            $documento = trim($fila['NUMERO_DOCUMENTO'] ?? '');

            try {
                $this->guardarFila($proceso, $fila, $resultado);
            } catch (RuntimeException $error) {
                $resultado->fallar($numero, $error->getMessage(), $documento !== '' ? $documento : null);
            }
        }

        return $resultado;
    }

    /**
     * @param  array<string, string>  $fila
     */
    private function guardarFila(Proceso $proceso, array $fila, ResultadoImportacion $resultado): void
    {
        $vacante = $this->resolverVacante($fila);
        $this->verificarLugarDeInscripcion($fila, $vacante);

        DB::transaction(function () use ($proceso, $fila, $vacante, $resultado): void {
            $postulante = $this->guardarPostulante($fila);

            $inscripcion = Inscripcion::withTrashed()->firstOrNew([
                'id_pro' => $proceso->id_pro,
                'id_pos' => $postulante->id_pos,
            ]);

            $existia = $inscripcion->exists;

            $inscripcion->fill([
                'id_mod' => $vacante->id_mod,
                'id_car' => $vacante->id_car,
                'id_sed' => $vacante->id_sed,
                'id_pai' => $this->pais($fila['CODIGO_PAIS_COLEGIO'] ?? '', 'CODIGO_PAIS_COLEGIO', obligatorio: false)?->id_pai,
                'codigo_colegio_ins' => $this->colegio($fila['CODIGO_COLEGIO'] ?? ''),
                'nombre_colegio_ins' => $this->recortar($fila['NOMBRE_COLEGIO'] ?? '', 200),
                'tipo_colegio_ins' => $this->tipoColegio($fila['TIPO_COLEGIO'] ?? ''),
                'anio_graduacion_ins' => ctype_digit(trim($fila['GRADUACION'] ?? '')) ? (int) $fila['GRADUACION'] : null,
                'veces_unu_ins' => (int) ($fila['VECES_POST_UNU'] ?? 0),
                'veces_otros_ins' => (int) ($fila['VECES_POST_OTROS'] ?? 0),
                'observacion_ins' => $this->recortar($fila['OBSERVACION'] ?? '', 255),
                'estado_ins' => EstadoInscripcion::Inscrito,
            ]);

            $inscripcion->deleted_at = null;
            $inscripcion->codigo_ins ??= $this->siguienteCodigo($proceso);
            $inscripcion->fecha_ins ??= $proceso->fecha_inicio_inscripcion_pro ?? now()->toDateString();
            $inscripcion->save();

            $existia ? $resultado->actualizar() : $resultado->crear();
        });
    }

    /**
     * @param  array<string, string>  $fila
     */
    private function guardarPostulante(array $fila): Postulante
    {
        $tipo = $this->tipoDocumento($fila['CODIGO_TIPO_DOCUMENTO'] ?? '');
        $numero = trim($fila['NUMERO_DOCUMENTO'] ?? '');

        if ($numero === '') {
            throw new RuntimeException('El número de documento está vacío.');
        }

        if ($tipo->longitud() !== null && mb_strlen($numero) !== $tipo->longitud()) {
            throw new RuntimeException("El {$tipo->abreviatura()} «{$numero}» no tiene {$tipo->longitud()} dígitos.");
        }

        $postulante = Postulante::withTrashed()->firstOrNew([
            'tipo_documento_pos' => $tipo,
            'numero_documento_pos' => $numero,
        ]);

        $postulante->fill([
            'solo_un_apellido_pos' => trim($fila['CODIGO_SOLO_UN_APELLIDO'] ?? '0') === '1',
            'primer_apellido_pos' => $this->obligatorio($fila['PRIMER_APELLIDO'] ?? '', 'PRIMER_APELLIDO'),
            'segundo_apellido_pos' => $this->recortar($fila['SEGUNDO_APELLIDO'] ?? '', 80),
            'nombres_pos' => $this->obligatorio($fila['NOMBRES'] ?? '', 'NOMBRES'),
            'apellido_casada_pos' => $this->recortar($fila['APELLIDO_CASADA'] ?? '', 80),
            'estado_civil_pos' => $this->estadoCivil($fila['ESTADO_CIVIL'] ?? ''),
            'sexo_pos' => $this->sexo($fila['SEXO'] ?? ''),
            'fecha_nacimiento_pos' => $this->fecha($fila['FECHA_NACIMIENTO'] ?? ''),
            'id_pai' => $this->pais($fila['CODIGO_NACIMIENTO_PAIS'] ?? '', 'CODIGO_NACIMIENTO_PAIS')->id_pai,
            'id_nac' => $this->nacionalidad($fila['CODIGO_NACIONALIDAD'] ?? '')->id_nac,
            'ubigeo_nacimiento_pos' => $this->ubigeo($fila['CODIGO_NACIMIENTO_UBIGEO'] ?? '', 'CODIGO_NACIMIENTO_UBIGEO'),
            'condicion_discapacidad_pos' => trim($fila['CONDICION_DISCAPACIDAD'] ?? '0') === '1',
            'id_tdi' => $this->tipoDiscapacidad($fila['TIPO_DISCAPACIDAD'] ?? '')?->id_tdi,
            'celular_pos' => $this->recortar($fila['CELULAR'] ?? '', 15),
            'telefono_pos' => $this->recortar($fila['TELEFONO'] ?? '', 15),
            'correo_pos' => $this->recortar(mb_strtolower($fila['CORREO_ELECTRONICO'] ?? ''), 150),
            'ubigeo_direccion_pos' => $this->ubigeo($fila['CODIGO_UBIGEO_DIRECCION'] ?? '', 'CODIGO_UBIGEO_DIRECCION'),
            'direccion_pos' => $this->recortar($fila['DIRECCION'] ?? '', 200),
            'lengua_materna_pos' => $this->recortar($fila['LENGUA_MATERNA'] ?? '', 60),
            'id_ide' => $this->identidadEtnica($fila['IDENTIDAD_ETNICA'] ?? '')?->id_ide,
            'id_lna' => $this->lenguaNativa($fila['LENGUA_NATIVA'] ?? '')?->id_lna,
            'id_lex' => $this->lenguaExtranjera($fila['LENGUA_EXTRANJERA'] ?? '')?->id_lex,
            'estado_pos' => EstadoRegistro::Habilitado,
        ]);

        $postulante->deleted_at = null;
        $postulante->save();

        return $postulante;
    }

    /**
     * @param  array<string, string>  $fila
     */
    private function resolverVacante(array $fila): Vacante
    {
        $codigo = trim($fila['CODIGO_CARRERA'] ?? '');

        if (! ctype_digit($codigo)) {
            throw new RuntimeException('CODIGO_CARRERA está vacío o no es numérico.');
        }

        return $this->vacantesPorCodigo[(int) $codigo]
            ?? throw new RuntimeException("El código de carrera {$codigo} no está en el cuadro de vacantes del proceso. Importa primero la oferta.");
    }

    /**
     * El codigo de lugar de inscripcion tiene que corresponder a la misma
     * modalidad que la carrera; si no, la fila esta cruzada y hay que revisarla
     * antes de darla por buena.
     *
     * @param  array<string, string>  $fila
     */
    private function verificarLugarDeInscripcion(array $fila, Vacante $vacante): void
    {
        $codigo = trim($fila['CODIGO_LUGAR_INSCRIPCION'] ?? '');

        if (! ctype_digit($codigo)) {
            return;
        }

        $modalidad = $this->modalidadPorLugar[(int) $codigo] ?? null;

        if ($modalidad !== null && $modalidad !== $vacante->id_mod) {
            throw new RuntimeException("El lugar de inscripción {$codigo} no corresponde a la modalidad de la carrera indicada.");
        }
    }

    private function cargarCatalogos(Proceso $proceso): void
    {
        $this->paises = Pais::all()->keyBy('codigo_pai')->all();
        $this->nacionalidades = Nacionalidad::all()->keyBy('codigo_nac')->all();
        $this->lenguasNativas = LenguaNativa::all()->keyBy('codigo_lna')->all();
        $this->lenguasExtranjeras = LenguaExtranjera::all()->keyBy('codigo_lex')->all();
        $this->tiposDiscapacidad = TipoDiscapacidad::all()->keyBy('codigo_tdi')->all();

        $this->identidadesEtnicas = IdentidadEtnica::all()
            ->keyBy(static fn (IdentidadEtnica $identidad): string => normalizar_texto($identidad->codigo_ide))
            ->all();

        $this->vacantesPorCodigo = Vacante::where('id_pro', $proceso->id_pro)
            ->whereNotNull('codigo_externo_vac')
            ->get()
            ->keyBy('codigo_externo_vac')
            ->all();

        $this->modalidadPorLugar = ProcesoModalidad::where('id_pro', $proceso->id_pro)
            ->whereNotNull('codigo_lugar_prm')
            ->pluck('id_mod', 'codigo_lugar_prm')
            ->all();

        $ultimo = Inscripcion::withTrashed()
            ->where('id_pro', $proceso->id_pro)
            ->max('codigo_ins');

        $this->siguienteCorrelativo = $ultimo === null
            ? 1
            : ((int) mb_substr((string) $ultimo, -5)) + 1;
    }

    /**
     * Correlativo por proceso con la forma 2027I-00001.
     */
    private function siguienteCodigo(Proceso $proceso): string
    {
        $prefijo = str_replace('-', '', $proceso->codigo_pro);

        return $prefijo.'-'.str_pad((string) $this->siguienteCorrelativo++, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, string>  $fila
     */
    private function estaVacia(array $fila): bool
    {
        foreach ($fila as $valor) {
            if (trim($valor) !== '') {
                return false;
            }
        }

        return true;
    }

    private function obligatorio(string $valor, string $campo): string
    {
        $valor = trim($valor);

        if ($valor === '') {
            throw new RuntimeException("{$campo} es obligatorio y llegó vacío.");
        }

        return $valor;
    }

    private function recortar(string $valor, int $longitud): ?string
    {
        $valor = trim($valor);

        return $valor === '' ? null : mb_substr($valor, 0, $longitud);
    }

    private function tipoDocumento(string $valor): TipoDocumento
    {
        return TipoDocumento::tryFrom((int) trim($valor))
            ?? throw new RuntimeException("CODIGO_TIPO_DOCUMENTO «{$valor}» no es un tipo de documento válido.");
    }

    private function estadoCivil(string $valor): EstadoCivil
    {
        return EstadoCivil::tryFrom(mb_strtoupper(trim($valor)))
            ?? throw new RuntimeException("ESTADO_CIVIL «{$valor}» no es un estado civil válido.");
    }

    private function sexo(string $valor): Sexo
    {
        return Sexo::tryFrom(mb_strtoupper(trim($valor)))
            ?? throw new RuntimeException("SEXO «{$valor}» no es válido; se espera M o F.");
    }

    private function tipoColegio(string $valor): ?TipoColegio
    {
        $valor = trim($valor);

        return $valor === '' ? null : TipoColegio::tryFrom((int) $valor);
    }

    /**
     * El formato pide DD/MM/YYYY, pero si la celda quedo como fecha de Excel
     * llega el numero de serie; se aceptan los dos.
     */
    private function fecha(string $valor): Carbon
    {
        $valor = trim($valor);

        if ($valor === '') {
            throw new RuntimeException('FECHA_NACIMIENTO es obligatoria y llegó vacía.');
        }

        if (ctype_digit($valor)) {
            return Carbon::create(1899, 12, 30)->addDays((int) $valor);
        }

        $fecha = Carbon::createFromFormat('!d/m/Y', $valor);

        if ($fecha === false) {
            throw new RuntimeException("FECHA_NACIMIENTO «{$valor}» no tiene el formato DD/MM/YYYY.");
        }

        return $fecha;
    }

    private function pais(string $valor, string $campo, bool $obligatorio = true): ?Pais
    {
        $valor = trim($valor);

        if ($valor === '') {
            if ($obligatorio) {
                throw new RuntimeException("{$campo} es obligatorio y llegó vacío.");
            }

            return null;
        }

        return $this->paises[(int) $valor]
            ?? throw new RuntimeException("{$campo} «{$valor}» no está en el catálogo de países.");
    }

    private function nacionalidad(string $valor): Nacionalidad
    {
        $valor = trim($valor);

        return $this->nacionalidades[(int) $valor]
            ?? throw new RuntimeException("CODIGO_NACIONALIDAD «{$valor}» no está en el catálogo.");
    }

    /**
     * El ubigeo se guarda por su codigo de seis digitos; solo hay que
     * comprobar que exista en el padron, y una vez comprobado se recuerda para
     * no volver a consultarlo en cada fila.
     */
    private function ubigeo(string $valor, string $campo): ?string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        $codigo = str_pad($valor, 6, '0', STR_PAD_LEFT);

        if (! isset($this->ubigeosVerificados[$codigo])) {
            $this->ubigeosVerificados[$codigo] = Ubigeo::where('codigo_ubi', $codigo)->exists();
        }

        return $this->ubigeosVerificados[$codigo]
            ? $codigo
            : throw new RuntimeException("{$campo} «{$valor}» no está en el padrón de ubigeos.");
    }

    /**
     * Un codigo modular que no esta en el padron no invalida la inscripcion:
     * el padron del MINEDU va por detras de los colegios nuevos. Se descarta
     * el codigo y se conserva el nombre que declaro el postulante.
     */
    private function colegio(string $valor): ?string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        $codigo = str_pad($valor, 7, '0', STR_PAD_LEFT);

        if (! isset($this->colegiosVerificados[$codigo])) {
            $this->colegiosVerificados[$codigo] = Colegio::where('codigo_modular_col', $codigo)->exists();
        }

        return $this->colegiosVerificados[$codigo] ? $codigo : null;
    }

    private function tipoDiscapacidad(string $valor): ?TipoDiscapacidad
    {
        $valor = trim($valor);

        return $valor === '' ? null : ($this->tiposDiscapacidad[(int) $valor] ?? null);
    }

    private function identidadEtnica(string $valor): ?IdentidadEtnica
    {
        $normalizado = normalizar_texto($valor);

        return $normalizado === '' ? null : ($this->identidadesEtnicas[$normalizado] ?? null);
    }

    private function lenguaNativa(string $valor): ?LenguaNativa
    {
        $valor = trim($valor);

        return $valor === '' ? null : ($this->lenguasNativas[(int) $valor] ?? null);
    }

    private function lenguaExtranjera(string $valor): ?LenguaExtranjera
    {
        $valor = trim($valor);

        return $valor === '' ? null : ($this->lenguasExtranjeras[(int) $valor] ?? null);
    }
}
