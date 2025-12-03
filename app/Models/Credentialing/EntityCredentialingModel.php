<?php

namespace App\Models\Credentialing;

use Illuminate\Database\Eloquent\Model;

class EntityCredentialingModel extends Model
{
    protected $table = 'profesionalizacion.tbl_profesionalizacion';

    // primary field
    protected $primaryKey = 'id_tbl_profesionalizacion';

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
        'id_usuario_creacion',
        'id_usuario_actualizacion',
        'id_cat_sig_puesto',
        'id_cat_estatus',
        'id_tbl_empleados',
        'observacion',
        'fecha_inicio',
    ];

    // field casting
    protected $casts = [];
}
