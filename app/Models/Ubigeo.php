<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Distrito del padron del INEI. El codigo de seis digitos es la llave con la
 * que llegan y salen los datos, asi que tambien es la ruta de las relaciones.
 *
 * @property int $id_ubi
 * @property string $codigo_ubi
 * @property string $departamento_ubi
 * @property string $provincia_ubi
 * @property string $distrito_ubi
 */
class Ubigeo extends Model
{
    protected $table = 'tbl_ubigeo';

    protected $primaryKey = 'id_ubi';

    protected $fillable = ['codigo_ubi', 'departamento_ubi', 'provincia_ubi', 'distrito_ubi'];

    /**
     * Texto completo tal como lo muestra el maestro: DEPARTAMENTO/PROVINCIA/DISTRITO.
     */
    public function descripcion(): string
    {
        return "{$this->departamento_ubi}/{$this->provincia_ubi}/{$this->distrito_ubi}";
    }
}
