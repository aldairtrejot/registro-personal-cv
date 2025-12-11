<?php

namespace App\Http\Controllers\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cv\UsuarioSistema;

class AuthLoginController extends Controller
{
    public function authentication(Request $request)
{
    $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ]);

    $user = UsuarioSistema::where('email', $request->email)
        ->where('activo', true)
        ->first();

    if (!$user || !password_verify($request->password, $user->password_hash)) {
        return response()->json([
            'status'  => false,
            'message' => 'Usuario o contraseña incorrectos.',
        ], 200);
    }

    Auth::login($user);
    $request->session()->regenerate();

    return response()->json([
        'status'  => true,
        'message' => 'Inicio de sesión correcto.',
    ], 200);
}
}
