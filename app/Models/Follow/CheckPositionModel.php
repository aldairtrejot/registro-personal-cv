<?php

namespace App\Models\Follow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class CheckPositionModel extends Model
{
    public function checkPosition()
    {
        $exists = DB::table('administracion.users')
            ->join(
                'profesionalizacion.tbl_empleados',
                'administracion.users.id_tbl_empleado',
                '=',
                'profesionalizacion.tbl_empleados.id_tbl_empleados'
            )
            ->join(
                'profesionalizacion.tbl_profesionalizacion',
                'profesionalizacion.tbl_empleados.id_tbl_empleados',
                '=',
                'profesionalizacion.tbl_profesionalizacion.id_tbl_empleados'
            )
            ->where('administracion.users.id', Auth::id())
            ->exists(); // Devuelve true o false directamente

        return $exists;
    }
     /**
     * Regresa el id_cat_sig_puesto del usuario logueado, o null si no existe.
     */
    public function currentSigPositionId(): ?int
    {
        $val = DB::table('administracion.users')
            ->join('profesionalizacion.tbl_empleados', 'administracion.users.id_tbl_empleado', '=', 'profesionalizacion.tbl_empleados.id_tbl_empleados')
            ->join('profesionalizacion.tbl_profesionalizacion', 'profesionalizacion.tbl_empleados.id_tbl_empleados', '=', 'profesionalizacion.tbl_profesionalizacion.id_tbl_empleados')
            ->where('administracion.users.id', Auth::id())
            ->value('profesionalizacion.tbl_profesionalizacion.id_cat_sig_puesto');

        return $val !== null ? (int) $val : null;
    }
}
