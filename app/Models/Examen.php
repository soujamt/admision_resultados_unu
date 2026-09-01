<?php

namespace App\Models;

use Database\Factories\ExamenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examen extends Model
{
    /** @use HasFactory<ExamenFactory> */
    use HasFactory;

    protected $table = 'tbl_examen';

    protected $primaryKey = 'id_exa';

    protected $fillable = ['id_pro', 'nombre_exa', 'fecha_exa', 'puntaje_acierto_exa', 'puntaje_error_exa', 'puntaje_blanco_exa', 'resuelto_en_exa'];

    protected function casts(): array
    {
        return ['fecha_exa' => 'date', 'resuelto_en_exa' => 'datetime', 'puntaje_acierto_exa' => 'decimal:3', 'puntaje_error_exa' => 'decimal:3', 'puntaje_blanco_exa' => 'decimal:3'];
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_pro', 'id_pro');
    }

    public function postulantes(): HasMany
    {
        return $this->hasMany(ExamenPostulante::class, 'id_exa', 'id_exa');
    }

    public function importaciones(): HasMany
    {
        return $this->hasMany(ExamenImportacion::class, 'id_exa', 'id_exa');
    }

    public function aulas(): HasMany
    {
        return $this->hasMany(ExamenAula::class, 'id_exa', 'id_exa');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class, 'id_exa', 'id_exa');
    }
}
