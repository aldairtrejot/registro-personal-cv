<?php

namespace App\Http\Controllers\Administration\Files;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ValidateFileController extends Controller
{
    // Alias de compatibilidad
    public function validateFile(Request $request)
    {
        return $this->validateUpload($request);
    }

    /**
     * POST /files/validate
     *
     * Body:
     *  - file (required, pdf, max 2MB)
     *
     * Valida:
     *  - Usuario autenticado
     *  - Fecha de bloqueo (NULL o >= hoy)
     *  - Que el archivo sea PDF y no exceda 2MB
     */
    public function validateUpload(Request $request)
    {
        try {
            // 1) Autenticación
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'ok'      => false,
                    'status'  => false,
                    'code'    => 401,
                    'message' => 'No autenticado.',
                ], 401);
            }

            // 2) Fecha de bloqueo
            $tz    = config('app.timezone', 'America/Mexico_City');
            $today = Carbon::today($tz);

            $row = DB::table('administracion.users')
                ->select('fecha_bloqueo')
                ->where('id', $user->id)
                ->first();

            if ($row && $row->fecha_bloqueo) {
                $limit = Carbon::parse($row->fecha_bloqueo, $tz);
                // Si quieres bloquear también el MISMO día, cambia a: $limit->greaterThan($today)
                $ventanaOk = $limit->greaterThanOrEqualTo($today);
                if (!$ventanaOk) {
                    return response()->json([
                        'ok'            => false,
                        'status'        => false,
                        'code'          => 422,
                        'message'       => 'La fecha de carga ha expirado.',
                        'fecha_bloqueo' => $limit->toDateString(),
                    ], 422);
                }
            }

            // 3) Validación de archivo (solo PDF, máx 2MB)
            $request->validate([
                'file' => ['required', 'file', 'mimes:pdf', 'max:2048'], // 2 MB
            ], [
                'file.required' => 'El archivo es obligatorio.',
                'file.file'     => 'El archivo no es válido.',
                'file.mimes'    => 'Solo se permite PDF.',
                'file.max'      => 'El archivo no puede exceder 2MB.',
            ]);

            // 4) Respuesta OK (info básica útil para el front)
            $uploaded = $request->file('file');

            return response()->json([
                'ok'      => true,
                'status'  => true,
                'code'    => 200,
                'message' => null,
                'data'    => [
                    'original_name' => $uploaded->getClientOriginalName(),
                    'size_kb'       => round($uploaded->getSize() / 1024, 2),
                    'mimetype'      => $uploaded->getMimeType(),
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'status'  => false,
                'code'    => 422,
                'message' => 'Hay errores de validación en el archivo.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            Log::error('ValidateFileController.validateUpload error', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'ok'      => false,
                'status'  => false,
                'code'    => 500,
                'message' => 'No se pudo completar la acción. Por favor, vuelve a intentarlo.',
            ], 500);
        }
    }
}
