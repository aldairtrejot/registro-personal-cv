<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CvTokenAcceso extends Model
{
    protected $table = 'profesionalizacion.tbl_cv_tokens_acceso';
    protected $primaryKey = 'id_tbl_cv_tokens_acceso';
    public $timestamps = false;

    protected $fillable = [
        'curp',
        'correo',
        'token',
        'creado_en',
        'expira_en',
        'usado_en',
    ];
}
