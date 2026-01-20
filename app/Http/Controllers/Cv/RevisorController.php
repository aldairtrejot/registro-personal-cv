<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\MailController as MailHelper;
use App\Models\Cv\Empleado;
use App\Models\Cv\CvExperienciaLaboral;
use App\Models\Cv\CvEstudiosAcademicos;
use App\Models\Cv\CvCursosCapacitaciones;
use App\Models\Cv\CvTokenAcceso;
use Illuminate\Http\Request;

class RevisorController extends Controller
{
    public function index(Request $request)
    {
        $query = Empleado::query()->where('estatus_cv', '>', 0);

        if ($request->filled('status')) {
            $map = [
                'edicion' => 1,
                'enviado' => 2,
                'aprobado' => 3,
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
                    'id' => $e->id_tbl_empleados,
                    'nombre' => trim("{$e->nombre} {$e->primer_apellido} {$e->segundo_apellido}"),
                    'curp' => $e->curp,
                    'area' => $e->area_adscripcion,
                    'fechaActualizacion' => optional($e->updated_at)->format('d/m/Y H:i'),
                    'status' => $this->cvStatusLabel($e->estatus_cv),
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

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:edicion,enviado,aprobado,rechazado',
            'motivo' => 'required_if:status,rechazado|nullable|string|max:500',
        ]);

        $empleado = Empleado::findOrFail($id);

        $map = [
            'edicion' => 1,
            'enviado' => 2,
            'aprobado' => 3,
            'rechazado' => 4,
        ];

        $status = $data['status'];
        $motivo = trim((string)($data['motivo'] ?? ''));

        $empleado->estatus_cv = $map[$status];
        $empleado->save();

        if ($status === 'rechazado') {
            $this->enviarCorreoRechazo($empleado, $motivo);
        }

        return response()->json(['ok' => true]);
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

    private function enviarCorreoRechazo(Empleado $empleado, string $motivo): void
    {
        $ultimoToken = CvTokenAcceso::where('curp', $empleado->curp)
            ->orderByDesc('creado_en')
            ->first();

        if (!$ultimoToken || !$ultimoToken->correo) {
            return;
        }

        $correo = $ultimoToken->correo;
        $nombreCompleto = trim("{$empleado->nombre} {$empleado->primer_apellido} {$empleado->segundo_apellido}");
        $motivoSafe = e($motivo);

        $html = "
            <p>Hola <strong>{$nombreCompleto}</strong>,</p>
            <p>Tu registro de CV fue <strong>rechazado</strong> durante el proceso de revisión.</p>
            <p><strong>Motivo:</strong><br>{$motivoSafe}</p>
            <p>Por favor, ingresa nuevamente al sistema para corregir tu información y volver a enviarla.</p>
            <p>Para continuar con la corrección, ingresa al siguiente enlace y solicita un nuevo código de acceso con tu CURP:</p>
            <p>
                <a href=\"" . route('registro.wizard') . "\" target=\"_blank\">
                    Ir al registro de CV
                </a>
            </p>
            <p>Una vez que hayas corregido tus datos, recuerda finalizar y enviar el CV para que pueda ser revisado nuevamente.</p>
        ";

        $mailData = [
            'affair' => 'Tu registro de CV requiere correcciones',
            'mail' => $correo,
            'content' => $html,
        ];

        $mailer = new MailHelper();
        $mailer->sendMail($mailData);
    }
}
