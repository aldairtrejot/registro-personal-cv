<?php

namespace App\Http\Controllers\Administration\Designarch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ViewDesignarchController extends Controller
{
    /**
     * Vista principal del módulo.
     */
    public function design()
    {
        return view('administration.designarch.list');
    }

    /**
     * Sube los archivos enviados.
     * Directorio: storage/app/public/designarch/{subcarpeta}
     * Reglas: PDF/JPG/PNG hasta 5 MB por archivo.
     */
    public function upload(Request $request)
    {
        // Mapea los campos esperados desde Follow.vue => subcarpeta
        $fields = [
            'file_titulo_lic'      => 'titulo_licenciatura',
            'file_cedula_lic'      => 'cedula_licenciatura',
            'file_titulo_pos'      => 'titulo_posgrado',
            'file_cedula_pos'      => 'cedula_posgrado',
            'file_identificacion'  => 'identificacion_oficial',
            'file_antiguedad'      => 'antiguedad_rama',
            'file_constancia_func' => 'constancia_funciones',
            'file_trabajador_base' => 'trabajador_base',
        ];

        // Validación: todos opcionales; se sube lo que venga
        $rules = [];
        foreach ($fields as $input => $_) {
            $rules[$input] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']; // 5 MB
        }
        $validated = $request->validate($rules);

        $basePath = 'designarch';
        $saved    = [];

        foreach ($fields as $input => $subdir) {
            if ($request->hasFile($input)) {
                $file = $request->file($input);
                // Guarda en: storage/app/public/designarch/{subdir}/<hash>.<ext>
                $path = $file->store($basePath . '/' . $subdir, 'public');
                $saved[$input] = $path;
            }
        }

        // Si quisieras, aquí puedes persistir a BD los paths/uuids relacionados al usuario.

        // Respuesta: JSON si es AJAX/axios; redirect con flash si es form tradicional
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok'    => true,
                'msg'   => 'Archivos cargados correctamente.',
                'files' => $saved,
            ]);
        }

        return back()->with([
            'design_success' => 'Archivos cargados correctamente.',
            'design_files'   => $saved,
        ]);
    }
}
