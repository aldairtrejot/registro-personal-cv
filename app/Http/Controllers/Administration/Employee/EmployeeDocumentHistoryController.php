<?php

namespace App\Http\Controllers\Administration\Employee;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\Administration\Employee\EmployeeDocumentHistoryModel;
use Illuminate\Support\Facades\Log;

class EmployeeDocumentHistoryController extends Controller
{
    // GET /employee/document/{doc_id}/history
    public function index(Request $request, int $doc_id)
    {
        try {
            if ($doc_id <= 0) {
                return response()->json(['status' => false, 'message' => 'doc_id inválido'], 200);
            }

            $model = new EmployeeDocumentHistoryModel();
            $rows  = $model->listByDocId($doc_id);

            return response()->json(['status' => true, 'data' => $rows], 200);
        } catch (\Throwable $e) {
            Log::error('EmployeeDocumentHistoryController@index failed', [
                'doc_id' => $doc_id,
                'error'  => $e->getMessage(),
            ]);

            $msg = config('app.debug')
                ? ('Error al obtener historial de documento: ' . $e->getMessage())
                : 'Error al obtener historial de documento.';

            return response()->json([
                'status'  => false,
                'message' => $msg,
            ], 200);
        }
    }
}
