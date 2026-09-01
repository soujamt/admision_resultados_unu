<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Models\Concerns\TieneEstado;
use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Una de las cinco areas academicas del Art. 4. El examen de admision se
 * aplica agrupando las carreras por area, no por facultad.
 *
 * @property int $id_are
 * @property int $numero_are
 * @property string $nombre_are
 * @property EstadoRegistro $estado_are
 * @property-read Collection<int, Carrera> $carreras
 */
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_area';

    protected $primaryKey = 'id_are';

    protected $fillable = ['numero_are', 'nombre_are', 'estado_are'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['estado_are' => EstadoRegistro::class];
    }

    /**
     * @return HasMany<Carrera, $this>
     */
    public function carreras(): HasMany
    {
        return $this->hasMany(Carrera::class, 'id_are', 'id_are');
    }

    public function etiqueta(): string
    {
        return "Área {$this->numero_are}: {$this->nombre_are}";
    }
}
