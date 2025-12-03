<?php

namespace App\Models\Collection\Area;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class CollectionAreaModel extends Model
{

    /**
     * The function returns the list of active roles for the combox
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listCollection()
    {
        return DB::table('catalogo.cat_zona') // Query the 'tbl_roleses' table in the 'app' schema
            ->select(
                'catalogo.cat_zona.id_cat_zona as id', // Select 'id_tbl_roleses' and alias it as 'id'
                'catalogo.cat_zona.descripcion as descripcion' // Select 'nombre' and alias it as 'descripcion'
            )
            ->where('catalogo.cat_zona.estatus', TRUE) // Filter only roles where 'estatus' is true
            ->orderBy('catalogo.cat_zona.descripcion', 'ASC') // Order the results alphabetically by 'nombre'
            ->get(); // Execute the query and return the results
    }

    /**
     * Summary of listOptionsSelect
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listConllectionSelect($id)
    {
        return DB::table('catalogo.cat_zona') // Query the 'tbl_roles' table in the 'app' schema
            ->select(
                'catalogo.cat_zona.id_cat_zona as id', // Select 'id_tbl_roles' and alias it as 'id'
                'catalogo.cat_zona.descripcion as descripcion' // Select 'nombre' and alias it as 'descripcion'
            )
            ->where('catalogo.cat_zona.id_cat_zona', $id)
            ->orderBy('catalogo.cat_zona.descripcion', 'ASC')
            ->get();
    }
}
