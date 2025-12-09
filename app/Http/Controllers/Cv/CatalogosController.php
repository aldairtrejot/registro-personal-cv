<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\Cv\CatPais;
use App\Models\Cv\CatNivelEstudios;
use App\Models\Cv\CatAreaEstudio;
use App\Models\Cv\CatPuesto;
use App\Models\Cv\CatPuestoEspecifico;
use App\Models\Cv\CatUnidad;
use Illuminate\Support\Facades\DB;

class CatalogosController extends Controller
{
    // ====== ESTUDIOS ======

    public function paises()
    {
        return CatPais::where('activo', true)
            ->orderBy('nombre')
            ->get([
                'id_pais as id',
                'nombre',
            ]);
    }

    public function nivelesEstudio()
    {
        return CatNivelEstudios::where('activo', true)
            ->orderBy('descripcion')
            ->get([
                'id_nivel_estudios as id',
                'descripcion as nombre',
            ]);
    }

    public function areasEstudio()
    {
        return CatAreaEstudio::where('activo', true)
            ->orderBy('nombre_area')
            ->get([
                'id_area_estudio as id',
                'nombre_area as nombre',
            ]);
    }

    // ====== PUESTOS ======

    public function puestos()
    {
        return CatPuesto::where('activo', true)
            ->orderBy('nombre')
            ->get([
                'id_puesto as id',
                'nombre',
            ]);
    }

    public function puestosEspecificos()
    {
        return CatPuestoEspecifico::orderBy('nombre_puesto_especifico')
            ->get([
                'id_puesto_especifico as id',
                'nombre_puesto_especifico as nombre',
            ]);
    }

    // ====== UNIDADES / COORDINACIONES ======

    public function unidades()
    {
        return CatUnidad::where('activo', true)
            ->orderBy('nombre_unidad')
            ->get([
                'id_unidad as id',
                'nombre_unidad as nombre',
            ]);
    }

    public function coordinacionesPorUnidad($id_unidad)
    {
        return DB::table('profesionalizacion.rel_unidad_coordinacion as r')
            ->join(
                'profesionalizacion.cat_coordinaciones as c',
                'c.id_coordinacion',
                '=',
                'r.id_coordinacion'
            )
            ->where('r.id_unidad', $id_unidad)
            ->where('r.activo', true)
            ->where('c.activo', true)
            ->orderBy('c.nombre_coordinacion')
            ->get([
                'c.id_coordinacion as id',
                'c.nombre_coordinacion as nombre',
            ]);
    }
}
