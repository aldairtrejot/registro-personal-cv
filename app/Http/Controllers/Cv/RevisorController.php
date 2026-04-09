<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Mail\CvRechazadoMail;
use App\Models\Cv\Empleado;
use App\Models\Cv\CvExperienciaLaboral;
use App\Models\Cv\CvEstudiosAcademicos;
use App\Models\Cv\CvCursosCapacitaciones;
use App\Models\Cv\CvTokenAcceso;
use App\Services\Cv\CvFolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RevisorController extends Controller
{
    public function index(Request $request)
    {
        $query = Empleado::query()
            ->with(['puesto'])
            ->where('estatus_cv', '>', 0);

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
                    ->orWhereRaw('LOWER(segundo_apellido) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(area_adscripcion) LIKE ?', ["%{$q}%"]);
            });
        }

        $empleados = $query
            ->orderBy('nombre')
            ->limit(100)
            ->get()
            ->map(function (Empleado $e) {
                return [
                    'id' => $e->id_tbl_empleados,
                    'nombre' => trim("{$e->nombre} {$e->primer_apellido} {$e->segundo_apellido}"),
                    'curp' => $e->curp,
                    'area' => $e->area_adscripcion,
                    'puesto' => $e->puesto_label,
                    'fechaActualizacion' => $e->actualizado_en
                        ? \Carbon\Carbon::parse($e->actualizado_en)->format('d/m/Y H:i')
                        : null,
                    'status' => $this->cvStatusLabel($e->estatus_cv),
                ];
            });

        return response()->json($empleados);
    }

    public function show($id)
    {
        $empleado = Empleado::with(['puesto'])->findOrFail($id);

        if (empty($empleado->puesto_actual)) {
            $empleado->setAttribute('puesto_actual', $empleado->puesto_label);
        }

        $empleado->setAttribute(
            'fecha_inicio_puesto',
            $empleado->fecha_inicio_puesto
                ? \Carbon\Carbon::parse($empleado->fecha_inicio_puesto)->format('Y-m-d')
                : null
        );

        $experiencias = CvExperienciaLaboral::where('id_tbl_empleados', $id)
            ->orderBy('orden')
            ->get();

        $estudios = CvEstudiosAcademicos::where('id_tbl_empleados', $id)->first();

        $cursos = CvCursosCapacitaciones::where('id_tbl_empleados', $id)
            ->orderBy('orden')
            ->get();

        return response()->json([
            'empleado' => $empleado,
            'experiencias' => $experiencias,
            'estudios' => $estudios,
            'cursos' => $cursos,
        ]);
    }

    public function catalogoPuestos()
    {
        $puestos = DB::table('profesionalizacion.cat_puestos')
            ->select('id_puesto', 'nombre')
            ->orderBy('id_puesto', 'asc')
            ->get();

        return response()->json($puestos);
    }

    public function updatePuesto(Request $request, $id)
    {
        $data = $request->validate([
            'id_puesto' => 'required|integer|min:1',
        ], [
            'id_puesto.required' => 'Selecciona un puesto.',
            'id_puesto.integer' => 'El puesto seleccionado no es válido.',
        ]);

        $empleado = Empleado::with(['puesto'])->findOrFail($id);

        $puesto = DB::table('profesionalizacion.cat_puestos')
            ->select('id_puesto', 'nombre')
            ->where('id_puesto', (int) $data['id_puesto'])
            ->first();

        if (!$puesto) {
            return response()->json([
                'message' => 'El puesto seleccionado no existe en el catálogo.',
            ], 422);
        }

        $empleado->id_puesto = (int) $puesto->id_puesto;
        $empleado->puesto_actual = $puesto->nombre;
        $empleado->save();

        return response()->json([
            'ok' => true,
            'id_puesto' => (int) $empleado->id_puesto,
            'puesto_actual' => $puesto->nombre,
        ]);
    }

    public function updateStatus(Request $request, $id, CvFolioService $folioSvc)
    {
        $data = $request->validate([
            'status' => 'required|in:edicion,enviado,aprobado,rechazado',
            'motivo' => 'nullable|string|max:1000',
        ], [
            'status.required' => 'El estatus es obligatorio.',
            'status.in' => 'El estatus seleccionado no es válido.',
            'motivo.string' => 'El motivo debe ser texto.',
            'motivo.max' => 'El motivo no puede exceder 1000 caracteres.',
        ]);

        $status = $data['status'];
        $motivo = trim((string) ($data['motivo'] ?? ''));

        if ($status === 'rechazado' && $motivo === '') {
            return response()->json([
                'message' => 'El motivo de rechazo es obligatorio.',
            ], 422);
        }

        $map = [
            'edicion'   => 1,
            'enviado'   => 2,
            'aprobado'  => 3,
            'rechazado' => 4,
        ];

        $empleado = Empleado::findOrFail($id);

        DB::beginTransaction();

        try {
            $empleado->estatus_cv = $map[$status];

            if ($status === 'aprobado') {
                $consec = $folioSvc->asignarONormalizarAlAprobar((int) $empleado->id_tbl_empleados);
                $empleado->folio_cv = $consec;
                $empleado->motivo_rechazo_cv = null;
            } elseif ($status === 'rechazado') {
                $empleado->motivo_rechazo_cv = $motivo;
            } else {
                $empleado->motivo_rechazo_cv = null;
            }

            $empleado->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'message' => 'No se pudo actualizar el estatus del CV.',
            ], 500);
        }

        $correoEnviado = null;
        $message = 'Estatus actualizado correctamente.';

        if ($status === 'rechazado') {
            $correoEnviado = $this->enviarCorreoRechazo($empleado, $motivo);

            $message = $correoEnviado
                ? 'CV rechazado y notificación enviada.'
                : 'CV rechazado, pero no se pudo enviar el correo.';
        }

        if ($status === 'aprobado') {
            $message = 'CV aprobado correctamente.';
        }

        return response()->json([
            'ok' => true,
            'folio' => $empleado->folio_cv ?? null,
            'correo_enviado' => $correoEnviado,
            'message' => $message,
            'motivo_rechazo_cv' => $empleado->motivo_rechazo_cv,
        ]);
    }

    private function cvStatusLabel($estatus_cv)
    {
        return match ((int) $estatus_cv) {
            1 => 'edicion',
            2 => 'enviado',
            3 => 'aprobado',
            4 => 'rechazado',
            default => 'sin_cv',
        };
    }

    private function enviarCorreoRechazo(Empleado $empleado, string $motivo): bool
    {
        $ultimoToken = CvTokenAcceso::where('curp', $empleado->curp)
            ->orderByDesc('creado_en')
            ->first();

        $correo = null;
        $origenCorreo = null;

        if ($ultimoToken && !empty($ultimoToken->correo)) {
            $correo = trim((string) $ultimoToken->correo);
            $origenCorreo = 'cv_token_acceso';
        } elseif (!empty($empleado->correo)) {
            $correo = trim((string) $empleado->correo);
            $origenCorreo = 'tbl_empleados';
        }

        Log::info('Resolución de correo para rechazo CV', [
            'empleado_id' => $empleado->id_tbl_empleados,
            'curp' => $empleado->curp,
            'correo_token' => $ultimoToken->correo ?? null,
            'correo_tbl_empleados' => $empleado->correo ?? null,
            'correo_final' => $correo,
            'origen_correo' => $origenCorreo,
        ]);

        if (!$correo) {
            Log::warning('No se encontró correo destino para rechazo CV', [
                'empleado_id' => $empleado->id_tbl_empleados,
                'curp' => $empleado->curp,
            ]);
            return false;
        }

        try {
            Mail::to($correo)->send(new CvRechazadoMail($empleado, $motivo));

            Log::info('Correo de rechazo enviado con Mailable', [
                'empleado_id' => $empleado->id_tbl_empleados,
                'curp' => $empleado->curp,
                'correo_final' => $correo,
                'origen_correo' => $origenCorreo,
            ]);

            return true;
        } catch (\Throwable $e) {
            report($e);

            Log::error('Error enviando correo de rechazo con Mailable', [
                'empleado_id' => $empleado->id_tbl_empleados,
                'curp' => $empleado->curp,
                'correo_final' => $correo,
                'origen_correo' => $origenCorreo,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function updateFolio(Request $request, $id, CvFolioService $folioSvc)
    {
        $data = $request->validate([
            'folio' => 'required|integer|min:1',
        ], [
            'folio.required' => 'Captura un folio.',
            'folio.integer'  => 'El folio debe ser numérico.',
            'folio.min'      => 'El folio debe ser mayor a 0.',
        ]);

        $empleado = Empleado::findOrFail($id);

        $folio = $folioSvc->asignarFolioManual((int) $empleado->id_tbl_empleados, (int) $data['folio'], 'manual');

        $empleado->folio_cv = $folio;
        $empleado->save();

        return response()->json([
            'ok' => true,
            'folio' => $folio,
        ]);
    }
}