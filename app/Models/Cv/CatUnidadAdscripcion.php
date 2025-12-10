<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatUnidadAdscripcion extends Model
{
    protected $table = 'profesionalizacion.cat_unidades_adscripcion';
    protected $primaryKey = 'id_unidad_adscripcion';
    public $timestamps = false;

    protected $fillable = [
        'nombre_unidad_adscripcion',
        'activo',
    ];
}
