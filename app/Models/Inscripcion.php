<?php

namespace App\Models;

use App\Enums\EstadoInscripcion;
use App\Enums\TipoColegio;
use Database\Factories\InscripcionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Ficha de inscripcion de un postulante a un proceso.
 *
 * El Art. 28 le da caracter de declaracion jurada, por eso lo que se declaro
 * en una convocatoria queda congelado aqui aunque los datos de la persona
 * cambien despues.
 *
 * @property int $id_ins
 * @property int $id_pro
 * @property int $id_pos
 * @property int $id_mod
 * @property int $id_car
 * @property int $id_sed
 * @property ?string $codigo_ins
 * @property ?int $id_pai
 * @property ?string $codigo_colegio_ins
 * @property ?string $nombre_colegio_ins
 * @property ?TipoColegio $tipo_colegio_ins
 * @property ?int $anio_graduacion_ins
 * @property int $veces_unu_ins
 * @property int $veces_otros_ins
 * @property ?string $foto_ins
 * @property ?string $observacion_ins
 * @property ?Carbon $fecha_ins
 * @property EstadoInscripcion $estado_ins
 * @property-read Proceso $proceso
 * @property-read Postulante $postulante
 * @property-read Modalidad $modalidad
 * @property-read Carrera $carrera
 * @property-read Sede $sede
 */
class Inscripcion extends Model
{
    /** @use HasFactory<InscripcionFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_inscripcion';

    protected $primaryKey = 'id_ins';

    protected $fillable = [
        'id_pro',
        'id_pos',
        'id_mod',
        'id_car',
        'id_sed',
        'codigo_ins',
        'id_pai',
        'codigo_colegio_ins',
        'nombre_colegio_ins',
        'tipo_colegio_ins',
        'anio_graduacion_ins',
        'veces_unu_ins',
        'veces_otros_ins',
        'foto_ins',
        'observacion_ins',
        'fecha_ins',
        'estado_ins',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_colegio_ins' => TipoColegio::class,
            'fecha_ins' => 'date',
            'estado_ins' => EstadoInscripcion::class,
        ];
    }

    /**
     * @return BelongsTo<Proceso, $this>
     */
    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_pro', 'id_pro');
    }

    /**
     * @return BelongsTo<Postulante, $this>
     */
    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class, 'id_pos', 'id_pos');
    }

    /**
     * @return BelongsTo<Modalidad, $this>
     */
    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(Modalidad::class, 'id_mod', 'id_mod');
    }

    /**
     * @return BelongsTo<Carrera, $this>
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_car', 'id_car');
    }

    /**
     * @return BelongsTo<Sede, $this>
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sed', 'id_sed');
    }

    /**
     * @return BelongsTo<Colegio, $this>
     */
    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'codigo_colegio_ins', 'codigo_modular_col');
    }

    /**
     * @return BelongsTo<Pais, $this>
     */
    public function paisColegio(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'id_pai', 'id_pai');
    }

    /**
     * @return HasMany<AsignacionExamen, $this>
     */
    public function asignacionesExamen(): HasMany
    {
        return $this->hasMany(AsignacionExamen::class, 'id_ins', 'id_ins');
    }

    /**
     * Fila del cuadro de vacantes contra la que compite esta ficha.
     *
     * @return BelongsTo<Vacante, $this>
     */
    public function vacante(): BelongsTo
    {
        return $this->belongsTo(Vacante::class, 'id_pro', 'id_pro')
            ->where('id_mod', $this->id_mod)
            ->where('id_car', $this->id_car)
            ->where('id_sed', $this->id_sed);
    }

    public function tieneFoto(): bool
    {
        return filled($this->foto_ins);
    }

    /**
     * @param  Builder<$this>  $consulta
     */
    public function scopeDelProceso(Builder $consulta, Proceso|int $proceso): void
    {
        $consulta->where('id_pro', $proceso instanceof Proceso ? $proceso->id_pro : $proceso);
    }

    /**
     * @param  Builder<$this>  $consulta
     */
    public function scopeVigente(Builder $consulta): void
    {
        $consulta->whereNot('estado_ins', EstadoInscripcion::Anulado);
    }
}
