<?php

namespace App\Models;

use Database\Factories\ExamenAulaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamenAula extends Model
{
    /** @use HasFactory<ExamenAulaFactory> */
    use HasFactory;

    protected $table = 'tbl_examen_aula';

    protected $primaryKey = 'id_eau';

    protected $fillable = ['id_exa', 'id_aul', 'id_are', 'capacidad_eau'];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_exa', 'id_exa');
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class, 'id_aul', 'id_aul');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'id_are', 'id_are');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionExamen::class, 'id_eau', 'id_eau');
    }
}
