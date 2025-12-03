<?php

namespace App\Http\Controllers\Administration\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessStatusController extends Controller
{
    // cat_estatus (proceso)
    private const STAGE = [
        'PROCESO'    => 1,
        'EMPLEADO'   => 2, // COMPLETADO POR EMPLEADO
        'REVISOR'    => 3, // COMPLETADO POR REVISOR
        'SUPERVISOR' => 4, // COMPLETADO POR SUPERVISOR
        'DGES'       => 5, // COMPLETADO POR DGES
        'RECHAZADO'  => 6,
    ];

    // Flujo lineal
    private const FLOW = [2, 3, 4, 5];

    // cat_estatus_documento
    private const DOC = [
        'RECHAZADO' => 1,
        'ACEPTADO'  => 2,
        'PROCESO'   => 3,
    ];

    /** EXISTS tabla (schema.table) */
    private function hasTablePg(string $schema, string $table): bool
    {
        $row = DB::selectOne(
            "SELECT 1 FROM information_schema.tables
             WHERE table_schema = ? AND table_name = ? LIMIT 1",
            [$schema, $table]
        );
        return (bool)$row;
    }

    /** EXISTS columna (schema.table.column) */
    private function hasColumnPg(string $schema, string $table, string $column): bool
    {
        $row = DB::selectOne(
            "SELECT 1 FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1",
            [$schema, $table, $column]
        );
        return (bool)$row;
    }

    /** Primera columna existente de una lista */
    private function firstExistingColumnPg(string $schema, string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if ($this->hasColumnPg($schema, $table, $c)) return $c;
        }
        return null;
    }

    /** Resolver rol real del usuario (tolerante al esquema) */
    private function resolveRoleId(): int
    {
        $u = Auth::user();
        if (!$u) return 0;

        // 0) ¿admin explícito?
        if ($this->hasColumnPg('administracion','users','es_administrador')) {
            if ((int)($u->es_administrador ?? 0) === 1) return 1;
        }

        // 1) Intentar en tabla rel_users_rol con columnas variables
        if ($this->hasTablePg('administracion','rel_users_rol')) {

            $roleCol   = $this->firstExistingColumnPg('administracion','rel_users_rol',
                          ['id_roles','id_rol','id_cat_rol','id_cat_roles']);
            $userFkCol = $this->firstExistingColumnPg('administracion','rel_users_rol',
                          ['id_users','id_user','users_id']);
            $statusCol = $this->firstExistingColumnPg('administracion','rel_users_rol',
                          ['estatus','status','activo','is_active']);
            $orderCol  = $this->firstExistingColumnPg('administracion','rel_users_rol',
                          ['id_rel_users_rol','id','id_rel']);

            if ($roleCol && $userFkCol) {
                $q = DB::table('administracion.rel_users_rol')->where($userFkCol, $u->id);
                if ($statusCol) $q->where($statusCol, 1);
                if ($orderCol)  $q->orderBy($orderCol, 'desc');

                $relRole = $q->value($roleCol);
                if (!is_null($relRole)) return (int)$relRole;
            }
        }

        // 2) Respaldo: columna directa en users (si existe)
        $userRoleCol = $this->firstExistingColumnPg('administracion','users',
                        ['id_roles','id_rol','id_cat_rol','id_cat_roles']);
        if ($userRoleCol) {
            $direct = DB::table('administracion.users')->where('id', $u->id)->value($userRoleCol);
            if (!is_null($direct)) return (int)$direct;
        }

        return 0;
    }

    /** Qué rol puede avanzar desde qué etapa */
    private function roleCanAdvanceFrom(int $roleId): ?int
    {
        return match ($roleId) {
            3 => self::STAGE['EMPLEADO'],   // revisor: avanza 2 -> 3
            4 => self::STAGE['REVISOR'],    // supervisor: avanza 3 -> 4
            5 => self::STAGE['SUPERVISOR'], // dges: avanza 4 -> 5
            1 => null,                      // admin: sin restricción
            default => -1,                  // rol no autorizado
        };
    }

    /**
     * POST /follow/status/can-advance
     * Body: { prof_id }
     */
    public function canAdvance(Request $r)
    {
        $profId = (int) $r->input('prof_id');
        if ($profId <= 0) {
            return response()->json(['status'=>false, 'message'=>'El identificador del proceso no es válido']);
        }

        $proceso = DB::table('profesionalizacion.tbl_profesionalizacion as p')
            ->select('p.id_tbl_profesionalizacion','p.id_tbl_empleados','p.id_cat_estatus')
            ->where('p.id_tbl_profesionalizacion', $profId)
            ->first();

        if (!$proceso) {
            return response()->json(['status'=>false, 'message'=>'No encontramos el proceso']);
        }

        // (Opcional) al menos 1 documento
        $totalDocs = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
            ->where('id_tbl_profesionalizacion', $profId)
            ->count();
        if ($totalDocs === 0) {
            return response()->json([
                'status'=>true, 'can_advance'=>false, 'reason'=>'Este proceso no tiene documentos',
            ]);
        }

        // Todos aceptados y con UUID
        $pend = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion as d')
            ->where('d.id_tbl_profesionalizacion', $profId)
            ->where(function($q){
                $q->whereNull('d.uuid')
                  ->orWhere('d.uuid', '=', '')
                  ->orWhereNull('d.id_cat_estatus_documento')
                  ->orWhere('d.id_cat_estatus_documento', '<>', self::DOC['ACEPTADO']);
            })
            ->count();
        if ($pend > 0) {
            return response()->json([
                'status'=>true, 'can_advance'=>false,
                'reason'=>'Faltan documentos aprobados o con archivo válido',
            ]);
        }

        // Última etapa ya alcanzada
        if ((int)$proceso->id_cat_estatus === self::STAGE['DGES']) {
            return response()->json([
                'status'=>true, 'can_advance'=>false, 'reason'=>'El proceso ya está en la última etapa',
            ]);
        }

        // Ventana EMPLEADO -> REVISOR
        if ((int)$proceso->id_cat_estatus === self::STAGE['EMPLEADO']) {
            $userEmp = DB::table('administracion.users')
                ->select('fecha_bloqueo')
                ->where('id_tbl_empleado', $proceso->id_tbl_empleados)
                ->orderBy('id','asc')
                ->first();

            if ($userEmp && $userEmp->fecha_bloqueo) {
                $today = Carbon::today();
                $limit = Carbon::parse($userEmp->fecha_bloqueo);
                if ($today->gt($limit)) {
                    return response()->json([
                        'status'=>true, 'can_advance'=>false,
                        'reason'=>'Se terminó el tiempo para avanzar desde EMPLEADO',
                    ]);
                }
            }
        }

        // Permisos por rol (usando resolveRoleId)
        $roleId  = $this->resolveRoleId();
        $mustFrom = $this->roleCanAdvanceFrom($roleId);
        if ($mustFrom === -1) {
            return response()->json(['status'=>false,'message'=>'Tu usuario no tiene permiso para avanzar el proceso']);
        }
        if ($mustFrom !== null && (int)$proceso->id_cat_estatus !== $mustFrom) {
            return response()->json([
                'status'=>true, 'can_advance'=>false,
                'reason'=>'Tu rol no puede avanzar desde esta etapa',
                'debug' => config('app.debug') ? [
                    'roleId'=>$roleId, 'mustFrom'=>$mustFrom, 'atStage'=>(int)$proceso->id_cat_estatus
                ] : null,
            ]);
        }

        return response()->json(['status'=>true,'can_advance'=>true]);
    }

    /**
     * POST /follow/status/advance
     * Body: { prof_id, observaciones? }
     * Avanza 2->3->4->5 y registra historia de proceso + historia de docs.
     */
    public function advance(Request $r)
    {
        $profId = (int) $r->input('prof_id');
        $obs    = trim((string) $r->input('observaciones',''));
        if ($profId <= 0) {
            return response()->json(['status'=>false, 'message'=>'El identificador del proceso no es válido']);
        }

        // Revalidar
        $can = $this->canAdvance($r);
        $payload = json_decode($can->getContent(), true);
        if (!$payload['status']) return $can;
        if (empty($payload['can_advance'])) return $can;

        DB::beginTransaction();
        try {
            // Lock proceso
            $proceso = DB::table('profesionalizacion.tbl_profesionalizacion')
                ->where('id_tbl_profesionalizacion', $profId)
                ->select('id_tbl_profesionalizacion','id_tbl_empleados','id_cat_estatus')
                ->lockForUpdate()
                ->first();

            if (!$proceso) {
                DB::rollBack();
                return response()->json(['status'=>false,'message'=>'No encontramos el proceso (bloqueo)']);
            }

            $current = (int)$proceso->id_cat_estatus;
            $idx = array_search($current, self::FLOW, true);
            if ($idx === false || $idx === count(self::FLOW)-1) {
                DB::rollBack();
                return response()->json(['status'=>false,'message'=>'No hay una etapa siguiente']);
            }
            $next = self::FLOW[$idx+1];

            // 1) Actualiza etapa del proceso
            $affected = DB::table('profesionalizacion.tbl_profesionalizacion')
                ->where('id_tbl_profesionalizacion', $profId)
                ->update([
                    'id_cat_estatus'           => $next,
                    'observacion'              => $obs !== '' ? $obs : DB::raw('observacion'),
                    'actualizado_en'           => now(),
                    'id_usuario_actualizacion' => Auth::id() ?: 0,
                ]);
            if ($affected === 0) {
                throw new \RuntimeException('No se pudo actualizar el estatus del proceso');
            }

            // 2) Upsert historia del proceso (con id_cat_estatus + usuario por etapa)
            $hist = DB::table('profesionalizacion.ctrl_historia_profesionalizacion')
                ->where('id_tbl_profesionalizacion', $profId)
                ->lockForUpdate()
                ->first();

            $now = now();
            $histPayload = [
                'id_cat_estatus' => $next,
                'actualizado_en' => $now,
            ];

            if ($next === self::STAGE['REVISOR']) {
                $histPayload['fecha_estatus_revisor'] = $now;
                $histPayload['estatus_revisor']       = true;
                if ($this->hasColumnPg('profesionalizacion','ctrl_historia_profesionalizacion','id_usuario_revisor')) {
                    $histPayload['id_usuario_revisor'] = Auth::id() ?: 0;
                }
            } elseif ($next === self::STAGE['SUPERVISOR']) {
                $histPayload['fecha_estatus_supervisor'] = $now;
                $histPayload['estatus_supervisor']       = true;
                if ($this->hasColumnPg('profesionalizacion','ctrl_historia_profesionalizacion','id_usuario_supervisor')) {
                    $histPayload['id_usuario_supervisor'] = Auth::id() ?: 0;
                }
            } elseif ($next === self::STAGE['DGES']) {
                if ($this->hasColumnPg('profesionalizacion','ctrl_historia_profesionalizacion','fecha_estatus_dgces')) {
                    $histPayload['fecha_estatus_dgces'] = $now;
                }
                if ($this->hasColumnPg('profesionalizacion','ctrl_historia_profesionalizacion','estatus_dgces')) {
                    $histPayload['estatus_dgces'] = true;
                }
                if ($this->hasColumnPg('profesionalizacion','ctrl_historia_profesionalizacion','id_usuario_dgces')) {
                    $histPayload['id_usuario_dgces'] = Auth::id() ?: 0;
                }
            }

            if ($hist) {
                DB::table('profesionalizacion.ctrl_historia_profesionalizacion')
                    ->where('id_ctrl_historia_profesionalizacion', $hist->id_ctrl_historia_profesionalizacion)
                    ->update($histPayload);
            } else {
                $histPayload['id_tbl_profesionalizacion'] = $profId;
                $histPayload['creado_en'] = $now;
                $histPayload['id_usuario_creacion'] = Auth::id() ?: 0;
                DB::table('profesionalizacion.ctrl_historia_profesionalizacion')->insert($histPayload);
            }

            // 3) Historia de documentos (append 1 fila por doc del proceso)
            $docs = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
                ->select('id_ctrl_documentos_profesionalizacion','id_cat_estatus_documento')
                ->where('id_tbl_profesionalizacion', $profId)
                ->get();

            foreach ($docs as $d) {
                $isAccepted = ((int)$d->id_cat_estatus_documento === self::DOC['ACEPTADO']);
                DB::table('profesionalizacion.ctrl_historia_documentos')->insert([
                    'id_ctrl_documentos_profesionalizacion' => $d->id_ctrl_documentos_profesionalizacion,
                    'id_cat_estatus_documento'              => $d->id_cat_estatus_documento,
                    'id_usuario_creacion'                   => Auth::id() ?: 0,
                    'id_usuario_actualizacion'              => null,
                    'id_usuario_autorizacion'               => $isAccepted ? (Auth::id() ?: 0) : null,
                    'fecha_autorizacion'                    => $isAccepted ? $now : null,
                    'actualizado_en'                        => $now,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'         => true,
                'new_stage_id'   => $next,
                'new_stage_text' => $this->labelStage($next),
                'message'        => 'El proceso avanzó.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>false,'message'=>'No pudimos avanzar el proceso: '.$e->getMessage()]);
        }
    }

    private function labelStage(int $id): string
    {
        return match ($id) {
            2 => 'COMPLETADO POR EMPLEADO',
            3 => 'COMPLETADO POR REVISOR',
            4 => 'COMPLETADO POR SUPERVISOR',
            5 => 'COMPLETADO POR DGCES',
            6 => 'RECHAZADO',
            default => 'PROCESO',
        };
    }
}