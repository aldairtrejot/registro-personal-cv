<?php

namespace App\Http\Middleware;

use Closure;

class LoadUserRoles
{
    /**
     * Middleware desactivado temporalmente.
     * No carga roles ni toca la tabla users.
     */
    public function handle($request, Closure $next)
    {
        // Aquí antes se hacía Auth::check() y consultas a users / roles.
        // Como estamos montando el nuevo sistema y aún no existe la tabla 'users',
        // lo dejamos vacío para que no rompa las rutas nuevas.
        return $next($request);
    }
}
