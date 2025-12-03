<?php

namespace App\Http\Controllers\Administration\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File as Fs;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CedulaValidationController extends Controller
{
    /**
     * Sube el PDF de verificación de cédula para un documento específico
     * y lo guarda en profesionalizacion.ctrl_documentos_profesionalizacion.uuid_verificacion_cedula
     * El nombre físico se fuerza con prefijo VAL_.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'doc_id' => 'required|integer|min:1',
                'file'   => 'required|file|mimes:pdf|max:2048', // 2MB
            ]);

            $docId = (int) $request->input('doc_id');

            // 1) Metadatos mínimos
            $meta = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion AS c')
                ->join('catalogo.cat_tipo_documento AS d', 'd.id_cat_tipo_documento', '=', 'c.id_cat_tipo_documento')
                ->join('profesionalizacion.tbl_profesionalizacion AS p', 'p.id_tbl_profesionalizacion', '=', 'c.id_tbl_profesionalizacion')
                ->join('profesionalizacion.tbl_empleados AS e', 'e.id_tbl_empleados', '=', 'p.id_tbl_empleados')
                ->where('c.id_ctrl_documentos_profesionalizacion', $docId)
                ->selectRaw("
                    c.id_tbl_profesionalizacion,
                    d.clave AS tipo_clave,
                    COALESCE(e.rfc, '') AS rfc
                ")
                ->first();

            if (!$meta) {
                return response()->json(['status' => false, 'message' => 'No encontramos el documento.'], 404);
            }

            $tipoClave = strtoupper(preg_replace('/[^\w\-]/', '', (string) $meta->tipo_clave));
            $rfc       = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $meta->rfc));

            // 2) Armar nombre con prefijo VAL_
            $file = $request->file('file');
            $ext  = strtolower($file->getClientOriginalExtension() ?: 'pdf');

            $tz   = config('app.timezone', 'America/Mexico_City');
            $now  = Carbon::now()->timezone($tz);
            $date = $now->format('Ymd');
            $time = $now->format('His');

            $baseName   = "{$tipoClave}_{$rfc}_{$date}_{$time}";
            $storedName = 'VAL_' . $baseName . '.' . $ext;

            // 3) Carpeta destino (DESKTOP_UPLOAD_PATH)
            $basePath = rtrim(str_replace('\\', '/', (string) env('DESKTOP_UPLOAD_PATH')), '/');
            if (empty($basePath)) {
                return response()->json(['status' => false, 'message' => 'Falta configurar la carpeta de destino.'], 500);
            }
            if (!Fs::exists($basePath)) {
                if (!Fs::makeDirectory($basePath, 0755, true)) {
                    return response()->json(['status' => false, 'message' => 'No se pudo crear la carpeta de destino.'], 500);
                }
            }
            if (!is_writable($basePath)) {
                return response()->json(['status' => false, 'message' => 'No hay permisos para guardar en la carpeta de destino.'], 500);
            }

            // Evitar colisión
            $targetPath = $basePath . '/' . $storedName;
            if (Fs::exists($targetPath)) {
                $i = 1;
                do {
                    $storedName = 'VAL_' . $baseName . "_{$i}." . $ext;
                    $targetPath = $basePath . '/' . $storedName;
                    $i++;
                } while (Fs::exists($targetPath));
            }

            // 4) Mover archivo
            $file->move($basePath, $storedName);

            // 5) Guardar SOLO uuid_verificacion_cedula
            DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
                ->where('id_ctrl_documentos_profesionalizacion', $docId)
                ->update([
                    'uuid_verificacion_cedula' => $storedName,
                    'id_cat_estatus_documento' => DB::raw("COALESCE(id_cat_estatus_documento, 3)"),
                    'actualizado_en'           => now(),
                ]);

            // 6) URL de visualización
            $viewUrl = url("/cloud/view/{$storedName}");

            return response()->json([
                'status'                    => true,
                'message'                   => 'Archivo de verificación cargado',
                'uuid'                      => $storedName,
                'filename'                  => $storedName,
                'viewUrl'                   => $viewUrl,
                'id_tbl_profesionalizacion' => (int) $meta->id_tbl_profesionalizacion,
            ]);
        } catch (\Throwable $e) {
            Log::error('[CedulaValidationController@store] error', ['ex' => $e]);
            return response()->json([
                'status'  => false,
                'message' => 'No pudimos subir el archivo de verificación. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Elimina la verificación de cédula.
     * - Limpia profesionalizacion.ctrl_documentos_profesionalizacion.uuid_verificacion_cedula
     * - Elimina el archivo físico en DESKTOP_UPLOAD_PATH (si existe)
     * 🔒 Solo Admin(1) y Revisor(3) pueden ejecutar esta acción.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'doc_id' => ['required', 'integer', 'min:1'],
        ]);

        // 🔒 Valida rol por DB: administracion.rel_users_rol usando Auth::id()
        $userId = (int) (Auth::id() ?? 0);
        if ($userId <= 0) {
            return response()->json([
                'status'  => false,
                'message' => 'No autenticado.',
            ], Response::HTTP_FORBIDDEN);
        }

        $isAllowed = DB::table('administracion.rel_users_rol')
            ->where('id_users', $userId)
            ->whereIn('id_tbl_roles', [1, 3]) // 1: Admin, 3: Revisor
            ->exists();

        if (!$isAllowed) {
            return response()->json([
                'status'  => false,
                'message' => 'No autorizado para eliminar verificación.',
            ], Response::HTTP_FORBIDDEN);
        }

        $docId = (int) $request->input('doc_id');

        // Buscar documento SOLO por id_ctrl_documentos_profesionalizacion
        $doc = DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
            ->where('id_ctrl_documentos_profesionalizacion', $docId)
            ->first();

        if (!$doc) {
            return response()->json([
                'status'  => false,
                'message' => 'Documento no encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $uuid = $doc->uuid_verificacion_cedula ?? null;

        DB::beginTransaction();
        try {
            // Limpiar campo en BD
            DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
                ->where('id_ctrl_documentos_profesionalizacion', $docId)
                ->update([
                    'uuid_verificacion_cedula' => null,
                    'actualizado_en'           => now(),
                ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[CedulaValidationController@destroy] DB error', ['ex' => $e]);
            return response()->json([
                'status'  => false,
                'message' => 'No se pudo eliminar la verificación.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Borrar archivo físico (best-effort)
        if ($uuid) {
            try {
                $this->deleteFromDesktopUploadPath($uuid);
            } catch (\Throwable $e) {
                Log::warning('[CedulaValidationController@destroy] no se pudo borrar archivo físico', [
                    'uuid' => $uuid,
                    'ex'   => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Verificación de cédula eliminada.',
            'removed' => $uuid,
        ]);
    }

    /**
     * Elimina el archivo en DESKTOP_UPLOAD_PATH si existe (también prueba con .pdf).
     */
    protected function deleteFromDesktopUploadPath(string $filename): void
    {
        $filename = trim($filename, '/');
        $candidatos = [$filename];
        if (!str_ends_with(strtolower($filename), '.pdf')) {
            $candidatos[] = $filename . '.pdf';
        }

        $basePath = rtrim(str_replace('\\', '/', (string) env('DESKTOP_UPLOAD_PATH', '')), '/');
        if ($basePath === '') return;

        foreach ($candidatos as $name) {
            $absolute = $basePath . '/' . $name;
            if (Fs::exists($absolute)) {
                @Fs::delete($absolute);
            }
            $absoluteLower = $basePath . '/' . strtolower($name);
            if ($absoluteLower !== $absolute && Fs::exists($absoluteLower)) {
                @Fs::delete($absoluteLower);
            }
        }
    }
}

