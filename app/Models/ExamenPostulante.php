<?php

namespace App\Models;

use Database\Factories\ExamenPostulanteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamenPostulante extends Model
{
    /** @use HasFactory<ExamenPostulanteFactory> */
    use HasFactory;

    protected $table = 'tbl_examen_postulante';

    protected $primaryKey = 'id_exp';

    protected $fillable = ['id_exa', 'id_ins', 'codigo_cartilla_exp', 'documento_exp', 'nombre_exp', 'codigo_carrera_exp', 'codigo_modalidad_exp', 'aula_origen_exp'];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_exa', 'id_exa');
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'id_ins', 'id_ins');
    }

    public function respuesta(): HasOne
    {
        return $this->hasOne(ExamenRespuesta::class, 'id_exp', 'id_exp');
    }

    public function resultado(): HasOne
    {
        return $this->hasOne(Resultado::class, 'id_exp', 'id_exp');
    }
}
