<?php
 
namespace App\Http\Controllers\Administration\Zone;
 
use App\Http\Controllers\Controller;
use App\Models\Administration\Zone\EntityZoneModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use HTMLPurifier;
use HTMLPurifier_Config;
 
class SaveZoneController extends Controller
{
    /**
     * Sanitize and dispatch to storage function.
     */
    public function save(Request $request)
    {
        try {
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);
 
            // Sanitizar campos
            $request->merge([
                'descripcion' => strtoupper($purifier->purify(trim($request->descripcion))),
                'estatus' => $request->estatus == '1' ? true : false,
            ]);
 
            return $this->storage($request);
 
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Ocurrió un error al guardar la Zona.',
            ], 200);
        }
    }
 
    /**
     * Valida, guarda o actualiza el registro.
     */
    private function storage($request)
    {
        try {
            $model = new EntityZoneModel();
            $timestamp = Carbon::now();
 
            $rules = [
                'descripcion' => 'required|max:255',
                'estatus' => 'boolean',
            ];
 
            $request->validate($rules);
 
            $data = [
                'descripcion' => $request->descripcion,
                'estatus' => $request->estatus,
            ];
 
            if ($request->filled('id_cat_zona')) {
                // Editar
                $model::where('id_cat_zona', $request->id_cat_zona)->update($data);
                $message = 'Zona actualizada correctamente.';
            } else {
                // Crear
                $data['creado_en'] = $timestamp;
                $data['id_usuario_creacion'] = Auth::user()->id;
 
                $model::create($data);
                $message = 'Zona guardada correctamente.';
            }
 
            return response()->json([
                'status' => true,
                'message' => $message,
            ], 200);
 
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Error al guardar la Zona.',
            ], 200);
        }
    }
}