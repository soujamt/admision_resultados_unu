<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * El maestro oficial no numera las identidades etnicas: el codigo que se
 * reporta es el mismo texto de la descripcion.
 *
 * @property int $id_ide
 * @property string $codigo_ide
 * @property string $nombre_ide
 */
class IdentidadEtnica extends Model
{
    protected $table = 'tbl_identidad_etnica';

    protected $primaryKey = 'id_ide';

    protected $fillable = ['codigo_ide', 'nombre_ide'];
}
