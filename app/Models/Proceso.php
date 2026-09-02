<?php

namespace App\Models;

use App\Enums\Convocatoria;
use App\Enums\EstadoRegistro;
use App\Models\Concerns\TieneEstado;
use Database\Factories\ProcesoFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Una convocatoria concreta del proceso de admision: 2027-I, 2027-II, 2027-III.
 *
 * Todo lo configurable (vacantes, modalidades abiertas) y todo lo registrado
 * (inscripciones, fotos) cuelga de aqui, de modo que dos convocatorias nunca
 * comparten datos.
 *
 * @property int $id_pro
 * @property string $codigo_pro
 * @property string $nombre_pro
 * @property int $anio_pro
 * @property Convocatoria $convocatoria_pro
 * @property ?Carbon $fecha_inicio_inscripcion_pro
 * @property ?Carbon $fecha_fin_inscripcion_pro
 * @property ?Carbon $fecha_examen_pro
 * @property ?string $resolucion_pro
 * @property EstadoRegistro $estado_pro
 * @property-read Collection<int, Vacante> $vacantes
 * @property-read Collection<int, Inscripcion> $inscripciones
 * @property-read Collection<int, Examen> $examenes
 * @property-read Collection<int, Ingresante> $ingresantes
 * @property-read Collection<int, Modalidad> $modalidades
 */
class Proceso extends Model
{
    /** @use HasFactory<ProcesoFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_proceso';

    protected $primaryKey = 'id_pro';

    protected $fillable = [
        'codigo_pro',
        'nombre_pro',
        'anio_pro',
        'convocatoria_pro',
        'fecha_inicio_inscripcion_pro',
        'fecha_fin_inscripcion_pro',
        'fecha_examen_pro',
        'resolucion_pro',
        'estado_pro',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'convocatoria_pro' => Convocatoria::class,
            'fecha_inicio_inscripcion_pro' => 'date',
            'fecha_fin_inscripcion_pro' => 'date',
            'fecha_examen_pro' => 'date',
            'estado_pro' => EstadoRegistro::class,
        ];
    }

    /**
     * @return HasMany<Vacante, $this>
     */
    public function vacantes(): HasMany
    {
        return $this->hasMany(Vacante::class, 'id_pro', 'id_pro');
    }

    /**
     * @return HasMany<Inscripcion, $this>
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_pro', 'id_pro');
    }

    /**
     * @return HasMany<Examen, $this>
     */
    public function examenes(): HasMany
    {
        return $this->hasMany(Examen::class, 'id_pro', 'id_pro');
    }

    /**
     * Padron oficial de ingresantes del Art. 89.
     *
     * @return HasMany<Ingresante, $this>
     */
    public function ingresantes(): HasMany
    {
        return $this->hasMany(Ingresante::class, 'id_pro', 'id_pro');
    }

    /**
     * @return BelongsToMany<Modalidad, $this>
     */
    public function modalidades(): BelongsToMany
    {
        return $this->belongsToMany(Modalidad::class, 'tbl_proceso_modalidad', 'id_pro', 'id_mod', 'id_pro', 'id_mod')
            ->withPivot(['id_prm', 'codigo_lugar_prm', 'nombre_lugar_prm', 'estado_prm'])
            ->withTimestamps();
    }

    /**
     * Codigo con el que se nombra el proceso a partir del anio y la
     * convocatoria: 2027 + Primera => "2027-I".
     */
    public static function componerCodigo(int $anio, Convocatoria $convocatoria): string
    {
        return $anio.'-'.$convocatoria->romano();
    }

    /**
     * Deshace componerCodigo(). Devuelve null cuando el texto no tiene la
     * forma esperada, para que quien lo llame decida si aborta.
     *
     * @return ?array{anio: int, convocatoria: Convocatoria}
     */
    public static function interpretarCodigo(string $codigo): ?array
    {
        if (preg_match('/^(\d{4})\s*-\s*(I{1,3})$/i', trim($codigo), $partes) !== 1) {
            return null;
        }

        $convocatoria = Convocatoria::tryFrom(mb_strlen($partes[2]));

        return $convocatoria === null
            ? null
            : ['anio' => (int) $partes[1], 'convocatoria' => $convocatoria];
    }

    /**
     * Título institucional usado en padrones y resultados oficiales.
     */
    public function tituloConvocatoria(): string
    {
        return $this->anio_pro.' - '.mb_strtoupper($this->convocatoria_pro->etiqueta());
    }

    /**
     * Carpeta del disco privado donde viven los archivos del proceso.
     */
    public function carpeta(): string
    {
        return 'procesos/'.$this->codigo_pro;
    }
}
