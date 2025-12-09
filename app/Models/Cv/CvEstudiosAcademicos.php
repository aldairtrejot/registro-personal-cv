<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CvEstudiosAcademicos extends Model
{
    protected $table = 'profesionalizacion.tbl_cv_estudios_academicos';
    protected $primaryKey = 'id_tbl_cv_estudios_academicos';
    public $timestamps = false;

    protected $fillable = [
        'id_tbl_empleados',
        'institucion',
        'id_pais',
        'pais',
        'id_nivel_estudios',
        'nivel',
        'numero_cedula',
        'carrera_generica',
        'carrera_especifica',
        'area_estudios',
        'creado_en',
        'actualizado_en',
    ];
}
