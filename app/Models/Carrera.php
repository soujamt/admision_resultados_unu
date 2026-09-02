<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Models\Concerns\TieneEstado;
use Database\Factories\CarreraFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Carrera profesional que oferta la universidad.
 *
 * @property int $id_car
 * @property int $id_fac
 * @property int $id_are
 * @property string $codigo_car
 * @property string $nombre_car
 * @property string $nombre_corto_car
 * @property ?string $puntaje_minimo_car nota final minima del Art. 81; null hereda la del examen
 * @property EstadoRegistro $estado_car
 * @property-read Facultad $facultad
 * @property-read Area $area
 * @property-read Collection<int, Vacante> $vacantes
 */
class Carrera extends Model
{
    /** @use HasFactory<CarreraFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_carrera';

    protected $primaryKey = 'id_car';

    protected $fillable = ['id_fac', 'id_are', 'codigo_car', 'nombre_car', 'nombre_corto_car', 'puntaje_minimo_car', 'estado_car'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['estado_car' => EstadoRegistro::class, 'puntaje_minimo_car' => 'decimal:2'];
    }

    /**
     * @return BelongsTo<Facultad, $this>
     */
    public function facultad(): BelongsTo
    {
        return $this->belongsTo(Facultad::class, 'id_fac', 'id_fac');
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'id_are', 'id_are');
    }

    /**
     * @return HasMany<Vacante, $this>
     */
    public function vacantes(): HasMany
    {
        return $this->hasMany(Vacante::class, 'id_car', 'id_car');
    }
}
