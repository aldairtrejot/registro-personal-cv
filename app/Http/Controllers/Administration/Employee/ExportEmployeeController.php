<?php
// app/Http/Controllers/Administration/Employee/ExportEmployeeController.php

namespace App\Http\Controllers\Administration\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;

// Excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

use Carbon\Carbon;

class ExportEmployeeController extends Controller
{
    /* =================== Helpers =================== */

    private function tableHasColumn(string $schema, string $table, string $column): bool
    {
        static $cache = [];
        $key = "$schema.$table.$column";
        if (array_key_exists($key, $cache)) return $cache[$key];

        $sql = "SELECT 1
                  FROM information_schema.columns
                 WHERE table_schema = ? AND table_name = ? AND column_name = ?
                 LIMIT 1";
        $cache[$key] = (bool) DB::selectOne($sql, [$schema, $table, $column]);
        return $cache[$key];
    }

    private function tableExists(string $schema, string $table): bool
    {
        static $cache = [];
        $key = "$schema.$table";
        if (array_key_exists($key, $cache)) return $cache[$key];

        $sql = "SELECT 1
                  FROM information_schema.tables
                 WHERE table_schema = ? AND table_name = ?
                 LIMIT 1";
        $cache[$key] = (bool) DB::selectOne($sql, [$schema, $table]);
        return $cache[$key];
    }

    private function pickRole(array $roles): ?int
    {
        foreach ([1, 5, 4, 3, 2] as $r) {
            if (in_array($r, $roles, true)) return $r;
        }
        return null;
    }

    private function buildZonaCase(string $field, array $prioridad): string
    {
        $parts = [];
        $i = 1;
        foreach ($prioridad as $label) {
            $label = str_replace("'", "''", $label);
            $parts[] = "WHEN '{$label}' THEN {$i}";
            $i++;
        }
        return "CASE {$field} " . implode(' ', $parts) . " ELSE 999 END";
    }

    private function zonaResueltaSubquery(): Builder
    {
        $prioridad = config('zonas.prioridad', [
            'HRAES','CENTRO','SURESTE','SUROESTE','NORESTE','NOROESTE','NO CONCURRENTES'
        ]);

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
            'empleado'   => ['COMPLETADO POR EMPLEADO', 'COMPLETADO POR EL EMPLEADO'],
            'revisor'    => ['COMPLETADO POR REVISOR', 'VALIDADO POR REVISOR'],
            'supervisor' => ['COMPLETADO POR SUPERVISOR', 'VALIDADO POR SUPERVISOR'],
        ];
        $cands = $candidatesMap[$key] ?? [];
        if (!$cands) return $cache[$key] = null;

        $upperCands = array_map(fn($s) => mb_strtoupper(trim($s), 'UTF-8'), $cands);
        $placeholders = implode(',', array_fill(0, count($upperCands), '?'));

        $id = DB::table('catalogo.cat_estatus')
            ->whereRaw("UPPER(TRIM(descripcion)) IN ({$placeholders})", $upperCands)
            ->value('id_cat_estatus');

        return $cache[$key] = ($id !== null ? (int) $id : null);
    }

    private function applyRoleScope(\Illuminate\Database\Query\Builder $q, ?int $adminStatusId): array
    {
        $roles = (array) (session('roles') ?? session('user_roles') ?? []);
        $roles = array_values(array_filter(array_map(
            fn($v) => is_numeric($v) ? (int) trim((string) $v) : null,
            $roles
        )));

            $clues = session('clues_permitidas');
    $clues = is_array($clues)
        ? array_values(array_unique(array_map('intval', $clues)))
        : [];

    $applyClues = function () use ($q, $clues) {
        if (!empty($clues)) {
            $q->whereIn('cl.id_tbl_clues', $clues);
        }
    };
        $mainRole = $this->pickRole($roles) ?? 2;
        $user     = Auth::user();
        $isAdmin  = ($mainRole === 1);

        $statusFilter = null;
        if ($isAdmin) {
            if ($adminStatusId !== null && $adminStatusId !== 9999) {
                $statusFilter = [(int) $adminStatusId];
            }
        } else {
            if ($mainRole === 3) {
                $id = $this->resolveStatusId('empleado');
                $statusFilter = [$id ?? 2];
            } elseif ($mainRole === 4) {
                $id = $this->resolveStatusId('revisor');
                $statusFilter = [$id ?? 3];
            } elseif ($mainRole === 5) {
                $id = $this->resolveStatusId('supervisor');
                $statusFilter = [$id ?? 4];
            }
        }
        if (is_array($statusFilter)) {
            $q->whereIn('u_ult.id_cat_estatus', $statusFilter);
        }

        $entityColExists = $this->tableHasColumn('profesionalizacion', 'tbl_clues', 'id_cat_entidad');

        if ($isAdmin) return [$statusFilter, $mainRole];

        if ($mainRole === 2) {
            if (!empty($user?->id_tbl_empleado)) {
                $q->where('e.id_tbl_empleados', (int) $user->id_tbl_empleado);
                return [$statusFilter, $mainRole];
            }
            if ($entityColExists && !empty($user?->id_cat_entidad)) {
                $q->where('cl.id_cat_entidad', (int) $user->id_cat_entidad);
            }
            return [$statusFilter, $mainRole];
        }

        if ($mainRole === 3) {
            if ($entityColExists && !empty($user?->id_cat_entidad)) {
                $q->where('cl.id_cat_entidad', (int) $user->id_cat_entidad);
            }
                 $applyClues();
            return [$statusFilter, $mainRole];
        }

        if ($mainRole === 4) {
            $zonas = [];
            if (!empty($user?->id_cat_zona)) {
                $zonas = [(int) $user->id_cat_zona];
            } elseif (is_array(session('zonas_permitidas'))) {
                $zonas = array_values(array_filter(array_map('intval', session('zonas_permitidas'))));
            }

            if (!empty($zonas)) {
                $q->whereIn('zr.id_cat_zona', $zonas);
            } elseif (!empty($user?->id_cat_entidad) && $entityColExists) {
                $q->where('cl.id_cat_entidad', (int) $user->id_cat_entidad);
            }
                 $applyClues();
            return [$statusFilter, $mainRole];
        }

        if ($mainRole === 5) {
            $ramas = [];
            if (!empty($user?->id_cat_rama)) {
                $ramas = [(int) $user->id_cat_rama];
            } elseif (is_array(session('ramas_permitidas'))) {
                $ramas = array_values(array_filter(array_map('intval', session('ramas_permitidas'))));
            }
            if (!empty($ramas)) {
                $q->whereIn('pl.id_cat_rama', $ramas);
            }
            return [$statusFilter, $mainRole];
        }

        if (!empty($user?->id_cat_entidad) && $entityColExists) {
            $q->where('cl.id_cat_entidad', (int) $user->id_cat_entidad);
        }
        return [$statusFilter, $mainRole];
    }

    private function hasAnyWithStatus(Builder $q): bool
    {
        $qq = clone $q;
        return $qq->exists();
    }

    /* =================== Export =================== */

    public function export(Request $request)
    {
        $format   = strtolower($request->get('format', 'xlsx')); // xlsx | csv
        $statusId = $request->has('id_cat_estatus')
            ? (($request->id_cat_estatus === '' || $request->id_cat_estatus === '9999') ? null : (int) $request->id_cat_estatus)
            : null;
        $search   = trim((string) $request->get('search', ''));

        // Fecha dinámica del ESTATUS (tbl_profesionalizacion)
        $fechaColSql = 'NULL::timestamp AS fecha_estatus,';
        if ($this->tableHasColumn('profesionalizacion', 'tbl_profesionalizacion', 'actualizado_en')) {
            $fechaColSql = 'p.actualizado_en AS fecha_estatus,';
        } elseif ($this->tableHasColumn('profesionalizacion', 'tbl_profesionalizacion', 'updated_at')) {
            $fechaColSql = 'p.updated_at AS fecha_estatus,';
        }

        // === ID del siguiente puesto: p.id_cat_sig_puesto ===
        $puestoProfCol = 'p.id_cat_sig_puesto AS id_cat_puesto_prof,';

        // === Campo fijo: MOTIVO DEL RECHAZO desde p.observacion ===
        $motivoRechazoCol = "p.observacion AS motivo_rechazo,";

        // === Campo fijo: FECHA CODIGO desde p.fecha_inicio ===
        $fechaCodigoCol = "p.fecha_inicio::timestamp AS fecha_codigo,";

        // CLUES dinámico
        $cluesExpr = 'cl.clave_clues';
        $hasClaveClues = $this->tableHasColumn('profesionalizacion', 'tbl_clues', 'clave_clues');
        $hasClave      = $this->tableHasColumn('profesionalizacion', 'tbl_clues', 'clave');
        if (!$hasClaveClues && $hasClave)       $cluesExpr = 'cl.clave';
        elseif (!$hasClaveClues && !$hasClave)  $cluesExpr = "COALESCE(cl.clave_clues, cl.clave, '')";

        // ========= Subconsulta: último registro por empleado (u_ult) =========
        $uUltSql = "
            (
              SELECT
                p.id_tbl_profesionalizacion         AS prof_id,
                (p.id_tbl_empleados)::int           AS emp_id,
                (p.id_cat_estatus)::int             AS id_cat_estatus,
                {$fechaColSql}
                {$puestoProfCol}
                {$motivoRechazoCol}
                {$fechaCodigoCol}
                ROW_NUMBER() OVER (
                  PARTITION BY p.id_tbl_empleados
                  ORDER BY p.id_tbl_profesionalizacion DESC
                ) AS rn
              FROM profesionalizacion.tbl_profesionalizacion p
              WHERE p.id_cat_estatus IS NOT NULL
            ) AS u_ult
        ";

        $q = DB::table('profesionalizacion.tbl_empleados AS e')
            ->join('profesionalizacion.tbl_plazas AS pl', 'pl.id_tbl_plazas', '=', 'e.id_tbl_plazas')
            ->join('profesionalizacion.tbl_clues  AS cl', 'cl.id_tbl_clues',  '=', 'pl.id_tbl_clues')
            ->join(DB::raw($uUltSql), function ($j) {
                $j->on('u_ult.emp_id', '=', 'e.id_tbl_empleados')
                  ->where('u_ult.rn', '=', 1);
            })
            ->leftJoin('catalogo.cat_estatus AS ces', 'ces.id_cat_estatus', '=', 'u_ult.id_cat_estatus')
            ->leftJoin('catalogo.cat_entidad AS ent', 'ent.id_cat_entidad', '=', 'cl.id_cat_entidad')
            ->leftJoin('catalogo.cat_rama    AS cr',  'cr.id_cat_rama',      '=', 'pl.id_cat_rama')
            ->leftJoin('catalogo.cat_puesto  AS cp',  'cp.id_cat_puesto',    '=', 'pl.id_cat_puesto')
            ->leftJoin('catalogo.cat_puesto  AS cpp', 'cpp.id_cat_puesto',   '=', 'u_ult.id_cat_puesto_prof');

        // === Historial para comentario UR/DGRH (sin cambios de lógica)
        $hasHist = $this->tableExists('profesionalizacion','ctrl_historia_profesionalizacion');
        if ($hasHist) {
            $histInner = DB::table('profesionalizacion.ctrl_historia_profesionalizacion as h')
                ->selectRaw("
                    h.id_tbl_profesionalizacion  AS prof_id,
                    TRIM(COALESCE(h.observaciones,'')) AS observaciones,
                    COALESCE(h.actualizado_en, h.creado_en) AS fref,
                    ROW_NUMBER() OVER (
                        PARTITION BY h.id_tbl_profesionalizacion
                        ORDER BY COALESCE(h.actualizado_en, h.creado_en) DESC NULLS LAST,
                                 h.id_ctrl_historia_profesionalizacion DESC
                    ) AS rn
                ")
                ->whereRaw("TRIM(COALESCE(h.observaciones,'')) <> ''");

            $q->leftJoinSub($histInner, 'h_obs', function($j) {
                $j->on('h_obs.prof_id','=','u_ult.prof_id')
                  ->where('h_obs.rn','=',1);
            });
        }

        $q->leftJoin('administracion.users AS u', 'u.id_tbl_empleado', '=', 'e.id_tbl_empleados')
          ->leftJoin('profesionalizacion.tmp_base as tb', function ($j) {
              $j->on(DB::raw("TRIM(UPPER(tb.rfc))"),  '=', DB::raw("TRIM(UPPER(e.rfc))"))
                ->orOn(DB::raw("TRIM(UPPER(tb.curp))"), '=', DB::raw("TRIM(UPPER(e.curp))"));
          })
          ->leftJoinSub($this->zonaResueltaSubquery(), 'zr', function ($j) {
              $j->on('zr.id_cat_entidad', '=', 'cl.id_cat_entidad')
                ->where('zr.rn', '=', 1);
          });

        /* ====== UR: Determinar HRV/AYO por empleado desde tmp_base.nomina ====== */
        $hasTbNomina = $this->tableHasColumn('profesionalizacion','tmp_base','nomina');

        // Regla base por nomina (con fallback a zonas si no hay dato/columna)
        $urCase = $hasTbNomina
            ? "
                CASE
                  WHEN UPPER(COALESCE(tb.nomina,'')) = 'HRAES'        THEN 'HRV'
                  WHEN UPPER(COALESCE(tb.nomina,'')) = 'TRANSFERIDOS' THEN 'AYO'
                  ELSE (CASE WHEN COALESCE(zr.has_hraes,false) THEN 'HRV' ELSE 'AYO' END)
                END
              "
            : "(CASE WHEN COALESCE(zr.has_hraes,false) THEN 'HRV' ELSE 'AYO' END)";

        // Usaremos base (sin alias) para reutilizar en búsqueda
        $urExprBase = "$urCase || ' - ' || COALESCE(ent.descripcion,'')";
        $urExpr = "$urExprBase AS ur";

        // PUESTO ACTUAL (dinámico para el código)
        $puestoCodeCol = "NULL";
        foreach (['codigo','codigo_puesto','clave','clave_puesto','cve_puesto'] as $cand) {
            if ($this->tableHasColumn('catalogo', 'cat_puesto', $cand)) { $puestoCodeCol = "cp.$cand"; break; }
        }
        $puestoActualExpr = "
            CASE
              WHEN COALESCE($puestoCodeCol,'') <> ''
                   THEN COALESCE(cp.descripcion,'') || ' - ' || COALESCE($puestoCodeCol,'')
              ELSE COALESCE(cp.descripcion,'')
            END AS puesto_actual
        ";

        // PUESTO A PROFESIONALIZAR (sobre cpp.*)
        $puestoProfCodeCol = "NULL";
        foreach (['codigo','codigo_puesto','clave','clave_puesto','cve_puesto'] as $cand) {
            if ($this->tableHasColumn('catalogo', 'cat_puesto', $cand)) { $puestoProfCodeCol = "cpp.$cand"; break; }
        }
        $puestoProfesExpr = "
            CASE
              WHEN COALESCE($puestoProfCodeCol,'') <> ''
                   THEN COALESCE(cpp.descripcion,'') || ' - ' || COALESCE($puestoProfCodeCol,'')
              ELSE COALESCE(cpp.descripcion,'')
            END AS puesto_a_profesionalizar
        ";

        // ---------- TIPO TRABAJADOR / FIGF / FISSA / ZONA ECONÓMICA (igual) ----------
        $tipoTrabCol = "NULL";
        foreach ([['profesionalizacion','tbl_empleados','tipo_trabajador'],
                  ['profesionalizacion','tbl_empleados','tipo_nomina'],
                  ['profesionalizacion','tbl_plazas','tipo_trabajador'],
                  ['profesionalizacion','tbl_plazas','tipo_nomina']] as $spec) {
            if ($this->tableHasColumn($spec[0], $spec[1], $spec[2])) {
                $alias = $spec[1] === 'tbl_empleados' ? 'e' : 'pl';
                $tipoTrabCol = "{$alias}.{$spec[2]}";
                break;
            }
        }
        $tipoTrabBase = ($tipoTrabCol === "NULL") ? "NULL" : "({$tipoTrabCol})::text";
        $tbTipoCols = [];
        if ($this->tableHasColumn('profesionalizacion', 'tmp_base', 'tipo_contratacion')) {
            $tbTipoCols[] = "NULLIF(tb.tipo_contratacion,'')";
        }
        if ($this->tableHasColumn('profesionalizacion', 'tmp_base', 'tipo_contratacic')) {
            $tbTipoCols[] = "NULLIF(tb.tipo_contratacic,'')";
        }
        $tbTipoSql = implode(",\n                ", $tbTipoCols);
        $tipoTrabExpr = "
            COALESCE(
                " . ($tbTipoSql !== '' ? $tbTipoSql . ',' : '') . "
                COALESCE({$tipoTrabBase}, '')
            ) AS tipo_trabajador
        ";

        $figfCol = "NULL::timestamp";
        foreach ([['profesionalizacion','tbl_empleados','figf'],
                  ['profesionalizacion','tbl_empleados','fecha_ingreso'],
                  ['profesionalizacion','tbl_plazas','figf']] as $spec) {
            if ($this->tableHasColumn($spec[0], $spec[1], $spec[2])) {
                $alias = $spec[1] === 'tbl_empleados' ? 'e' : 'pl';
                $figfCol = "{$alias}.{$spec[2]}";
                break;
            }
        }
        $prefTbFigf = $this->tableHasColumn('profesionalizacion','tmp_base','fecha_ingreso')
            ? "NULLIF(tb.fecha_ingreso,'')::timestamp,"
            : "";
        $figfExpr = "
            COALESCE(
                {$prefTbFigf}
                {$figfCol}
            ) AS figf
        ";

        $fissaCol = "NULL::timestamp";
        foreach ([['profesionalizacion','tbl_empleados','fissa'],
                  ['profesionalizacion','tbl_empleados','fecha_inicio'],
                  ['profesionalizacion','tbl_plazas','fissa']] as $spec) {
            if ($this->tableHasColumn($spec[0], $spec[1], $spec[2])) {
                $alias = $spec[1] === 'tbl_empleados' ? 'e' : 'pl';
                $fissaCol = "{$alias}.{$spec[2]}";
                break;
            }
        }
        $fissaExpr = "
            COALESCE(
                NULLIF(tb.fecha_ingreso_ssa,'')::timestamp,
                {$fissaCol}
            ) AS fissa
        ";

        $tbZonaParts = [];
        if ($this->tableHasColumn('profesionalizacion', 'tmp_base', 'zona_economica')) {
            $tbZonaParts[] = "NULLIF(tb.zona_economica,'')";
        }
        if ($this->tableHasColumn('profesionalizacion', 'tmp_base', 'zona_economic')) {
            $tbZonaParts[] = "NULLIF(tb.zona_economic,'')";
        }
        $tbZonaSql = implode(",\n                ", $tbZonaParts);

        $zonaEconCol = "NULL";
        foreach ([['profesionalizacion','tbl_plazas','zona_economica'],
                  ['profesionalizacion','tbl_empleados','zona_economica']] as $spec) {
            if ($this->tableHasColumn($spec[0], $spec[1], $spec[2])) {
                $alias = $spec[1] === 'tbl_empleados' ? 'e' : 'pl';
                $zonaEconCol = "{$alias}.{$spec[2]}";
                break;
            }
        }
        $zonaEconBase = ($zonaEconCol === "NULL") ? "NULL" : "({$zonaEconCol})::text";
        $zonaEconExpr = "
            COALESCE(
                " . ($tbZonaSql !== '' ? $tbZonaSql . ',' : '') . "
                COALESCE({$zonaEconBase}, ''),
                ''
            ) AS zona_economica
        ";

        // ======== SELECT final ========
        $selectComentarios = $hasHist
            ? "COALESCE(h_obs.observaciones, 'sin observación')"
            : "'sin observación'";

        $q->selectRaw("
            TRIM(
              COALESCE(e.primer_apellido,'') || ' ' ||
              COALESCE(e.segundo_apellido,'') || ' ' ||
              COALESCE(e.nombre,'')
            )                                   AS nombre_completo,
            e.rfc,
            e.curp,
            COALESCE(ces.descripcion,'SIN ESTATUS') AS estatus,

            'Estado'                            AS indicador,
            ent.id_cat_entidad                  AS estado,
            $urExpr,
            $tipoTrabExpr,
            $figfExpr,
            $fissaExpr,
            $puestoActualExpr,
            $puestoProfesExpr,
            $zonaEconExpr,
            COALESCE(u.email, '')               AS correo,

            COALESCE(u_ult.motivo_rechazo, 'sin observación')   AS motivo_rechazo,
            {$selectComentarios}                                 AS comentario_ur,
            {$selectComentarios}                                 AS comentario_dgrh,
            COALESCE(u_ult.fecha_codigo, NULL)                   AS fecha_codigo,

            {$cluesExpr}                        AS clues,
            COALESCE(zr.zona, '')               AS zona,
            u_ult.fecha_estatus                 AS fecha_actualizacion
        ")
        ->orderByRaw("
          UPPER(TRIM(
            COALESCE(e.primer_apellido,'') || ' ' ||
            COALESCE(e.segundo_apellido,'') || ' ' ||
            COALESCE(e.nombre,'')
          )) ASC
        ");

        // Ámbito + estatus
        $this->applyRoleScope($q, $statusId);

        // Búsqueda (usar misma regla de UR)
        if ($search !== '') {
            $needle = '%'.str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $search).'%';
            $hasClaveClues = $this->tableHasColumn('profesionalizacion', 'tbl_clues', 'clave_clues');
            $hasClave      = $this->tableHasColumn('profesionalizacion', 'tbl_clues', 'clave');

            $q->where(function ($qq) use ($needle, $hasClaveClues, $hasClave, $hasHist, $urExprBase) {
                $qq->where('e.primer_apellido', 'ILIKE', $needle)
                   ->orWhere('e.segundo_apellido', 'ILIKE', $needle)
                   ->orWhere('e.nombre', 'ILIKE', $needle)
                   ->orWhere('e.rfc', 'ILIKE', $needle)
                   ->orWhere('e.curp', 'ILIKE', $needle)
                   ->orWhere('ces.descripcion', 'ILIKE', $needle)
                   ->orWhere('ent.descripcion', 'ILIKE', $needle)
                   ->orWhere('cr.descripcion', 'ILIKE', $needle)
                   ->orWhere('cp.descripcion', 'ILIKE', $needle)
                   ->orWhere('zr.zona', 'ILIKE', $needle)
                   ->orWhereRaw("$urExprBase ILIKE ?", [$needle])
                   ->orWhereRaw("COALESCE(u_ult.motivo_rechazo,'') ILIKE ?", [$needle]);

                if ($hasHist) {
                    $qq->orWhereRaw("COALESCE(h_obs.observaciones,'') ILIKE ?", [$needle]);
                }

                if ($hasClaveClues) { $qq->orWhere('cl.clave_clues', 'ILIKE', $needle); }
                if ($hasClave)      { $qq->orWhere('cl.clave',       'ILIKE', $needle); }
            });
        }

        // Vacío
        if (!$this->hasAnyWithStatus($q)) {
            return response('', 204)->header('X-Empty-Export', '1');
        }

        // Columnas de export
        $columns = [
            'indicador','estado','ur','rfc','curp','nombre_completo',
            'tipo_trabajador','figf','fissa',
            'puesto_actual','puesto_a_profesionalizar',
            'zona_economica','correo','estatus',
            'motivo_rechazo','comentario_ur','comentario_dgrh','fecha_codigo'
        ];

        $headersHuman = [
            'No.',
            'INDICADOR','ESTADO','UR','RFC','CURP','NOMBRE',
            'TIPO DE TRABAJADOR','FIGF','FISSA',
            'PUESTO ACTUAL','PUESTO A PROFESIONALIZAR',
            'ZONA ECONOMICA','CORREO','ESTATUS',
            'MOTIVO DEL RECHAZO','COMENTARIO UR','COMENTARIO DGRH - IMSSBIENESTAR',
            'FECHA CODIGO'
        ];

        /* --- CSV --- */
        if ($format === 'csv') {
            $fname = 'empleados_' . now('America/Mexico_City')->format('Ymd_His') . '.csv';

            return response()->streamDownload(function () use ($q, $columns, $headersHuman) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $headersHuman);

                $n = 1;
                foreach ($q->cursor() as $r) {
                    $row = [$n++];
                    foreach ($columns as $c) {
                        $val = $r->$c ?? null;
                        if (in_array($c, ['figf','fissa','fecha_codigo','fecha_actualizacion'], true) && $val) {
                            try {
                                $val = Carbon::parse($val)->timezone('America/Mexico_City')->format('d/m/Y');
                            } catch (\Throwable $e) {}
                        }
                        $row[] = $val;
                    }
                    fputcsv($out, $row);
                }
                fclose($out);
            }, $fname, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        /* --- XLSX --- */
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Empleados');

        $userName = Auth::user()?->name ?? 'SIPROIB';
        $spreadsheet->getProperties()
            ->setCreator($userName)
            ->setLastModifiedBy($userName)
            ->setTitle('Empleados - SIPROIB')
            ->setSubject('Exportación con columnas personalizadas en orden específico.')
            ->setDescription('Exportación con columnas personalizadas en orden específico.')
            ->setCategory('Reportes');

        $col = 1;
        foreach ($headersHuman as $h) {
            $sheet->setCellValueByColumnAndRow($col++, 1, $h);
        }

        $lastColIndex  = count($headersHuman);
        $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);
        $headerRange   = "A1:{$lastColLetter}1";

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7A1B32']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '7A1B32']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');

        $setup = $sheet->getPageSetup();
        $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $setup->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.4)->setRight(0.25)->setLeft(0.25)->setBottom(0.4);

        $rIdx = 2;
        $n    = 1;

        $textCols = ['rfc','curp','ur','nombre_completo','tipo_trabajador',
                     'puesto_actual','puesto_a_profesionalizar','correo','estatus',
                     'motivo_rechazo','comentario_ur','comentario_dgrh'];

        $dateCols = ['figf','fissa','fecha_codigo','fecha_actualizacion'];

        foreach ($q->cursor() as $r) {
            $cIdx = 1;
            $sheet->setCellValueByColumnAndRow($cIdx++, $rIdx, $n++); // No.

            foreach ($columns as $c) {
                $value = $r->$c ?? null;

                if (in_array($c, $dateCols, true) && $value) {
                    try {
                        $dt = Carbon::parse($value)->timezone('America/Mexico_City');
                        $excelSerial = ExcelDate::PHPToExcel($dt);
                        $sheet->setCellValueByColumnAndRow($cIdx, $rIdx, $excelSerial);
                        $colLetter = Coordinate::stringFromColumnIndex($cIdx);
                        $sheet->getStyle("{$colLetter}{$rIdx}")
                              ->getNumberFormat()
                              ->setFormatCode('dd/mm/yyyy');
                    } catch (\Throwable $e) {
                        $sheet->setCellValueExplicitByColumnAndRow($cIdx, $rIdx, (string) $value, DataType::TYPE_STRING);
                    }
                } elseif (in_array($c, $textCols, true)) {
                    $sheet->setCellValueExplicitByColumnAndRow($cIdx, $rIdx, (string) $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValueByColumnAndRow($cIdx, $rIdx, $value);
                }
                $cIdx++;
            }
            $rIdx++;
        }

        for ($rowIdx = 2; $rowIdx < $rIdx; $rowIdx++) {
            $sheet->getRowDimension($rowIdx)->setRowHeight(-1);
        }
        $wrapCols = [4, 7, 11, 12, 14, 15, 16, 17];
        foreach ($wrapCols as $i) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getStyle("{$colLetter}2:{$colLetter}" . ($rIdx - 1))
                  ->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }
        for ($i = 1; $i <= $lastColIndex; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        if (method_exists($writer, 'setPreCalculateFormulas')) {
            $writer->setPreCalculateFormulas(false);
        }

        $fname = 'empleados_' . now('America/Mexico_City')->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fname, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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