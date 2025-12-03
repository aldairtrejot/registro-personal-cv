<?php

namespace App\Http\Controllers\Follow;

use App\Http\Controllers\Controller;
use App\Models\Follow\DocumentRequirementModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResetUuidsController extends Controller
{
    /**
     * POST /follow/reset-uuids
     * Requiere id_tbl_profesionalizacion (o lo deduce del usuario).
     * Valida: p.id_cat_estatus = 1 y ventana (fecha_bloqueo NULL o >= hoy).
     * Limpia UUIDs SOLO de documentos RECHAZADOS (id_cat_estatus_documento = 1).
     * Registra auditoría en p (sin cambiar estatus).
     */
    public function resetGlobal(Request $request, DocumentRequirementModel $docs)
    {
        $id = (int) $request->input('id_tbl_profesionalizacion', 0);

        if ($id <= 0) {
            $id = $this->resolveActiveProcessIdFromAuth();
            if ($id <= 0) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'No se pudo identificar un proceso activo (estatus=1) para este usuario.',
                ], 422);
            }
        }

        $userId = (int) Auth::id();
        $tz     = config('app.timezone', 'America/Mexico_City');
        $today  = Carbon::today($tz);

        try {
            DB::beginTransaction();

            // Validar estatus=1 y ventana abierta
            $ctx = DB::table('profesionalizacion.tbl_profesionalizacion as p')
                ->join('profesionalizacion.tbl_empleados as e', 'e.id_tbl_empleados', '=', 'p.id_tbl_empleados')
                ->join('administracion.users as u', 'u.id_tbl_empleado', '=', 'e.id_tbl_empleados')
                ->where('p.id_tbl_profesionalizacion', $id)
                ->select(['p.id_cat_estatus', 'u.fecha_bloqueo'])
                ->first();

            if (!$ctx) {
                DB::rollBack();
                return response()->json(['ok' => false, 'message' => 'Proceso no encontrado.'], 404);
            }

            $estatusOk = ((int) $ctx->id_cat_estatus === 1);
            $ventanaOk = (is_null($ctx->fecha_bloqueo) || Carbon::parse($ctx->fecha_bloqueo, $tz)->greaterThanOrEqualTo($today));

            if (!$estatusOk || !$ventanaOk) {
                DB::rollBack();
                $razones = [];
                if (!$estatusOk) $razones[] = 'estatus distinto de 1';
                if (!$ventanaOk) $razones[] = 'fecha_bloqueo vencida';
                return response()->json([
                    'ok'      => false,
                    'message' => 'No se pudo completar la acción. Por favor, vuelve a intentarlo.',
                ], 403);
            }

            // Limpiar UUIDs SOLO de documentos RECHAZADOS
            $sql = <<<SQL
UPDATE profesionalizacion.ctrl_documentos_profesionalizacion
SET uuid = NULL
WHERE id_tbl_profesionalizacion = :id
  AND id_cat_estatus_documento = 1
  AND COALESCE(uuid, '') <> ''
SQL;
            $affected = DB::update($sql, ['id' => $id]);

            // Auditoría en p (sin cambiar estatus)
            DB::table('profesionalizacion.tbl_profesionalizacion')
                ->where('id_tbl_profesionalizacion', $id)
                ->update([
                    'id_usuario_actualizacion' => $userId,
                    'actualizado_en'           => now(),
                ]);

            DB::commit();

            return response()->json([
                'ok'       => true,
                'message'  => 'UUIDs limpiados para documentos rechazados (si aplicaba) y auditoría registrada.',
                'affected' => (int) $affected,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('ResetUuidsController@resetGlobal error', ['error' => $e->getMessage()]);
            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo limpiar UUIDs',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function resolveActiveProcessIdFromAuth(): int
    {
        $userId = (int) Auth::id();
        if (!$userId) return 0;

        $rowUser = DB::table('administracion.users')
            ->select('id_tbl_empleado')
            ->where('id', $userId)
            ->first();

        $idEmpleado = (int) ($rowUser->id_tbl_empleado ?? 0);
        if ($idEmpleado <= 0) return 0;

        $row = DB::table('profesionalizacion.tbl_profesionalizacion')
            ->select('id_tbl_profesionalizacion')
            ->where('id_tbl_empleados', $idEmpleado)
            ->where('id_cat_estatus', 1)
            ->orderByDesc('creado_en')
            ->first();

        return (int) ($row->id_tbl_profesionalizacion ?? 0);
    }
}

