<?php

namespace App\Models;

use Database\Factories\NacionalidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_nac
 * @property int $codigo_nac
 * @property string $nombre_nac
 */
class Nacionalidad extends Model
{
    /** @use HasFactory<NacionalidadFactory> */
    use HasFactory;

    protected $table = 'tbl_nacionalidad';

    protected $primaryKey = 'id_nac';

    protected $fillable = ['codigo_nac', 'nombre_nac'];
}
