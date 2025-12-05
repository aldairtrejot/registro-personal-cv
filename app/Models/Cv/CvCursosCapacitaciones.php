<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CvCursosCapacitaciones extends Model
{
    protected $table = 'profesionalizacion.tbl_cv_cursos_capacitaciones';
    protected $primaryKey = 'id_tbl_cv_cursos_capacitaciones';
    public $timestamps = false;

    protected $fillable = [
        'id_tbl_empleados',
        'periodo',
        'nombre_curso',
        'institucion',
        'orden',
        'creado_en',
        'actualizado_en',
    ];
}
