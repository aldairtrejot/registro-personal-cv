<?php
// app/Http/Controllers/Administration/Files/ViewFileController.php
namespace App\Http\Controllers\Administration\Files;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File as Fs;
use Symfony\Component\HttpFoundation\Response;

class ViewFileController extends Controller
{
    public function view(string $filename)
    {
        // 🔐 doble verificación
        if (!Auth::check()) {
            // 401 (o redirigir a login si prefieres)
            abort(Response::HTTP_UNAUTHORIZED, 'Debes iniciar sesión para ver este archivo.');
        }

        // Normaliza separadores
        $filename = trim(str_replace('\\', '/', $filename), '/');

        // Intentar con o sin .pdf
        $candidatos = [$filename];
        if (!str_ends_with(strtolower($filename), '.pdf')) {
            $candidatos[] = $filename . '.pdf';
        }

        // 1) Ruta absoluta (debe ser fuera de public/)
        $basePath = rtrim(str_replace('\\', '/', (string) env('DESKTOP_UPLOAD_PATH')), '/');
        if ($basePath !== '') {
            foreach ($candidatos as $name) {
                $absolute = $basePath . '/' . $name;
                if (Fs::exists($absolute)) {
                    return response()->file($absolute);
                }
            }
        }

        // 2) Alternativas en storage/app/*
        $bases = ['cloud', 'profesionalizacion', 'uploads', 'docs'];
        foreach ($bases as $b) {
            foreach ($candidatos as $name) {
                $path = "{$b}/{$name}";
                if (Storage::disk('local')->exists($path)) {
                    return response()->file(Storage::disk('local')->path($path));
                }
            }
        }

        abort(Response::HTTP_NOT_FOUND, 'Archivo no encontrado');
    }
}

