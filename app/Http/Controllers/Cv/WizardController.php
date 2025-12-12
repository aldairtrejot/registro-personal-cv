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

class WizardController extends Controller
{
public function sendToken(Request $request)
{
    $data = $request->validate([
        'curp'   => 'required|string|max:18',
        'correo' => 'required|email|max:150',
    ]);

    $curp   = strtoupper(trim($data['curp']));
    $correo = strtolower(trim($data['correo']));

    // 1) Buscar al empleado por CURP
    $empleado = Empleado::whereRaw('UPPER(curp) = ?', [$curp])->first();

    if (!$empleado) {
        return response()->json([
            'ok'      => false,
            'message' => 'No se encontró un empleado con esa CURP.',
        ], 404);
    }

    // 2) Validar que el correo NO esté usado por otro CURP
    $this->validarCorreoUnico($curp, $correo);

    // 3) Generar token
    $token = (string) random_int(100000, 999999);

    // 4) Guardar correo en ambas tablas en una sola transacción
    DB::transaction(function () use ($curp, $correo, $empleado, $token) {
        // Actualizar correo en tbl_empleados si está vacío o diferente
        if (empty($empleado->correo) || strtolower($empleado->correo) !== $correo) {
            $empleado->correo = $correo;
            $empleado->save();
        }

        // Registrar token en tbl_cv_tokens_acceso
        CvTokenAcceso::create([
            'curp'      => $curp,
            'correo'    => $correo,
            'token'     => $token,
            'creado_en' => Carbon::now(),
            'expira_en' => Carbon::now()->addMinutes(15),
        ]);
    });

    // 5) Enviar el correo (esto ya no afecta la transacción)
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

    $mailData = [
        'affair'  => 'Código de acceso para registro de CV',
        'mail'    => $correo, // ya normalizado
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
            'curp'                  => 'required|string|max:18',
            'nombres'               => 'required|string|max:150',
            'primer_apellido'       => 'required|string|max:150',
            'segundo_apellido'      => 'nullable|string|max:150',
            'puesto_actual'         => 'nullable|string|max:150',
            'fecha_inicio'          => 'nullable|date',
            'area_adscripcion'      => 'nullable|string|max:150',
            'id_puesto'             => 'nullable|integer',
            'id_unidad_adscripcion' => 'nullable|integer',
        ]);

        $empleado = Empleado::where('curp', $data['curp'])->firstOrFail();

        $empleado->nombre                = $data['nombres'];
        $empleado->primer_apellido       = $data['primer_apellido'];
        $empleado->segundo_apellido      = $data['segundo_apellido'] ?? null;
        $empleado->puesto_actual         = $data['puesto_actual'] ?? null;
        $empleado->fecha_inicio_puesto   = $data['fecha_inicio'] ?? null;
        $empleado->area_adscripcion      = $data['area_adscripcion'] ?? null;
        $empleado->id_puesto             = $data['id_puesto'] ?? null;
        $empleado->id_unidad_adscripcion = $data['id_unidad_adscripcion'] ?? null;
        $empleado->estatus_cv            = 1; // En edición
        $empleado->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Paso 4: experiencias laborales
     */
    public function saveExperiencias(Request $request)
    {
        $data = $request->validate([
            'curp'                        => 'required|string|max:18',
            'experiencias'                => 'required|array|min:1|max:3',
            'experiencias.*.fecha_inicio' => 'nullable|date',
            'experiencias.*.fecha_termino'=> 'nullable|date',
            'experiencias.*.sector'       => 'nullable|string|max:20',
            'experiencias.*.puesto'       => 'nullable|string|max:150',
            'experiencias.*.institucion'  => 'nullable|string|max:200',
            'experiencias.*.campo'        => 'nullable|string|max:100',
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
            'id_pais'            => 'nullable|integer',
            'pais'               => 'nullable|string|max:100',
            'id_nivel_estudios'  => 'nullable|integer',
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
     *  🔥 Ahora permite hasta 5 cursos.
     */
    public function saveCursos(Request $request)
    {
        $data = $request->validate([
            'curp'                 => 'required|string|max:18',
            'cursos'               => 'required|array|min:1|max:5', // <- antes max:3
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
            $empleado->estatus_cv = 2; // Enviado para revisión
            $empleado->save();
        }

        return response()->json(['ok' => true]);
    }
    /**
 * Valida que el correo NO esté ya utilizado por otro CURP
 * en tbl_empleados o en tbl_cv_tokens_acceso.
 */
protected function validarCorreoUnico(string $curp, string $correo): void
{
    $curp   = strtoupper(trim($curp));
    $correo = strtolower(trim($correo));

    // ¿El correo ya está en empleados con otra CURP?
    $existeEnEmpleados = Empleado::whereRaw('LOWER(correo) = ?', [$correo])
        ->whereRaw('UPPER(curp) <> ?', [$curp])
        ->exists();

    // ¿El correo ya está en tokens con otra CURP?
    $existeEnTokens = CvTokenAcceso::whereRaw('LOWER(correo) = ?', [$correo])
        ->whereRaw('UPPER(curp) <> ?', [$curp])
        ->exists();

    if ($existeEnEmpleados || $existeEnTokens) {
        abort(response()->json([
            'ok'      => false,
            'message' => 'El correo ingresado ya está en uso por otro registro. Por favor, utiliza un correo diferente.',
        ], 422));
    }
}
/**
 * Endpoint para validar en caliente que el correo esté disponible.
 * (Se puede usar desde el paso 1 del wizard antes de enviar el token)
 */
public function checkCorreo(Request $request)
{
    $data = $request->validate([
        'curp'   => 'required|string|max:18',
        'correo' => 'required|email|max:150',
    ]);

    // Si algo está mal, el método lanza 422
    $this->validarCorreoUnico($data['curp'], $data['correo']);

    return response()->json([
        'ok'      => true,
        'message' => 'El correo está disponible.',
    ]);
}

}
