<?php

namespace App\Http\Controllers\Auth\Recover;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\GeneratePasswordController;
use App\Http\Controllers\Helpers\MailController;
use App\Models\Administration\User\EntityUserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
class RecoverPasswordController extends Controller
{
    public function setPassword(Request $request)
    {
        try {
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            $request->merge([
                'email' => $purifier->purify(trim($request->email)),
            ]);

            $key = 'login-attempts:' . $request->ip();

            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.rate_limiter_message'),
                ], 200);
            }

            // session count
            RateLimiter::hit($key);

            return $this->sendMail($request);

        } catch (\Throwable $th) {
            //\Log::info($th); // Optional: log the error for debugging
            return response()->json([
                'status' => false, // Return a JSON response with status false on error
                'message' => __('default.error_message'), // Default error message from language file
            ], 200); // Respond with HTTP status 200 even on error
        }
    }


    private function sendMail($request)
    {
        try {
            $mailController = new MailController();
            $generatePasswordController = new GeneratePasswordController();
            $templateMailController = new TemplateMailController();
            $newPassword = $generatePasswordController->setPassword(12);

            $request->validate([
                'email' => 'required|email',
            ]);

            $user = EntityUserModel::where('email', $request->email)
                ->where('estatus', true) // o 1 si es entero
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => false, // Return a JSON response with status false on error
                    'message' => __('default.error_user_not_found_by_email'), // Default error message from language file
                ], 200); // Respond with HTTP status 200 even on error
            }

            $updated = EntityUserModel::where('email', $request->email)
                ->update([
                    'password' => Hash::make($newPassword),
                    'password_update' => false, // o 0 si es tipo entero
                ]);

            if (!$updated) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.error_message'), // Default error message
                ], 200); // Return general error response
            }

            // data for email
            $data = [
                'affair' => 'Restablecimiento de contraseña',
                'mail' => $request->email,
                'content' => $templateMailController->contentMail($request->email, $newPassword),
            ];

            // mail sending
            $mailController->sendMail($data);

            return response()->json([
                'status' => true, // Success response
                'message' => __('default.email_steps_sent_message'),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(), // Return validation errors
            ], 422); // HTTP 422 for validation errors
        } catch (\Throwable $th) {
            //\Log::info($th);
            return response()->json([
                'status' => false,
                'message' => __('default.error_message'), // Default error message
            ], 200); // Return general error response
        }
    }
}
