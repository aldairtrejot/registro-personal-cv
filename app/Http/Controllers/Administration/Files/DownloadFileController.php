<?php

namespace App\Http\Controllers\Administration\Files;

use App\Http\Controllers\Controller;

class DownloadFileController extends Controller
{
    public function download(string $filename)
    {
        try {
            $basePath = rtrim(str_replace('\\', '/', env('DESKTOP_UPLOAD_PATH', '')), '/');
            $safeName = basename($filename);
            $path = $basePath . '/' . $safeName;

            if (!file_exists($path)) {
                return response()->json(false, 404);
            }

            return response()->download($path);
        } catch (\Throwable $e) {
            \Log::error('Download file error', ['exception' => $e]);
            return response()->json(false, 500);
        }
    }
}
