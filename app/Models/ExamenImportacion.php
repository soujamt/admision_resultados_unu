<?php

namespace App\Models;

use Database\Factories\ExamenImportacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamenImportacion extends Model
{
    /** @use HasFactory<ExamenImportacionFactory> */
    use HasFactory;

    protected $table = 'tbl_examen_importacion';

    protected $primaryKey = 'id_exi';

    protected $fillable = ['id_exa', 'tipo_exi', 'archivo_exi', 'hash_exi', 'filas_exi', 'errores_exi', 'id_usu'];

    protected function casts(): array
    {
        return ['errores_exi' => 'array'];
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_exa', 'id_exa');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usu', 'id_usu');
    }
}
