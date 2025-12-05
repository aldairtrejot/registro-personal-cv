<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\Cv\Empleado;
use App\Models\Cv\CvExperienciaLaboral;
use App\Models\Cv\CvEstudiosAcademicos;
use App\Models\Cv\CvCursosCapacitaciones;
use Illuminate\Http\Request;

class RevisorController extends Controller
{
    public function index(Request $request)
    {
        $query = Empleado::query()->where('estatus_cv', '>', 0);

        if ($request->filled('status')) {
            $map = [
                'edicion'   => 1,
                'enviado'   => 2,
                'aprobado'  => 3,
                'rechazado' => 4,
            ];
            if (isset($map[$request->status])) {
                $query->where('estatus_cv', $map[$request->status]);
            }
        }

        if ($request->filled('q')) {
            $q = strtolower($request->q);
            $query->where(function ($qBuilder) use ($q) {
                $qBuilder->whereRaw('LOWER(curp) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(nombre) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(primer_apellido) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(segundo_apellido) LIKE ?', ["%{$q}%"]);
            });
        }

        $empleados = $query
            ->orderBy('nombre')
            ->limit(100)
            ->get()
            ->map(function (Empleado $e) {
                return [
                    'id'                 => $e->id_tbl_empleados,
                    'nombre'             => trim("{$e->nombre} {$e->primer_apellido} {$e->segundo_apellido}"),
                    'curp'               => $e->curp,
                    'area'               => $e->area_adscripcion,
                    'fechaActualizacion' => $e->fecha_inicio_puesto,
                    'status'             => $this->cvStatusLabel($e->estatus_cv),
                ];
            });

        return response()->json($empleados);
    }

    public function show($id)
    {
        $empleado = Empleado::findOrFail($id);

        $experiencias = CvExperienciaLaboral::where('id_tbl_empleados', $id)
            ->orderBy('orden')
            ->get();

        $estudios = CvEstudiosAcademicos::where('id_tbl_empleados', $id)->first();
        $cursos   = CvCursosCapacitaciones::where('id_tbl_empleados', $id)
            ->orderBy('orden')
            ->get();

        return response()->json([
            'empleado'     => $empleado,
            'experiencias' => $experiencias,
            'estudios'     => $estudios,
            'cursos'       => $cursos,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:edicion,enviado,aprobado,rechazado',
        ]);

        $empleado = Empleado::findOrFail($id);

        $map = [
            'edicion'   => 1,
            'enviado'   => 2,
            'aprobado'  => 3,
            'rechazado' => 4,
        ];

        $empleado->estatus_cv = $map[$data['status']];
        $empleado->save();

        return response()->json(['ok' => true]);
    }

    private function cvStatusLabel($estatus_cv)
    {
        return match ((int) $estatus_cv) {
            1       => 'edicion',
            2       => 'enviado',
            3       => 'aprobado',
            4       => 'rechazado',
            default => 'sin_cv',
        };
    }
}
