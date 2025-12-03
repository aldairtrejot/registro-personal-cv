<?php

namespace App\Http\Controllers\Administration\UserRole;
use App\Http\Controllers\Log\ActivityLogController;
use App\Models\Administration\UserRole\EntityUserRoleModel;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class AssignUserRoleController extends Controller
{
    /**
     * The function adds roles to the user and adds them to its log table
     * @param mixed $idUser
     * @param mixed $role
     * @return void
     */
    public function assignRole($idUser, $role)
    {
        try {
            $activityLogController = new ActivityLogController(); // Create an instance of the LogRolC class
            $timestamp = Carbon::now(); // Get the current timestamp

            foreach ($role as $roleAdd) { // Loop through each role to assign
                EntityUserRoleModel::create([ // Create a new role assignment record
                    'id_users' => $idUser, // User ID to assign the role to
                    'id_tbl_roles' => $roleAdd, // Role ID to assign
                    'creado_en' => $timestamp, // Timestamp of creation
                    'id_usuario_creacion' => Auth::user()->id, // ID of the authenticated user making the change
                    'estatus' => TRUE, // Set status to active
                ]);
            }

            // add log
            $data = [ // Prepare data for the log
                'id_users' => $idUser, // User ID for logging
                'id_tbl_roles' => $role // Assigned roles for logging
            ];
            $activityLogController->createLog('administracion.rel_usuario_rol', $data, config('defined.LOG_CREATE')); // Create a log entry
        } catch (\Throwable $th) {
            // \Log::info($th); // Log the error if needed (currently commented)
        }
    }
}
