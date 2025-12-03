<?php

namespace App\Http\Controllers\Administration\User;

use App\Http\Controllers\Administration\UserRole\AssignUserRoleController;
use App\Http\Controllers\Administration\Role\ValidateRoleController;
use App\Http\Controllers\Administration\UserRole\DestroyUserRoleController;
use App\Http\Controllers\Log\ActivityLogController;
use App\Models\Collection\Payroll\EntityPayrollModel;
use App\Http\Controllers\Controller;
use App\Models\Administration\User\EntityUserModel;
use App\Models\Administration\User\UniqueMailUserModel;
use App\Models\Collection\Coordination\EntityCoordinationModel;
use App\Models\Collection\Department\EntityDepartmentModel;
use App\Models\Collection\MexicanEntity\EntityMexicanEntityModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use HTMLPurifier;
use HTMLPurifier_Config;


class SaveUserController extends Controller
{
    /**
     * The function sanitizes the data and returns the validation function, as well as the editing or aggregation.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        try {
            $config = HTMLPurifier_Config::createDefault(); // create a default HTMLPurifier configuration
            $purifier = new HTMLPurifier($config); // instantiate HTMLPurifier with the config

            $request->merge([
                'name' => strtoupper($purifier->purify(trim($request->name))), // sanitize and trim name, then convert to uppercase
                'email' => strtolower($purifier->purify(trim($request->email))), // sanitize and trim email
                'password' => $purifier->purify(trim($request->password)), // sanitize and trim password
            ]);

            return $this->storage($request); // return to storage

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false, // return a JSON response with status false on error
                'message' => __('default.error_message'), // Message error
            ], 200); // respond with HTTP status code 200 even in error case
        }
    }

    /**
     * The function validates the information, and saves either new records or updates, where it records its log.
     * @param mixed $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    private function storage($request)
    {
        try {
            $entityUserModel = new EntityUserModel(); // Create instance of the User model
            $timestamp = Carbon::now(); // Get current timestamp
            $activityLogController = new ActivityLogController();
            //$activityHistoryController = new ActivityHistoryController(); // Create instance of the log controller
            //$validateRoleController = new ValidateRoleController();
            $uniqueMailUserModel = new UniqueMailUserModel();
            $assignUserRoleController = new AssignUserRoleController();
            $destroyUserRoleController = new DestroyUserRoleController();

            $rules = [ // Validation rules
                'name' => 'required|max:70', // Name is required and max 70 characters
                'role' => ['required', 'array'],
                'fecha_bloqueo' => [
                    'required',
                    'date',
                    'after_or_equal:2025-08-01',
                    'before_or_equal:2026-06-01',
                ],
                'role.*' => ['integer'],
                'id_cat_entidad' => ['required', 'integer'],
                'id_cat_rama' => ['required', 'integer'],
                'id_cat_zona' => ['required', 'integer'],
                'email' => 'required|email|max:55', // Valid email required with max 55 characters
                'estatus' => 'boolean', // Must be boolean
                'password' => [ // Password rules
                    'required',
                    Password::min(8)
                        ->mixedCase()
                        ->letters()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(),
                ],
            ];

            if (isset($request->id)) { // If ID exists, it's an update
                unset($rules['password']); // Remove password rule on update
            }

            $request->validate($rules); // Run validation

            if ($uniqueMailUserModel->uniqueMail($request->id, $request->email)) { // Check for unique email
                return response()->json([
                    'status' => false,
                    'message' => __('default.unique_mail_message'), // Email already exists
                ], 200);
            }

            /*
            if (!$validateRoleController->validateRole($request->role)) { // Validate if the role is valid
                return response()->json([
                    'status' => false, // Return false if role is invalid
                    'message' => __('default.error_message'), // Default error message
                ], 200);
            }


            if (!EntityDepartmentModel::where('id_cat_unidad', $request->id_cat_unidad)->first()) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.unique_mail_message'), // Email already exists
                ], 200);
            }

            if (!EntityCoordinationModel::where('id_cat_coordinacion', $request->id_cat_coordinacion)->first()) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.unique_mail_message'), // Email already exists
                ], 200);
            }

            if (!EntityMexicanEntityModel::where('id_cat_entidad', $request->id_cat_entidad)->first()) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.unique_mail_message'), // Email already exists
                ], 200);
            }

            if (!EntityPayrollModel::where('id_cat_tipo_nomina', $request->id_cat_tipo_nomina)->first()) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.unique_mail_message'), // Email already exists
                ], 200);
            }     */

            $data = [ // Prepare user data
                'name' => $request->name,
                'email' => $request->email,
                'id_cat_entidad' => $request->id_cat_entidad,
                'password' => Hash::make($request->password), // Hash the password
                'estatus' => $request->estatus,
                'creado_en' => $timestamp,
                'id_usuario_creacion' => Auth::user()->id,
                'password_update' => FALSE,
                'es_administrador' => TRUE,
                'id_cat_zona' => $request->id_cat_zona,
                'fecha_bloqueo' => $request->fecha_bloqueo,
                'id_cat_rama' => $request->id_cat_rama,
            ];

            if (isset($request->id)) { // Edit existing user

                unset($data['password']); // Do not update password
                unset($data['creado_en']); // Remove creation timestamp
                unset($data['id_usuario_creacion']); // Remove creator ID
                unset($data['password_update']); // Remove password update
                $data['actualizado_en'] = $timestamp; // Add updated timestamp
                $data['id_usuario_modificacion'] = Auth::user()->id; // Set modifier user

                $entityUserModel::where('id', $request->id)->update($data); // Update user data
                $data['role'] = $request->role;
                $data['id'] = $request->id;
                $activityLogController->createLog('administracion.users', $data, config('defined.LOG_EDIT'));

                $destroyUserRoleController->destroyRole($request->id, $request->role); // Remove old roles
                $assignUserRoleController->assignRole($request->id, $request->role); // Assign roles to new user
                $message = __('default.edit_success_message');

            } else { // Create new user
                $entityUserModel::create($data); // Save new user    
                $data['role'] = $request->role;

                $userId = entityUserModel::where('email', $request->email)->value('id'); // Get new user ID
                $assignUserRoleController->assignRole($userId, $request->role); // Assign roles to new user

                $activityLogController->createLog('administracion.users', $data, config('defined.LOG_CREATE'));

                $message = __('default.save_success_message');
            }

            return response()->json([
                'status' => true, // Success response
                'message' => $message,
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
