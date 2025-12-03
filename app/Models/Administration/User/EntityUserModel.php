<?php

namespace App\Models\Administration\User;

use Illuminate\Database\Eloquent\Model;

class EntityUserModel extends Model
{
    protected $table = 'administracion.users';

    // primary field
    protected $primaryKey = 'id';

    // incremental status
    public $incrementing = true;

    // primary field type
    protected $keyType = 'int';

    // create and update fields
    public $timestamps = false;

    // fields
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'estatus',
        'creado_en',
        'id_usuario_creacion',
        'id_usuario_modificacion',
        'es_administrador',
        'password_update',
        'actualizado_en',
        'id_cat_entidad',
        'id_cat_zona',
        'id_cat_rama',
        'id_tbl_empleado',
        'fecha_bloqueo',
    ];

    // field casting
    protected $casts = [
        'estatus' => 'boolean',
    ];
}
