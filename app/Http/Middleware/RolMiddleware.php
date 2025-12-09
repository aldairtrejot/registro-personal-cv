<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RolMiddleware
{
    public function handle(Request $request, Closure $next, string $rol)
    {
        $user = $request->user();

        if (!$user) {
            // Si no está logueado
            return redirect()->route('login');
        }

        if (method_exists($user, 'hasRol') && !$user->hasRol($rol)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
