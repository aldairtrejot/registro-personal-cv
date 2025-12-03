<?php

namespace App\Models\Credentialing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class MainCredentialingModel extends Model
{
    public function main()
    {
        $result = DB::table('profesionalizacion.tbl_profesionalizacion')
            ->select(
                'profesionalizacion.tbl_profesionalizacion.id_tbl_profesionalizacion',
                'profesionalizacion.tbl_profesionalizacion.id_cat_estatus'
            )
            ->join(
                'profesionalizacion.tbl_empleados',
                'profesionalizacion.tbl_profesionalizacion.id_tbl_empleados',
                '=',
                'profesionalizacion.tbl_empleados.id_tbl_empleados'
            )
            ->join(
                'administracion.users',
                'profesionalizacion.tbl_empleados.id_tbl_empleados',
                '=',
                'administracion.users.id_tbl_empleado'
            )
            ->where('administracion.users.id', Auth::id())
            ->first(); // devuelve una colección

        return $result; // lo convertimos en array puro
    }
}
