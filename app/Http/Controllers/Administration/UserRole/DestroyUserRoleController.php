<?php

namespace App\Http\Controllers\Administration\UserRole;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Log\ActivityLogController;
use App\Models\Administration\UserRole\EntityUserRoleModel;

class DestroyUserRoleController extends Controller
{
    /**
     * The function removes the user's assigned roles and records them in its log table.
     * @param mixed $idUser
     * @param mixed $role
     * @return void
     */
    public function destroyRole($idUser, $role)
    {
        try {
            $activityHistoryController = new ActivityLogController(); // Create an instance of the LogRolC class
            EntityUserRoleModel::where('id_users', $idUser)->delete(); // Delete all roles assigned to the user

            $data = [ // Prepare data for the log
                'id_users' => $idUser, // User ID for logging
                'id_tbl_roles' => $role // Assigned roles for logging
            ];
            //$activityHistoryController->createLog('administracion.rel_usuario_rol', $data, config('defined.LOG_DESTROY')); // Create a log entry
        } catch (\Throwable $th) {
            // \Log::info($th); // Log any exception that occurs
        }
    }
}

