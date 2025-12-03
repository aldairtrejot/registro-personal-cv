<?php

namespace App\Http\Controllers\Follow;

use App\Http\Controllers\Controller;
use App\Models\Follow\DataFollowModel;

class DataFollowController extends Controller
{
    public function dataFollow()
    {
        try {
            $dataFollowModel = new DataFollowModel();
            $data = $dataFollowModel->dataFollow();

            return response()->json([
                'status' => true, // Return successful response
                'data' => $data // Send packaged data
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
