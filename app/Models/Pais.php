<?php

namespace App\Models;

use Database\Factories\PaisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_pai
 * @property int $codigo_pai
 * @property string $nombre_pai
 */
class Pais extends Model
{
    /** @use HasFactory<PaisFactory> */
    use HasFactory;

    protected $table = 'tbl_pais';

    protected $primaryKey = 'id_pai';

    protected $fillable = ['codigo_pai', 'nombre_pai'];

    /**
     * El pais 1 es PERU en el maestro oficial, y varias reglas del formato de
     * inscripcion dependen de si el postulante nacio o estudio en el pais.
     */
    public const CODIGO_PERU = 1;

    public function esPeru(): bool
    {
        return $this->codigo_pai === self::CODIGO_PERU;
    }
}
