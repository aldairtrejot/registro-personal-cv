<?php
 
namespace App\Providers;
 
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
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
       
        Blade::if('hasrole', function () {
            $userRoles = session('user_roles', []);
            $args = func_get_args();
 
            foreach ($args as $id) {
                if (in_array($id, $userRoles)) {
                    return true;
                }
            }
 
            return false;
        });
    }
}
 
 