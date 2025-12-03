<?php

namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;

class EntityLogModel extends Model
{
    protected $table = 'log.tbl_registro_acitvidad';

    // primary field
    protected $primaryKey = 'id_tbl_registro_acitvidad';

    // incremental status
    public $incrementing = true;

    // primary field type
    protected $keyType = 'int';

    // create and update fields
    public $timestamps = false;

    // fields
    protected $fillable = [
        'nombre_tabla',
        'accion',
        'descripcion',
        'creado_en',
        'id_usuario_creacion',
    ];

    // field casting
    protected $casts = [];
}
