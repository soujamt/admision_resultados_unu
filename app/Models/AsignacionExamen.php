<?php

namespace App\Models;

use Database\Factories\AsignacionExamenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionExamen extends Model
{
    /** @use HasFactory<AsignacionExamenFactory> */
    use HasFactory;

    protected $table = 'tbl_asignacion_examen';

    protected $primaryKey = 'id_ase';

    protected $fillable = ['id_exp', 'id_eau', 'asiento_ase'];

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(ExamenPostulante::class, 'id_exp', 'id_exp');
    }

    public function aulaExamen(): BelongsTo
    {
        return $this->belongsTo(ExamenAula::class, 'id_eau', 'id_eau');
    }
}
