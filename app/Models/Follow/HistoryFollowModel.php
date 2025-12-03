<?php

namespace App\Models\Follow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class HistoryFollowModel extends Model
{
    public function historyFollow()
    {
        return DB::table('profesionalizacion.ctrl_historia_profesionalizacion')
            ->select(
                'profesionalizacion.ctrl_historia_profesionalizacion.id_ctrl_historia_profesionalizacion',
                'catalogo.cat_estatus.descripcion AS estatus',
                'profesionalizacion.ctrl_historia_profesionalizacion.observaciones',
                DB::raw("TO_CHAR(profesionalizacion.ctrl_historia_profesionalizacion.actualizado_en, 'DD/MM/YYYY') AS actualizado_en")
            )
            ->join('profesionalizacion.tbl_profesionalizacion', 'profesionalizacion.ctrl_historia_profesionalizacion.id_tbl_profesionalizacion', '=', 'profesionalizacion.tbl_profesionalizacion.id_tbl_profesionalizacion')
            ->join('profesionalizacion.tbl_empleados', 'profesionalizacion.tbl_profesionalizacion.id_tbl_empleados', '=', 'profesionalizacion.tbl_empleados.id_tbl_empleados')
            ->join('administracion.users', 'profesionalizacion.tbl_empleados.id_tbl_empleados', '=', 'administracion.users.id_tbl_empleado')
            ->join('catalogo.cat_estatus', 'profesionalizacion.ctrl_historia_profesionalizacion.id_cat_estatus', '=', 'catalogo.cat_estatus.id_cat_estatus')
            ->where('administracion.users.id', Auth::id())
            ->orderBy('profesionalizacion.ctrl_historia_profesionalizacion.actualizado_en', 'DESC')
            ->get();
    }
}
