<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_lex
 * @property int $codigo_lex
 * @property string $nombre_lex
 */
class LenguaExtranjera extends Model
{
    protected $table = 'tbl_lengua_extranjera';

    protected $primaryKey = 'id_lex';

    protected $fillable = ['codigo_lex', 'nombre_lex'];
}
