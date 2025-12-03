<?php

namespace App\Http\Controllers\Administration\Files;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File as Fs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UploadFileController extends Controller
{
    /**
     * Sube un PDF (<=2MB) a la carpeta de DESKTOP_UPLOAD_PATH, actualiza el UUID del documento
     * (id_ctrl_documentos_profesionalizacion) y, si ya no faltan documentos, avanza el proceso a estatus 2.
     * Valida:
     *  - Usuario dueño del proceso
     *  - Proceso en estatus=1
     *  - Ventana abierta (fecha_bloqueo NULL o >= hoy)
     *  - Documento no aceptado (id_cat_estatus_documento != 2)
     */
    public function upload(Request $request)
    {
        try {
            // 0) Validación de entrada (solo PDF y <= 2MB)
            $request->validate([
                'file'   => ['required', 'file', 'mimes:pdf', 'max:2048'],
                'doc_id' => ['required', 'integer'],
            ], [
                'file.required' => 'El archivo es obligatorio.',
                'file.mimes'    => 'Solo se permite PDF.',
                'file.max'      => 'El archivo no puede exceder 2MB.',
            ]);

            $docId = (int) $request->input('doc_id');
            if ($docId <= 0) {
                return response()->json(['ok' => false, 'message' => 'Identificador del documento inválido.'], 422);
            }

            $userId = (int) Auth::id();
            $tz     = config('app.timezone', 'America/Mexico_City');
            $today  = Carbon::today($tz);

            // 1) Contexto del documento (pertenencia + proceso + ventana + tipo + rfc)
            $ctx = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion as c')
                ->join('catalogo.cat_tipo_documento as d', 'c.id_cat_tipo_documento', '=', 'd.id_cat_tipo_documento')
                ->join('profesionalizacion.tbl_profesionalizacion as p', 'c.id_tbl_profesionalizacion', '=', 'p.id_tbl_profesionalizacion')
                ->join('profesionalizacion.tbl_empleados as e', 'p.id_tbl_empleados', '=', 'e.id_tbl_empleados')
                ->join('administracion.users as u', 'e.id_tbl_empleados', '=', 'u.id_tbl_empleado')
                ->where('c.id_ctrl_documentos_profesionalizacion', $docId)
                ->where('u.id', $userId)
                ->select([
                    'c.id_ctrl_documentos_profesionalizacion',
                    'c.id_tbl_profesionalizacion',
                    'c.id_cat_tipo_documento',
                    'c.id_cat_estatus_documento',
                    'c.uuid',
                    'd.clave as tipo_clave',
                    'p.id_cat_estatus',
                    'u.fecha_bloqueo',
                    'e.rfc',
                ])
                ->first();

            if (!$ctx) {
                return response()->json(['ok' => false, 'message' => 'Documento no encontrado para este usuario.'], 404);
            }

            // 2) Reglas previas: estatus=1 y ventana abierta
            $estatusOk = ((int) $ctx->id_cat_estatus === 1);
            $ventanaOk = (is_null($ctx->fecha_bloqueo) || Carbon::parse($ctx->fecha_bloqueo, $tz)->greaterThanOrEqualTo($today));

            if (!$estatusOk || !$ventanaOk) {
                $razones = [];
                if (!$estatusOk) $razones[] = 'estatus distinto de 1';
                if (!$ventanaOk) $razones[] = 'fecha_bloqueo vencida';
                return response()->json([
                    'ok'      => false,
                    'message' => 'No se pudo completar la acción. Por favor, vuelve a intentarlo.',
                ], 403);
            }

            // 3) Política de reemplazo: si ya fue aceptado (2), NO permitir reemplazo
            if ((int) $ctx->id_cat_estatus_documento === 2) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'El documento ya fue aceptado; no es posible reemplazarlo.',
                ], 409);
            }

            // 4) Preparar nombre de archivo
            $file = $request->file('file');
            $rfc   = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $ctx->rfc));
            $clave = strtoupper(preg_replace('/[^\w\-]/', '', (string) $ctx->tipo_clave));
            $now   = Carbon::now($tz);
            $date  = $now->format('Ymd');
            $time  = $now->format('His');
            $ext   = strtolower($file->getClientOriginalExtension() ?: 'pdf');

            $baseName   = "{$clave}_{$rfc}_{$date}_{$time}";
            $storedName = "{$baseName}.{$ext}";

            // 5) Carpeta destino (DESKTOP_UPLOAD_PATH)
            $basePath = rtrim(str_replace('\\', '/', (string) env('DESKTOP_UPLOAD_PATH')), '/');
            if ($basePath === '') {
                return response()->json(['ok' => false, 'message' => 'Ruta no configurada (DESKTOP_UPLOAD_PATH).'], 500);
            }
            if (!Fs::exists($basePath) && !Fs::makeDirectory($basePath, 0755, true)) {
                return response()->json(['ok' => false, 'message' => 'No se pudo crear la carpeta destino.'], 500);
            }
            if (!is_writable($basePath)) {
                return response()->json(['ok' => false, 'message' => 'La carpeta destino no es escribible.'], 500);
            }

            // Evitar colisión
            $targetPath = $basePath . '/' . $storedName;
            if (Fs::exists($targetPath)) {
                $i = 1;
                do {
                    $storedName = "{$baseName}_{$i}.{$ext}";
                    $targetPath = $basePath . '/' . $storedName;
                    $i++;
                } while (Fs::exists($targetPath));
            }

            // 6) Mover archivo
            $file->move($basePath, $storedName);
            if (!Fs::exists($targetPath)) {
                return response()->json(['ok' => false, 'message' => 'No se pudo guardar el archivo en disco.'], 500);
            }

            // 7) Guardar en BD (transacción): actualizar doc y, si corresponde, avanzar proceso a 2
            $finalized = false;
            $faltantes = null;

            try {
                DB::beginTransaction();

                // 7.1) Actualiza UUID e "en proceso" (3)
                $affected = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
                    ->where('id_ctrl_documentos_profesionalizacion', $docId)
                    ->update([
                        'uuid'                     => $storedName,
                        'id_cat_estatus_documento' => 3, // En proceso
                        'id_usuario_actualizacion' => $userId,
                        'actualizado_en'           => Carbon::now($tz)->format('Y-m-d H:i:s'),
                    ]);

                if ($affected === 0) {
                    DB::rollBack();
                    @Fs::delete($targetPath);
                    return response()->json(['ok' => false, 'message' => 'No se pudo actualizar el documento.'], 409);
                }

                // 7.2) ¿Faltan documentos con uuid NULL?
                $faltantes = (int) DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
                    ->where('id_tbl_profesionalizacion', $ctx->id_tbl_profesionalizacion)
                    ->whereNull('uuid')
                    ->count();

                // 7.3) Si no faltan y la ventana sigue ok, avanzar a 2
                if ($faltantes === 0 && $estatusOk && $ventanaOk) {
                    $updated = DB::table('profesionalizacion.tbl_profesionalizacion')
                        ->where('id_tbl_profesionalizacion', $ctx->id_tbl_profesionalizacion)
                        ->where('id_cat_estatus', 1)
                        ->update([
                            'id_cat_estatus'           => 2,
                            'id_usuario_actualizacion' => $userId,
                            'actualizado_en'           => Carbon::now($tz)->format('Y-m-d H:i:s'),
                        ]);
                    $finalized = $updated > 0;
                }

                DB::commit();
            } catch (\Throwable $dbEx) {
                DB::rollBack();
                @Fs::delete($targetPath);

                Log::error('UploadFileController DB error', [
                    'msg'  => $dbEx->getMessage(),
                    'file' => $dbEx->getFile(),
                    'line' => $dbEx->getLine(),
                ]);

                return response()->json(['ok' => false, 'message' => 'Error al guardar metadatos de documento.'], 500);
            }

            // 8) URL de vista: SIEMPRE cloud/view
            $viewUrl = route('files.cloud.view', ['filename' => $storedName], false);

            // 9) Respuesta OK
            return response()->json([
                'ok'          => true,
                'message'     => 'Archivo subido correctamente',
                'uuid'        => $storedName,
                'filename'    => $storedName,
                'displayName' => $storedName,
                'viewUrl'     => $viewUrl,
                'id_tbl_profesionalizacion' => (int) $ctx->id_tbl_profesionalizacion,
                'finalized'   => $finalized,
                'new_status'  => $finalized ? 2 : (int) $ctx->id_cat_estatus,
                'missing'     => (int) $faltantes,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Hay errores de validación.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Upload error', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['ok' => false, 'message' => 'No se pudo completar la acción. Por favor, vuelve a intentarlo.'], 500);
        }
    }
}






