<?php

namespace App\Http\Controllers\Administration\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\GeneratePasswordController;
use App\Models\Administration\Role\CollectionRoleModel;
use App\Models\Administration\User\EntityUserModel;
use App\Models\Collection\Area\CollectionAreaModel;
use App\Models\Collection\Department\CollectionDepartmentModel;
use App\Models\Collection\Entity\CollectionEntityModel;

use Illuminate\Http\Request;

class MainUserController extends Controller
{
    /**
     * The function starts the search for information, corresponding to whether it is being added or modified.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function main(Request $request)
    {
        try {

            $collectionRoleModel = new CollectionRoleModel(); // Instance of RoleM model
            $data = new EntityUserModel(); // Instance of UserM model
            $generatePasswordController = new GeneratePasswordController(); // Instance of custom password generator
            $collectionEntityModel = new CollectionEntityModel();
            $collectionAreaModel = new CollectionAreaModel();
            $collectionDepartmentModel = new CollectionDepartmentModel();

            if (isset($request->id)) { // Check if we are editing an existing user

                if (!preg_match('/^\d+$/', $request->id)) { // Validate that ID is a number
                    return response()->json([
                        'status' => false, // return a JSON response with status false on error
                        'message' => __('default.error_message'), // Default error message from config
                    ], 200); // Respond with HTTP status 200 even on error
                }

                $data = EntityUserModel::select('name', 'email', 'estatus', 'id_cat_entidad', 'id_cat_zona', 'id_cat_rama', 'fecha_bloqueo') // Select only required columns
                    ->find($request->id); // Find user by ID

                if (!$data) { // If no user was found
                    return response()->json([
                        'status' => false, // return a JSON response with status false on error
                        'message' => __('default.error_message'), // Default error message from config
                    ], 200); // Respond with HTTP status 200 even on error
                }

                $listOptionsRole = $collectionRoleModel->listCollection(); // Get all available role options
                $listSelectRole = $collectionRoleModel->listConllectionSelect($request->id); // Get selected roles for the user
                $listOptionsArea = $collectionAreaModel->listCollection(); // Get all available role options
                $listSelectArea = $collectionAreaModel->listConllectionSelect($data->id_cat_zona);

                $listOptionsDepartment = $collectionDepartmentModel->listCollection(); // Get all available role options
                $listSelectDepartment = $collectionDepartmentModel->listConllectionSelect($data->id_cat_rama);

                $listOptionsEntity = isset($data->id_cat_zona) ? $collectionEntityModel->listCollection($data->id_cat_zona) : []; // Get all available role options
                $listSelectEntity = $collectionEntityModel->listConllectionSelect($data->id_cat_entidad);

            } else { // If we are creating a new user
                $data->estatus = TRUE; // Set default status to true
                $data->password = $generatePasswordController->setPassword(12); // Generate a 12-character password
                $listOptionsRole = $collectionRoleModel->listCollection(); // Get all available role options
                $listSelectRole = []; // No selected roles by default
                $listOptionsArea = $collectionAreaModel->listCollection(); // Get all available role options
                $listSelectArea = []; // No selected roles by default
                $listOptionsEntity = []; // Get all available role options
                $listSelectEntity = []; // No selected roles by default
                $listOptionsDepartment = $collectionDepartmentModel->listCollection(); // Get all available role options
                $listSelectDepartment = []; // No selected roles by default
            }

            $result = [ // Package the data to return to the frontend
                'data' => $data, // User data
                'listOptionsRole' => $listOptionsRole, // All roles
                'listSelectRole' => $listSelectRole, // Selected roles (if editing)
                'listOptionsEntity' => $listOptionsEntity, // Selected roles (if editing)
                'listSelectEntity' => $listSelectEntity, // Selected roles (if editing)
                'listOptionsArea' => $listOptionsArea, // Selected roles (if editing)
                'listSelectArea' => $listSelectArea, // Selected roles (if editing)
                'listOptionsDepartment' => $listOptionsDepartment, // Selected roles (if editing)
                'listSelectDepartment' => $listSelectDepartment, // Selected roles (if editing)
            ];

            return response()->json([
                'status' => true, // Return successful response
                'result' => $result // Send packaged data
            ], 200); // Respond with HTTP status 200

        } catch (\Exception $e) { // Catch any exception
            // \Log::info($e);
            return response()->json([
                'status' => false, // return a JSON response with status false on error
                'message' => __('default.error_message'), // Default error message from config
            ], 200); // Respond with HTTP status 200 even on error
        }
    }
}
