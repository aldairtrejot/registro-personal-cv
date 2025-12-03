<?php

namespace App\Http\Controllers\Administration\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\TemplateTableController;
use App\Models\Administration\User\TableUserModel;
use Illuminate\Http\Request;


class TableUserController extends Controller
{
    /**
     * The function cleans, sanitizes and validates the query information of the table to obtain 
     * the SQL and send it to the client.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function table(Request $request)
    {
        $templateTableController = new TemplateTableController();   // Create instance of the Table controller/helper
        $objectModel = new TableUserModel();     // Create instance of the User model
        try {
            // Validate and sanitize pagination parameters from the request
            $data = $templateTableController->validateAndSanitizePagination($request);

            // Call the list method with validated pagination and search parameters
            $result = $objectModel->list(
                $data['limit'],
                $data['offset'],
                $data['search'],
                $data['select']
            );

            // Return the response with paginated data
            return response()->json([
                'status' => true,
                'allRow' => $result['allRow'], // Total records
                'list' => $result['list'],     // Paginated records
                'row' => $result['row'],       // Current page row number
            ], 200);
        } catch (\Exception $e) {
            // Catch all other exceptions and return a general error message
            //\Log::info($e);
            return response()->json([
                'status' => false,
                'message' => 'Error',
            ], 200); // HTTP 200 with failure status
        }
    }
}
