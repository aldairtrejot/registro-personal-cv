<?php

namespace App\Http\Controllers\Log;

use App\Http\Controllers\Controller;
use App\Models\Log\EntityLogModel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    /**
     * The function creates the log in the applications expecting as parameters, the name of the table, 
     * the values ​​and the action it performs.
     * @param mixed $tableName
     * @param mixed $data
     * @param mixed $action
     * @return void
     */
    public function createLog($tableName, $data, $action)
    {
        try {
            $timestamp = Carbon::now(); // current timestamp
            $data = json_encode($data); // convert data array to JSON string

            EntityLogModel::create([
                'nombre_tabla' => $tableName, // name of the table being logged
                'accion' => $action, // type of action performed (e.g., create, update, delete)
                'descripcion' => $data, // the data being logged in JSON format
                'creado_en' => $timestamp, // date and time of the action
                'id_usuario_creacion' => Auth::user()->id, // ID of the user who performed the action
            ]);
        } catch (\Throwable $th) {
            //\Log::info($th); // optional: log the error for debugging
        }
    }
}
