<?php

namespace App\Http\Controllers\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Cv\UsuarioSistema;

class AuthLoginController extends Controller
{
    public function authentication(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'status'  => false,
                'message' => "Demasiados intentos. Intenta nuevamente en {$seconds} segundos.",
            ], 429);
        }

        $user = UsuarioSistema::where('email', $request->email)
            ->where('activo', true)
            ->first();

        if (!$user || !password_verify($request->password, $user->password_hash)) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'status'  => false,
                'message' => 'Usuario o contraseña incorrectos.',
            ], 200);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'status'  => true,
            'message' => 'Inicio de sesión correcto.',
        ], 200);
    }

    private function throttleKey(Request $request): string
    {
        return 'login:' . mb_strtolower((string) $request->input('email')) . '|' . $request->ip();
    }
}
