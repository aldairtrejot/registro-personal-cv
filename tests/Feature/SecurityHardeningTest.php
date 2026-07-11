<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\Login\AuthLoginController;
use App\Http\Middleware\CheckRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_revisor_download_routes_keep_auth_and_role_middleware(): void
    {
        $excelRoute = Route::getRoutes()->getByName('cv.reportes.empleados_terminados');
        $this->assertNotNull($excelRoute);
        $this->assertContains('auth', $excelRoute->gatherMiddleware());
        $this->assertContains('role:1,3', $excelRoute->gatherMiddleware());

        $zipRoute = collect(Route::getRoutes())->first(
            fn ($route) => $route->uri() === 'revisor/pdf/aprobados.zip'
        );

        $this->assertNotNull($zipRoute);
        $this->assertContains('auth', $zipRoute->gatherMiddleware());
        $this->assertContains('role:1,3', $zipRoute->gatherMiddleware());
    }

    public function test_role_middleware_blocks_guest_users(): void
    {
        Auth::logout();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(CheckRole::class)->handle(
            Request::create('/cv/reportes/empleados-terminados', 'GET'),
            fn () => response('OK', 200),
            1,
            3
        );
    }

    public function test_login_returns_429_when_rate_limit_is_reached(): void
    {
        $email = 'rate-limit-test@example.test';
        $key = 'login:' . $email . '|127.0.0.1';
        $controller = app(AuthLoginController::class);

        RateLimiter::clear($key);

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 60);
        }

        $response = $controller->authentication(Request::create('/auth/authentication', 'POST', [
            'email' => $email,
            'password' => 'wrong-password',
        ], [], [], ['REMOTE_ADDR' => '127.0.0.1']));

        $this->assertSame(429, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['status']);

        RateLimiter::clear($key);
    }
}
