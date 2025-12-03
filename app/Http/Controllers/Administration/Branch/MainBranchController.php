<?php
 
namespace App\Http\Controllers\Administration\Branch;
 
use App\Http\Controllers\Controller;
use App\Models\Administration\Branch\BranchModel;
use Illuminate\Http\Request;
 
class MainBranchController extends Controller
{
    /**
     * Returns branch data for editing (or default for creating).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function main(Request $request)
    {
        try {
            $data = new BranchModel(); // Instancia por defecto

            if (isset($request->id)) {
                if (!preg_match('/^\d+$/', $request->id)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'ID inválido.',
                    ], 200);
                }

                $data = BranchModel::select('id_cat_rama', 'descripcion','estatus')
                    ->find($request->id);
 
                if (!$data) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Rama no encontrada.',
                    ], 200);
                }
            } else {
                // Valores por defecto si es nuevo
                $data->estatus = true;
            }
 
            return response()->json([
                'status' => true,
                'result' => $data
            ], 200);
 
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener la información.',
            ], 200);
        }
    }
}