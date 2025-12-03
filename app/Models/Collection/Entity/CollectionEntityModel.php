<?php

namespace App\Models\Collection\Entity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class CollectionEntityModel extends Model
{
    /**
     * The function returns the list of active roles for the combox
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listCollection($id)
    {
        return DB::table('catalogo.cat_entidad')
            ->select(
                'catalogo.cat_entidad.id_cat_entidad as id',
                DB::raw("CONCAT(catalogo.cat_entidad.abrev, ' - ',
                                catalogo.cat_entidad.descripcion) as descripcion")
            )
            ->join(
                'catalogo.rel_entidad_zona',
                'catalogo.cat_entidad.id_cat_entidad',
                '=',
                'catalogo.rel_entidad_zona.id_cat_entidad'
            )
            ->where('catalogo.rel_entidad_zona.id_cat_zona', '=', $id)
            ->where('catalogo.cat_entidad.estatus', TRUE)
            ->where('catalogo.rel_entidad_zona.estatus', TRUE)
            ->orderBy('catalogo.cat_entidad.descripcion', 'ASC')
            ->get();
    }

    /**
     * Summary of listOptionsSelect
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listConllectionSelect($id)
    {
        return DB::table('catalogo.cat_entidad') // Query the 'tbl_roles' table in the 'app' schema
            ->select(
                'catalogo.cat_entidad.id_cat_entidad as id', // Select 'id_tbl_roles' and alias it as 'id'
                DB::raw("CONCAT(catalogo.cat_entidad.abrev, ' - ',
                                catalogo.cat_entidad.descripcion) as descripcion")
            )
            ->where('catalogo.cat_entidad.id_cat_entidad', $id)
            ->orderBy('catalogo.cat_entidad.descripcion', 'ASC')
            ->get();
    }
}
