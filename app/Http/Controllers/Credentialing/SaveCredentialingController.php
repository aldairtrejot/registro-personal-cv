<?php

namespace App\Http\Controllers\Credentialing;

use App\Http\Controllers\Controller;
use App\Models\Credentialing\EntityCredentialingModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\Log\ActivityLogController;

class SaveCredentialingController extends Controller
{
    public function saveCredentialing($idEmployee, $idPosition, $endDate)
    {
        try {
            $timestamp = Carbon::now();
            $activityLogController = new ActivityLogController();

            $data = [ // Prepare user data
                'creado_en' => $timestamp,
                'id_usuario_creacion' => Auth::user()->id,
                'id_cat_sig_puesto' => $idPosition,
                'id_cat_estatus' => config('defined.PROCESO'),
                'id_tbl_empleados' => $idEmployee,
                'fecha_inicio' => $endDate,
            ];

            EntityCredentialingModel::create($data);
            $activityLogController->createLog('profesionalizacion.tbl_profesionalizacion', $data, config('defined.LOG_CREATE'));

            return true;

        } catch (\Throwable $th) {
            // \Log::info($th);
            return false;
        }
    }
}
