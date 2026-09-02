<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Enums\GrupoModalidad;
use App\Models\Concerns\TieneEstado;
use Database\Factories\ModalidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modalidad de admision del Art. 5.
 *
 * @property int $id_mod
 * @property string $codigo_mod
 * @property string $nombre_mod
 * @property GrupoModalidad $grupo_mod
 * @property ?int $codigo_externo_mod
 * @property ?string $articulo_mod
 * @property EstadoRegistro $estado_mod
 */
class Modalidad extends Model
{
    /** @use HasFactory<ModalidadFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_modalidad';

    protected $primaryKey = 'id_mod';

    protected $fillable = [
        'codigo_mod',
        'nombre_mod',
        'grupo_mod',
        'codigo_externo_mod',
        'articulo_mod',
        'estado_mod',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grupo_mod' => GrupoModalidad::class,
            'estado_mod' => EstadoRegistro::class,
        ];
    }

    /**
     * Art. 23: los exonerados, trasladados y de convenio que no logran su
     * ingreso por su modalidad pasan automaticamente al examen ordinario sin
     * costo alguno, con excepcion de los postulantes del CEPREUNU.
     */
    public function pasaAlExamenOrdinario(): bool
    {
        return $this->grupo_mod->pasaAlOrdinario() && ! $this->esCepreunu();
    }

    /**
     * Si la modalidad consume el cupo del CEPREUNU que limita el Art. 16.
     * Son las dos que el convenio genera: la exoneracion y la reserva.
     */
    public function esCepreunu(): bool
    {
        return str_contains($this->codigo_mod, 'CEPREUNU');
    }
}
