<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatPais extends Model
{
    protected $table = 'profesionalizacion.cat_paises';
    protected $primaryKey = 'id_pais';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'activo',
    ];
}
