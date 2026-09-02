<?php

namespace App\Models;

use App\Enums\CondicionIngresante;
use Database\Factories\IngresanteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Fila del padron oficial de ingresantes del Art. 89.
 *
 * Vive aparte de tbl_resultado porque el Art. 72 declara los resultados del
 * examen inmodificables: quien renuncia, no completa expediente o no matricula
 * deja de ser ingresante sin que cambie su nota ni su orden de merito.
 *
 * @property int $id_ing
 * @property int $id_pro
 * @property int $id_ins
 * @property int $id_vac
 * @property ?int $id_exa
 * @property ?int $id_res
 * @property ?int $id_sustituido_ing
 * @property ?string $puntaje_ing
 * @property ?int $orden_carrera_ing
 * @property CondicionIngresante $condicion_ing
 * @property ?string $motivo_ing
 * @property ?Carbon $condicion_en_ing
 * @property-read Proceso $proceso
 * @property-read Inscripcion $inscripcion
 * @property-read Vacante $vacante
 * @property-read ?Examen $examen
 * @property-read ?Resultado $resultado
 * @property-read ?Ingresante $sustituido
 * @property-read ?Ingresante $sustituto
 */
class Ingresante extends Model
{
    /** @use HasFactory<IngresanteFactory> */
    use HasFactory;

    protected $table = 'tbl_ingresante';

    protected $primaryKey = 'id_ing';

    protected $fillable = [
        'id_pro',
        'id_ins',
        'id_vac',
        'id_exa',
        'id_res',
        'id_sustituido_ing',
        'puntaje_ing',
        'orden_carrera_ing',
        'condicion_ing',
        'motivo_ing',
        'condicion_en_ing',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'puntaje_ing' => 'decimal:4',
            'condicion_ing' => CondicionIngresante::class,
            'condicion_en_ing' => 'datetime',
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
     * @return BelongsTo<Inscripcion, $this>
     */
    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'id_ins', 'id_ins');
    }

    /**
     * @return BelongsTo<Vacante, $this>
     */
    public function vacante(): BelongsTo
    {
        return $this->belongsTo(Vacante::class, 'id_vac', 'id_vac');
    }

    /**
     * @return BelongsTo<Examen, $this>
     */
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_exa', 'id_exa');
    }

    /**
     * @return BelongsTo<Resultado, $this>
     */
    public function resultado(): BelongsTo
    {
        return $this->belongsTo(Resultado::class, 'id_res', 'id_res');
    }

    /**
     * El ingresante al que este reemplaza por el Art. 93.
     *
     * @return BelongsTo<Ingresante, $this>
     */
    public function sustituido(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_sustituido_ing', 'id_ing');
    }

    /**
     * Quien tomo su vacante cuando perdio la condicion.
     *
     * @return HasOne<Ingresante, $this>
     */
    public function sustituto(): HasOne
    {
        return $this->hasOne(self::class, 'id_sustituido_ing', 'id_ing');
    }

    public function estaVigente(): bool
    {
        return $this->condicion_ing === CondicionIngresante::Vigente;
    }
}
