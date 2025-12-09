<?php

namespace App\Models\Profesionalizacion;

use Illuminate\Database\Eloquent\Model;

class CatRol extends Model
{
    protected $table = 'profesionalizacion.cat_roles';
    protected $primaryKey = 'id_rol';
    public $timestamps = false;

    protected $fillable = [
        'nombre_rol',
        'descripcion',
        'activo',
    ];

    public function usuarios()
    {
        return $this->belongsToMany(
            UsuarioSistema::class,
            'profesionalizacion.rel_usuario_rol',
            'id_rol',
            'id_usuario'
        )->withPivot('activo', 'created_at');
    }
}
