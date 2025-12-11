<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Directiva @hasrole que consulta roles en profesionalizacion.rel_usuario_rol
        Blade::if('hasrole', function (...$roles) {
            $user = Auth::user();

            if (!$user) {
                return false;
            }

            // Roles del usuario en BD
            $userRoles = DB::table('profesionalizacion.rel_usuario_rol')
                ->where('id_usuario', $user->id_usuario)
                ->where('activo', true)
                ->pluck('id_rol')
                ->map(fn ($r) => (int) $r)
                ->toArray();

            // Roles requeridos en el Blade: @hasrole(1,3,4)
            $requiredRoles = collect($roles)->flatten()
                ->map(fn ($r) => (int) $r)
                ->toArray();

            return count(array_intersect($userRoles, $requiredRoles)) > 0;
        });
    }
}
