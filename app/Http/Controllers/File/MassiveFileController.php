<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Log\ActivityLogController;
use App\Models\File\MassiveFileModel;
use App\Models\File\SaveMassiveFileModel;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
class MassiveFileController extends Controller
{
    public function storeDocuments($idEmployee, $idNextPosition)
    {
        try {
            // class
            $massiveFileModel = new MassiveFileModel();
            $saveMassiveFileModel = new SaveMassiveFileModel();
            $activityLogController = new ActivityLogController();

            // value data
            $documents = $massiveFileModel->massiveFile($idNextPosition);
            $timestamp = Carbon::now();
            $userId = Auth::id();
            $idStatus = config('defined.DOCUMENT_ESTATUS_PROCESO');
            $data = [];

            // foreach
            foreach ($documents as $idDocumento) {
                $data[] = [
                    'creado_en' => $timestamp,
                    'id_usuario_creacion' => $userId,
                    'id_cat_tipo_documento' => $idDocumento,
                    'id_cat_estatus_documento' => $idStatus,
                    'id_tbl_profesionalizacion' => $idEmployee,
                ];
            }

            // save data
            if (!empty($data)) {
                $saveMassiveFileModel->saveMassiveFile($data);
            }

            $activityLogController->createLog('profesionalizacion.ctrl_documentos_profesionalizacion ', $data, config('defined.LOG_CREATE'));

            return true;

        } catch (\Throwable $th) {
            // \Log::info($th);
            return false;
        }

    }
}
