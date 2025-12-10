<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class RelCarrera extends Model
{
    protected $table = 'profesionalizacion.rel_carreras';
    protected $primaryKey = 'id_rel';
    public $timestamps = false;

    protected $fillable = [
        'id_carrera_especifica',
        'id_carrera_generica',
        'id_area',
    ];
}
