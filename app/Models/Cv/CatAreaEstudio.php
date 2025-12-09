<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatAreaEstudio extends Model
{
    protected $table = 'profesionalizacion.cat_areas_estudio';
    protected $primaryKey = 'id_area_estudio';
    public $timestamps = false;

    protected $fillable = [
        'nombre_area',
        'activo',
    ];
}
