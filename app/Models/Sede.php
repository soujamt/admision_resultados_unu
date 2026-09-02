<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Models\Concerns\TieneEstado;
use Database\Factories\SedeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Local donde se dicta una carrera: la sede central de Coronel Portillo y las
 * filiales. Una misma carrera puede ofertarse en varias, por eso la sede se
 * decide en el cuadro de vacantes y no en la carrera.
 *
 * @property int $id_sed
 * @property string $codigo_sed
 * @property string $nombre_sed
 * @property ?string $codigo_ubi
 * @property bool $es_filial_sed
 * @property EstadoRegistro $estado_sed
 * @property-read ?Ubigeo $ubigeo
 */
class Sede extends Model
{
    /** @use HasFactory<SedeFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_sede';

    protected $primaryKey = 'id_sed';

    protected $fillable = ['codigo_sed', 'nombre_sed', 'codigo_ubi', 'es_filial_sed', 'estado_sed'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'es_filial_sed' => 'boolean',
            'estado_sed' => EstadoRegistro::class,
        ];
    }

    /**
     * @return BelongsTo<Ubigeo, $this>
     */
    public function ubigeo(): BelongsTo
    {
        return $this->belongsTo(Ubigeo::class, 'codigo_ubi', 'codigo_ubi');
    }

    /**
     * @return HasMany<Aula, $this>
     */
    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class, 'id_sed', 'id_sed');
    }

    /**
     * Sigla usada en los padrones y resultados oficiales.
     */
    public function abreviatura(): string
    {
        return match ($this->codigo_sed) {
            'CORONEL_PORTILLO' => 'SCP-C',
            default => $this->codigo_sed,
        };
    }

    /**
     * Ciudad que se imprime en la cabecera de los documentos oficiales.
     */
    public function ubicacionCabecera(): string
    {
        return match ($this->codigo_sed) {
            'CORONEL_PORTILLO' => 'PUCALLPA',
            'AGUAYTIA' => 'AGUAYTÍA',
            'ATALAYA' => 'ATALAYA',
            default => mb_strtoupper($this->nombre_sed),
        };
    }
}
