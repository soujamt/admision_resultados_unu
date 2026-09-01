<?php

namespace App\Models;

use App\Enums\EstadoCivil;
use App\Enums\EstadoRegistro;
use App\Enums\Sexo;
use App\Enums\TipoDocumento;
use App\Models\Concerns\TieneEstado;
use Database\Factories\PostulanteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * La persona, no la postulacion: aqui vive lo que la identifica y no depende
 * del proceso. Lo que se declara para una convocatoria concreta (carrera,
 * modalidad, colegio, foto) va en Inscripcion.
 *
 * El ubigeo se referencia por su codigo oficial de seis digitos y no por el id
 * autoincremental, porque ese codigo es la llave con la que los datos llegan y
 * salen hacia el MINEDU.
 *
 * @property int $id_pos
 * @property TipoDocumento $tipo_documento_pos
 * @property string $numero_documento_pos
 * @property bool $solo_un_apellido_pos
 * @property string $primer_apellido_pos
 * @property ?string $segundo_apellido_pos
 * @property string $nombres_pos
 * @property ?string $apellido_casada_pos
 * @property EstadoCivil $estado_civil_pos
 * @property Sexo $sexo_pos
 * @property Carbon $fecha_nacimiento_pos
 * @property int $id_pai
 * @property int $id_nac
 * @property ?string $ubigeo_nacimiento_pos
 * @property bool $condicion_discapacidad_pos
 * @property ?int $id_tdi
 * @property ?string $celular_pos
 * @property ?string $telefono_pos
 * @property ?string $correo_pos
 * @property ?string $ubigeo_direccion_pos
 * @property ?string $direccion_pos
 * @property ?string $lengua_materna_pos
 * @property ?int $id_ide
 * @property ?int $id_lna
 * @property ?int $id_lex
 * @property EstadoRegistro $estado_pos
 * @property-read Collection<int, Inscripcion> $inscripciones
 */
class Postulante extends Model
{
    /** @use HasFactory<PostulanteFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_postulante';

    protected $primaryKey = 'id_pos';

    protected $fillable = [
        'tipo_documento_pos',
        'numero_documento_pos',
        'solo_un_apellido_pos',
        'primer_apellido_pos',
        'segundo_apellido_pos',
        'nombres_pos',
        'apellido_casada_pos',
        'estado_civil_pos',
        'sexo_pos',
        'fecha_nacimiento_pos',
        'id_pai',
        'id_nac',
        'ubigeo_nacimiento_pos',
        'condicion_discapacidad_pos',
        'id_tdi',
        'celular_pos',
        'telefono_pos',
        'correo_pos',
        'ubigeo_direccion_pos',
        'direccion_pos',
        'lengua_materna_pos',
        'id_ide',
        'id_lna',
        'id_lex',
        'estado_pos',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_documento_pos' => TipoDocumento::class,
            'solo_un_apellido_pos' => 'boolean',
            'estado_civil_pos' => EstadoCivil::class,
            'sexo_pos' => Sexo::class,
            'fecha_nacimiento_pos' => 'date',
            'condicion_discapacidad_pos' => 'boolean',
            'estado_pos' => EstadoRegistro::class,
        ];
    }

    /**
     * @return HasMany<Inscripcion, $this>
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_pos', 'id_pos');
    }

    /**
     * @return BelongsTo<Pais, $this>
     */
    public function paisNacimiento(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'id_pai', 'id_pai');
    }

    /**
     * @return BelongsTo<Nacionalidad, $this>
     */
    public function nacionalidad(): BelongsTo
    {
        return $this->belongsTo(Nacionalidad::class, 'id_nac', 'id_nac');
    }

    /**
     * @return BelongsTo<Ubigeo, $this>
     */
    public function ubigeoNacimiento(): BelongsTo
    {
        return $this->belongsTo(Ubigeo::class, 'ubigeo_nacimiento_pos', 'codigo_ubi');
    }

    /**
     * @return BelongsTo<Ubigeo, $this>
     */
    public function ubigeoDireccion(): BelongsTo
    {
        return $this->belongsTo(Ubigeo::class, 'ubigeo_direccion_pos', 'codigo_ubi');
    }

    /**
     * @return BelongsTo<TipoDiscapacidad, $this>
     */
    public function tipoDiscapacidad(): BelongsTo
    {
        return $this->belongsTo(TipoDiscapacidad::class, 'id_tdi', 'id_tdi');
    }

    /**
     * @return BelongsTo<IdentidadEtnica, $this>
     */
    public function identidadEtnica(): BelongsTo
    {
        return $this->belongsTo(IdentidadEtnica::class, 'id_ide', 'id_ide');
    }

    /**
     * @return BelongsTo<LenguaNativa, $this>
     */
    public function lenguaNativa(): BelongsTo
    {
        return $this->belongsTo(LenguaNativa::class, 'id_lna', 'id_lna');
    }

    /**
     * @return BelongsTo<LenguaExtranjera, $this>
     */
    public function lenguaExtranjera(): BelongsTo
    {
        return $this->belongsTo(LenguaExtranjera::class, 'id_lex', 'id_lex');
    }

    /**
     * Apellidos y nombres como se imprimen en los padrones: PRIMER SEGUNDO, NOMBRES.
     */
    public function nombreCompleto(): string
    {
        $apellidos = trim($this->primer_apellido_pos.' '.($this->segundo_apellido_pos ?? ''));

        return $apellidos.', '.$this->nombres_pos;
    }


    /**
     * @param  Builder<$this>  $consulta
     */
    public function scopeDocumento(Builder $consulta, TipoDocumento $tipo, string $numero): void
    {
        $consulta->where('tipo_documento_pos', $tipo)->where('numero_documento_pos', $numero);
    }
}
