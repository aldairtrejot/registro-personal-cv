<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
// use App\Http\Controllers\Helpers\MailController as MailHelper; // 🔕 TOKEN (comentado)

use App\Models\Cv\Empleado;
// use App\Models\Cv\CvTokenAcceso; // 🔕 TOKEN (comentado)

use App\Models\Cv\CvExperienciaLaboral;
use App\Models\Cv\CvEstudiosAcademicos;
use App\Models\Cv\CvCursosCapacitaciones;

use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB; // 🔕 TOKEN (comentado)
// use Illuminate\Support\Carbon;      // 🔕 TOKEN (comentado)
// use Illuminate\Support\Facades\Log; // 🔕 TOKEN (comentado)

use Illuminate\Http\Exceptions\HttpResponseException;
// use Illuminate\Support\Facades\Cache; // (no usado)
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WizardController extends Controller
{
    private const CURP_REGEX = '/^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]{2}$/';

    private function normalizeCurp(string $curp): string
    {
        return strtoupper(trim($curp));
    }

    // ✅ CORREO: se guarda tal cual (en minúsculas) y SIN token
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

    /**
     * ✅ ENDPOINT: api/cv/send-token
     * 🔥 Ahora en modo sin token: valida CURP + CORREO y lo GUARDA en tbl_empleados.correo
     * 🔕 Todo lo relacionado a token queda comentado.
     */
    public function sendToken(Request $request)
    {
        // 🔕 TOKEN: ignoramos el flujo de token (comentado)
        // $requireToken = filter_var(env('CV_REQUIRE_TOKEN', 'true'), FILTER_VALIDATE_BOOLEAN);

        // ✅ SIN TOKEN: el correo se captura SIEMPRE
        $data = $request->validate([
            'curp'   => 'required|string|max:18',
            'correo' => 'required|email|max:150',
        ]);

        $curp   = $this->normalizeCurp($data['curp']);
        $correo = $this->normalizeMail($data['correo']);

        $this->assertCurpFormato($curp);

        $empleado = Empleado::whereRaw('UPPER(curp) = ?', [$curp])->first();

        if (!$empleado) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró un empleado con esa CURP.',
            ], 404);
        }

        // ✅ GUARDA CORREO SI CAMBIÓ O SI ESTABA VACÍO
        if (!empty($correo) && (empty($empleado->correo) || strtolower((string) $empleado->correo) !== $correo)) {
            $empleado->correo = $correo;
            $empleado->save();
        }

        // 🔕 ============================
        // 🔕 FLUJO ORIGINAL CON TOKEN (COMENTADO)
        // 🔕 ============================
        /*
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

        // ... envío de correo ...
        */

        return response()->json([
            'ok' => true,
            'message' => 'CURP validada correctamente. Correo guardado.',
            'empleado' => $empleado,
        ]);
    }

    /**
     * 🔕 api/cv/validate-token (COMENTADO / OMITIDO)
     * ✅ Se deja el endpoint vivo por compatibilidad, pero NO valida token.
     */
    public function validateToken(Request $request)
    {
        // 🔕 TOKEN: no se usa
        $data = $request->validate([
            'curp'   => 'required|string|max:18',
            'correo' => 'required|email|max:150',
            // 'token'  => 'nullable|string|max:10', // 🔕 TOKEN
        ]);

        $curp   = $this->normalizeCurp($data['curp']);
        $correo = $this->normalizeMail($data['correo']);

        $this->assertCurpFormato($curp);

        $empleado = Empleado::whereRaw('UPPER(curp) = ?', [$curp])->first();

        if (!$empleado) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró un empleado con esa CURP.',
            ], 404);
        }

        // ✅ también guarda correo aquí por seguridad
        if (!empty($correo) && (empty($empleado->correo) || strtolower((string) $empleado->correo) !== $correo)) {
            $empleado->correo = $correo;
            $empleado->save();
        }

        return response()->json([
            'ok' => true,
            'empleado' => $empleado,
        ]);
    }

    public function saveDatosPersonales(Request $request)
    {
        $data = $request->validate([
            'curp' => 'required|string|max:18',

            // ✅ CORREO (AHORA SÍ SE GUARDA)
            'correo' => 'required|email|max:150',

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

        // ✅ GUARDA CORREO (SIN MAYÚSCULAS)
        $correo = $this->normalizeMail($data['correo']);
        if (!empty($correo) && (empty($empleado->correo) || strtolower((string) $empleado->correo) !== $correo)) {
            $empleado->correo = $correo;
        }

        // ✅ A MAYÚSCULAS (solo texto de CV)
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
            $empleado->nacionalidad = $data['nacionalidad'];
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

        // ✅ Cursos ahora es OPCIONAL
        'cursos' => 'nullable|array|max:5',
        'cursos.*.periodo' => 'nullable|string|max:100',
        'cursos.*.nombre' => 'nullable|string|max:200',
        'cursos.*.institucion' => 'nullable|string|max:200',

        'enviar' => 'nullable|boolean',
    ]);

    $curp = $this->normalizeCurp($data['curp']);
    $this->assertCurpFormato($curp);

    $empleado = Empleado::whereRaw('UPPER(curp) = ?', [$curp])->firstOrFail();

    // ✅ Filtra cursos vacíos para no guardar registros en blanco
    $cursos = collect($data['cursos'] ?? [])
        ->filter(function ($curso) {
            return !empty(trim($curso['periodo'] ?? '')) ||
                   !empty(trim($curso['nombre'] ?? '')) ||
                   !empty(trim($curso['institucion'] ?? ''));
        })
        ->values();

    // ✅ Si el usuario captura un curso, debe estar completo
    foreach ($cursos as $index => $curso) {
        if (
            empty(trim($curso['periodo'] ?? '')) ||
            empty(trim($curso['nombre'] ?? '')) ||
            empty(trim($curso['institucion'] ?? ''))
        ) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'Si capturas un curso, debes completar período, nombre del curso e institución.',
            ], 422));
        }
    }

    CvCursosCapacitaciones::where('id_tbl_empleados', $empleado->id_tbl_empleados)->delete();

    foreach ($cursos as $i => $curso) {
        CvCursosCapacitaciones::create([
            'id_tbl_empleados' => $empleado->id_tbl_empleados,
            'periodo' => $this->upper($curso['periodo'] ?? null),
            'nombre_curso' => $this->upper($curso['nombre'] ?? null),
            'institucion' => $this->upper($curso['institucion'] ?? null),
            'orden' => $i + 1,
        ]);
    }

    // ✅ Aunque no tenga cursos, sí puede finalizar
    if (!empty($data['enviar'])) {
        $empleado->estatus_cv = 2;
        $empleado->save();
    }

    return response()->json(['ok' => true]);
}

    // 🔕 TOKEN: esto era para bloquear correos por token también
    protected function validarCorreoUnico(string $curp, string $correo): void
    {
        $curp = $this->normalizeCurp($curp);
        $correo = strtolower(trim($correo));

        $existeEnEmpleados = Empleado::whereRaw('LOWER(correo) = ?', [$correo])
            ->whereRaw('UPPER(curp) <> ?', [$curp])
            ->exists();

        // 🔕 TOKEN (comentado)
        /*
        $existeEnTokens = CvTokenAcceso::whereRaw('LOWER(correo) = ?', [$correo])
            ->whereRaw('UPPER(curp) <> ?', [$curp])
            ->exists();
        */

        if ($existeEnEmpleados /* || $existeEnTokens */) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'El correo ingresado ya está en uso por otro registro. Por favor, utiliza un correo diferente.',
            ], 422));
        }
    }

    public function checkCorreo(Request $request)
    {
        // 🔕 TOKEN: aquí antes se omitía si no había token.
        // ✅ Si quieres seguir validando unicidad aunque no uses token, dejamos esto activo.

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
