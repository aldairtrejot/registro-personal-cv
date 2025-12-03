<?php

namespace App\Http\Controllers\Administration\Employee;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\Administration\Employee\EmployeeHistoryModel;

class EmployeeHistoryController extends Controller
{
    public function index(Request $request, int $prof_id)
    {
        try {
            if ($prof_id <= 0) {
                return response()->json(['status' => false, 'message' => 'El identificador del proceso no es válido'], 200);
            }

            $model = new EmployeeHistoryModel();
            $rows  = $model->listByProfId($prof_id);

            return response()->json(['status' => true, 'data' => $rows], 200);

        } catch (\Throwable $e) {
            \Log::error('EmployeeHistoryController@index failed', [
                'prof_id' => $prof_id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // En local muestra el motivo exacto; en otros entornos, mensaje genérico
            $msg = app()->isLocal() ? $e->getMessage() : 'No pudimos cargar el historial. Intenta de nuevo.';
            return response()->json(['status' => false, 'message' => $msg], 200);
        }
    }
}