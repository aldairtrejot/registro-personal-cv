<?php

namespace App\Http\Controllers\Administration\Employee;

use App\Http\Controllers\Controller;
use App\Models\Follow\DocumentRequirementModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RejectProcessController extends Controller
{
    /**
     * Mapeo real de tu BD:
     * 1 = EMPLEADO
     * 2 = EMPLEADO (enviado / intermedio)
     * 3 = REVISOR
     * 4 = SUPERVISOR
     * 5 = DGES
     */
    private const STAGE = [
        'EMPLEADO'   => 1,
        'EMP_INTER'  => 2, // etapa intermedia antes de revisor
        'REVISOR'    => 3,
        'SUPERVISOR' => 4,
        'DGES'       => 5,
    ];

    // cat_estatus_documento
    private const DOC = [
        'RECHAZADO' => 1,
        'ACEPTADO'  => 2,
        'PROCESO'   => 3,
    ];

    public function __construct(
        private DocumentRequirementModel $docReq
    ) {}

    /**
     * POST /employee/reject
     * Body: { prof_id: int, observacion: string }
     *
     * Reglas solicitadas:
     *   - Rechazo baja escalonado: 5->4, 4->3, 3->2, 2->1
     *   - Solo cuando el proceso pasa a 1 (EMPLEADO) se:
     *       * limpian UUIDs de documentos RECHAZADOS
     *       * y esos documentos se mueven a PROCESO (3) (para re-subida)
     *   - Debe existir al menos 1 documento RECHAZADO (1) para poder rechazar
     */
    public function reject(Request $request)
    {
        $request->validate([
            'prof_id'     => 'required|integer|min:1',
            'observacion' => 'required|string|min:3',
        ]);

        $profId = (int) $request->input('prof_id');
        $obs    = trim((string) $request->input('observacion'));
        $userId = (int) (Auth::id() ?? 0);
        $now    = Carbon::now();

        // 0) Traer proceso
        $proceso = DB::table('profesionalizacion.tbl_profesionalizacion as p')
            ->select('p.id_tbl_profesionalizacion','p.id_tbl_empleados','p.id_cat_estatus')
            ->where('p.id_tbl_profesionalizacion', $profId)
            ->first();

        if (!$proceso) {
            return response()->json(['status'=>false,'message'=>'No encontramos este proceso.'], 404);
        }

        $current = (int) $proceso->id_cat_estatus;
        $next    = $this->stageAfterReject($current); // 5->4, 4->3, 3->2, 2->1

        if ($next === $current) {
            return response()->json([
                'status'  => false,
                'message' => 'No se pudo completar la acción. Por favor, vuelve a intentarlo.',
            ], 409);
        }

        // 1) Verificar que exista al menos 1 documento RECHAZADO
        $rejectedDocs = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
            ->where('id_tbl_profesionalizacion', $profId)
            ->where('id_cat_estatus_documento', self::DOC['RECHAZADO'])
            ->pluck('id_ctrl_documentos_profesionalizacion');

        if ($rejectedDocs->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'No puedes rechazar: no hay documentos marcados como rechazados.',
            ], 409);
        }

        DB::beginTransaction();
        try {
            // 2) Bajar etapa + dejar observación
            DB::table('profesionalizacion.tbl_profesionalizacion')
                ->where('id_tbl_profesionalizacion', $profId)
                ->update([
                    'id_cat_estatus'           => $next,
                    'observacion'              => $obs,
                    'id_usuario_actualizacion' => $userId,
                    'actualizado_en'           => $now,
                ]);

            // 3) Traza de rechazo para esos docs (historia)
            foreach ($rejectedDocs as $docId) {
                DB::table('profesionalizacion.ctrl_historia_documentos')->insert([
                    'id_ctrl_documentos_profesionalizacion' => $docId,
                    'id_cat_estatus_documento'              => self::DOC['RECHAZADO'],
                    'observaciones'                         => $obs,
                    'creado_en'                             => $now,
                    'actualizado_en'                        => $now,
                    'id_usuario_creacion'                   => $userId,
                    'id_usuario_actualizacion'              => $userId,
                    'id_usuario_autorizacion'               => null,
                    'fecha_autorizacion'                    => null,
                ]);
            }

            // 4) SOLO cuando pasa a 1 (EMPLEADO) => limpiar uuid + pasar rechazados a PROCESO
            if ($next === self::STAGE['EMPLEADO']) {
                // limpia UUIDs de RECHAZADOS solo si el proceso YA está en 1
                $this->docReq->resetUuidsForProcess($profId, self::STAGE['EMPLEADO']);

                // mover documentos rechazados a PROCESO para permitir re-subida
                DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
                    ->whereIn('id_ctrl_documentos_profesionalizacion', $rejectedDocs->all())
                    ->update([
                        'id_cat_estatus_documento' => self::DOC['PROCESO'],
                        'fecha_autorizacion'       => null,
                        'id_usuario_autorizacion'  => null,
                        'id_usuario_actualizacion' => $userId,
                        'actualizado_en'           => $now,
                    ]);
            }

            DB::commit();

            $msg = match (true) {
                $current === self::STAGE['DGES']       && $next === self::STAGE['SUPERVISOR'] => 'El proceso regresó a SUPERVISOR. Los archivos se conservan.',
                $current === self::STAGE['SUPERVISOR'] && $next === self::STAGE['REVISOR']    => 'El proceso regresó a REVISOR. Los archivos se conservan.',
                $current === self::STAGE['REVISOR']    && $next === self::STAGE['EMP_INTER']  => 'El proceso regresó a la etapa intermedia del EMPLEADO. Los archivos se conservan.',
                $current === self::STAGE['EMP_INTER']  && $next === self::STAGE['EMPLEADO']   => 'El proceso regresó a EMPLEADO. Los documentos rechazados pueden volver a cargarse.',
                default => 'Se aplicó el rechazo.',
            };

            return response()->json([
                'status'  => true,
                'message' => $msg,
                'result'  => [
                    'prof_id'       => $profId,
                    'old_stage'     => $current,
                    'new_stage'     => $next,
                    'rejected_docs' => count($rejectedDocs),
                ],
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'No pudimos aplicar el rechazo. Intenta de nuevo.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Regresa la etapa previa válida según reglas:
     * 5 -> 4, 4 -> 3, 3 -> 2, 2 -> 1; si no hay anterior, devuelve la misma.
     */
    private function stageAfterReject(int $current): int
    {
        return match ($current) {
            self::STAGE['DGES']       => self::STAGE['SUPERVISOR'], // 5 -> 4
            self::STAGE['SUPERVISOR'] => self::STAGE['REVISOR'],    // 4 -> 3
            self::STAGE['REVISOR']    => self::STAGE['EMP_INTER'],  // 3 -> 2
            self::STAGE['EMP_INTER']  => self::STAGE['EMPLEADO'],   // 2 -> 1
            default                   => $current,                  // ya en 1 u otro no contemplado
        };
    }
}
