<?php

namespace App\Models\Cv;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UsuarioSistema extends Authenticatable
{
    use Notifiable;

    // 👇 Tabla correcta en tu esquema
    protected $table = 'profesionalizacion.usuarios_sistema';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false; // si luego quieres manejar created_at/updated_at, lo cambiamos

    protected $fillable = [
        'username',
        'nombre_completo',
        'email',
        'password_hash',
        'id_empleado',
        'activo',
    ];

    protected $hidden = [
        'password_hash',
    ];

    // Para que Auth use password_hash
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function roles()
    {
        return $this->belongsToMany(
            \App\Models\Cv\Rol::class,
            'profesionalizacion.rel_usuario_rol',
            'id_usuario',
            'id_rol'
        )->withPivot('activo');
    }
}
