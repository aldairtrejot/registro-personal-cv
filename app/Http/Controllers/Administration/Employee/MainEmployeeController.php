<?php

namespace App\Http\Controllers\Administration\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MainEmployeeController extends Controller
{
    /**
     * Devuelve opciones de estatus para el filtro.
     * Incluye "TODOS" con id = 9999 para evitar que el select lo oculte.
     * Ruta: POST employee/main
     */
    public function main(Request $request)
    {
        try {
            $rows = DB::table('catalogo.cat_estatus')
                ->select('id_cat_estatus', 'descripcion')
                ->orderBy('id_cat_estatus')
                ->get();

            $options = $rows->map(function ($r) {
                $txt = strtoupper($r->descripcion);
                return [
                    'id'          => (int) $r->id_cat_estatus,
                    'value'       => (int) $r->id_cat_estatus,
                    'text'        => $txt,
                    'label'       => $txt,
                    'name'        => $txt,
                    'title'       => $txt,
                    'descripcion' => $r->descripcion,
                ];
            })->toArray();

            // ❌ Ya no insertamos "TODOS"
            // array_unshift($options, [...] );

            return response()->json([
                'status'            => true,
                'message'           => 'Listo',
                'listOptionsPeriod' => $options,
                'listSelectPeriod'  => [], // sin selección por defecto
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'No pudimos cargar los estatus. Intenta de nuevo.',
            ], 200);
        }
    }
}