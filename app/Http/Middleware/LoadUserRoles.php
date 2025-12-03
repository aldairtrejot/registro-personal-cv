<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;   // 👈 FALTA ESTO
use App\Models\Role;

class LoadUserRoles
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            /* ===================== ROLES ===================== */
            $roles = Role::where('id_users', $user->id)
                ->pluck('id_tbl_roles')
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values()
                ->all();
            session(['user_roles' => $roles]);

            /* ===================== ZONAS ===================== */
            $zonas = [];
            if (!empty($user->id_cat_zona)) {
                $zonas[] = (int) $user->id_cat_zona;
            }
            session(['zonas_permitidas' => $zonas]);

            /* ===================== RAMAS ===================== */
            $ramas = [];
            if (!empty($user->id_cat_rama)) {
                $ramas[] = (int) $user->id_cat_rama;
            }
            session(['ramas_permitidas' => $ramas]);

            /* ===================== TIPOS DE NÓMINA ===================== */
            $ID_NOMINA_HRAES = 1;
            $ID_NOMINA_AYO   = 2;

            $usersNominaHraes = [ /* ... */ ];
            $usersNominaAyo   = [ /* ... */ ];

            $allowed = [];
            if (in_array($user->id, $usersNominaHraes, true)) {
                $allowed = [$ID_NOMINA_HRAES];
            } elseif (in_array($user->id, $usersNominaAyo, true)) {
                $allowed = [$ID_NOMINA_AYO];
            }

            session(['tipos_nomina_permitidos' => $allowed]);

            /* ===================== CLUES PERMITIDAS ===================== */
            $cluesPermitidas = [];

            try {
                $exists = DB::selectOne("
                    SELECT 1
                    FROM information_schema.tables
                    WHERE table_schema = 'administracion'
                      AND table_name   = 'rel_users_clues_scope'
                    LIMIT 1
                ");

                if ($exists) {
                    $cluesPermitidas = DB::table('administracion.rel_users_clues_scope')
                        ->where('id_users', $user->id)
                        ->where('estatus', true)
                        ->pluck('id_tbl_clues')
                        ->map(fn($v) => (int) $v)
                        ->unique()
                        ->values()
                        ->all();
                }
            } catch (\Throwable $e) {
                $cluesPermitidas = [];
            }

            session(['clues_permitidas' => $cluesPermitidas]);
        }

        return $next($request);
    }
}
