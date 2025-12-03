<?php

namespace App\Http\Controllers\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * The function closes the application session.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            Auth::logout(); // Logs out the current authenticated user

            $request->session()->invalidate(); // Invalidates the user's session

            $request->session()->regenerateToken(); // Regenerates the CSRF token for security

            return response()->json([
                'status' => true, // Return successful response
            ], 200); // Respond with HTTP status 200
        } catch (\Throwable $th) {
            // \Log::info($th); // Optional: log the error for debugging
            return response()->json([
                'status' => false, // Return a JSON response with status false on error
                'message' => __('default.error_message'), // Default error message from language file
            ], 200); // Respond with HTTP status 200 even on error
        }
    }

}
