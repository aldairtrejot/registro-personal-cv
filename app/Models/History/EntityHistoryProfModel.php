<?php

namespace App\Models\History;

use Illuminate\Database\Eloquent\Model;

class EntityHistoryProfModel extends Model
{
    protected $table = 'profesionalizacion.ctrl_historia_profesionalizacion';

    // primary field
    protected $primaryKey = 'id_ctrl_historia_profesionalizacion';

    // incremental status
    public $incrementing = true;

    // primary field type
    protected $keyType = 'int';

    // create and update fields
    public $timestamps = false;

    // fields
    protected $fillable = [
        'creado_en',
        'actualizado_en',
        'fecha_estatus_revisor',
        'estatus_revisor',
        'fecha_estatus_supervisor',
        'estatus_supervisor',
        'fecha_estatus_dgces',
        'estatus_dgces',
        'id_tbl_profesionalizacion',
        'id_cat_estatus',
        'id_usuario_revisor',
        'id_usuario_supervisor',
        'id_usuario_dgces',
        'id_usuario_actualizacion',
        'id_usuario_creacion',
        'observaciones',
    ];

    // field casting
    protected $casts = [];
}
