<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Cuenta de acceso al sistema.
 *
 * Por ahora los datos personales viven aqui mismo. Cuando entre el modulo de
 * inscripciones se separara una tabla `tbl_persona` y esta se quedara solo con
 * las credenciales.
 *
 * @property int $id_usu
 * @property int $id_rol
 * @property string $nombre_usu
 * @property string $usuario_usu
 * @property string $correo_usu
 * @property string $clave_usu
 * @property ?Carbon $clave_cambiada_en_usu
 * @property EstadoRegistro $estado_usu
 * @property-read Rol $rol
 */
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'tbl_usuario';

    protected $primaryKey = 'id_usu';

    protected $fillable = [
        'id_rol',
        'nombre_usu',
        'usuario_usu',
        'correo_usu',
        'clave_usu',
        'clave_cambiada_en_usu',
        'estado_usu',
    ];

    protected $hidden = [
        'clave_usu',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clave_usu' => 'hashed',
            'clave_cambiada_en_usu' => 'datetime',
            'estado_usu' => EstadoRegistro::class,
        ];
    }

    /**
     * La columna de la contrasena no se llama `password`, asi que hay que
     * decirselo al guard.
     */
    public function getAuthPassword(): string
    {
        return $this->clave_usu;
    }

    /**
     * Correo al que se envian las notificaciones y el enlace de recuperacion.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->correo_usu;
    }

    public function routeNotificationForMail(): string
    {
        return $this->correo_usu;
    }

    /**
     * @return BelongsTo<Rol, $this>
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    /**
     * @param  Builder<$this>  $consulta
     */
    public function scopeHabilitado(Builder $consulta): void
    {
        $consulta->where('estado_usu', EstadoRegistro::Habilitado);
    }

    public function estaHabilitado(): bool
    {
        return $this->estado_usu === EstadoRegistro::Habilitado;
    }

    /**
     * Un usuario deshabilitado no conserva ningun permiso, aunque su rol si los
     * tenga: es la forma de cortarle el acceso sin tocar el rol entero.
     */
    public function puede(Permiso $permiso): bool
    {
        if (! $this->estaHabilitado()) {
            return false;
        }

        return $this->rol?->estaHabilitado() && $this->rol->tiene($permiso);
    }

    /**
     * Iniciales para el avatar de la cabecera.
     */
    public function iniciales(): string
    {
        $palabras = preg_split('/\s+/', trim($this->nombre_usu)) ?: [];

        return mb_strtoupper(mb_substr($palabras[0] ?? '', 0, 1).mb_substr($palabras[1] ?? '', 0, 1));
    }
}
