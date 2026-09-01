<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modalidad abierta en un proceso.
 *
 * Guarda el codigo de lugar de inscripcion con el que la modalidad viaja en
 * el archivo que se reporta (593, 594...). Ese codigo se renumera en cada
 * proceso, por eso no puede vivir en tbl_modalidad.
 *
 * @property int $id_prm
 * @property int $id_pro
 * @property int $id_mod
 * @property ?int $codigo_lugar_prm
 * @property ?string $nombre_lugar_prm
 * @property EstadoRegistro $estado_prm
 * @property-read Proceso $proceso
 * @property-read Modalidad $modalidad
 */
class ProcesoModalidad extends Model
{
    protected $table = 'tbl_proceso_modalidad';

    protected $primaryKey = 'id_prm';

    protected $fillable = [
        'id_pro',
        'id_mod',
        'codigo_lugar_prm',
        'nombre_lugar_prm',
        'estado_prm',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['estado_prm' => EstadoRegistro::class];
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
}
