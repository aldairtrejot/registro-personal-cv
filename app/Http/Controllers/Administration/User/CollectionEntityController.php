<?php

namespace App\Http\Controllers\Administration\User;

use App\Http\Controllers\Controller;
use App\Models\Collection\Entity\CollectionEntityModel;
use Illuminate\Http\Request;

class CollectionEntityController extends Controller
{
    public function entity(Request $request)
    {
        try {
            $collectionEntityModel = new CollectionEntityModel();

            $request->validate([
                'id' => ['required', 'min:1']
            ]);

            $result = $collectionEntityModel->listCollection($request->id);

            // Si no hay resultados, envía listas vacías
            if ($result->isEmpty()) {
                $listOptionsEntity = [];
                $listSelectEntity = [];
            } else {
                $listOptionsEntity = $result;
                $listSelectEntity = []; // Por ahora ninguno seleccionado
            }

            return response()->json([
                'status' => true,
                'listOptionsEntity' => $listOptionsEntity,
                'listSelectEntity' => $listSelectEntity
            ], 200);

        } catch (\Throwable $th) {
            \Log::info($th);
            return response()->json([
                'status' => false,
                'message' => __('default.error_message'),
            ], 200);
        }
    }
}
