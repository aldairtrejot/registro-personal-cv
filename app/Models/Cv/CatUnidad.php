<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class CatUnidad extends Model
{
    // 👇 Nombre completo de la tabla en PostgreSQL (esquema + tabla)
    protected $table = 'profesionalizacion.cat_unidades';

    protected $primaryKey = 'id_unidad';

    public $timestamps = false;

    protected $fillable = [
        'nombre_unidad',
        'activo',
    ];
}
