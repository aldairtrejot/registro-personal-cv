<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatCarreraEspecifica extends Model
{
    protected $table = 'profesionalizacion.cat_carreras_especificas';
    protected $primaryKey = 'id_carrera_especifica';
    public $timestamps = false;

    protected $fillable = [
        'nombre_especifico',
        'activo',
    ];
}
