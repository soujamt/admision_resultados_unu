<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Institucion educativa del padron del MINEDU, identificada por su codigo
 * modular.
 *
 * @property int $id_col
 * @property string $codigo_modular_col
 * @property string $nombre_col
 */
class Colegio extends Model
{
    protected $table = 'tbl_colegio';

    protected $primaryKey = 'id_col';

    protected $fillable = ['codigo_modular_col', 'nombre_col'];
}
