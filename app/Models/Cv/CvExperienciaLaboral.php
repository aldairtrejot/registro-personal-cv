<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CvExperienciaLaboral extends Model
{
    protected $table = 'profesionalizacion.tbl_cv_experiencia_laboral';
    protected $primaryKey = 'id_tbl_cv_experiencia_laboral';
    public $timestamps = false;

    protected $fillable = [
        'id_tbl_empleados',
        'fecha_inicio',
        'fecha_termino',
        'sector',
        'puesto',
        'institucion',
        'campo_experiencia',
        'orden',
        'creado_en',
        'actualizado_en',
    ];
}
