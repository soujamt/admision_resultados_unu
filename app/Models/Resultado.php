<?php

namespace App\Models;

use Database\Factories\ResultadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resultado extends Model
{
    /** @use HasFactory<ResultadoFactory> */
    use HasFactory;

    protected $table = 'tbl_resultado';

    protected $primaryKey = 'id_res';

    protected $fillable = ['id_exa', 'id_exp', 'id_vac', 'puntaje_res', 'orden_general_res', 'orden_carrera_res', 'estado_res'];

    protected function casts(): array
    {
        return ['puntaje_res' => 'decimal:4'];
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_exa', 'id_exa');
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(ExamenPostulante::class, 'id_exp', 'id_exp');
    }

    public function vacante(): BelongsTo
    {
        return $this->belongsTo(Vacante::class, 'id_vac', 'id_vac');
    }
}
