<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Models\Concerns\TieneEstado;
use Database\Factories\VacanteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fila del cuadro de vacantes del Art. 15: cuantas plazas ofrece una carrera
 * en una sede, por modalidad y proceso.
 *
 * `codigo_externo_vac` es el codigo de carrera que exige el formato de
 * inscripcion (2555, 2556...). Se renumera en cada proceso y ademas cambia
 * segun la modalidad, asi que corresponde a esta fila y no a tbl_carrera.
 *
 * @property int $id_vac
 * @property int $id_pro
 * @property int $id_mod
 * @property int $id_car
 * @property int $id_sed
 * @property int $cantidad_vac
 * @property int $cantidad_arrastre_vac plazas sumadas por los Arts. 17, 18 y 19
 * @property ?string $motivo_arrastre_vac
 * @property ?int $codigo_externo_vac
 * @property EstadoRegistro $estado_vac
 * @property-read int $inscritos  solo cuando la consulta lo selecciona (VacanteService::cuadro)
 * @property-read Proceso $proceso
 * @property-read Modalidad $modalidad
 * @property-read Carrera $carrera
 * @property-read Sede $sede
 */
class Vacante extends Model
{
    /** @use HasFactory<VacanteFactory> */
    use HasFactory, TieneEstado;

    protected $table = 'tbl_vacante';

    protected $primaryKey = 'id_vac';

    protected $fillable = [
        'id_pro',
        'id_mod',
        'id_car',
        'id_sed',
        'cantidad_vac',
        'cantidad_arrastre_vac',
        'motivo_arrastre_vac',
        'codigo_externo_vac',
        'estado_vac',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['estado_vac' => EstadoRegistro::class];
    }

    /**
     * Plazas que la vacante ofrece de verdad: las que aprobo el Consejo
     * Universitario por el Art. 15 mas las que le arrastraron los Arts. 17,
     * 18 y 19. Toda adjudicacion y todo conteo de desiertas usa esta cifra.
     */
    public function plazas(): int
    {
        return $this->cantidad_vac + $this->cantidad_arrastre_vac;
    }

    /**
     * @return BelongsTo<Proceso, $this>
     */
    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_pro', 'id_pro');
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
     * Inscripciones que compiten por esta fila del cuadro. No hay clave
     * foranea hacia la vacante: la inscripcion apunta al proceso, la
     * modalidad, la carrera y la sede por separado para que borrar una fila
     * del cuadro nunca arrastre fichas de postulantes.
     *
     * @return HasMany<Inscripcion, $this>
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_pro', 'id_pro')
            ->where('id_mod', $this->id_mod)
            ->where('id_car', $this->id_car)
            ->where('id_sed', $this->id_sed);
    }
}
