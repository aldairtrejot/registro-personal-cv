<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatCarreraGenerica extends Model
{
    protected $table = 'profesionalizacion.cat_carreras_genericas';
    protected $primaryKey = 'id_carrera_generica';
    public $timestamps = false;

    protected $fillable = [
        'nombre_generico',
        'activo',
    ];
}
