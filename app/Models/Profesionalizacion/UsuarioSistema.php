<?php

namespace App\Models\Profesionalizacion;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UsuarioSistema extends Authenticatable
{
    use Notifiable;

    protected $table = 'profesionalizacion.usuarios_sistema';
    protected $primaryKey = 'id_usuario';
    public $timestamps = true;

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

    protected $casts = [
        'activo'     => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function roles()
    {
        return $this->belongsToMany(
            CatRol::class,
            'profesionalizacion.rel_usuario_rol',
            'id_usuario',
            'id_rol'
        )->withPivot('activo', 'created_at');
    }

    public function hasRol(string $rol): bool
    {
        return $this->roles()
            ->where('nombre_rol', strtoupper($rol))
            ->wherePivot('activo', true)
            ->exists();
    }

    public function esAdmin(): bool
    {
        return $this->hasRol('ADMINISTRADOR');
    }

    public function esEmpleado(): bool
    {
        return $this->hasRol('EMPLEADO');
    }
}
