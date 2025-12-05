<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\MailController as MailHelper;
use App\Models\Cv\Empleado;
use App\Models\Cv\CvTokenAcceso;
use App\Models\Cv\CvExperienciaLaboral;
use App\Models\Cv\CvEstudiosAcademicos;
use App\Models\Cv\CvCursosCapacitaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WizardController extends Controller
{
    /**
     * Paso 1: enviar token al correo
     */
    public function sendToken(Request $request)
    {
        $data = $request->validate([
            'curp'   => 'required|string|max:18',
            'correo' => 'required|email|max:150',
        ]);

        $empleado = Empleado::where('curp', $data['curp'])->first();

        if (!$empleado) {
            return response()->json([
                'ok'      => false,
                'message' => 'No se encontró un empleado con esa CURP.',
            ], 404);
        }

        $token = (string) random_int(100000, 999999);

        CvTokenAcceso::create([
            'curp'      => $data['curp'],
            'correo'    => $data['correo'],
            'token'     => $token,
            'expira_en' => Carbon::now()->addMinutes(15),
        ]);

        $nombreCompleto = trim("{$empleado->nombre} {$empleado->primer_apellido} {$empleado->segundo_apellido}");

        $html = "
            <p>Hola <strong>{$nombreCompleto}</strong>,</p>
            <p>Tu código de acceso para continuar con el registro de tu CV es:</p>
            <p style=\"font-size:24px;font-weight:bold;\">{$token}</p>
            <p>Este código es válido por 15 minutos.</p>
            <p>Para continuar con tu registro, entra al siguiente enlace:</p>
            <p>
                <a href=\"" . url('/registro-personal-cv/public/registro-cv') . "\" target=\"_blank\">
                    Ir al registro de CV
                </a>
            </p>
            <p>Si tú no solicitaste este código, puedes ignorar este mensaje.</p>
        ";

        $mailData = [
            'affair'  => 'Código de acceso para registro de CV',
            'mail'    => $data['correo'],
            'content' => $html,
        ];

        $mailer  = new MailHelper();
        $enviado = $mailer->sendMail($mailData);

        if (!$enviado) {
            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo enviar el correo con el código. Verifica la configuración de correo.',
            ], 500);
        }

        return response()->json([
            'ok'         => true,
            'message'    => 'Se envió un código de verificación a tu correo.',
            // SOLO para pruebas locales, puedes comentar esto en producción:
            'token_demo' => app()->environment('local') ? $token : null,
        ]);
    }

    /**
     * Paso 2: validar token
     */
    public function validateToken(Request $request)
    {
        $data = $request->validate([
            'curp'   => 'required|string|max:18',
            'correo' => 'required|email|max:150',
            'token'  => 'required|string|max:10',
        ]);

        $now = Carbon::now();

        $registro = CvTokenAcceso::where('curp', $data['curp'])
            ->where('correo', $data['correo'])
            ->where('token', $data['token'])
            ->whereNull('usado_en')
            ->where('expira_en', '>=', $now)
            ->latest('creado_en')
            ->first();

        if (!$registro) {
            return response()->json([
                'ok'      => false,
                'message' => 'Token inválido o expirado.',
            ], 422);
        }

        $registro->usado_en = $now;
        $registro->save();

        $empleado = Empleado::where('curp', $data['curp'])->first();

        return response()->json([
            'ok'       => true,
            'empleado' => $empleado,
        ]);
    }

    /**
     * Paso 3: datos personales
     */
    public function saveDatosPersonales(Request $request)
    {
        $data = $request->validate([
            'curp'            => 'required|string|max:18',
            'nombres'         => 'required|string|max:150',
            'primer_apellido' => 'required|string|max:150',
            'segundo_apellido'=> 'nullable|string|max:150',
            'puesto_actual'   => 'nullable|string|max:150',
            'fecha_inicio'    => 'nullable|date',
            'area_adscripcion'=> 'nullable|string|max:150',
        ]);

        $empleado = Empleado::where('curp', $data['curp'])->firstOrFail();

        $empleado->nombre              = $data['nombres'];
        $empleado->primer_apellido     = $data['primer_apellido'];
        $empleado->segundo_apellido    = $data['segundo_apellido'] ?? null;
        $empleado->puesto_actual       = $data['puesto_actual'] ?? null;
        $empleado->fecha_inicio_puesto = $data['fecha_inicio'] ?? null;
        $empleado->area_adscripcion    = $data['area_adscripcion'] ?? null;
        $empleado->estatus_cv          = 1; // En edición
        $empleado->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Paso 4: experiencias laborales
     */
    public function saveExperiencias(Request $request)
    {
        $data = $request->validate([
            'curp'                       => 'required|string|max:18',
            'experiencias'               => 'required|array|min:1|max:3',
            'experiencias.*.fecha_inicio'=> 'nullable|date',
            'experiencias.*.fecha_termino'=> 'nullable|date',
            'experiencias.*.sector'      => 'nullable|string|max:20',
            'experiencias.*.puesto'      => 'nullable|string|max:150',
            'experiencias.*.institucion' => 'nullable|string|max:200',
            'experiencias.*.campo'       => 'nullable|string|max:100',
        ]);

        $empleado = Empleado::where('curp', $data['curp'])->firstOrFail();

        CvExperienciaLaboral::where('id_tbl_empleados', $empleado->id_tbl_empleados)->delete();

        foreach ($data['experiencias'] as $i => $exp) {
            CvExperienciaLaboral::create([
                'id_tbl_empleados'  => $empleado->id_tbl_empleados,
                'fecha_inicio'      => $exp['fecha_inicio'] ?? null,
                'fecha_termino'     => $exp['fecha_termino'] ?? null,
                'sector'            => $exp['sector'] ?? null,
                'puesto'            => $exp['puesto'] ?? null,
                'institucion'       => $exp['institucion'] ?? null,
                'campo_experiencia' => $exp['campo'] ?? null,
                'orden'             => $i + 1,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Paso 5: estudios académicos
     */
    public function saveEstudios(Request $request)
    {
        $data = $request->validate([
            'curp'               => 'required|string|max:18',
            'institucion'        => 'nullable|string|max:200',
            'pais'               => 'nullable|string|max:100',
            'nivel'              => 'nullable|string|max:100',
            'numero_cedula'      => 'nullable|string|max:50',
            'carrera_generica'   => 'nullable|string|max:150',
            'carrera_especifica' => 'nullable|string|max:150',
            'area_estudios'      => 'nullable|string|max:150',
        ]);

        $empleado = Empleado::where('curp', $data['curp'])->firstOrFail();

        $estudios = CvEstudiosAcademicos::firstOrNew([
            'id_tbl_empleados' => $empleado->id_tbl_empleados,
        ]);

        $payload = collect($data)->except(['curp'])->toArray();

        $estudios->fill($payload);
        $estudios->id_tbl_empleados = $empleado->id_tbl_empleados;
        $estudios->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Paso 6: cursos y capacitaciones
     */
    public function saveCursos(Request $request)
    {
        $data = $request->validate([
            'curp'                 => 'required|string|max:18',
            'cursos'               => 'required|array|min:1|max:3',
            'cursos.*.periodo'     => 'nullable|string|max:100',
            'cursos.*.nombre'      => 'nullable|string|max:200',
            'cursos.*.institucion' => 'nullable|string|max:200',
            'enviar'               => 'nullable|boolean',
        ]);

        $empleado = Empleado::where('curp', $data['curp'])->firstOrFail();

        CvCursosCapacitaciones::where('id_tbl_empleados', $empleado->id_tbl_empleados)->delete();

        foreach ($data['cursos'] as $i => $curso) {
            CvCursosCapacitaciones::create([
                'id_tbl_empleados' => $empleado->id_tbl_empleados,
                'periodo'          => $curso['periodo'] ?? null,
                'nombre_curso'     => $curso['nombre'] ?? null,
                'institucion'      => $curso['institucion'] ?? null,
                'orden'            => $i + 1,
            ]);
        }

        if (!empty($data['enviar'])) {
            $empleado->estatus_cv = 2; // Enviado
            $empleado->save();
        }

        return response()->json(['ok' => true]);
    }
}
