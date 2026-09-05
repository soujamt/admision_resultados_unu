<?php

namespace App\Models;

use Database\Factories\AsignacionExamenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AsignacionExamen extends Model
{
    /** @use HasFactory<AsignacionExamenFactory> */
    use HasFactory;

    protected $table = 'tbl_asignacion_examen';

    protected $primaryKey = 'id_ase';

    protected $fillable = ['id_ins', 'id_eau', 'asiento_ase'];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'id_ins', 'id_ins');
    }

    public function aulaExamen(): BelongsTo
    {
        return $this->belongsTo(ExamenAula::class, 'id_eau', 'id_eau');
    }

    /**
     * Clave con la que se ordenan los padrones de postulantes.
     *
     * `Str::ascii` es lo que hace real el orden alfabetico: comparado byte a
     * byte «ALVAREZ» con tilde cae despues de «BENITES» y «ÑAHUI» despues de
     * «ZUÑIGA», porque en UTF-8 las tildes y la eñe van por encima de la «Z».
     * El documento solo desempata, para que dos homonimos salgan siempre en el
     * mismo orden en un padron que se publica.
     */
    public function claveAlfabetica(): string
    {
        $postulante = $this->inscripcion->postulante;

        return Str::ascii(mb_strtoupper($postulante->nombreCompleto())).'|'.$postulante->numero_documento_pos;
    }
}
