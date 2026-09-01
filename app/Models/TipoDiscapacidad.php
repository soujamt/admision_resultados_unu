<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_tdi
 * @property int $codigo_tdi
 * @property string $nombre_tdi
 */
class TipoDiscapacidad extends Model
{
    protected $table = 'tbl_tipo_discapacidad';

    protected $primaryKey = 'id_tdi';

    protected $fillable = ['codigo_tdi', 'nombre_tdi'];
}
