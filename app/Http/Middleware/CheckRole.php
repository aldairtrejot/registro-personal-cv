<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$requiredRoles)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $userRoles = Role::where('id_users', $user->id)->pluck('id_tbl_roles')->toArray();

        foreach ($requiredRoles as $roleId) {
            if (in_array($roleId, $userRoles)) {
                return $next($request);
            }
        }

        abort(403, 'Access denied');
    }
}
