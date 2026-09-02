<?php

namespace App\Models;

use App\Enums\EstadoResultado;
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

    protected $fillable = ['id_exa', 'id_exp', 'id_vac', 'puntaje_directo_res', 'factor_dificultad_res', 'puntaje_res', 'puntaje_minimo_res', 'orden_general_res', 'orden_carrera_res', 'repesca_res', 'estado_res', 'motivo_res'];

    protected function casts(): array
    {
        return [
            'puntaje_directo_res' => 'decimal:4',
            'factor_dificultad_res' => 'decimal:6',
            'puntaje_res' => 'decimal:4',
            'puntaje_minimo_res' => 'decimal:4',
            'repesca_res' => 'boolean',
            'estado_res' => EstadoResultado::class,
        ];
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
