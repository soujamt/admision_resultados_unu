<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Enums\GrupoModalidad;
use App\Models\Concerns\TieneEstado;
use Database\Factories\ModalidadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modalidad de admision del Art. 5.
 *
 * @property int $id_mod
 * @property string $codigo_mod
 * @property string $nombre_mod
 * @property GrupoModalidad $grupo_mod
 * @property ?int $codigo_externo_mod
 * @property ?string $articulo_mod
 * @property EstadoRegistro $estado_mod
 */
class Modalidad extends Model
{
    /** @use HasFactory<ModalidadFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_modalidad';

    protected $primaryKey = 'id_mod';

    protected $fillable = [
        'codigo_mod',
        'nombre_mod',
        'grupo_mod',
        'codigo_externo_mod',
        'articulo_mod',
        'estado_mod',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grupo_mod' => GrupoModalidad::class,
            'estado_mod' => EstadoRegistro::class,
        ];
    }

}
