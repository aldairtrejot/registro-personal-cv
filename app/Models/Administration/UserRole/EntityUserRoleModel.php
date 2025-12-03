<?php

namespace App\Models\Administration\UserRole;

use Illuminate\Database\Eloquent\Model;

class EntityUserRoleModel extends Model
{
    protected $table = 'administracion.rel_users_rol';

    // primary field
    protected $primaryKey = 'id_rel_users_rol';

    // incremental status
    public $incrementing = true;

    // primary field type
    protected $keyType = 'int';

    // create and update fields
    public $timestamps = false;

    // fields
    protected $fillable = [
        'id_users',
        'id_tbl_roles',
        'estatus',
        'creado_en',
        'id_usuario_creacion',
        'id_usuario_modificacion',
        'actualizado_en',
    ];

    // field casting
    protected $casts = [
        'estatus' => 'boolean',
    ];
}
