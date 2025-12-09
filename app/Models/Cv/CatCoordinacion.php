<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatCoordinacion extends Model
{
    protected $table = 'profesionalizacion.cat_coordinaciones';
    protected $primaryKey = 'id_coordinacion';
    public $timestamps = false;

    protected $fillable = [
        'nombre_coordinacion',
        'activo',
    ];
}
