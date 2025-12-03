<?php

namespace App\Http\Controllers\Follow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessStatusController extends Controller
{
    /**
     * POST /follow/finalize-upload
     * Avanza el proceso a estatus 2 (Envío a Revisión) SOLO si:
     *  - El proceso pertenece al usuario autenticado.
     *  - p.id_cat_estatus = 1.
     *  - u.fecha_bloqueo es NULL o >= hoy (ventana abierta).
     *  - TODOS los documentos requeridos tienen uuid (sin faltantes).
     *
     * Body: { id_tbl_profesionalizacion: int }
     */
    public function finalizeUpload(Request $request)
    {
        $id = (int) $request->input('id_tbl_profesionalizacion');

        if ($id <= 0) {
            return response()->json([
                'status'  => false,
                'message' => 'Parámetro inválido (id_tbl_profesionalizacion).',
            ], 422);
        }

        $userId = (int) Auth::id();
        $tz     = config('app.timezone', 'America/Mexico_City');
        $today  = Carbon::today($tz);

        // 1) Contexto y pertenencia
        $row = DB::table('profesionalizacion.tbl_profesionalizacion as p')
            ->join('profesionalizacion.tbl_empleados as e', 'p.id_tbl_empleados', '=', 'e.id_tbl_empleados')
            ->join('administracion.users as u', 'e.id_tbl_empleados', '=', 'u.id_tbl_empleado')
            ->where('p.id_tbl_profesionalizacion', $id)
            ->where('u.id', $userId)
            ->select(['p.id_cat_estatus', 'u.fecha_bloqueo'])
            ->first();

        if (!$row) {
            return response()->json([
                'status'  => false,
                'message' => 'Proceso no encontrado para este usuario.',
            ], 404);
        }

        // 2) Validaciones de ventana y estatus
        $estatusOk = ((int) $row->id_cat_estatus === 1);
        $ventanaOk = (is_null($row->fecha_bloqueo) || Carbon::parse($row->fecha_bloqueo, $tz)->greaterThanOrEqualTo($today));

        if (!$estatusOk || !$ventanaOk) {
            $razones = [];
            if (!$estatusOk) $razones[] = 'estatus distinto de 1';
            if (!$ventanaOk) $razones[] = 'fecha_bloqueo vencida';
            return response()->json([
                'status'  => false,
                'message' => 'No se pudo completar la acción. Por favor, vuelve a intentarlo.',
            ], 403);
        }

        // 3) Verificar que todos los documentos requeridos tengan uuid
        $faltantes = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
            ->where('id_tbl_profesionalizacion', $id)
            ->whereNull('uuid')
            ->count();

        if ($faltantes > 0) {
            return response()->json([
                'status'  => false,
                'message' => 'Aún hay documentos sin cargar.',
                'missing' => (int) $faltantes,
            ], 409);
        }

        // 4) Avanzar a estatus 2 + auditoría
        DB::table('profesionalizacion.tbl_profesionalizacion')
            ->where('id_tbl_profesionalizacion', $id)
            ->update([
                'id_cat_estatus'           => 2,
                'id_usuario_actualizacion' => $userId,
                'actualizado_en'           => now(),
            ]);

        return response()->json([
            'status'     => true,
            'new_status' => 2,
            'message'    => 'Proceso enviado a revisión.',
        ], 200);
    }
}


