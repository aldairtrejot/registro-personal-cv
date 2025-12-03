<?php

namespace App\Http\Controllers\Administration\Employee;

use App\Http\Controllers\Controller;
use App\Models\Administration\Employee\EntityEmployeeModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SaveEmployeeController extends Controller
{
    /**
     * Guarda/actualiza datos del EMPLEADO.
     * Ruta: POST /employee/save
     * Espera en el body:
     *  - id (id_tbl_empleados)
     *  - nombre, primer_apellido, segundo_apellido (opcional)
     *  - rfc, curp
     *  - id_cat_estatus
     */
    public function save(Request $request)
    {
        // 1) Validación (sin exists:conexion)
        $rules = [
            'id'               => 'required|integer|min:1',
            'nombre'           => 'required|string|max:100',
            'primer_apellido'  => 'required|string|max:100',
            'segundo_apellido' => 'nullable|string|max:100',
            'rfc'              => ['required','string','max:13','regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i'],
            'curp'             => ['required','string','max:18','regex:/^[A-Z][AEIOUX][A-Z]{2}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/i'],
            'id_cat_estatus'   => ['required','integer'],
        ];

        // 📣 Mensajes de validación más claros (sin cambiar reglas/lógica)
        $messages = [
            // id
            'id.required' => 'Falta el identificador del empleado.',
            'id.integer'  => 'El identificador no es válido.',
            'id.min'      => 'El identificador no es válido.',

            // nombre
            'nombre.required' => 'Escribe el nombre.',
            'nombre.string'   => 'El nombre no es válido.',
            'nombre.max'      => 'El nombre es demasiado largo (máximo 100 caracteres).',

            // primer_apellido
            'primer_apellido.required' => 'Escribe el primer apellido.',
            'primer_apellido.string'   => 'El primer apellido no es válido.',
            'primer_apellido.max'      => 'El primer apellido es demasiado largo (máximo 100 caracteres).',

            // segundo_apellido (opcional)
            'segundo_apellido.string' => 'El segundo apellido no es válido.',
            'segundo_apellido.max'    => 'El segundo apellido es demasiado largo (máximo 100 caracteres).',

            // rfc
            'rfc.required' => 'Escribe el RFC.',
            'rfc.string'   => 'El RFC no es válido.',
            'rfc.max'      => 'El RFC es demasiado largo (máximo 13 caracteres).',
            'rfc.regex'    => 'Revisa el formato del RFC.',

            // curp
            'curp.required' => 'Escribe la CURP.',
            'curp.string'   => 'La CURP no es válida.',
            'curp.max'      => 'La CURP es demasiado larga (máximo 18 caracteres).',
            'curp.regex'    => 'Revisa el formato de la CURP.',

            // id_cat_estatus
            'id_cat_estatus.required' => 'Selecciona un estatus.',
            'id_cat_estatus.integer'  => 'Selecciona un estatus válido.',
        ];

        try {
            $data = $request->validate($rules, $messages);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        // Verifica que el estatus exista en catalogo.cat_estatus pero sin conexiones extra
        $estatusExiste = DB::table('catalogo.cat_estatus')
            ->where('id_cat_estatus', $data['id_cat_estatus'])
            ->exists();
        if (!$estatusExiste) {
            return response()->json([
                'status' => false,
                'errors' => ['id_cat_estatus' => ['El estatus elegido no existe.']],
            ], 422);
        }

        $idEmpleado = (int) $data['id'];

        DB::beginTransaction();
        try {
            // 2) Actualiza datos base del empleado
            $actualizados = EntityEmployeeModel::where('id_tbl_empleados', $idEmpleado)->update([
                'nombre'           => trim($data['nombre']),
                'primer_apellido'  => trim($data['primer_apellido']),
                'segundo_apellido' => trim($data['segundo_apellido'] ?? ''),
                'rfc'              => strtoupper(trim($data['rfc'])),
                'curp'             => strtoupper(trim($data['curp'])),
            ]);

            if ($actualizados === 0) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'No encontramos al empleado.',
                ], 404);
            }

            // 3) Upsert del estatus en profesionalizacion.tbl_profesionalizacion
            // Asegúrate de que ESTA tabla exista y tenga estas columnas:
            //   id_tbl_empleados (PK/FK), id_cat_estatus (int)
            DB::table('profesionalizacion.tbl_profesionalizacion')->updateOrInsert(
                ['id_tbl_empleados' => $idEmpleado],
                ['id_cat_estatus' => (int) $data['id_cat_estatus']]
            );

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Los datos del empleado se guardaron.',
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Employee save error', ['error' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
            return response()->json([
                'status'  => false,
                'message' => 'No pudimos guardar los cambios. Intenta de nuevo.',
            ], 500);
        }
    }
}
