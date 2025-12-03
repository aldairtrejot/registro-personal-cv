<?php

namespace App\Http\Controllers\Follow;
use App\Http\Controllers\Credentialing\SaveCredentialingController;
use App\Http\Controllers\File\MassiveFileController;
use App\Models\Collection\Position\ValidatePositionModel;
use App\Models\Credentialing\ValidateUniqueModel;
use App\Models\Follow\GetDataEmployeeModel;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\History\EntityHistoryProfModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
class CheckPositionController extends Controller
{
    public function checkPosition(Request $request)
    {
        try {
            $timestamp = Carbon::now();
            $validatePositionModel = new ValidatePositionModel();
            $validateUniqueModel = new ValidateUniqueModel();
            $saveCredentialingController = new SaveCredentialingController();
            $massiveFileController = new MassiveFileController();
            $getDataEmployeeModel = new GetDataEmployeeModel();

            // Validación del request
            $request->validate([
                'id_cat_sig_puesto' => 'required|integer',
                'fecha_inicio' => [
                    'required',
                    'date',
                    'after_or_equal:2010-01-01',
                    'before_or_equal:2026-01-01',
                ],
            ]);

            $result = $validatePositionModel->validatePosition($request->id_cat_sig_puesto);

            if (empty($result) || (method_exists($result, 'isEmpty') && $result->isEmpty())) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.error_message'),
                ], 200);
            }

            $isUniqueData = $validateUniqueModel->validateUnique();

            if ($isUniqueData) {
                return response()->json([
                    'status' => false,
                    'message' => __('default.error_message'),
                ], 200);
            }

            // Save Data
            $status = $saveCredentialingController->saveCredentialing($result[0]->id_tbl_empleados, $request->id_cat_sig_puesto, $request->fecha_inicio);
            $idEmployee = $getDataEmployeeModel->getDataEmployee($result[0]->id_tbl_empleados);
            $result = $massiveFileController->storeDocuments($idEmployee->id_tbl_profesionalizacion, $request->id_cat_sig_puesto);

            // guardar historia
            EntityHistoryProfModel::create([
                'creado_en' => $timestamp,
                'actualizado_en' => $timestamp,
                'id_cat_estatus' => config('defined.PROCESO'),
                'id_usuario_creacion' => Auth::user()->id,
                'id_tbl_profesionalizacion' => $idEmployee->id_tbl_profesionalizacion
            ]);

            if ($status) {
                return response()->json([
                    'status' => true,
                    'message' => __('default.save_success_message'),
                ], 200);

            } else {
                return response()->json([
                    'status' => false,
                    'message' => __('default.error_message'),
                ], 200);
            }

        } catch (ValidationException $e) {
            // Si la validación falla
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            // \Log::info($th);
            return response()->json([
                'status' => false,
                'message' => __('default.error_message'),
            ], 500); // mejor devolver 500 en caso de excepción general
        }
    }
}