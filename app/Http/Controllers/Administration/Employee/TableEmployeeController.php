<?php

namespace App\Http\Controllers\Administration\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\TemplateTableController;
use App\Models\Administration\Employee\TableEmployeeModel;
use Illuminate\Http\Request;

class TableEmployeeController extends Controller
{
    /**
     * Ruta: POST /employee/table
     */
    public function table(Request $request)
    {
        $limit  = (int) $request->input('limit', 5);
        $offset = (int) $request->input('offset', 0);
        $search = (string) $request->input('search', '');
        $select = (int) $request->input('select', 5);

        // 🔧 Normaliza id_cat_estatus sin importar el shape
        $raw = $request->input('id_cat_estatus', null);

        if (is_array($raw)) {
            $raw = $raw['id'] ?? $raw['value'] ?? $raw['id_cat_estatus'] ?? null;
        } elseif (is_object($raw)) {
            $raw = $raw->id ?? $raw->value ?? $raw->id_cat_estatus ?? null;
        }

        // Limpia strings
        if (is_string($raw)) {
            $raw = trim($raw);
        }

        // 🎯 Reglas:
        // - '' o null  => null (SIN FILTRO)
        // - 9999       => null (SIN FILTRO)
        // - '0' o 0    => 0  (INACTIVO)
        // - '1' o 1    => 1  (ACTIVO)
        // - cualquier otro entero => ese valor
        $statusId = null;
        if ($raw === '' || $raw === null) {
            $statusId = null;
        } elseif ((string)$raw === '9999') {
            $statusId = null;
        } elseif (is_numeric($raw)) {
            $statusId = (int) $raw; // ⚠️ aquí ya NO anulamos el 0
        } else {
            // Si te mandan algo raro (p.ej. 'all', 'todos'), trátalo como sin filtro
            $val = strtolower((string)$raw);
            $statusId = in_array($val, ['all','todos','todo','*'], true) ? null : null;
        }

        $model = new TableEmployeeModel();

        try {
            $data = $model->list($limit, $offset, $search, $select, $statusId);
            return response()->json($data);
        } catch (\Throwable $e) {
            \Log::error('Employee table error', ['ex' => $e]);
            return response()->json([
                'status'  => false,
                'message' => 'No pudimos cargar la tabla de empleados. Intenta de nuevo.',
            ], 500);
        }
    }
}