<?php

namespace App\Http\Controllers\Administration\UserEmployee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ToogleUserEmployeeStatusController extends Controller
{
    public function toggle(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer',
                'estatus' => 'required|boolean',
            ]);

            DB::table('administracion.users')
                ->where('id_tbl_empleado', $request->id)
                ->update(['estatus' => $request->estatus]);

            return response()->json(['status' => true]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }
}
