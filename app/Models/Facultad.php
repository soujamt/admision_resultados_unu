<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Models\Concerns\TieneEstado;
use Database\Factories\FacultadFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Facultad de las que enumera el Art. 1 del reglamento.
 *
 * @property int $id_fac
 * @property string $codigo_fac
 * @property string $nombre_fac
 * @property EstadoRegistro $estado_fac
 * @property-read Collection<int, Carrera> $carreras
 */
class Facultad extends Model
{
    /** @use HasFactory<FacultadFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_facultad';

    protected $primaryKey = 'id_fac';

    protected $fillable = ['codigo_fac', 'nombre_fac', 'estado_fac'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['estado_fac' => EstadoRegistro::class];
    }

    /**
     * @return HasMany<Carrera, $this>
     */
    public function carreras(): HasMany
    {
        return $this->hasMany(Carrera::class, 'id_fac', 'id_fac');
    }
}
