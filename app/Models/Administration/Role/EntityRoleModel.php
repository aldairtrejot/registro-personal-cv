<?php

namespace App\Models\Administration\Role;

use Illuminate\Database\Eloquent\Model;

class EntityRoleModel extends Model
{
    // Nombre de la tabla
    protected $table = 'administracion.tbl_roles';

    // Llave primaria
    protected $primaryKey = 'id_roles';

    // Llave primaria autoincremental
    public $incrementing = true;

    // Tipo de llave primaria
    protected $keyType = 'int';

    // No usar timestamps (created_at, updated_at)
    public $timestamps = false;

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
        'descripcion',
        'estatus',
    ];

    // Cast de campos
    protected $casts = [
        'estatus' => 'boolean',
    ];
}

