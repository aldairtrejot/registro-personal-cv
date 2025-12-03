<?php

namespace App\Http\Controllers\Administration\Entity;

use App\Http\Controllers\Controller;
use App\Models\Administration\Entity\EntityModel;
use Illuminate\Http\Request;

class MainEntityController extends Controller
{
    /**
     * POST /role/entity/main
     * - Sin id: defaults para crear
     * - Con id: detalle
     * - Con id + estatus: actualiza estatus
     */
    public function main(Request $request)
    {
        try {
            // Defaults (crear)
            if (!$request->filled('id')) {
                return response()->json([
                    'status' => true,
                    'result' => (object)[
                        'id_cat_entidad' => null,
                        'abrev'          => null,
                        'descripcion'    => null,
                        'clave_entidad'  => null,
                        'estatus'        => true,
                    ],
                ], 200);
            }

            // Validar id
            $id = $request->input('id');
            if (!preg_match('/^\d+$/', (string)$id)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'ID inválido.',
                ], 200);
            }

            // Buscar entidad con nombres reales de columnas
            $entity = EntityModel::select([
                    'id_cat_entidad',
                    'abrev',
                    'descripcion',
                    'clave_entidad',
                    'estatus',
                ])
                ->find($id);

            if (!$entity) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Entidad no encontrada.',
                ], 200);
            }

            // Actualizar estatus (si viene)
            if ($request->has('estatus')) {
                $newStatus = ($request->input('estatus') === true || $request->input('estatus') === 1 || $request->input('estatus') === '1');

                $entityToUpdate = EntityModel::find($id);
                $entityToUpdate->estatus = $newStatus;
                $entityToUpdate->save();

                $entity->estatus = $newStatus;

                return response()->json([
                    'status'  => true,
                    'message' => $newStatus ? 'Entidad activada.' : 'Entidad desactivada.',
                    'result'  => $entity,
                ], 200);
            }

            // Solo detalle
            return response()->json([
                'status' => true,
                'result' => $entity,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'Error al procesar la solicitud.',
            ], 200);
        }
    }
}

