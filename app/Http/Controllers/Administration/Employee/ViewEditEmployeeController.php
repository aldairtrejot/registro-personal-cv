<?php

namespace App\Http\Controllers\Administration\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

class ViewEditEmployeeController extends Controller
{
    /* =========================  UTILIDADES  ========================= */

    private function tableHasColumn(string $schema, string $table, string $column): bool
    {
        static $cache = [];
        $k = "$schema.$table.$column";
        if (array_key_exists($k, $cache)) return $cache[$k];
        $sql = "SELECT 1 FROM information_schema.columns
                WHERE table_schema=? AND table_name=? AND column_name=? LIMIT 1";
        return $cache[$k] = (bool) DB::selectOne($sql, [$schema,$table,$column]);
    }

    /**
     * Rol principal desde la sesión (fallback).
     */
    private function userMainRole(): ?int
    {
        $arr = (array) (session('roles') ?? session('user_roles') ?? []); 
        $roles = collect($arr)->map(fn($v)=>(int)$v)->unique()->values()->all();
        foreach ([1,5,4,3,2] as $r) if (in_array($r,$roles,true)) return $r;
        return null;
    }

    /**
     * ✅ Rol principal desde BD usando Auth::id()
     * Busca en administracion.rel_users_rol con id_users = Auth::id() y lee id_tbl_roles.
     * Si hay varias filas, se toma la más reciente (id_rel_users_rol DESC).
     */
    private function resolveMainRoleFromDb(): ?int
    {
        $uid = Auth::id();
        if (!$uid) return null;

        // Si la tabla no existe, regresamos null (no rompemos nada).
        $exists = DB::selectOne("
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = 'administracion' AND table_name = 'rel_users_rol'
            LIMIT 1
        ");
        if (!$exists) return null;

        // Intento directo con columnas que indicaste
        try {
            $q = DB::table('administracion.rel_users_rol')
                ->where('id_users', $uid);

            // Si existe una columna de estatus/activo, filtramos (opcional, no obligatorio)
            $colStatus = DB::selectOne("
                SELECT 1 FROM information_schema.columns
                WHERE table_schema='administracion' AND table_name='rel_users_rol' AND column_name='estatus' LIMIT 1
            ");
            if ($colStatus) {
                $q->where('estatus', 1);
            }

            $role = $q->orderByDesc('id_rel_users_rol')->value('id_tbl_roles');

            if (is_null($role)) return null;
            $role = (int) $role;
            return $role > 0 ? $role : null;
        } catch (\Throwable $e) {
            Log::warning('[resolveMainRoleFromDb] fallo consulta rel_users_rol', ['ex' => $e->getMessage()]);
            return null;
        }
    }

    private function buildZonaCase(string $field, array $prioridad): string
    {
        $parts = [];
        $i=1;
        foreach ($prioridad as $label) {
            $label = str_replace("'", "''", $label);
            $parts[] = "WHEN '{$label}' THEN {$i}";
            $i++;
        }
        return "CASE {$field} " . implode(' ', $parts) . " ELSE 999 END";
    }

    /**
     * Zona resuelta con la misma lógica del export:
     * - Calcula total de zonas por entidad y si existe HRAES.
     * - Si hay múltiples zonas, HRAES se manda al final.
     * - Se prioriza con tu lista de zonas (config('zonas.prioridad', ...)).
     */
    private function zonaResueltaSubquery(): Builder
    {
        $prioridad = config('zonas.prioridad', [
            'HRAES','CENTRO','SURESTE','SUROESTE','NORESTE','NOROESTE','NO CONCURRENTES'
        ]);

        // Subconsulta base con conteos y flag de HRAES
        $inner = DB::table('catalogo.rel_entidad_zona as rez')
            ->join('catalogo.cat_zona as cz', 'cz.id_cat_zona', '=', 'rez.id_cat_zona')
            ->selectRaw("
                rez.id_cat_entidad,
                rez.id_cat_zona,
                cz.descripcion AS zona,
                COUNT(*) OVER (PARTITION BY rez.id_cat_entidad) AS total_zonas,
                MAX(CASE WHEN UPPER(cz.descripcion) = 'HRAES' THEN 1 ELSE 0 END)
                    OVER (PARTITION BY rez.id_cat_entidad) AS has_hraes_flag
            ")
            ->where('rez.estatus', true);

        $caseBase = $this->buildZonaCase('z.zona', $prioridad);

        // Aplicamos la priorización y democión de HRAES cuando hay múltiples
        return DB::query()->fromSub($inner, 'z')
            ->selectRaw("
                z.id_cat_entidad,
                z.id_cat_zona,
                z.zona,
                CASE WHEN z.has_hraes_flag > 0 THEN true ELSE false END AS has_hraes,
                ROW_NUMBER() OVER (
                    PARTITION BY z.id_cat_entidad
                    ORDER BY
                        CASE
                            WHEN UPPER(z.zona) = 'HRAES' AND z.total_zonas > 1 THEN 999
                            ELSE {$caseBase}
                        END,
                        z.id_cat_zona
                ) AS rn
            ");
    }

    private function resolveStatusId(string $key): ?int
    {
        static $cache = [];
        if (isset($cache[$key])) return $cache[$key];

        $candidatesMap = [
            'empleado'   => ['COMPLETADO POR EMPLEADO','COMPLETADO POR EL EMPLEADO'],
            'revisor'    => ['COMPLETADO POR REVISOR','VALIDADO POR REVISOR'],
            'supervisor' => ['COMPLETADO POR SUPERVISOR','VALIDADO POR SUPERVISOR'],
        ];
        $cands = $candidatesMap[$key] ?? [];
        if (!$cands) return $cache[$key] = null;

        $uppers = array_map(fn($s)=>mb_strtoupper(trim($s),'UTF-8'), $cands);
        $id = DB::table('catalogo.cat_estatus')
            ->whereIn(DB::raw('UPPER(TRIM(descripcion))'), $uppers)
            ->value('id_cat_estatus');

        return $cache[$key] = ($id!==null ? (int) $id : null);
    }

/**
 * Tipos de nómina del usuario.
 *
 * Prioridad:
 * 1) Override desde sesión (middleware: tipos_nomina_permitidos).
 * 2) Campo en administracion.users (id_cat_tipo_nomina / cat_tipo_nomina / tipo_nomina / nomina).
 * 3) Si sigue vacío: inferir desde la ZONA del usuario:
 *      - ZONA = 'HRAES'   => nómina HRAES (1)
 *      - cualquier otra   => nómina AYO / TRANSFERIDOS (2)
 * 4) Fallback final: nómina de su propia plaza (id_tbl_empleado).
 */
private function resolveUserTipoNominaCodes(): array
{
    $user = Auth::user();
    if (!$user) return [];

    /* ========== 1) Override desde sesión (si viene llenado) ========== */
    $fromSession = session('tipos_nomina_permitidos');
    if (is_array($fromSession) && !empty($fromSession)) {
        $codes = [];
        foreach ($fromSession as $v) {
            if ($v === null || $v === '') continue;
            $codes[] = is_numeric($v) ? (int)$v : (string)$v;
        }
        return array_values(array_unique($codes, SORT_REGULAR));
    }

    $codes = [];

    /* ========== 2) Intentar leer desde administracion.users ========== */
    try {
        $tblExists = DB::selectOne("
            SELECT 1 FROM information_schema.tables
            WHERE table_schema='administracion' AND table_name='users' LIMIT 1
        ");

        if ($tblExists) {
            // posibles nombres del campo de nómina en users
            $candidates = ['id_cat_tipo_nomina','cat_tipo_nomina','tipo_nomina','nomina'];
            $colFound = null;

            foreach ($candidates as $col) {
                $colExists = DB::selectOne("
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_schema='administracion'
                      AND table_name='users'
                      AND column_name=?
                    LIMIT 1
                ", [$col]);

                if ($colExists) {
                    $colFound = $col;
                    break;
                }
            }

            if ($colFound) {
                $val = DB::table('administracion.users')
                    ->where('id', $user->id)
                    ->value($colFound);

                if ($val !== null && $val !== '') {
                    // si viene texto, lo mapeamos
                    if (is_string($val)) {
                        $valUpper = mb_strtoupper(trim($val), 'UTF-8');
                        if (in_array($valUpper, ['HRAES','HRV'], true)) {
                            $val = 1; // HRAES
                        } elseif (in_array($valUpper, ['TRANSFERIDOS','AYO'], true)) {
                            $val = 2; // AYO / Transferidos
                        }
                    }
                    $codes[] = is_numeric($val) ? (int)$val : (string)$val;
                }
            }
        }
    } catch (\Throwable $e) {
        // silencioso
    }

    /* ========== 3) NUEVO: inferir desde la ZONA del usuario ========== */
    if (empty($codes) && !empty($user->id_cat_zona)) {
        try {
            $zonaDesc = DB::table('catalogo.cat_zona')
                ->where('id_cat_zona', (int)$user->id_cat_zona)
                ->value('descripcion');

            if ($zonaDesc !== null && $zonaDesc !== '') {
                $z = mb_strtoupper(trim($zonaDesc), 'UTF-8');

                // Ajusta los IDs si en tu catálogo de tipo_nomina son otros
                $ID_NOMINA_HRAES = 1;
                $ID_NOMINA_AYO   = 2;

                if ($z === 'HRAES') {
                    $codes[] = $ID_NOMINA_HRAES;
                } else {
                    // Cualquier otra zona => nómina AYO / TRANSFERIDOS
                    $codes[] = $ID_NOMINA_AYO;
                }
            }
        } catch (\Throwable $e) {
            // también silencioso
        }
    }

    /* ========== 4) Fallback: nómina de su propia plaza (si tiene empleado ligado) ========== */
    if (empty($codes) && !empty($user->id_tbl_empleado)) {
        $row = DB::table('profesionalizacion.tbl_empleados as ue')
            ->join('profesionalizacion.tbl_plazas as upl', 'upl.id_tbl_plazas', '=', 'ue.id_tbl_plazas')
            ->where('ue.id_tbl_empleados', (int)$user->id_tbl_empleado)
            ->select('upl.cat_tipo_nomina')
            ->first();

        if ($row && $row->cat_tipo_nomina !== null && $row->cat_tipo_nomina !== '') {
            $codes[] = is_numeric($row->cat_tipo_nomina)
                ? (int)$row->cat_tipo_nomina
                : (string)$row->cat_tipo_nomina;
        }
    }

    return array_values(array_unique($codes, SORT_REGULAR));
}



/**
 * Verifica si el usuario actual puede editar/ver el registro p.id_tbl_profesionalizacion = $profId.
 * Devuelve el id_tbl_empleados si está permitido; de lo contrario aborta 403/404.
 */
private function assertCanEditByRole(int $profId): int
{
    $user = Auth::user();
    $main = $this->resolveMainRoleFromDb() ?? $this->userMainRole();

    // IDs explícitos de tu catálogo
    $ST_EMPLEADO   = 2; // COMPLETADO POR EMPLEADO
    $ST_REVISOR    = 3; // COMPLETADO POR REVISOR
    $ST_SUPERVISOR = 4; // COMPLETADO POR SUPERVISOR

    $zr = $this->zonaResueltaSubquery();

    $row = DB::table('profesionalizacion.tbl_profesionalizacion as p')
        ->join('profesionalizacion.tbl_empleados as e', 'e.id_tbl_empleados', '=', 'p.id_tbl_empleados')
        ->leftJoin('profesionalizacion.tbl_plazas as pl', 'pl.id_tbl_plazas', '=', 'e.id_tbl_plazas')
        ->leftJoin('profesionalizacion.tbl_clues as cl', 'cl.id_tbl_clues', '=', 'pl.id_tbl_clues')
        ->leftJoinSub($zr, 'zr', fn($j) =>
            $j->on('zr.id_cat_entidad', '=', 'cl.id_cat_entidad')->where('zr.rn', 1)
        )
        ->selectRaw('
            p.id_tbl_empleados as emp_id,
            p.id_cat_estatus   as status_id,
            cl.id_cat_entidad  as ent_id,
            zr.id_cat_zona     as zona_id,
            pl.id_cat_rama     as rama_id,
            pl.cat_tipo_nomina as tipo_nomina,
            cl.id_tbl_clues    as clues_id
        ')
        ->where('p.id_tbl_profesionalizacion', $profId)
        ->first();

    if (!$row) {
        abort(404);
    }

    // ===== Helper rama =====
    $checkRama = function () use ($user, $row) {
        $ramas = [];
        if (!empty($user?->id_cat_rama)) {
            $ramas[] = (int) $user->id_cat_rama;
        }
        if (is_array(session('ramas_permitidas'))) {
            $ramas = array_values(array_unique(array_merge(
                $ramas,
                array_map('intval', session('ramas_permitidas'))
            )));
        }
        if (!empty($ramas)) {
            return in_array((int)($row->rama_id ?? 0), $ramas, true);
        }
        return true; // sin ramas configuradas no restringe
    };

    // ===== Helper nómina =====
    $checkNomina = function () use ($row) {
        $nominas = $this->resolveUserTipoNominaCodes();
        if (!empty($nominas)) {
            $rowNom = (string) $row->tipo_nomina;
            $nomStr = array_map('strval', $nominas);
            return in_array($rowNom, $nomStr, true);
        }
        return true;
    };

    // ===== Helper CLUES =====
    $cluesScope = session('clues_permitidas');
    $cluesScope = is_array($cluesScope)
        ? array_values(array_unique(array_map('intval', $cluesScope)))
        : [];

    $checkClues = function () use ($row, $cluesScope): bool {
        if (empty($cluesScope)) return true; // sin configuración => sin restricción
        return in_array((int)($row->clues_id ?? 0), $cluesScope, true);
    };

    // ====== 1) ADMIN ======
    if ($main === 1) {
        return (int) $row->emp_id;
    }

    // ====== 2) USUARIO ======
    if ($main === 2) {
        if (!empty($user?->id_tbl_empleado) && (int) $user->id_tbl_empleado === (int) $row->emp_id) {
            return (int) $row->emp_id;
        }
        abort(403);
    }

    // ====== 3) REVISOR ======
    // Solo EMPLEADO (2) + entidad/zona + rama + nómina + CLUES
    if ($main === 3) {
        $okSt = ((int) $row->status_id === $ST_EMPLEADO);

        $sameEnt = !empty($user?->id_cat_entidad) && (int) $user->id_cat_entidad === (int) $row->ent_id;
        $zonas   = is_array(session('zonas_permitidas'))
            ? array_values(array_filter(array_map('intval', session('zonas_permitidas'))))
            : [];
        $inZone  = !empty($zonas) && in_array((int)($row->zona_id ?? 0), $zonas, true);

        if (!($sameEnt || $inZone) || !$okSt) {
            abort(403);
        }

        if (!$checkRama())   abort(403);
        if (!$checkNomina()) abort(403);
        if (!$checkClues())  abort(403);

        return (int) $row->emp_id;
    }

    // ====== 4) SUPERVISOR ======
    // Solo puede abrir REVISOR (3), no los ya completados por SUPERVISOR
    // + zona/entidad + rama + nómina + CLUES
    if ($main === 4) {
        $okSt = ((int) $row->status_id === $ST_REVISOR);

        $zonas = [];
        if (!empty($user?->id_cat_zona)) {
            $zonas[] = (int) $user->id_cat_zona;
        }
        if (is_array(session('zonas_permitidas'))) {
            $zonas = array_values(array_unique(array_merge(
                $zonas,
                array_map('intval', session('zonas_permitidas'))
            )));
        }

        $inZone  = !empty($zonas) && in_array((int)($row->zona_id ?? 0), $zonas, true);
        $sameEnt = !empty($user?->id_cat_entidad) && (int) $user->id_cat_entidad === (int) $row->ent_id;

        if (!($inZone || $sameEnt) || !$okSt) {
            abort(403);
        }

        if (!$checkRama())   abort(403);
        if (!$checkNomina()) abort(403);
        if (!$checkClues())  abort(403);

        return (int) $row->emp_id;
    }

    // ====== 5) DGES ======
    // Solo SUPERVISOR (4) + rama + nómina (sin CLUES)
    if ($main === 5) {
        $okSt = ((int) $row->status_id === $ST_SUPERVISOR);
        if (!$okSt) abort(403);

        if (!$checkRama())   abort(403);
        if (!$checkNomina()) abort(403);

        return (int) $row->emp_id;
    }

    // Rol desconocido
    abort(403);
}


    /* =========================  ACCIONES  ========================= */

    public function edit(string $prof)
    {
        try {
            if (!preg_match('/^\d+$/', $prof)) abort(404);

            // validar alcance y obtener el id_tbl_empleados
            $empId = $this->assertCanEditByRole((int)$prof);

            // 🔑 Resolvemos el rol desde BD (fallback a sesión) y lo pasamos a la vista
            $mainRole = $this->resolveMainRoleFromDb() ?? $this->userMainRole() ?? 0;

            // Opcional: lo dejamos también en sesión para quien lo lea desde ahí
            session(['main_role' => $mainRole]);

            // Fallback de vista: primero 'edit', si no existe, 'form'
            $view = null;
            if (view()->exists('administration.employee.edit')) {
                $view = 'administration.employee.edit';
            } elseif (view()->exists('administration.employee.form')) {
                $view = 'administration.employee.form';
            } else {
                throw new \RuntimeException(
                    "No se encontró ninguna vista. Busqué: administration.employee.edit y administration.employee.form"
                );
            }

            return view($view, [
                'prof_id'   => (int) $prof,
                'id'        => (int) $empId,
                'main_role' => (int) $mainRole,   // <<<<<< aquí va para Blade / window.USER_ROLE_MAIN
            ]);
        } catch (\Throwable $e) {
            Log::error('[employee.edit] error', ['msg' => $e->getMessage()]);
            abort(404);
        }
    }

    public function get(Request $request)
    {
        try {
            $profId = (int) $request->input('prof_id', 0);
            $empId  = (int) $request->input('id', 0);

            if ($profId <= 0 && $empId <= 0) {
                return response()->json(['status'=>false,'message'=>'Falta información para identificar el expediente.'], 422);
            }

            if ($profId <= 0) {
                $prof = DB::table('profesionalizacion.tbl_profesionalizacion')
                    ->where('id_tbl_empleados', $empId)
                    ->orderByDesc('id_tbl_profesionalizacion')
                    ->select('id_tbl_profesionalizacion')
                    ->first();
                if (!$prof) return response()->json(['status'=>false,'message'=>'Este empleado aún no tiene un proceso registrado.'], 404);
                $profId = (int) $prof->id_tbl_profesionalizacion;
            }

            $this->assertCanEditByRole($profId);

            // ====== Columnas dinámicas ======
            $cluesExpr = 'cl.clave_clues';
            $hasClaveClues = $this->tableHasColumn('profesionalizacion','tbl_clues','clave_clues');
            $hasClave      = $this->tableHasColumn('profesionalizacion','tbl_clues','clave');
            if (!$hasClaveClues && $hasClave) {
                $cluesExpr = 'cl.clave';
            } elseif (!$hasClaveClues && !$hasClave) {
                $cluesExpr = "COALESCE(cl.clave_clues, cl.clave, '')";
            }

            // === ENTIDAD (UR) con misma regla del Export ===
            // 1) Descripción de entidad
            $entDescExpr = $this->tableHasColumn('catalogo','cat_entidad','descripcion')
                ? "TRIM(COALESCE(ent.descripcion,''))" : "''";

            // 2) Caso por nómina (tmp_base.nomina) con fallback a zonas HRAES
            $hasTbNomina = $this->tableHasColumn('profesionalizacion','tmp_base','nomina');

            $urCase = $hasTbNomina
                ? "
                    CASE
                      WHEN UPPER(COALESCE(tb.nomina,'')) = 'HRAES'        THEN 'HRV'
                      WHEN UPPER(COALESCE(tb.nomina,'')) = 'TRANSFERIDOS' THEN 'AYO'
                      ELSE (CASE WHEN COALESCE(zr.has_hraes,false) THEN 'HRV' ELSE 'AYO' END)
                    END
                  "
                : "(CASE WHEN COALESCE(zr.has_hraes,false) THEN 'HRV' ELSE 'AYO' END)";

            // 3) Etiqueta final exactamente como en export:
            //    <HRV|AYO> || ' - ' || ent.descripcion
            $entidadLabelExpr = "$urCase || ' - ' || COALESCE(ent.descripcion,'')";

            // ====== Puestos (sin cambios) ======
            $puestoDescExpr = "TRIM(COALESCE(cp.descripcion,''))";
            $puestoCodeCols = [];
            foreach (['codigo_puesto','codigo','clave','cod_puesto'] as $c) {
                if ($this->tableHasColumn('catalogo','cat_puesto',$c)) $puestoCodeCols[] = "cp.$c";
            }
            $puestoCodeExpr = $puestoCodeCols
                ? "NULLIF(TRIM(COALESCE(" . implode(', ',$puestoCodeCols) . ",'')),'')"
                : "NULL";
            // 🔧 concatenar código, no repetir descripción
            $puestoLabelExpr = $puestoCodeCols
                ? "{$puestoDescExpr} || CASE WHEN {$puestoCodeExpr} IS NOT NULL THEN ' - ' || {$puestoCodeExpr} ELSE '' END"
                : $puestoDescExpr;

            $sigPuestoDescExpr = "TRIM(COALESCE(cp_next.descripcion,''))";
            $sigPuestoCodeCols = [];
            foreach (['codigo_puesto','codigo','clave','cod_puesto'] as $c) {
                if ($this->tableHasColumn('catalogo','cat_puesto',$c)) $sigPuestoCodeCols[] = "cp_next.$c";
            }
            $sigPuestoCodeExpr = $sigPuestoCodeCols
                ? "NULLIF(TRIM(COALESCE(" . implode(', ',$sigPuestoCodeCols) . ",'')),'')"
                : "NULL";
            // 🔧 concatenar código del siguiente puesto
            $sigPuestoLabelExpr = $sigPuestoCodeCols
                ? "{$sigPuestoDescExpr} || CASE WHEN {$sigPuestoCodeExpr} IS NOT NULL THEN ' - ' || {$sigPuestoCodeExpr} ELSE '' END"
                : $sigPuestoDescExpr;

            $zr = $this->zonaResueltaSubquery();

            $row = DB::table('profesionalizacion.tbl_profesionalizacion AS p')
                ->join('profesionalizacion.tbl_empleados AS e','e.id_tbl_empleados','=','p.id_tbl_empleados')
                ->leftJoin('profesionalizacion.tbl_plazas AS pl','pl.id_tbl_plazas','=','e.id_tbl_plazas')
                ->leftJoin('profesionalizacion.tbl_clues  AS cl','cl.id_tbl_clues','=','pl.id_tbl_clues')
                ->leftJoin('catalogo.cat_estatus AS ce','ce.id_cat_estatus','=','p.id_cat_estatus')
                ->leftJoin('catalogo.cat_entidad AS ent','ent.id_cat_entidad','=','cl.id_cat_entidad')
                ->leftJoin('catalogo.cat_rama    AS cr','cr.id_cat_rama','=','pl.id_cat_rama')
                ->leftJoin('catalogo.cat_puesto  AS cp','cp.id_cat_puesto','=','pl.id_cat_puesto')
                ->leftJoin('catalogo.cat_puesto  AS cp_next','cp_next.id_cat_puesto','=','p.id_cat_sig_puesto')
                ->leftJoin('administracion.users AS u','u.id_tbl_empleado','=','e.id_tbl_empleados')
                // === JOIN tmp_base como en export para leer tb.nomina ===
                ->leftJoin('profesionalizacion.tmp_base as tb', function ($j) {
                    $j->on(DB::raw("TRIM(UPPER(tb.rfc))"),  '=', DB::raw("TRIM(UPPER(e.rfc))"))
                      ->orOn(DB::raw("TRIM(UPPER(tb.curp))"), '=', DB::raw("TRIM(UPPER(e.curp))"));
                })
                ->leftJoinSub($zr,'zr', fn($j)=>$j->on('zr.id_cat_entidad','=','cl.id_cat_entidad')->where('zr.rn',1))
                ->where('p.id_tbl_profesionalizacion', $profId)
                ->selectRaw("
                    e.id_tbl_empleados AS id,
                    p.id_tbl_profesionalizacion AS prof_id,
                    e.nombre,
                    e.primer_apellido,
                    e.segundo_apellido,
                    e.rfc,
                    e.curp,
                    TRIM(
                      COALESCE(e.primer_apellido,'') || ' ' ||
                      COALESCE(e.segundo_apellido,'') || ' ' ||
                      COALESCE(e.nombre,'')
                    ) AS nombre_completo,
                    COALESCE(ce.descripcion, 'SIN ESTATUS') AS estatus,
                    {$entidadLabelExpr} AS entidad_label,
                    COALESCE(u.email, '') AS correo,
                    COALESCE(cr.descripcion, '') AS rama,
                    {$puestoLabelExpr} AS puesto_label,
                    {$sigPuestoLabelExpr} AS siguiente_puesto_label,
                    {$cluesExpr} AS clues,
                    COALESCE(zr.zona, '') AS zona,
                    p.id_cat_estatus,
                    p.observacion AS observacion
                ")
                ->first();

            if (!$row) {
                return response()->json(['status'=>false,'message'=>'No encontramos este expediente.'], 404);
            }

            return response()->json([
                'status' => true,
                'result' => [
                    'id'                   => (int) $row->id,
                    'prof_id'              => (int) $row->prof_id,
                    'nombre'               => $row->nombre,
                    'primer_apellido'      => $row->primer_apellido,
                    'segundo_apellido'     => $row->segundo_apellido,
                    'rfc'                  => $row->rfc,
                    'curp'                 => $row->curp,
                    'nombre_completo'      => $row->nombre_completo,
                    'estatus'              => $row->estatus,
                    'entidad_label'        => $row->entidad_label, // ahora: HRV/AYO + ' - ' + entidad
                    'correo'               => $row->correo,
                    'rama'                 => $row->rama,
                    'puesto_label'         => $row->puesto_label,
                    'siguiente_puesto'     => $row->siguiente_puesto_label,
                    'clues'                => $row->clues,
                    'zona'                 => $row->zona,
                    'id_cat_estatus'       => $row->id_cat_estatus,
                    'observacion'          => $row->observacion ?? '',
                ],
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Employee get error', ['ex' => $th]);
            return response()->json([
                'status'  => false,
                'message' => 'No pudimos cargar el expediente. Intenta de nuevo.',
            ], 500);
        }
    }

    public function activity(Request $request)
    {
        try {
            $profId = (int) $request->input('prof_id', 0);
            if ($profId <= 0) {
                return response()->json(['status'=>false,'message'=>'Falta el identificador del expediente.'], 422);
            }

            $this->assertCanEditByRole($profId);

            $rows = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion as d')
                ->leftJoin('catalogo.cat_tipo_documento as ctd', 'ctd.id_cat_tipo_documento', '=', 'd.id_cat_tipo_documento')
                ->leftJoin('catalogo.cat_estatus_documento as ced', 'ced.id_cat_estatus_documento', '=', 'd.id_cat_estatus_documento')
                ->where('d.id_tbl_profesionalizacion', $profId)
                ->selectRaw("
                    d.id_ctrl_documentos_profesionalizacion   as id,
                    d.uuid,
                    TRIM(COALESCE(d.nombre,''))               as nombre,
                    TRIM(COALESCE(d.observaciones,''))        as observaciones,
                    d.creado_en,
                    d.actualizado_en,
                    TRIM(COALESCE(ctd.descripcion,''))        as tipo_documento,
                    TRIM(COALESCE(ced.descripcion,''))        as estatus_documento
                ")
                ->orderByRaw("COALESCE(d.actualizado_en, d.creado_en) DESC NULLS LAST")
                ->get();

            $mapSeverity = function (?string $desc): string {
                $t = mb_strtoupper(trim((string)$desc), 'UTF-8');
                if ($t === '') return '';
                if (Str::contains($t, ['APROB', 'AUTORIZ'])) return 'green';
                if (Str::contains($t, ['RECHAZ', 'CANCEL'])) return 'red';
                if (Str::contains($t, ['PEND', 'REV', 'EN REV'])) return 'yellow';
                return '';
            };

            $makeViewUrl = fn($uuid, $id) => url("/cloud/view/{$uuid}");
            $makeEditUrl = fn($id)        => url("/documents/edit/{$id}");

            $items = $rows->map(function ($r) use ($mapSeverity, $makeViewUrl, $makeEditUrl) {
                $title = $r->tipo_documento !== '' ? $r->tipo_documento : ($r->nombre ?: 'Documento');
                $fecha = $r->actualizado_en ?: $r->creado_en;
                $subtitleParts = [];
                if ($r->estatus_documento !== '') $subtitleParts[] = "Estatus: {$r->estatus_documento}";
                if ($r->nombre !== '' && $r->tipo_documento !== $r->nombre) $subtitleParts[] = $r->nombre;
                if ($fecha) $subtitleParts[] = date('Y-m-d H:i', strtotime($fecha));
                $subtitle = implode(' • ', $subtitleParts);

                return [
                    'id'        => (int)$r->id,
                    'title'     => $title,
                    'subtitle'  => $subtitle,
                    'severity'  => $mapSeverity($r->estatus_documento),
                    'avatar_url'=> null,
                    'view_url'  => $makeViewUrl($r->uuid, $r->id),
                    'edit_url'  => $makeEditUrl($r->id),
                ];
            })->values();

            return response()->json([
                'status' => true,
                'items'  => $items,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('[employee.activity] error', ['ex' => $e]);
            return response()->json([
                'status' => false,
                'message' => 'No pudimos cargar la actividad de documentos. Intenta de nuevo.',
            ], 500);
        }
    }

    public function documents(Request $request)
    {
        try {
            $profId = (int) $request->input('prof_id', 0);
            if ($profId <= 0) {
                return response()->json(['status'=>false,'message'=>'El identificador del expediente no es válido.'], 422);
            }

            // Validación de alcance
            $this->assertCanEditByRole($profId);

            // Subconsulta: última actividad y “versión” (conteo) del historial
            $histAgg = DB::table('profesionalizacion.ctrl_historia_documentos')
                ->selectRaw('id_ctrl_documentos_profesionalizacion, MAX(actualizado_en) AS last_hist, COUNT(*) AS hist_count')
                ->groupBy('id_ctrl_documentos_profesionalizacion');

            $rows = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion AS d')
                ->leftJoin('catalogo.cat_tipo_documento AS td', 'td.id_cat_tipo_documento', '=', 'd.id_cat_tipo_documento')
                ->leftJoin('catalogo.cat_estatus_documento AS ed', 'ed.id_cat_estatus_documento', '=', 'd.id_cat_estatus_documento')
                ->leftJoinSub($histAgg, 'h', fn($j)=>$j->on('h.id_ctrl_documentos_profesionalizacion','=','d.id_ctrl_documentos_profesionalizacion'))
                ->where('d.id_tbl_profesionalizacion', $profId)
                ->orderByDesc('d.creado_en')
                ->selectRaw("
                    d.id_ctrl_documentos_profesionalizacion AS id,
                    d.uuid,
                    d.nombre,
                    d.observaciones,
                    d.id_cat_estatus_documento,
                    d.id_cat_tipo_documento,
                    d.creado_en,
                    d.actualizado_en,
                    COALESCE(td.descripcion,'') AS tipo_documento,
                    COALESCE(td.clave,'') AS tipo_clave,
                    COALESCE(ed.descripcion,'') AS estatus_documento,
                    d.uuid_verificacion_cedula,
                    COALESCE(h.last_hist, d.creado_en) AS last_hist,
                    COALESCE(h.hist_count, 0) AS hist_count
                ")
                ->get();

            $makeViewUrl = fn($uuid) => url("/cloud/view/{$uuid}");

            $docs = $rows->map(function($r) use ($makeViewUrl) {
                $clave = strtoupper((string)($r->tipo_clave ?? ''));
                $texto = trim(($r->tipo_documento ?? '') . ' ' . ($r->nombre ?? ''));

                // ¿Es cédula?
                $isCed = false;
                if ($clave !== '' && \Illuminate\Support\Str::contains($clave, ['CED','CEDULA'])) $isCed = true;
                elseif (preg_match('/c[eé]dula/i', $texto) === 1) $isCed = true;

                $uuidVer = $r->uuid_verificacion_cedula ?: null;
                $urlVer  = $uuidVer ? $makeViewUrl($uuidVer) : null;

                // 🔒 Marca de concurrencia (ETag simple basado en timestamp)
                $etagTs = $r->actualizado_en ?: $r->creado_en; // lo que el usuario vio
                $etag   = $etagTs ? (new \Carbon\Carbon($etagTs))->toIso8601String() : null;

                return [
                    'id'                         => (int)$r->id,
                    'uuid'                       => $r->uuid,
                    'nombre'                     => $r->nombre,
                    'observaciones'              => $r->observaciones,
                    'id_cat_estatus_documento'   => $r->id_cat_estatus_documento,
                    'id_cat_tipo_documento'      => $r->id_cat_tipo_documento,
                    'creado_en'                  => $r->creado_en,
                    'actualizado_en'             => $r->actualizado_en,
                    'tipo_documento'             => $r->tipo_documento,
                    'estatus_documento'          => $r->estatus_documento,
                    'url_view'                   => $r->uuid ? $makeViewUrl($r->uuid) : null,
                    'url_preview'                => null,
                    'url_download'               => null,
                    'url_edit'                   => null,
                    'can_edit'                   => true,
                    'uuid_verificacion_cedula'   => $uuidVer,
                    'url_verif_view'             => $urlVer,
                    'is_cedula'                  => $isCed,

                    // 🔒 Concurrencia
                    'etag'                       => $etag,                    // “lo que vi”
                    'hist_version'               => (int)$r->hist_count,      // versión que vi
                ];
            });

            return response()->json([
                'status' => true,
                'result' => $docs,
                'server_now' => now()->toIso8601String(),
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Employee documents error', ['ex' => $th]);
            return response()->json([
                'status' => false,
                'message' => 'No pudimos cargar los documentos. Intenta de nuevo.',
            ], 500);
        }
    }
/** Busca IDs por texto flexible (ILIKE) para cubrir variaciones de catálogo */
private function findStatusIdsLike(array $needles): array
{
    $needles = array_values(array_filter(array_map(
        fn($s) => trim($s ?? ''), $needles
    )));
    if (!$needles) return [];

    $ids = DB::table('catalogo.cat_estatus')
        ->where(function($q) use ($needles) {
            foreach ($needles as $s) {
                $q->orWhere(DB::raw('UPPER(TRIM(descripcion))'), 'LIKE', mb_strtoupper("%{$s}%", 'UTF-8'));
            }
        })
        ->pluck('id_cat_estatus')
        ->map(fn($v) => (int)$v)
        ->unique()
        ->values()
        ->all();

    return $ids;
}
/**
 * Aplica el filtro de CLUES si la sesión trae 'clues_permitidas'.
 * Si está vacío, no hace nada.
 */
private function applyCluesScope(Builder $query): void
{
    $clues = session('clues_permitidas');
    if (!is_array($clues) || empty($clues)) {
        return;
    }

    $ids = array_values(array_unique(array_map('intval', $clues)));
    if (!empty($ids)) {
        $query->whereIn('cl.id_tbl_clues', $ids);
    }
}


}
