<?php

namespace App\Http\Controllers\Administration\Files;

use App\Http\Controllers\Controller;

class DeleteFileController extends Controller
{
    public function delete(string $filename)
    {
        try {
            $basePath = rtrim(str_replace('\\', '/', env('DESKTOP_UPLOAD_PATH', '')), '/');
            $safeName = basename($filename);
            $path = $basePath . '/' . $safeName;

            if (!file_exists($path)) {
                return response()->json(false, 404);
            }

            if (!@unlink($path)) {
                return response()->json(false, 500);
            }

            return response()->json(['ok' => true, 'message' => 'Archivo eliminado correctamente.']);
        } catch (\Throwable $e) {
            \Log::error('Delete file error', ['exception' => $e]);
            return response()->json(false, 500);
        }
    }
}

