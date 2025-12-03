<?php

namespace App\Http\Controllers\Follow;

use App\Http\Controllers\Controller;
use App\Models\Follow\ProcessUpdateModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessUpdateController extends Controller
{
    /**
     * Marca actualizado_en e id_usuario_actualizacion
     * (NO toca id_cat_estatus)
     */
    public function markUpdated(Request $request)
    {
        try {
            $request->validate([
                'id_tbl_profesionalizacion' => 'required|integer'
            ]);

            $id      = (int) $request->id_tbl_profesionalizacion;
            $userId  = (int) Auth::id();

            // Verifica que exista el proceso
            $exists = DB::table('profesionalizacion.tbl_profesionalizacion')
                ->where('id_tbl_profesionalizacion', $id)
                ->exists();

            if (!$exists) {
                return response()->json([
                    'status'  => false,
                    'reason'  => 'ROW_NOT_FOUND',
                    'message' => "No existe id_tbl_profesionalizacion={$id}"
                ], 200);
            }

            $model = new ProcessUpdateModel();
            $ok    = $model->touchUpdated($id, $userId);

            if (!$ok) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No se modificaron filas. ¿Ya estaba con la misma marca?',
                ], 200);
            }

            $info = $model->getUpdatedInfo($id);

            return response()->json([
                'status'  => true,
                'message' => 'Proceso marcado como actualizado.',
                'data'    => $info,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            \Log::error('markUpdated error', ['exception' => $th]);
            return response()->json([
                'status'  => false,
                'message' => 'Error al marcar actualizado.',
            ], 500);
        }
    }

    /**
     * (Opcional) Consultar info para pintar en el panel
     */
    public function getUpdatedInfo(Request $request)
    {
        try {
            $request->validate([
                'id_tbl_profesionalizacion' => 'required|integer'
            ]);
            $id   = (int) $request->id_tbl_profesionalizacion;

            $model = new ProcessUpdateModel();
            $info  = $model->getUpdatedInfo($id);

            return response()->json([
                'status' => true,
                'data'   => $info,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'Error al consultar información.',
            ], 500);
        }
    }
}
