<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatUnidad extends Model
{
    protected $table = 'profesionalizacion.cat_unidades';
    protected $primaryKey = 'id_unidad';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'activo',
    ];
}
