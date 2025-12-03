<?php

namespace App\Http\Controllers\Follow;

use App\Http\Controllers\Controller;
use App\Models\Collection\Position\OnlyPositionModel;
use Illuminate\Http\Request;
class UpdatePositionController extends Controller
{
    public function maxPosition(Request $request)
    {
        try {
            $onlyPositionModel = new OnlyPositionModel();

            $listOptionsPosition = $onlyPositionModel->listCollection(); // Get all available role options
            $listSelectPosition = []; // No selected roles by default

            $result = [ // Package the data to return to the frontend
                'listOptionsPosition' => $listOptionsPosition, // Selected roles (if editing)
                'listSelectPosition' => $listSelectPosition, // Selected roles (if editing)
            ];

            return response()->json([
                'status' => true, // Return successful response
                'result' => $result // Send packaged data
            ], 200); // Respond with HTTP status 200

        } catch (\Exception $e) { // Catch any exception
            //\Log::info($e);
            return response()->json([
                'status' => false, // return a JSON response with status false on error
                'message' => __('default.error_message'), // Default error message from config
            ], 200); // Respond with HTTP status 200 even on error
        }
    }
}
