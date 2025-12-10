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

    // ====== RELACIONES ======

    public function carreraEspecifica()
    {
        return $this->belongsTo(
            CatCarreraEspecifica::class,
            'id_carrera_especifica',
            'id_carrera_especifica'
        );
    }

    public function carreraGenerica()
    {
        return $this->belongsTo(
            CatCarreraGenerica::class,
            'id_carrera_generica',
            'id_carrera_generica'
        );
    }

    public function area()
    {
        return $this->belongsTo(
            CatArea::class,
            'id_area',
            'id_area'
        );
    }
}
