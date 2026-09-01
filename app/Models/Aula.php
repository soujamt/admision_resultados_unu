<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Models\Concerns\TieneEstado;
use Database\Factories\AulaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Aula donde se rinde el examen de admision.
 *
 * @property int $id_aul
 * @property int $id_sed
 * @property string $codigo_aul
 * @property string $nombre_aul
 * @property ?string $pabellon_aul
 * @property int $capacidad_aul
 * @property int $orden_aul
 * @property EstadoRegistro $estado_aul
 * @property-read Sede $sede
 */
class Aula extends Model
{
    /** @use HasFactory<AulaFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_aula';

    protected $primaryKey = 'id_aul';

    protected $fillable = [
        'id_sed',
        'codigo_aul',
        'nombre_aul',
        'pabellon_aul',
        'capacidad_aul',
        'orden_aul',
        'estado_aul',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['estado_aul' => EstadoRegistro::class];
    }

    /**
     * @return BelongsTo<Sede, $this>
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sed', 'id_sed');
    }

    /**
     * Nombre con el pabellon delante, que es como se rotula en el padron de
     * ubicaciones: "Pabellón A · Aula 101".
     */
    public function etiqueta(): string
    {
        return $this->pabellon_aul === null
            ? $this->nombre_aul
            : $this->pabellon_aul.' · '.$this->nombre_aul;
    }

    /**
     * @param  Builder<$this>  $consulta
     */
    public function scopeOrdenadas(Builder $consulta): void
    {
        $consulta->orderBy('orden_aul')->orderBy('nombre_aul');
    }
}
