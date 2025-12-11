<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckRole
{
    /**
     * Maneja la petición.
     *
     * Uso en rutas: ->middleware('role:1') o ->middleware('role:1,3')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Roles asignados al usuario en profesionalizacion.rel_usuario_rol
        $userRoles = DB::table('profesionalizacion.rel_usuario_rol')
            ->where('id_usuario', $user->id_usuario)   // Ojo: id_usuario
            ->where('activo', true)
            ->pluck('id_rol')
            ->map(fn ($r) => (int) $r)
            ->toArray();

        // Roles requeridos por la ruta
        $requiredRoles = collect($roles)->flatten()->map(fn ($r) => (int) $r)->toArray();

        $hasRole = count(array_intersect($userRoles, $requiredRoles)) > 0;

        if (!$hasRole) {
            abort(403, 'No tiene permisos para acceder a esta página.');
        }

        return $next($request);
    }
}
