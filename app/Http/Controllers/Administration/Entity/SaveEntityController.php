<?php

namespace App\Http\Controllers\Administration\Entity;

use App\Http\Controllers\Controller;
use App\Models\Administration\Entity\EntityModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use HTMLPurifier;
use HTMLPurifier_Config;

class SaveEntityController extends Controller
{
    public function save(Request $request)
    {
        try {
            $config   = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            // Si viene id es edición; si NO viene, es creación (DB lo genera automáticamente)
            $id = $request->has('id') ? (int) $request->input('id') : null;

            // Normaliza/limpia
            $collapse = fn(string $s) => preg_replace('/\s+/', ' ', trim($s));
            $abrev        = mb_strtoupper($collapse((string)$request->abrev), 'UTF-8');
            $descripcion  = mb_strtoupper($collapse((string)$request->descripcion), 'UTF-8');
            $claveEntidad = mb_strtoupper($collapse((string)$request->clave_entidad), 'UTF-8');
            $estatus      = in_array($request->estatus, [true, 1, '1'], true);

            // Purifica
            $abrev        = $purifier->purify($abrev);
            $descripcion  = $purifier->purify($descripcion);
            $claveEntidad = $purifier->purify($claveEntidad);

            // Validación: usa el MODELO para evitar el error de “connection [catalogo]”
            $rules = [
                'abrev'         => ['required', 'max:50'],
                'descripcion'   => ['required', 'max:255'],
                'clave_entidad' => [
                    'required',
                    'max:100',
                    Rule::unique(EntityModel::class, 'clave_entidad')
                        ->ignore($id, 'id_cat_entidad'),
                ],
                'estatus'       => ['boolean'],
            ];

            $messages = [
                'abrev.required'         => 'La abreviatura es obligatoria.',
                'abrev.max'              => 'La abreviatura no debe exceder 50 caracteres.',
                'descripcion.required'   => 'La descripción es obligatoria.',
                'descripcion.max'        => 'La descripción no debe exceder 255 caracteres.',
                'clave_entidad.required' => 'La clave de entidad es obligatoria.',
                'clave_entidad.max'      => 'La clave de entidad no debe exceder 100 caracteres.',
                'clave_entidad.unique'   => 'La clave de entidad ya existe.',
            ];

            $validatorData = [
                'abrev'         => $abrev,
                'descripcion'   => $descripcion,
                'clave_entidad' => $claveEntidad,
                'estatus'       => $estatus,
            ];
            validator($validatorData, $rules, $messages)->validate();

            // Datos para BD
            $data = [
                'abrev'         => $abrev,
                'descripcion'   => $descripcion,
                'clave_entidad' => $claveEntidad,
                'estatus'       => $estatus,
            ];

            if ($id) {
                // UPDATE
                EntityModel::where('id_cat_entidad', $id)->update($data);

                return response()->json([
                    'status'  => true,
                    'message' => 'Entidad actualizada correctamente.',
                    'id'      => $id,
                ], 200);
            } else {
                // CREATE (DB asigna id automáticamente)
                $entity = EntityModel::create($data);

                return response()->json([
                    'status'  => true,
                    'message' => 'Entidad guardada correctamente.',
                    'id'      => (int) $entity->getKey(),
                ], 200);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            \Log::error('SaveEntityController@save', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'input' => $request->all(),
            ]);
            return response()->json([
                'status'  => false,
                'message' => 'Error al guardar la entidad.',
            ], 200);
        }
    }
}





