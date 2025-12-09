<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatPuestoEspecifico extends Model
{
    protected $table = 'profesionalizacion.cat_puestos_especificos';
    protected $primaryKey = 'id_puesto_especifico';
    public $timestamps = false;

    protected $fillable = [
        'nombre_puesto_especifico',
        // si tu tabla tiene "activo", agrégalo aquí
        // 'activo',
    ];
}
