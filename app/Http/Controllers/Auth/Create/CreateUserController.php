<?php

namespace App\Http\Controllers\Auth\Create;

use App\Http\Controllers\Controller;
use App\Models\Administration\UserRole\EntityUserRoleModel;
use App\Models\Auth\CreateUserModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Helpers\GeneratePasswordController;
use App\Http\Controllers\Helpers\MailController;
use App\Models\Administration\User\EntityUserModel;
use Illuminate\Support\Facades\RateLimiter;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CreateUserController extends Controller
{
    public function createUser(Request $request)
    {
        try {
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            $request->merge([
                'email' => $purifier->purify(trim($request->email)),
                'confirm_email' => $purifier->purify(trim($request->confirm_email)),
                'rfc' => $purifier->purify(strtoupper($request->rfc)),
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
            // \Log::info($th); // Optional: log the error for debugging
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
            $createUserModel = new CreateUserModel();
            $timestamp = Carbon::now();

            $request->validate([
                'email' => 'required|email|max:150',
                'confirm_email' => 'required|email|same:email',
                'rfc' => [
                    'required',
                    'regex:/^([A-ZÑ&]{3,4})(\d{2})(\d{2})(\d{2})([A-Z\d]{3})$/'
                ],
            ], [
                'rfc.regex' => 'Formato no válido.',
                'confirm_email.same' => 'No coincide el correo electrónico de confirmación.',
            ]);


            $onlyEmail = $createUserModel->validateOnlyEmail($request->email);

            if ($onlyEmail) {
                return response()->json([
                    'status' => false, // Return a JSON response with status false on error
                    'message' => __('default.data_exists'), // Default error message from language file
                ], 200); // Respond with HTTP status 200 even on error
            }

            $onlyRfc = $createUserModel->validateOnlyRfc($request->rfc);
            if (!$onlyRfc) {
                return response()->json([
                    'status' => false, // Return a JSON response with status false on error
                    'message' => __('default.is_not_exists_rfc'), // Default error message from language file
                ], 200); // Respond with HTTP status 200 even on error
            }

            $twoValidate = $createUserModel->twoValidate($request->email, $request->rfc);
            if ($twoValidate) {
                return response()->json([
                    'status' => false, // Return a JSON response with status false on error
                    'message' => __('default.data_exists'), // Default error message from language file
                ], 200); // Respond with HTTP status 200 even on error
            }

            $twoUniqueRfc = $createUserModel->twoUniqueRfc($request->rfc);
            if ($twoUniqueRfc) {
                return response()->json([
                    'status' => false, // Return a JSON response with status false on error
                    'message' => __('default.data_exists'), // Default error message from language file
                ], 200); // Respond with HTTP status 200 even on error
            }

            $data = $createUserModel->getData($request->rfc);
            if (!$data->estatus) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.error_message'), // Default error message
                ], 200); // Return general error response
            }

            // Validación de fecha de creación de usuario
            /*$isDate = $createUserModel->getDate(config('defined.FECHA_BLOQUEO_USUARIO'), config('defined.FECHA_CREACION_USUARIO'));
            $fechaCreacion = Carbon::parse($isDate[config('defined.FECHA_CREACION_USUARIO')]);
            $today = Carbon::today();*/

            $isDate = $createUserModel->getDate(
    config('defined.FECHA_BLOQUEO_USUARIO'),
    config('defined.FECHA_CREACION_USUARIO')
);
 
// Normalizar keys a lowercase para evitar problemas en Linux
$normalized = array_change_key_case($isDate, CASE_LOWER);
 
// Normalizar clave del config también
$keyFechaCreacion = strtolower(config('defined.FECHA_CREACION_USUARIO'));
 
if (!isset($normalized[$keyFechaCreacion])) {
    return response()->json([
        'status' => false,
        'message' => 'Error: clave FECHA_CREACION no encontrada en getDate().',
    ], 200);
}
 
$fechaCreacion = Carbon::parse($normalized[$keyFechaCreacion]);
$today = Carbon::today();

            if ($fechaCreacion->lt($today)) { // lt = less than
                return response()->json([
                    'status' => false,
                    'message' => __('default.create_is_end_date'),
                ], 200);
            }

            EntityUserModel::create([
                'name' => $data->nombre,
                'email' => $request->email,
                'password' => Hash::make($newPassword),
                'estatus' => TRUE,
                'creado_en' => $timestamp,
                'es_administrador' => FALSE,
                'password_update' => FALSE,
                'id_tbl_empleado' => $data->id,
                'fecha_bloqueo' => $isDate[config('defined.FECHA_BLOQUEO_USUARIO')]
            ]);

            $employee = $createUserModel->getDataEmployee($request->email);

            EntityUserRoleModel::create([
                'id_users' => $employee->id,
                'id_tbl_roles' => config('defined.ROLE_EMPLEADO'),
                'estatus' => TRUE,
                'creado_en' => $timestamp,
                'id_usuario_creacion' => $employee->id
            ]);

            // data for email
            $data = [
                'affair' => 'Alta de ususario',
                'mail' => $request->email,
                'content' => $templateMailController->contentMail($request->email, $newPassword, $data->nombre),
            ];

            // mail sending
            $mailController->sendMail($data);

            // \Log::info($newPassword);

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
            // \Log::info($th);
            return response()->json([
                'status' => false,
                'message' => __('default.error_message'), // Default error message
            ], 200); // Return general error response
        }
    }
}
