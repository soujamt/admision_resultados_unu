<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_lna
 * @property int $codigo_lna
 * @property string $nombre_lna
 */
class LenguaNativa extends Model
{
    protected $table = 'tbl_lengua_nativa';

    protected $primaryKey = 'id_lna';

    protected $fillable = ['codigo_lna', 'nombre_lna'];
}
