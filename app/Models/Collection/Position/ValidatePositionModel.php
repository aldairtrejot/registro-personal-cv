<?php

namespace App\Models\Collection\Position;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ValidatePositionModel extends Model
{
    public function validatePosition($id)
    {
        $userId = Auth::id(); // O el ID que necesites

        return DB::table('administracion.users')
            ->join('profesionalizacion.tbl_empleados', 'administracion.users.id_tbl_empleado', '=', 'profesionalizacion.tbl_empleados.id_tbl_empleados')
            ->join('profesionalizacion.tbl_plazas', 'profesionalizacion.tbl_empleados.id_tbl_plazas', '=', 'profesionalizacion.tbl_plazas.id_tbl_plazas')
            ->join('catalogo.cat_puesto', 'profesionalizacion.tbl_plazas.id_cat_puesto', '=', 'catalogo.cat_puesto.id_cat_puesto')
            ->join('catalogo.cat_sig_puesto', 'catalogo.cat_puesto.id_cat_puesto', '=', 'catalogo.cat_sig_puesto.id_cat_puesto')
            ->join('catalogo.cat_puesto AS max_position', 'catalogo.cat_sig_puesto.id_cat_puesto_siguiente', '=', 'max_position.id_cat_puesto')
            ->where('administracion.users.id', $userId)
            ->where('max_position.id_cat_puesto', $id)
            ->select(
                'max_position.id_cat_puesto AS id',
                'profesionalizacion.tbl_empleados.id_tbl_empleados AS id_tbl_empleados',
                DB::raw("CONCAT(max_position.descripcion, ' - ', max_position.codigo) AS descripcion")
            )
            ->get();
    }
}
