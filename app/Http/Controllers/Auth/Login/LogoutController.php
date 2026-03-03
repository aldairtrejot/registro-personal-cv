<?php

namespace App\Http\Controllers\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * Cierra sesión:
     * - AJAX/JSON: responde {status:true}
     * - Submit normal: redirige a /login
     */
    public function logout(Request $request)
    {
        try {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => true], 200);
            }

            return redirect('/login');

        } catch (\Throwable $th) {

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.error_message'),
                ], 200);
            }

            return redirect('/login')->with('error', __('default.error_message'));
        }
    }
}