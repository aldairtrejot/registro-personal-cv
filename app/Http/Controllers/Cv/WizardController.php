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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WizardController extends Controller
{
    private const CURP_REGEX = '/^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]{2}$/';

    private function normalizeCurp(string $curp): string
    {
        return strtoupper(trim($curp));
    }

    // ✅ Permitir null cuando CV_REQUIRE_TOKEN=false (solo CURP)
    private function normalizeMail(?string $correo): ?string
    {
        if ($correo === null) return null;
        $correo = strtolower(trim($correo));
        return $correo === '' ? null : $correo;
    }

    private function upper(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim((string) $v);
        return $v === '' ? null : Str::upper($v);
    }

    private function assertCurpFormato(string $curp): void
    {
        if (!preg_match(self::CURP_REGEX, $curp)) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'La CURP no tiene un formato válido.',
            ], 422));
        }
    }

    public function sendToken(Request $request)
    {
        $requireToken = filter_var(env('CV_REQUIRE_TOKEN', 'true'), FILTER_VALIDATE_BOOLEAN);

        $data = $request->validate([
            'curp'   => 'required|string|max:18',
            'correo' => $requireToken ? 'required|email|max:150' : 'nullable|email|max:150',
        ]);

        $curp   = $this->normalizeCurp($data['curp']);
        $correo = $this->normalizeMail($data['correo'] ?? null);

        $this->assertCurpFormato($curp);

        $empleado = Empleado::whereRaw('UPPER(curp) = ?', [$curp])->first();

        if (!$empleado) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró un empleado con esa CURP.',
            ], 404);
        }

        // ✅ ============================
        // ✅ MODO SIN TOKEN (SOLO CURP)
        // ✅ ============================
        if (!$requireToken) {
            return response()->json([
                'ok' => true,
                'message' => 'CURP validada correctamente (modo sin token).',
                'empleado' => $empleado,
            ]);
        }

        // ✅ ============================
        // ✅ FLUJO ORIGINAL CON TOKEN
        // ✅ ============================
        $this->validarCorreoUnico($curp, (string)$correo);

        $token = (string) random_int(100000, 999999);

        DB::transaction(function () use ($curp, $correo, $empleado, $token) {
            if (!empty($correo) && (empty($empleado->correo) || strtolower((string)$empleado->correo) !== $correo)) {
                $empleado->correo = $correo;
                $empleado->save();
            }

            CvTokenAcceso::create([
                'curp'      => $curp,
                'correo'    => $correo,
                'token'     => $token,
                'creado_en' => Carbon::now(),
                'expira_en' => Carbon::now()->addMinutes(15),
            ]);
        });

        $nombreCompleto = trim("{$empleado->nombre} {$empleado->primer_apellido} {$empleado->segundo_apellido}");

        $html = "
            <p>Hola <strong>{$nombreCompleto}</strong>,</p>
            <p>Tu código de acceso para continuar con el registro de tu CV es:</p>
            <p style=\"font-size:24px;font-weight:bold;\">{$token}</p>
            <p>Este código es válido por 15 minutos.</p>
            <p>Para continuar con tu registro, entra al siguiente enlace:</p>
            <p>
                <a href=\"" . route('registro.wizard') . "\" target=\"_blank\">
                    Ir al registro de CV
                </a>
            </p>
            <p>Si tú no solicitaste este código, puedes ignorar este mensaje.</p>
        ";

        if (config('mail.default') === 'log' || env('MAIL_MAILER') === 'log') {
            Log::info('CV TOKEN (MAIL_MAILER=log)', [
                'curp' => $curp,
                'correo' => $correo,
                'token' => $token,
                'expira_en' => Carbon::now()->addMinutes(15)->toDateTimeString(),
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Se generó el código (modo log).',
                'token_demo' => app()->environment('local') ? $token : null,
            ]);
        }

        $mailData = [
            'affair'  => 'Código de acceso para registro de CV',
            'mail'    => $correo,
            'content' => $html,
        ];

        $mailer = new MailHelper();

        try {
            $enviado = $mailer->sendMail($mailData);
        } catch (\Throwable $e) {
            report($e);
            $enviado = false;
        }

        if (!$enviado) {
            return response()->json([
                'ok' => false,
                'message' => 'No se pudo enviar el correo con el código. Verifica la configuración de correo.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Se envió un código de verificación a tu correo.',
            'token_demo' => app()->environment('local') ? $token : null,
        ]);
    }

    public function validateToken(Request $request)
    {
        $requireToken = filter_var(env('CV_REQUIRE_TOKEN', 'true'), FILTER_VALIDATE_BOOLEAN);

        $data = $request->validate([
            'curp'   => 'required|string|max:18',
            'correo' => $requireToken ? 'required|email|max:150' : 'nullable|email|max:150',
            'token'  => $requireToken ? 'required|string|max:10' : 'nullable|string|max:10',
        ]);

        $curp   = $this->normalizeCurp($data['curp']);
        $correo = $this->normalizeMail($data['correo'] ?? null);
        $token  = trim((string)($data['token'] ?? ''));

        $this->assertCurpFormato($curp);

        // ✅ ============================
        // ✅ MODO SIN TOKEN (SOLO CURP)
        // ✅ ============================
        if (!$requireToken) {
            $empleado = Empleado::whereRaw('UPPER(curp) = ?', [$curp])->first();

            if (!$empleado) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No se encontró un empleado con esa CURP.',
                ], 404);
            }

            return response()->json([
                'ok' => true,
                'empleado' => $empleado,
            ]);
        }

        // ✅ ============================
        // ✅ FLUJO ORIGINAL CON TOKEN
        // ✅ ============================
        $now = Carbon::now();

        $registro = CvTokenAcceso::whereRaw('UPPER(curp) = ?', [$curp])
            ->whereRaw('LOWER(correo) = ?', [strtolower((string)$correo)])
            ->where('token', $token)
            ->whereNull('usado_en')
            ->where('expira_en', '>=', $now)
            ->latest('creado_en')
            ->first();

        if (!$registro) {
            return response()->json([
                'ok' => false,
                'message' => 'Token inválido o expirado.',
            ], 422);
        }

        $registro->usado_en = $now;
        $registro->save();

        $empleado = Empleado::whereRaw('UPPER(curp) = ?', [$curp])->first();

        return response()->json([
            'ok' => true,
            'empleado' => $empleado,
        ]);
    }

    public function saveDatosPersonales(Request $request)
    {
        $data = $request->validate([
            'curp' => 'required|string|max:18',
            'nombres' => 'required|string|max:150',
            'primer_apellido' => 'required|string|max:150',
            'segundo_apellido' => 'nullable|string|max:150',

            'puesto_actual' => 'nullable|string|max:150',
            'fecha_inicio' => 'nullable|date|before_or_equal:today',
            'area_adscripcion' => 'nullable|string|max:250',

            'id_puesto' => 'nullable|integer',
            'id_unidad_adscripcion' => 'nullable|integer',

            // ✅ Nacionalidad SOLO NACIONAL / EXTRANJERO
            'nacionalidad' => 'required|in:NACIONAL,EXTRANJERO',
        ], [
            'nacionalidad.required' => 'Selecciona tu nacionalidad.',
            'nacionalidad.in' => 'Nacionalidad inválida.',
        ]);

        $curp = $this->normalizeCurp($data['curp']);
        $this->assertCurpFormato($curp);

        $empleado = Empleado::whereRaw('UPPER(curp) = ?', [$curp])->firstOrFail();

        // ✅ A MAYÚSCULAS
        $empleado->nombre = $this->upper($data['nombres']);
        $empleado->primer_apellido = $this->upper($data['primer_apellido']);
        $empleado->segundo_apellido = $this->upper($data['segundo_apellido'] ?? null);

        $empleado->puesto_actual = $this->upper($data['puesto_actual'] ?? null);
        $empleado->fecha_inicio_puesto = $data['fecha_inicio'] ?? null;
        $empleado->area_adscripcion = $this->upper($data['area_adscripcion'] ?? null);

        $empleado->id_puesto = $data['id_puesto'] ?? null;
        $empleado->id_unidad_adscripcion = $data['id_unidad_adscripcion'] ?? null;

        // ✅ Guardar nacionalidad SOLO si existe la columna (para no romper)
        $table = $empleado->getTable();
        if (Schema::hasColumn($table, 'nacionalidad')) {
            $empleado->nacionalidad = $data['nacionalidad']; // ya viene NACIONAL/EXTRANJERO
        }

        $empleado->estatus_cv = 1;
        $empleado->save();

        return response()->json(['ok' => true]);
    }

    public function saveExperiencias(Request $request)
    {
        $data = $request->validate([
            'curp' => 'required|string|max:18',
            'experiencias' => 'required|array|min:1|max:3',
            'experiencias.*.fecha_inicio' => 'nullable|date',
            'experiencias.*.fecha_termino' => 'nullable|date',
            'experiencias.*.sector' => 'nullable|in:PÚBLICO,PRIVADO',
            'experiencias.*.puesto' => 'nullable|string|max:150',
            'experiencias.*.institucion' => 'nullable|string|max:200',

            // ✅ CAMPO EXPERIENCIA MAX 100
            'experiencias.*.campo' => 'nullable|string|max:100',
        ]);

        $curp = $this->normalizeCurp($data['curp']);
        $this->assertCurpFormato($curp);

        $empleado = Empleado::whereRaw('UPPER(curp) = ?', [$curp])->firstOrFail();

        CvExperienciaLaboral::where('id_tbl_empleados', $empleado->id_tbl_empleados)->delete();

        foreach ($data['experiencias'] as $i => $exp) {
            CvExperienciaLaboral::create([
                'id_tbl_empleados' => $empleado->id_tbl_empleados,
                'fecha_inicio' => $exp['fecha_inicio'] ?? null,
                'fecha_termino' => $exp['fecha_termino'] ?? null,
                'sector' => $exp['sector'] ?? null,
                'puesto' => $this->upper($exp['puesto'] ?? null),
                'institucion' => $this->upper($exp['institucion'] ?? null),
                'campo_experiencia' => $this->upper($exp['campo'] ?? null),
                'orden' => $i + 1,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function saveEstudios(Request $request)
    {
        // ✅ NO SE PUEDE GUARDAR EN BLANCO
        $data = $request->validate([
            'curp' => 'required|string|max:18',

            'institucion' => 'required|string|max:200',
            'id_pais' => 'required|integer',
            'pais' => 'required|string|max:100',
            'id_nivel_estudios' => 'required|integer',
            'nivel' => 'required|string|max:100',

            'numero_cedula' => 'nullable|string|max:50',

            'carrera_generica' => 'required|string|max:150',
            'carrera_especifica' => 'required|string|max:150',
            'area_estudios' => 'required|string|max:150',
        ], [
            'institucion.required' => 'Debes capturar la institución.',
            'id_pais.required' => 'Debes seleccionar un país.',
            'id_nivel_estudios.required' => 'Debes seleccionar un nivel de estudios.',
            'carrera_generica.required' => 'Debes seleccionar la carrera genérica.',
            'carrera_especifica.required' => 'Debes seleccionar la carrera específica.',
            'area_estudios.required' => 'Debes seleccionar el área de estudios.',
        ]);

        $curp = $this->normalizeCurp($data['curp']);
        $this->assertCurpFormato($curp);

        $empleado = Empleado::whereRaw('UPPER(curp) = ?', [$curp])->firstOrFail();

        $estudios = CvEstudiosAcademicos::firstOrNew([
            'id_tbl_empleados' => $empleado->id_tbl_empleados,
        ]);

        // ✅ A MAYÚSCULAS
        $payload = [
            'institucion' => $this->upper($data['institucion']),
            'id_pais' => $data['id_pais'],
            'pais' => $this->upper($data['pais']),
            'id_nivel_estudios' => $data['id_nivel_estudios'],
            'nivel' => $this->upper($data['nivel']),
            'numero_cedula' => $this->upper($data['numero_cedula'] ?? null),
            'carrera_generica' => $this->upper($data['carrera_generica']),
            'carrera_especifica' => $this->upper($data['carrera_especifica']),
            'area_estudios' => $this->upper($data['area_estudios']),
        ];

        $estudios->fill($payload);
        $estudios->id_tbl_empleados = $empleado->id_tbl_empleados;
        $estudios->save();

        return response()->json(['ok' => true]);
    }

    public function saveCursos(Request $request)
    {
        $data = $request->validate([
            'curp' => 'required|string|max:18',
            'cursos' => 'required|array|min:1|max:5',
            'cursos.*.periodo' => 'nullable|string|max:100',
            'cursos.*.nombre' => 'nullable|string|max:200',
            'cursos.*.institucion' => 'nullable|string|max:200',
            'enviar' => 'nullable|boolean',
        ]);

        $curp = $this->normalizeCurp($data['curp']);
        $this->assertCurpFormato($curp);

        $empleado = Empleado::whereRaw('UPPER(curp) = ?', [$curp])->firstOrFail();

        CvCursosCapacitaciones::where('id_tbl_empleados', $empleado->id_tbl_empleados)->delete();

        foreach ($data['cursos'] as $i => $curso) {
            CvCursosCapacitaciones::create([
                'id_tbl_empleados' => $empleado->id_tbl_empleados,
                'periodo' => $this->upper($curso['periodo'] ?? null),
                'nombre_curso' => $this->upper($curso['nombre'] ?? null),
                'institucion' => $this->upper($curso['institucion'] ?? null),
                'orden' => $i + 1,
            ]);
        }

        if (!empty($data['enviar'])) {
            $empleado->estatus_cv = 2;
            $empleado->save();
        }

        return response()->json(['ok' => true]);
    }

    protected function validarCorreoUnico(string $curp, string $correo): void
    {
        $curp = $this->normalizeCurp($curp);
        $correo = strtolower(trim($correo));

        $existeEnEmpleados = Empleado::whereRaw('LOWER(correo) = ?', [$correo])
            ->whereRaw('UPPER(curp) <> ?', [$curp])
            ->exists();

        $existeEnTokens = CvTokenAcceso::whereRaw('LOWER(correo) = ?', [$correo])
            ->whereRaw('UPPER(curp) <> ?', [$curp])
            ->exists();

        if ($existeEnEmpleados || $existeEnTokens) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'El correo ingresado ya está en uso por otro registro. Por favor, utiliza un correo diferente.',
            ], 422));
        }
    }

    public function checkCorreo(Request $request)
    {
        $requireToken = filter_var(env('CV_REQUIRE_TOKEN', 'true'), FILTER_VALIDATE_BOOLEAN);

        // ✅ Si NO hay token, no tiene sentido bloquear por correo
        if (!$requireToken) {
            return response()->json([
                'ok' => true,
                'message' => 'Modo sin token: se omite validación de correo.',
            ]);
        }

        $data = $request->validate([
            'curp' => 'required|string|max:18',
            'correo' => 'required|email|max:150',
        ]);

        $curp = $this->normalizeCurp($data['curp']);
        $correo = strtolower(trim($data['correo']));

        $this->assertCurpFormato($curp);
        $this->validarCorreoUnico($curp, $correo);

        return response()->json([
            'ok' => true,
            'message' => 'El correo está disponible.',
        ]);
    }
}
