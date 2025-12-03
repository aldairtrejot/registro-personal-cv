<?php

namespace App\Models\Administration\Entity;

use Illuminate\Database\Eloquent\Model;

class EntityModel extends Model
{
    protected $table = 'catalogo.cat_entidad';
    protected $primaryKey = 'id_cat_entidad';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'abrev',
        'descripcion',
        'clave_entidad',
        'estatus',
        // 'id_usuario_creacion', 'creado_en', // si agregas auditoría
    ];

    protected $casts = [
        'estatus' => 'boolean',
    ];
}

