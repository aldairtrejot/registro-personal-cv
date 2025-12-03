<?php

namespace App\Models\Administration\Role;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class CollectionRoleModel extends Model
{
    /**
     * The function returns the list of active roles for the combox
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listCollection()
    {
        return DB::table('administracion.tbl_roles') // Query the 'tbl_roleses' table in the 'app' schema
            ->select(
                'administracion.tbl_roles.id_roles as id', // Select 'id_tbl_roleses' and alias it as 'id'
                'administracion.tbl_roles.nombre as descripcion' // Select 'nombre' and alias it as 'descripcion'
            )
            ->where('administracion.tbl_roles.estatus', TRUE) // Filter only roles where 'estatus' is true
            ->orderBy('administracion.tbl_roles.nombre', 'ASC') // Order the results alphabetically by 'nombre'
            ->get(); // Execute the query and return the results
    }

    /**
     * Summary of listOptionsSelect
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listConllectionSelect($id)
    {
        return DB::table('administracion.tbl_roles')
            ->select(
                'administracion.tbl_roles.id_roles as id',
                'administracion.tbl_roles.nombre as descripcion'
            )
            ->join(
                'administracion.rel_users_rol',
                'administracion.tbl_roles.id_roles',
                '=',
                'administracion.rel_users_rol.id_tbl_roles'
            )
            ->where('administracion.rel_users_rol.id_users', $id)
            ->orderBy('administracion.tbl_roles.nombre', 'ASC')
            ->get();
    }
}


