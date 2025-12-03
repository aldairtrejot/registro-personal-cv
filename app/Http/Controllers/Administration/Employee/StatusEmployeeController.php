<?php

namespace App\Http\Controllers\Administration\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class StatusEmployeeController extends Controller
{
    public function list(Request $request)
    {
        try {
            // Traemos id y descripción
            $rows = DB::table('catalogo.cat_estatus')
                ->select('id_cat_estatus', 'descripcion')
                ->orderBy('id_cat_estatus')
                ->get();

            // Mapeamos a un formato SENCILLO y estándar
            $options = $rows->map(function ($r) {
                $txt = strtoupper((string)$r->descripcion);
                $id  = (int)$r->id_cat_estatus;
                return [
                    'id'    => $id,
                    'value' => $id,
                    'text'  => $txt,
                    'label' => $txt,
                ];
            })->toArray();

            // Insertamos "TODOS" como primera opción
            array_unshift($options, [
                'id'    => 9999,
                'value' => 9999,
                'text'  => 'TODOS',
                'label' => 'TODOS',
            ]);

            return response()->json([
                'status' => true,
                'list'   => $options,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'No pudimos cargar la lista de estatus. Intenta de nuevo.'
            ], 500);
        }
    }
}