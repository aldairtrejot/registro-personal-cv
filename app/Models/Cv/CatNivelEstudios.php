<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatNivelEstudios extends Model
{
    protected $table = 'profesionalizacion.cat_nivel_estudios';
    protected $primaryKey = 'id_nivel_estudios';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'descripcion',
        'descripcion_latin',
        'activo',
    ];
}
