<?php

namespace App\Models;

use Database\Factories\ExamenPostulanteFactory;
use Illuminate\Database\Eloquent\Builder;
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

    protected $fillable = ['id_exa', 'id_ins', 'codigo_cartilla_exp', 'documento_exp', 'nombre_exp', 'codigo_carrera_exp', 'codigo_modalidad_exp', 'aula_origen_exp', 'anulado_en_exp', 'motivo_anulacion_exp'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['anulado_en_exp' => 'datetime'];
    }

    /**
     * Un postulante anulado por los Arts. 79, 96 y 105 al 108 no compite por
     * ninguna vacante, aunque su tarjeta optica se haya leido.
     */
    public function estaAnulado(): bool
    {
        return $this->anulado_en_exp !== null;
    }

    /**
     * Filas que entrego el lector optico. Las que no tienen cartilla las
     * completa la resolucion con los inscritos que no se presentaron, porque
     * el Art. 76 los publica como NSP y nunca recibieron tarjeta.
     *
     * @param  Builder<ExamenPostulante>  $consulta
     */
    public function scopeDelLector(Builder $consulta): void
    {
        $consulta->whereNotNull('codigo_cartilla_exp');
    }

    public function sePresento(): bool
    {
        return $this->codigo_cartilla_exp !== null;
    }

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
