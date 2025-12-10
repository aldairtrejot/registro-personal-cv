<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatArea extends Model
{
    protected $table = 'profesionalizacion.cat_areas';
    protected $primaryKey = 'id_area';
    public $timestamps = false;

    protected $fillable = [
        'nombre_area',
    ];
}
