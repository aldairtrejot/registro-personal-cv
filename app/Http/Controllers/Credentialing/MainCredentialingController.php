<?php

namespace App\Http\Controllers\Credentialing;

use App\Http\Controllers\Controller;
use App\Models\Credentialing\MainCredentialingModel;
use Illuminate\Http\Request;

class MainCredentialingController extends Controller
{
    public function main()
    {
        try {
            $mainCredentialingModel = new MainCredentialingModel();

            $employee = $mainCredentialingModel->main();

            return response()->json([
                'status' => true, // Return successful response
                'employee' => $employee, // Send packaged data
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
