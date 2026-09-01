<?php

namespace App\Models;

use Database\Factories\ExamenRespuestaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamenRespuesta extends Model
{
    /** @use HasFactory<ExamenRespuestaFactory> */
    use HasFactory;

    protected $table = 'tbl_examen_respuesta';

    protected $primaryKey = 'id_exr';

    protected $fillable = ['id_exp', 'nota_directa_exr', 'nota_transformada_exr', 'aciertos_exr', 'errores_exr', 'blancos_exr', 'dobles_exr', 'respuestas_exr'];

    protected function casts(): array
    {
        return ['respuestas_exr' => 'array', 'nota_directa_exr' => 'decimal:4', 'nota_transformada_exr' => 'decimal:4'];
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(ExamenPostulante::class, 'id_exp', 'id_exp');
    }
}
