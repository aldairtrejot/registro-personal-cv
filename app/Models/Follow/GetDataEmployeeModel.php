<?php

namespace App\Models\Follow;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class GetDataEmployeeModel extends Model
{
    public function getDataEmployee($id)
    {
        return DB::table('profesionalizacion.tbl_profesionalizacion')
            ->where('id_tbl_empleados', $id)
            ->first();
    }
}
