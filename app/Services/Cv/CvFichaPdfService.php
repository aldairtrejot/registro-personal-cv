<?php

namespace App\Services\Cv;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Process;

class CvFichaPdfService
{
    public function generarPdfPorEmpleado($empleado): string
    {
        $empleadoId = $empleado->id_tbl_empleados ?? $empleado->id ?? null;

        if (!$empleadoId) {
            throw new \RuntimeException('No se pudo identificar el ID del empleado.');
        }

        $base = $this->cargarDatosEmpleadoBase((int)$empleadoId);

        $nombre = trim((string)($empleado->nombres ?? $empleado->nombre ?? $base['nombre'] ?? $base['nombres'] ?? ''));
        $apPat  = trim((string)($empleado->apellido_paterno ?? $empleado->primer_apellido ?? $base['primer_apellido'] ?? $base['apellido_paterno'] ?? ''));
        $apMat  = trim((string)($empleado->apellido_materno ?? $empleado->segundo_apellido ?? $base['segundo_apellido'] ?? $base['apellido_materno'] ?? ''));

        $fullName = trim($nombre . ' ' . $apPat . ' ' . $apMat);

        $puesto = trim((string)(
            $empleado->puesto_actual
            ?? $empleado->puesto
            ?? $empleado->nombre_puesto
            ?? $base['puesto_actual']
            ?? $base['nombre_puesto']
            ?? ''
        ));

        $fechaInicioPuesto = $this->fmtFecha(
            $empleado->fecha_inicio_puesto
            ?? $empleado->fecha_ingreso
            ?? $base['fecha_inicio_puesto']
            ?? $base['fecha_ingreso']
            ?? $base['fecha_inicio']
            ?? $base['fecha_inicio_cargo']
            ?? $base['fecha_inicio_encargo']
            ?? $base['fecha_adscripcion']
            ?? null
        );

        $vars = [
            'fullName'          => $fullName ?: 'SIN NOMBRE',
            'puesto'            => $puesto,
            'fechaInicioPuesto' => $fechaInicioPuesto,
        ];

        $experiencias = $this->cargarExperiencias((int)$empleadoId);

        for ($i = 1; $i <= 3; $i++) {
            $e = $experiencias[$i - 1] ?? [];

            $vars["exp{$i}_puesto"]      = (string)($e['puesto'] ?? '');
            $vars["exp{$i}_institucion"] = (string)($e['institucion'] ?? '');
            $vars["exp{$i}_sector"]      = (string)($e['sector'] ?? '');
            $vars["exp{$i}_inicio"]      = $this->fmtFecha($e['inicio'] ?? null);
            $vars["exp{$i}_fin"]         = $this->fmtFecha($e['fin'] ?? null);
            $vars["exp{$i}_campo"]       = (string)($e['campo'] ?? '');
        }

        $est = $this->cargarEstudio((int)$empleadoId);
        $cedula = trim((string)($est['cedula'] ?? ''));

        $vars['grado_avance'] = $cedula !== '' ? 'TITULADO' : '';
        $vars['est_institucion'] = (string)($est['institucion'] ?? '');
        $vars['est_pais'] = (string)($est['pais'] ?? '');
        $vars['nivel'] = (string)($est['nivel'] ?? '');
        $vars['area_estudios'] = (string)($est['area_estudios'] ?? '');
        $vars['titulo_grado'] = (string)($est['titulo_grado'] ?? '');
        $vars['carrera_generica'] = (string)($est['carrera_generica'] ?? '');

        $cursos = $this->cargarCursos((int)$empleadoId);

        for ($i = 1; $i <= 5; $i++) {
            $c = $cursos[$i - 1] ?? [];

            $vars["curso{$i}_periodo"]     = (string)($c['periodo'] ?? '');
            $vars["curso{$i}_nombre"]      = (string)($c['nombre'] ?? '');
            $vars["curso{$i}_institucion"] = (string)($c['institucion'] ?? '');
        }

        $curp = strtoupper(trim((string)($empleado->curp ?? $base['curp'] ?? '')));
        $idForName = (string)($curp !== '' ? $curp : $empleadoId);

        return $this->generarPdfDesdeDocx($vars, $idForName);
    }

    private function generarPdfDesdeDocx(array $vars, string $idForName): string
    {
        $templatePath = config('cvpdf.template_docx_path') ?: config('cvpdf.template_path');
        $tmpRoot      = config('cvpdf.tmp_dir', storage_path('app/tmp'));
        $pdfDir       = config('cvpdf.pdf_dir', storage_path('app/tmp/pdf'));
        $profileRoot  = config('cvpdf.lo_profile_dir', storage_path('app/tmp/lo_profile'));

        $docxDir = $tmpRoot . DIRECTORY_SEPARATOR . 'docx';

        File::ensureDirectoryExists($tmpRoot);
        File::ensureDirectoryExists($docxDir);
        File::ensureDirectoryExists($pdfDir);
        File::ensureDirectoryExists($profileRoot);

        if (!$templatePath || !File::exists($templatePath)) {
            throw new \RuntimeException("No se encontró la plantilla DOCX: {$templatePath}");
        }

        $soffice = $this->resolveSofficePath();

        $baseName = 'cv_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $idForName)
            . '_' . Carbon::now()->format('Ymd_His_u')
            . '_' . mt_rand(1000, 9999);

        $docxPath = $docxDir . DIRECTORY_SEPARATOR . $baseName . '.docx';
        $runProfileDir = $profileRoot . DIRECTORY_SEPARATOR . $baseName;

        File::ensureDirectoryExists($runProfileDir);

        $placeholders = [
            'fullName',
            'puesto',
            'fechaInicioPuesto',

            'exp1_puesto','exp1_institucion','exp1_sector','exp1_inicio','exp1_fin','exp1_campo',
            'exp2_puesto','exp2_institucion','exp2_sector','exp2_inicio','exp2_fin','exp2_campo',
            'exp3_puesto','exp3_institucion','exp3_sector','exp3_inicio','exp3_fin','exp3_campo',

            'est_institucion','est_pais','nivel','grado_avance','area_estudios','titulo_grado','carrera_generica',

            'curso1_periodo','curso1_nombre','curso1_institucion',
            'curso2_periodo','curso2_nombre','curso2_institucion',
            'curso3_periodo','curso3_nombre','curso3_institucion',
            'curso4_periodo','curso4_nombre','curso4_institucion',
            'curso5_periodo','curso5_nombre','curso5_institucion',
        ];

        $tp = new TemplateProcessor($templatePath);

        foreach ($placeholders as $key) {
            $tp->setValue($key, $this->safeDocx($vars[$key] ?? ''));
        }

        $tp->saveAs($docxPath);

        if (!File::exists($docxPath)) {
            throw new \RuntimeException("No se generó el DOCX temporal: {$docxPath}");
        }

        $process = new Process([
            $soffice,
            '--headless',
            '--nologo',
            '--nofirststartwizard',
            '--nolockcheck',
            '--nodefault',
            '--convert-to',
            'pdf:writer_pdf_Export',
            '--outdir',
            $pdfDir,
            '-env:UserInstallation=' . $this->pathToFileUri($runProfileDir),
            $docxPath,
        ]);

        $process->setTimeout(180);
        $process->run();

        if (!$process->isSuccessful()) {
            $stderr = trim($process->getErrorOutput());
            $stdout = trim($process->getOutput());

            throw new \RuntimeException(
                "Error al convertir a PDF.\n" .
                "SOFFICE: {$soffice}\n" .
                "DOCX: {$docxPath}\n" .
                "PDF_DIR: {$pdfDir}\n" .
                "PROFILE_DIR: {$runProfileDir}\n" .
                "STDERR: " . ($stderr !== '' ? $stderr : '[vacío]') . "\n" .
                "STDOUT: " . ($stdout !== '' ? $stdout : '[vacío]')
            );
        }

        $pdfPath = $pdfDir . DIRECTORY_SEPARATOR . $baseName . '.pdf';

        if (!File::exists($pdfPath)) {
            $pdfs = glob($pdfDir . DIRECTORY_SEPARATOR . $baseName . '*.pdf') ?: [];
            if (!empty($pdfs)) {
                $pdfPath = $pdfs[0];
            }
        }

        if (!File::exists($pdfPath)) {
            throw new \RuntimeException(
                "LibreOffice terminó sin error, pero no generó el PDF esperado.\n" .
                "BaseName: {$baseName}\n" .
                "PDF_DIR: {$pdfDir}"
            );
        }

        @unlink($docxPath);

        try {
            File::deleteDirectory($runProfileDir);
        } catch (\Throwable $e) {
            // Ignorar limpieza
        }

        return $pdfPath;
    }

    private function resolveSofficePath(): string
    {
        $configured = trim((string) config('cvpdf.soffice_path', ''));

        $candidates = [];

        if ($configured !== '') {
            $candidates[] = $configured;
        }

        $candidates = array_unique(array_merge($candidates, [
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files\\LibreOffice\\program\\soffice.com',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.com',
            '/usr/bin/libreoffice',
            '/usr/bin/soffice',
            '/snap/bin/libreoffice',
        ]));

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate !== '' && File::exists($candidate)) {
                return $candidate;
            }
        }

        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                @exec('where.exe soffice.exe 2>NUL', $out1, $code1);
                if ($code1 === 0 && !empty($out1[0]) && File::exists(trim($out1[0]))) {
                    return trim($out1[0]);
                }

                @exec('where.exe soffice.com 2>NUL', $out2, $code2);
                if ($code2 === 0 && !empty($out2[0]) && File::exists(trim($out2[0]))) {
                    return trim($out2[0]);
                }
            } else {
                @exec('command -v soffice 2>/dev/null', $out3, $code3);
                if ($code3 === 0 && !empty($out3[0]) && File::exists(trim($out3[0]))) {
                    return trim($out3[0]);
                }

                @exec('command -v libreoffice 2>/dev/null', $out4, $code4);
                if ($code4 === 0 && !empty($out4[0]) && File::exists(trim($out4[0]))) {
                    return trim($out4[0]);
                }
            }
        } catch (\Throwable $e) {
            // Ignorar
        }

        throw new \RuntimeException(
            "No se encontró LibreOffice. Instálalo o configura la ruta correcta en .env.\n" .
            "Rutas revisadas:\n- " . implode("\n- ", $candidates)
        );
    }

    private function cargarDatosEmpleadoBase(int $empleadoId): array
    {
        $table = $this->resolveTable(['tbl_empleados']);
        $cols = $this->getColumnsSafe($table);

        $candidatas = [
            'curp',
            'nombre',
            'nombres',
            'primer_apellido',
            'apellido_paterno',
            'segundo_apellido',
            'apellido_materno',
            'puesto_actual',
            'nombre_puesto',
            'fecha_inicio_puesto',
            'fecha_ingreso',
            'fecha_inicio',
            'fecha_inicio_cargo',
            'fecha_inicio_encargo',
            'fecha_adscripcion',
        ];

        $selects = [];
        foreach ($candidatas as $campo) {
            $real = $this->pickColumn($cols, [$campo]);
            $selects[] = DB::raw(($real ? $this->qCol($real) : "NULL") . " as {$campo}");
        }

        $row = $this->fromTable($table)
            ->where('id_tbl_empleados', $empleadoId)
            ->select($selects)
            ->first();

        if (!$row) {
            return [];
        }

        return (array) $row;
    }

    private function pathToFileUri(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        if (preg_match('/^[A-Za-z]:/', $normalized)) {
            return 'file:///' . ltrim($normalized, '/');
        }

        return 'file://' . $normalized;
    }

    private function safeDocx($value): string
    {
        $value = (string)($value ?? '');
        $value = preg_replace("/[\\x00-\\x1F\\x7F]/u", '', $value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        return trim($value);
    }

    private function cargarExperiencias(int $empleadoId): array
    {
        $table = $this->resolveTable([
            'tbl_cv_experiencia_laboral',
            'tbl_cv_experiencias_laborales',
            'tbl_cv_experiencia_laborales',
        ]);

        $cols = $this->getColumnsSafe($table);

        $colPuesto      = $this->pickColumn($cols, ['puesto', 'cargo', 'puesto_desempenado']);
        $colInstitucion = $this->pickColumn($cols, ['institucion', 'empresa', 'dependencia']);
        $colSector      = $this->pickColumn($cols, ['sector', 'id_sector']);
        $colInicio      = $this->pickColumn($cols, ['fecha_inicio', 'inicio', 'fecha_inicial']);
        $colFin         = $this->pickColumn($cols, ['fecha_termino', 'fecha_fin', 'fin', 'fecha_final']);
        $colCampo       = $this->pickColumn($cols, ['campo_experiencia', 'campo', 'area_experiencia']);

        $orderCol = $this->pickColumn($cols, [
            'id_tbl_cv_experiencia_laboral',
            'id_tbl_cv_experiencias_laborales',
            'id_tbl_cv_experiencia_laborales',
            'id',
            'created_at',
            'updated_at',
        ]);

        $q = $this->fromTable($table)->where('id_tbl_empleados', $empleadoId)->limit(3);

        if ($orderCol) {
            $q->orderByDesc($orderCol);
        }

        $q->addSelect(DB::raw(($colPuesto      ? $this->qCol($colPuesto)      : "''") . " as puesto"));
        $q->addSelect(DB::raw(($colInstitucion ? $this->qCol($colInstitucion) : "''") . " as institucion"));
        $q->addSelect(DB::raw(($colSector      ? $this->qCol($colSector)      : "''") . " as sector"));
        $q->addSelect(DB::raw(($colInicio      ? $this->qCol($colInicio)      : "NULL") . " as inicio"));
        $q->addSelect(DB::raw(($colFin         ? $this->qCol($colFin)         : "NULL") . " as fin"));
        $q->addSelect(DB::raw(($colCampo       ? $this->qCol($colCampo)       : "''") . " as campo"));

        return $q->get()->map(fn($r) => [
            'puesto'      => (string)($r->puesto ?? ''),
            'institucion' => (string)($r->institucion ?? ''),
            'sector'      => (string)($r->sector ?? ''),
            'inicio'      => $r->inicio ?? null,
            'fin'         => $r->fin ?? null,
            'campo'       => (string)($r->campo ?? ''),
        ])->toArray();
    }

    private function cargarEstudio(int $empleadoId): array
    {
        $table = $this->resolveTable([
            'tbl_cv_estudios_academicos',
            'tbl_cv_estudio_academico',
        ]);

        $cols = $this->getColumnsSafe($table);

        $colInstitucion = $this->pickColumn($cols, ['institucion']);
        $colPais        = $this->pickColumn($cols, ['pais']);
        $colNivel       = $this->pickColumn($cols, ['nivel', 'nivel_estudios']);

        $colCedula = $this->pickColumn($cols, [
            'cedula',
            'numero_cedula',
            'no_cedula',
            'cedula_profesional',
            'cedula_prof',
        ]);

        $colArea = $this->pickColumn($cols, ['area_estudios', 'area_de_estudios', 'area']);

        $colCarreraEsp = $this->pickColumn($cols, [
            'carrera_especifica',
            'carrera_específica',
            'carrera',
            'titulo_grado',
            'titulo',
            'grado',
            'nombre_titulo',
        ]);

        $colCarreraGen = $this->pickColumn($cols, ['carrera_generica', 'carrera_general', 'carrera_gen']);

        $orderCol = $this->pickColumn($cols, [
            'id_tbl_cv_estudios_academicos',
            'id',
            'created_at',
            'updated_at',
        ]);

        $q = $this->fromTable($table)->where('id_tbl_empleados', $empleadoId)->limit(1);

        if ($orderCol) {
            $q->orderByDesc($orderCol);
        }

        $q->addSelect(DB::raw(($colInstitucion ? $this->qCol($colInstitucion) : "''") . " as institucion"));
        $q->addSelect(DB::raw(($colPais        ? $this->qCol($colPais)        : "''") . " as pais"));
        $q->addSelect(DB::raw(($colNivel       ? $this->qCol($colNivel)       : "''") . " as nivel"));
        $q->addSelect(DB::raw(($colCedula      ? $this->qCol($colCedula)      : "''") . " as cedula"));
        $q->addSelect(DB::raw(($colArea        ? $this->qCol($colArea)        : "''") . " as area_estudios"));
        $q->addSelect(DB::raw(($colCarreraEsp  ? $this->qCol($colCarreraEsp)  : "''") . " as titulo_grado"));
        $q->addSelect(DB::raw(($colCarreraGen  ? $this->qCol($colCarreraGen)  : "''") . " as carrera_generica"));

        $r = $q->first();

        if (!$r) {
            return [];
        }

        return [
            'institucion'      => (string)($r->institucion ?? ''),
            'pais'             => (string)($r->pais ?? ''),
            'nivel'            => (string)($r->nivel ?? ''),
            'cedula'           => trim((string)($r->cedula ?? '')),
            'area_estudios'    => (string)($r->area_estudios ?? ''),
            'titulo_grado'     => (string)($r->titulo_grado ?? ''),
            'carrera_generica' => (string)($r->carrera_generica ?? ''),
        ];
    }

    private function cargarCursos(int $empleadoId): array
    {
        $table = $this->resolveTable([
            'tbl_cv_cursos_capacitaciones',
            'tbl_cv_curso_capacitacion',
        ]);

        $cols = $this->getColumnsSafe($table);

        $colPeriodo     = $this->pickColumn($cols, ['periodo', 'periodo_curso', 'rango_fechas', 'vigencia']);
        $colNombre      = $this->pickColumn($cols, ['nombre_curso', 'nombre', 'curso']);
        $colInstitucion = $this->pickColumn($cols, ['institucion', 'instancia', 'dependencia']);
        $colInicio      = $this->pickColumn($cols, ['fecha_inicio', 'inicio', 'fecha_inicial']);
        $colFin         = $this->pickColumn($cols, ['fecha_fin', 'fecha_termino', 'fin', 'fecha_final']);

        $orderCol = $this->pickColumn($cols, [
            'id_tbl_cv_cursos_capacitaciones',
            'id',
            'created_at',
            'updated_at',
        ]);

        $q = $this->fromTable($table)->where('id_tbl_empleados', $empleadoId)->limit(5);

        if ($orderCol) {
            $q->orderByDesc($orderCol);
        }

        $q->addSelect(DB::raw(($colPeriodo     ? $this->qCol($colPeriodo)     : "''") . " as periodo"));
        $q->addSelect(DB::raw(($colNombre      ? $this->qCol($colNombre)      : "''") . " as nombre"));
        $q->addSelect(DB::raw(($colInstitucion ? $this->qCol($colInstitucion) : "''") . " as institucion"));
        $q->addSelect(DB::raw(($colInicio      ? $this->qCol($colInicio)      : "NULL") . " as inicio"));
        $q->addSelect(DB::raw(($colFin         ? $this->qCol($colFin)         : "NULL") . " as fin"));

        return $q->get()->map(function ($r) {
            $ini = $this->fmtFecha($r->inicio ?? null);
            $fin = $this->fmtFecha($r->fin ?? null);
            $periodoFechas = trim($ini . ($fin ? " - {$fin}" : ''));

            $periodoCapturado = trim((string)($r->periodo ?? ''));
            $periodo = $periodoCapturado !== '' ? $periodoCapturado : $periodoFechas;

            return [
                'periodo'     => $periodo,
                'nombre'      => (string)($r->nombre ?? ''),
                'institucion' => (string)($r->institucion ?? ''),
            ];
        })->toArray();
    }

    private function fmtFecha($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string)$value;
        }
    }

    private function pickColumn(array $cols, array $candidates): ?string
    {
        $colsLower = array_map('strtolower', $cols);

        foreach ($candidates as $candidate) {
            $idx = array_search(strtolower($candidate), $colsLower, true);
            if ($idx !== false) {
                return $cols[$idx];
            }
        }

        return null;
    }

    private function resolveTable(array $baseNames): string
    {
        $schemas = ['profesionalizacion', 'public', 'cv', 'administracion'];
        $driver = DB::getDriverName();

        foreach ($baseNames as $name) {
            if ($this->tableExists($name, $driver)) {
                return $name;
            }

            foreach ($schemas as $schema) {
                $full = "{$schema}.{$name}";
                if ($this->tableExists($full, $driver)) {
                    return $full;
                }
            }
        }

        throw new \RuntimeException('No se encontró ninguna tabla válida. Probé: ' . implode(', ', $baseNames));
    }

    private function tableExists(string $name, string $driver): bool
    {
        if ($driver === 'pgsql') {
            $r = DB::selectOne("select to_regclass(?) as reg", [$name]);
            return !empty($r?->reg);
        }

        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            return Schema::hasTable(end($parts));
        }

        return Schema::hasTable($name);
    }

    private function fromTable(string $table)
    {
        if (str_contains($table, '.')) {
            return DB::query()->from(DB::raw($table));
        }

        return DB::table($table);
    }

    private function qCol(string $col): string
    {
        return $col;
    }

    private function getColumnsSafe(string $table): array
    {
        try {
            if (DB::getDriverName() === 'pgsql' && str_contains($table, '.')) {
                [$schema, $name] = explode('.', $table, 2);

                $rows = DB::select(
                    "select column_name
                     from information_schema.columns
                     where table_schema = ? and table_name = ?",
                    [$schema, $name]
                );

                return array_map(fn($r) => $r->column_name, $rows);
            }

            $plain = str_contains($table, '.') ? explode('.', $table)[1] : $table;

            return Schema::getColumnListing($plain);
        } catch (\Throwable $e) {
            return [];
        }
    }
} 