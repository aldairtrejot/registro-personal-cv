<?php

namespace App\Models\Credentialing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class ValidateUniqueModel extends Model
{
    public function validateUnique()
    {
        $exists = DB::table('administracion.users')
            ->join('profesionalizacion.tbl_empleados', 'administracion.users.id_tbl_empleado', '=', 'profesionalizacion.tbl_empleados.id_tbl_empleados')
            ->join('profesionalizacion.tbl_profesionalizacion', 'profesionalizacion.tbl_empleados.id_tbl_empleados', '=', 'profesionalizacion.tbl_profesionalizacion.id_tbl_empleados')
            ->where('administracion.users.id', Auth::id())
            ->exists(); // Retorna true si hay registros, false si no hay

        return $exists;
    }
}