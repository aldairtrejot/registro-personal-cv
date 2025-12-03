<?php

namespace App\Models\Administration\Branch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class CollectionBranchModel extends Model
{
    /**
     * The function returns the list of active Branchs for the combox
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listCollection()
    {
        return DB::table('catalogo.cat_rama') // Query the 'tbl_Branchs' table in the 'app' schema
            ->select(
                'catalogo.cat_rama.id_cat_rama as id', // Select 'id_tbl_Branchs' and alias it as 'id'
                'catalogo.cat_rama.descripcion as descripcion' // Select 'nombre' and alias it as 'descripcion'
            )
            ->where('catalogo.cat_rama.estatus', TRUE) // Filter only Branchs where 'estatus' is true
            ->orderBy('catalogo.cat_rama.estatus', 'ASC') // Order the results alphabetically by 'descripcion'
            ->get(); // Execute the query and return the results
    }


    /**
     * Summary of listOptionsSelect
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listConllectionSelect($id)
    {
        return DB::table('catalogo.cat_rama')
            ->select(
                'catalogo.cat_rama.id_cat_rama as id',
                'catalogo.cat_rama.descripcion as descripcion'
            )
            ->join('catalogo.rel_usuario_rol', 'catalogo.cat_rama.id_cat_rama', '=', 'catalogo.rel_usuario_rol.id_cat_rama')
            ->where('catalogo.rel_usuario_rol.id_usuario', $id)
            ->orderBy('catalogo.cat_rama.descripcion', 'ASC')
            ->get();
    }
}
