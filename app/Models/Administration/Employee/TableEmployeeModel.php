<?php

namespace App\Models\Administration\Employee;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Query\Builder;

class TableEmployeeModel extends Model
{
    /* =================== API PRINCIPAL =================== */

    public function list($limit, $offset, $search, $select, $statusId = null)
    {
        // Normaliza filtro de estatus del front ('' | null | 9999 => sin filtro)
        if ($statusId === '' || $statusId === null) {
            $statusId = null;
        } elseif (is_numeric($statusId)) {
            $statusId = (int) $statusId;
            if ($statusId === 9999) $statusId = null; // "TODOS"
        } else {
            $statusId = null;
        }

        /* ========= Subconsulta: último estatus por empleado =========
           Traemos también el id_tbl_profesionalizacion como prof_id
        */
        $latest = DB::table('profesionalizacion.tbl_profesionalizacion as p')
            ->selectRaw('
                DISTINCT ON (p.id_tbl_empleados)
                p.id_tbl_empleados,
                p.id_tbl_profesionalizacion AS prof_id,
                p.id_cat_estatus
            ')
            ->whereNotNull('p.id_cat_estatus')
            ->orderBy('p.id_tbl_empleados')
            ->orderByDesc('p.id_tbl_profesionalizacion');

        /* ========= COUNT (total) ========= */
        $countQuery = DB::table('profesionalizacion.tbl_empleados AS e')
            ->leftJoin('profesionalizacion.tbl_plazas AS pl', 'pl.id_tbl_plazas', '=', 'e.id_tbl_plazas')
            ->leftJoin('profesionalizacion.tbl_clues  AS cl', 'cl.id_tbl_clues',  '=', 'pl.id_tbl_clues')
            ->joinSub($latest, 'lp', fn($j) => $j->on('lp.id_tbl_empleados', '=', 'e.id_tbl_empleados'))
            ->leftJoin('catalogo.cat_estatus AS ce', 'ce.id_cat_estatus', '=', 'lp.id_cat_estatus')
            ->leftJoinSub($this->zonaResueltaSubquery(), 'zr', function ($j) {
                $j->on('zr.id_cat_entidad', '=', 'cl.id_cat_entidad')
                  ->where('zr.rn', '=', 1);
            });

        // Reglas por rol (idénticas al export). $statusId solo aplica a Admin.
        $this->applyRoleScopeAndStatuses($countQuery, $statusId);

        // Búsqueda alineada al export
        $this->applySearchAligned($countQuery, $search);

        $allRow = (int) $countQuery->distinct('e.id_tbl_empleados')->count('e.id_tbl_empleados');

        /* ========= DATA (lista paginada) ========= */
        $query = DB::table('profesionalizacion.tbl_empleados AS e')
            ->leftJoin('profesionalizacion.tbl_plazas AS pl', 'pl.id_tbl_plazas', '=', 'e.id_tbl_plazas')
            ->leftJoin('profesionalizacion.tbl_clues  AS cl', 'cl.id_tbl_clues',  '=', 'pl.id_tbl_clues')
            ->joinSub($latest, 'lp', fn($j) => $j->on('lp.id_tbl_empleados', '=', 'e.id_tbl_empleados'))
            ->leftJoin('catalogo.cat_estatus AS ce', 'ce.id_cat_estatus', '=', 'lp.id_cat_estatus')
            ->leftJoinSub($this->zonaResueltaSubquery(), 'zr', function ($j) {
                $j->on('zr.id_cat_entidad', '=', 'cl.id_cat_entidad')
                  ->where('zr.rn', '=', 1);
            })
            ->selectRaw("
                e.id_tbl_empleados AS id,
                lp.prof_id AS prof_id,
                TRIM(
                    COALESCE(e.primer_apellido, '') || ' ' ||
                    COALESCE(e.segundo_apellido, '') || ' ' ||
                    COALESCE(e.nombre, '')
                ) AS nombre_completo,
                e.rfc,
                e.curp,
                COALESCE(ce.descripcion, 'SIN ESTATUS') AS estatus
            ");

        // Reglas por rol (idénticas al export)
        $this->applyRoleScopeAndStatuses($query, $statusId);

        // Búsqueda alineada
        $this->applySearchAligned($query, $search);

        // Evita duplicados (1 fila por empleado)
        $query->groupBy([
            'e.id_tbl_empleados',
            'lp.prof_id',
            'e.primer_apellido',
            'e.segundo_apellido',
            'e.nombre',
            'e.rfc',
            'e.curp',
            'ce.descripcion',
        ]);

        $row  = min($allRow, $offset + $limit);
        $list = $query
            ->orderBy('e.primer_apellido', 'ASC')
            ->orderBy('e.segundo_apellido', 'ASC')
            ->orderBy('e.nombre', 'ASC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return ['row' => $row, 'allRow' => $allRow, 'list' => $list];
    }
/* =================== ALCANCE POR ROL (DEFINITIVO) =================== */
    /* =================== ALCANCE POR ROL (DEFINITIVO) =================== */
    private function applyRoleScopeAndStatuses(Builder $query, ?int $statusIdForAdmin = null): void
    {
        $user        = Auth::user();
        $mainRole    = $this->resolveMainRoleEffective() ?? 2;
        $idZonaHraes = $this->getZonaHraesId();

        // ====== IDs de estatus (dinámicos, por descripción) ======
        $stEmpleado   = $this->resolveStatusId('empleado');
        $stRevisor    = $this->resolveStatusId('revisor');
        $stSupervisor = $this->resolveStatusId('supervisor');

        // Búsqueda flexible por texto, por si el catálogo trae variaciones
        $idsEmpleadoLike   = $this->findStatusIdsLike(['EMPLEADO']);
        $idsRevisorLike    = $this->findStatusIdsLike(['REVISOR']);
        $idsSupervisorLike = $this->findStatusIdsLike(['SUPERVISOR']);

        $idsEmpleadoAll = array_values(array_unique(array_filter(array_merge(
            $stEmpleado   ? [$stEmpleado]   : [],
            $idsEmpleadoLike
        ))));
        $idsRevisorAll = array_values(array_unique(array_filter(array_merge(
            $stRevisor    ? [$stRevisor]    : [],
            $idsRevisorLike
        ))));
        $idsSupervisorAll = array_values(array_unique(array_filter(array_merge(
            $stSupervisor ? [$stSupervisor] : [],
            $idsSupervisorLike
        ))));

        // ====== Helpers de nómina y rama ======
        $applyNomina = function () use ($query) {
            $nominas = $this->resolveUserTipoNominaCodes();
            if (!empty($nominas)) {
                $query->whereIn('pl.cat_tipo_nomina', $nominas);
            }
        };

        $applyRama = function () use ($query, $user) {
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
                $query->whereIn('pl.id_cat_rama', $ramas);
            }
        };
    // Helper de CLUES
    $clues = session('clues_permitidas');
    $clues = is_array($clues)
        ? array_values(array_unique(array_map('intval', $clues)))
        : [];

    $applyClues = function () use ($query, $clues) {
        if (!empty($clues)) {
            $query->whereIn('cl.id_tbl_clues', $clues);
        }
    };
        /* ================== 1) ADMIN ================== */
        // Puede ver todo; si el front manda estatus, se respeta solo aquí.
        if ($mainRole === 1) {
            if (!is_null($statusIdForAdmin)) {
                $query->where('lp.id_cat_estatus', (int) $statusIdForAdmin);
            }
            return;
        }

        /* ================== 2) USUARIO ================== */
        // Solo su propio expediente.
        if ($mainRole === 2) {
            if (!empty($user?->id_tbl_empleado)) {
                $query->where('e.id_tbl_empleados', (int) $user->id_tbl_empleado);
            } else {
                // usuario sin empleado ligado -> no ve nada
                $query->whereRaw('1=0');
            }
            return;
        }

        /* ================== 3) REVISOR ================== */
        // Solo expedientes COMPLETADOS POR EMPLEADO
        // Restricciones: Zona + Entidad de pago + Rama + Nómina
        if ($mainRole === 3) {
            if (!empty($idsEmpleadoAll)) {
                $query->whereIn('lp.id_cat_estatus', $idsEmpleadoAll);
            }

            // ---- Zona (del usuario + sesión) ----
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
            $this->applyZonaFilter($query, $zonas, $idZonaHraes);

            // ---- Entidad de pago (si la tiene) ----
            if (!empty($user?->id_cat_entidad)) {
                $query->where('cl.id_cat_entidad', (int) $user->id_cat_entidad);
            }

            // Siempre filtra por nómina y rama
            $applyNomina();
            $applyRama();
             $applyClues();
            return;
        }

        /* ================== 4) SUPERVISOR ================== */
        // Solo expedientes COMPLETADOS POR REVISOR
        // Restricciones: Zona + Entidad de pago + Rama + Nómina
        if ($mainRole === 4) {
            if (!empty($idsRevisorAll)) {
                $query->whereIn('lp.id_cat_estatus', $idsRevisorAll);
            }

            // ---- Zona (del usuario + sesión) ----
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
            $this->applyZonaFilter($query, $zonas, $idZonaHraes);

            // ---- Entidad de pago (si la tiene) ----
            if (!empty($user?->id_cat_entidad)) {
                $query->where('cl.id_cat_entidad', (int) $user->id_cat_entidad);
            }

            $applyNomina();  // tipo de nómina
            $applyRama();  
             $applyClues();  // rama
            return;
        }

        /* ================== 5) DGES ================== */
        // Solo expedientes COMPLETADOS POR SUPERVISOR
        // Restricciones: Rama + Nómina (no hay restricción de entidad según tus reglas)
        if ($mainRole === 5) {
            if (!empty($idsSupervisorAll)) {
                $query->whereIn('lp.id_cat_estatus', $idsSupervisorAll);
            }

            $applyRama();
            $applyNomina();
            return;
        }

        // Rol desconocido => sin acceso
        $query->whereRaw('1=0');
    }


    /* =================== BÚSQUEDA ALINEADA AL EXPORT =================== */

    private function applySearchAligned(Builder $query, string $search): void
    {
        $search = trim($search);
        if ($search === '') return;

        $pattern = str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $search);
        $needle  = '%' . $pattern . '%';

        $hasClaveClues = $this->columnExists('profesionalizacion', 'tbl_clues', 'clave_clues');
        $hasClave      = $this->columnExists('profesionalizacion', 'tbl_clues', 'clave');

        $query->where(function ($q) use ($needle, $hasClaveClues, $hasClave) {
            $q->where('e.primer_apellido', 'ILIKE', $needle)
              ->orWhere('e.segundo_apellido', 'ILIKE', $needle)
              ->orWhere('e.nombre', 'ILIKE', $needle)
              ->orWhere('e.rfc', 'ILIKE', $needle)
              ->orWhere('e.curp', 'ILIKE', $needle)
              ->orWhere('ce.descripcion', 'ILIKE', $needle)
              ->orWhere('zr.zona', 'ILIKE', $needle);

            if ($hasClaveClues) { $q->orWhere('cl.clave_clues', 'ILIKE', $needle); }
            if ($hasClave)      { $q->orWhere('cl.clave',       'ILIKE', $needle); }
        });
    }

    /* =================== UTILIDADES COMPARTIDAS =================== */

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


    private function pickRole(array $roles): ?int
    {
        foreach ([1, 5, 4, 3, 2] as $r) {
            if (in_array($r, $roles, true)) return $r;
        }
        return null;
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

        $id = DB::table('catalogo.cat_estatus')
            ->whereIn(DB::raw('UPPER(TRIM(descripcion))'), $upperCands)
            ->value('id_cat_estatus');

        return $cache[$key] = ($id !== null ? (int) $id : null);
    }

    private function columnExists(string $schema, string $table, string $column): bool
    {
        $sql = "
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ? AND column_name = ?
            LIMIT 1
        ";
        return (bool) DB::selectOne($sql, [$schema, $table, $column]);
    }

    private function tableExists(string $schema, string $table): bool
    {
        $sql = "
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = ? AND table_name = ?
            LIMIT 1
        ";
        return (bool) DB::selectOne($sql, [$schema, $table]);
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


    
public function fetchVisibleEmployeeById(int $id, ?int $statusIdForAdmin = null)
{
    $latest = DB::table('profesionalizacion.tbl_profesionalizacion as p')
        ->selectRaw('
            DISTINCT ON (p.id_tbl_empleados)
            p.id_tbl_empleados,
            p.id_tbl_profesionalizacion AS prof_id,
            p.id_cat_estatus
        ')
        ->whereNotNull('p.id_cat_estatus')
        ->orderBy('p.id_tbl_empleados')
        ->orderByDesc('p.id_tbl_profesionalizacion');

    $q = DB::table('profesionalizacion.tbl_empleados AS e')
        ->leftJoin('profesionalizacion.tbl_plazas AS pl', 'pl.id_tbl_plazas', '=', 'e.id_tbl_plazas')
        ->leftJoin('profesionalizacion.tbl_clues  AS cl', 'cl.id_tbl_clues',  '=', 'pl.id_tbl_clues')
        ->joinSub($latest, 'lp', fn($j) => $j->on('lp.id_tbl_empleados', '=', 'e.id_tbl_empleados'))
        ->leftJoin('catalogo.cat_estatus AS ce', 'ce.id_cat_estatus', '=', 'lp.id_cat_estatus')
        ->leftJoinSub($this->zonaResueltaSubquery(), 'zr', function ($j) {
            $j->on('zr.id_cat_entidad', '=', 'cl.id_cat_entidad')
              ->where('zr.rn', '=', 1);
        })
        ->selectRaw("
            e.id_tbl_empleados AS id,
            lp.prof_id AS prof_id,
            TRIM(
                COALESCE(e.primer_apellido, '') || ' ' ||
                COALESCE(e.segundo_apellido, '') || ' ' ||
                COALESCE(e.nombre, '')
            ) AS nombre_completo,
            e.rfc,
            e.curp,
            COALESCE(ce.descripcion, 'SIN ESTATUS') AS estatus
        ");

    $this->applyRoleScopeAndStatuses($q, $statusIdForAdmin);

    $q->where('e.id_tbl_empleados', $id);

    return $q->first();
}

/** Intenta leer el rol efectivo: 1) sesión main_role, 2) BD rel_users_rol, 3) sesión user_roles */
private function resolveMainRoleEffective(): ?int
{
    // 1) si el controlador ya pobló main_role, úsalo
    $mr = session('main_role');
    if (is_numeric($mr)) return (int)$mr;

    // 2) directo de BD (por si la lista se llama sin pasar por el controller que setea main_role)
    try {
        $uid = Auth::id();
        if ($uid) {
            $exists = DB::selectOne("
                SELECT 1 FROM information_schema.tables
                WHERE table_schema='administracion' AND table_name='rel_users_rol' LIMIT 1
            ");
            if ($exists) {
                $role = DB::table('administracion.rel_users_rol')
                    ->where('id_users', $uid)
                    ->orderByDesc('id_rel_users_rol')
                    ->value('id_tbl_roles');
                if ($role) return (int)$role;
            }
        }
    } catch (\Throwable $e) {
        // silencioso
    }

    // 3) fallback a lo que ya tenías
    $rolesArr = (array) (session('roles') ?? session('user_roles') ?? []);
    $roles = collect($rolesArr)->map(fn($v) => (int)$v)->unique()->values()->all();
    foreach ([1,5,4,3,2] as $r) if (in_array($r, $roles, true)) return $r;

    return null;
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
     * Devuelve el id de la zona "HRAES" (cacheado).
     */
    private function getZonaHraesId(): ?int
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $id = DB::table('catalogo.cat_zona')
                ->whereRaw('UPPER(TRIM(descripcion)) = ?', ['HRAES'])
                ->value('id_cat_zona');

            $cached = $id ? (int) $id : null;
        } catch (\Throwable $e) {
            $cached = null;
        }

        return $cached;
    }

    /**
     * Aplica el filtro de zona teniendo en cuenta el caso especial de HRAES.
     *
     * - Si las zonas NO incluyen HRAES => filtro normal por zr.id_cat_zona.
     * - Si SOLO hay HRAES          => se usa zr.has_hraes = true
     *   (es decir, entidades que tienen alguna zona HRAES).
     * - Si hay HRAES + otras zonas => (zr.id_cat_zona IN otras) OR zr.has_hraes = true.
     */
    private function applyZonaFilter(Builder $query, array $zonas, ?int $idZonaHraes): void
    {
        $zonas = array_values(array_unique(array_map('intval', $zonas)));
        if (empty($zonas)) {
            return;
        }

        // Si no conocemos el id de HRAES, dejamos el filtro clásico.
        if (!$idZonaHraes || !in_array($idZonaHraes, $zonas, true)) {
            $query->whereIn('zr.id_cat_zona', $zonas);
            return;
        }

        // Separar HRAES del resto.
        $zonasSinHraes = array_values(array_diff($zonas, [$idZonaHraes]));

        if (empty($zonasSinHraes)) {
            // Solo HRAES: usamos el flag has_hraes del subquery de zonas.
            $query->where('zr.has_hraes', true);
            return;
        }

        // HRAES + otras zonas: combinación.
        $query->where(function ($q) use ($zonasSinHraes) {
            $q->whereIn('zr.id_cat_zona', $zonasSinHraes)
              ->orWhere('zr.has_hraes', true);
        });
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
