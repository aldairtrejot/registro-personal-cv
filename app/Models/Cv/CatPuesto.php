<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatPuesto extends Model
{
    protected $table = 'profesionalizacion.cat_puestos';
    protected $primaryKey = 'id_puesto';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'activo',
    ];
}
