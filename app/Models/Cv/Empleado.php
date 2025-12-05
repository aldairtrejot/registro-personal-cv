<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'profesionalizacion.tbl_empleados';
    protected $primaryKey = 'id_tbl_empleados';
    public $timestamps = false;

    protected $fillable = [
        'curp',
        'nombre',
        'primer_apellido',
        'segundo_apellido',
        'puesto_actual',
        'fecha_inicio_puesto',
        'area_adscripcion',
        'estatus_cv',
    ];
}
