<?php

namespace App\Http\Controllers\Follow;

use App\Http\Controllers\Controller;
use App\Models\Follow\DocumentRequirementModel;

class DocumentRequirementController extends Controller
{
    
    public function listByPosition(int $positionId)
    {
        $docs = (new DocumentRequirementModel())->listForCurrentUser();

        $documents = array_map(function ($d) {
            return [
                'id_ctrl_documentos_profesionalizacion' => $d['id_ctrl_documentos_profesionalizacion'],
                'id_tbl_profesionalizacion'             => $d['id_tbl_profesionalizacion'],
                'id_cat_tipo_documento'                 => $d['id_cat_tipo_documento'],
                'descripcion'                           => $d['descripcion'],
                'uuid'                                   => $d['uuid'],
                'id_cat_estatus_documento'              => $d['id_cat_estatus_documento'], // ← CLAVE PARA EL FRONT
            ];
        }, $docs);

        return response()->json([
            'status' => true,
            'result' => ['documents' => $documents],
        ], 200);
    }
}





