<?php

namespace App\Http\Controllers\Auth\Login;

use App\Http\Controllers\Controller;
use App\Models\Auth\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use HTMLPurifier;
use HTMLPurifier_Config;

class AuthLoginController extends Controller
{
    /**
     * The function authenticates the user, to return the active session or the error when trying to \Log in
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function authentication(Request $request)
    {
        // class
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);

        // object start modification}

        $request->merge([
            'email' => $purifier->purify(trim($request->email)),
            'password' => $purifier->purify(trim($request->password)),
        ]);

        // the IP is optimized for registration
        $key = 'login-attempts:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'status' => false,
                'message' => __('default.rate_limiter_message'),
            ], 200);
        }

        // session count
        RateLimiter::hit($key);

        $credentials = $request->validate([
            'email' => 'required',
            'password' => 'required',
            'captcha' => 'required|captcha'
        ]);

        $user = UserModel::where('email', $credentials['email'])
            ->where('estatus', true)
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'status' => false,
                'message' => __('default.login_failure_message'),
            ], 200);
        }

        Auth::login($user);

        // If the credentials are incorrect, it returns an error.
        return response()->json([
            'status' => true,
            'message' => 'ok.',
        ], 200); // Code 401: Unauthorized
    }
}
