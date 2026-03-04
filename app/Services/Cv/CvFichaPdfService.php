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

        $fullName = trim(
            ($empleado->nombres ?? $empleado->nombre ?? '') . ' ' .
            ($empleado->apellido_paterno ?? $empleado->primer_apellido ?? '') . ' ' .
            ($empleado->apellido_materno ?? $empleado->segundo_apellido ?? '')
        );

        $vars = [
            'fullName'          => $fullName ?: 'SIN NOMBRE',
            'puesto'            => (string)($empleado->puesto_actual ?? $empleado->puesto ?? $empleado->nombre_puesto ?? ''),
            'fechaInicioPuesto' => $this->fmtFecha($empleado->fecha_inicio_puesto ?? $empleado->fecha_ingreso ?? null),
        ];

        // EXPERIENCIAS (3)
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

        // ESTUDIOS (1)
        $est = $this->cargarEstudio((int)$empleadoId);

        // ✅ REGLA: si hay cédula => TITULADO, si no => vacío
        $cedula = trim((string)($est['cedula'] ?? ''));
        $vars['grado_avance'] = $cedula !== '' ? 'TITULADO' : '';

        $vars['est_institucion']  = (string)($est['institucion'] ?? '');
        $vars['est_pais']         = (string)($est['pais'] ?? '');
        $vars['nivel']            = (string)($est['nivel'] ?? '');
        $vars['area_estudios']    = (string)($est['area_estudios'] ?? '');
        $vars['titulo_grado']     = (string)($est['titulo_grado'] ?? '');
        $vars['carrera_generica'] = (string)($est['carrera_generica'] ?? '');

        // CURSOS (5)
        $cursos = $this->cargarCursos((int)$empleadoId);
        for ($i = 1; $i <= 5; $i++) {
            $c = $cursos[$i - 1] ?? [];

            $vars["curso{$i}_periodo"]     = (string)($c['periodo'] ?? '');
            $vars["curso{$i}_nombre"]      = (string)($c['nombre'] ?? '');
            $vars["curso{$i}_institucion"] = (string)($c['institucion'] ?? '');
        }

        // ✅ Genera PDF desde DOCX
        $curp = strtoupper(trim((string)($empleado->curp ?? '')));
        $idForName = (string)($curp ?: ($empleadoId ?: 'emp'));

        return $this->generarPdfDesdeDocx($vars, $idForName);
    }

    private function generarPdfDesdeDocx(array $vars, string $idForName): string
    {
        $templatePath = config('cvpdf.template_docx_path') ?: config('cvpdf.template_path');
        $soffice      = config('cvpdf.soffice_path', 'soffice');
        $tmpDir       = config('cvpdf.tmp_dir', storage_path('app/tmp'));
        $pdfDir       = config('cvpdf.pdf_dir', storage_path('app/tmp/pdf'));

        File::ensureDirectoryExists($tmpDir);
        File::ensureDirectoryExists($pdfDir);

        if (!$templatePath || !File::exists($templatePath)) {
            throw new \RuntimeException("No se encontró la plantilla DOCX: {$templatePath}");
        }

        // Nombre único para evitar colisiones en producción
        $baseName = 'cv_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $idForName)
            . '_' . Carbon::now()->format('Ymd_His_u')
            . '_' . mt_rand(1000, 9999);

        $docxPath = $tmpDir . DIRECTORY_SEPARATOR . $baseName . '.docx';

        // Placeholders exactos existentes en tu plantilla fixed
        $placeholders = [
            'fullName','puesto','fechaInicioPuesto',

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

        // ✅ Siempre setear todos para que NO se quede ningún ${...} visible
        foreach ($placeholders as $k) {
            $tp->setValue($k, $this->safeDocx($vars[$k] ?? ''));
        }

        $tp->saveAs($docxPath);

        $process = new Process([
            $soffice,
            '--headless',
            '--nologo',
            '--nofirststartwizard',
            '--convert-to', 'pdf',
            '--outdir', $pdfDir,
            $docxPath,
        ]);

        $process->setTimeout(120);
        $process->run();

        @unlink($docxPath);

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Error al convertir a PDF: ' . $process->getErrorOutput());
        }

        // LibreOffice genera normalmente baseName.pdf
        $pdfPath = $pdfDir . DIRECTORY_SEPARATOR . $baseName . '.pdf';

        // Fallback por si LibreOffice cambia el nombre
        if (!File::exists($pdfPath)) {
            $pdfs = glob($pdfDir . DIRECTORY_SEPARATOR . $baseName . '*.pdf') ?: [];
            if (!empty($pdfs)) $pdfPath = $pdfs[0];
        }

        if (!File::exists($pdfPath)) {
            throw new \RuntimeException('No se generó el PDF.');
        }

        return $pdfPath;
    }

    private function safeDocx($v): string
    {
        $v = (string)($v ?? '');
        $v = preg_replace("/[\\x00-\\x1F\\x7F]/u", '', $v);
        $v = str_replace(["\r\n", "\r"], "\n", $v);
        return trim($v);
    }

    // =========================
    //       DATA LOADERS
    // =========================

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
            'id', 'created_at', 'updated_at'
        ]);

        $q = $this->fromTable($table)->where('id_tbl_empleados', $empleadoId)->limit(3);
        if ($orderCol) $q->orderByDesc($orderCol);

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

        $colCedula      = $this->pickColumn($cols, [
            'cedula',
            'numero_cedula',
            'no_cedula',
            'cedula_profesional',
            'cedula_prof'
        ]);

        $colArea        = $this->pickColumn($cols, ['area_estudios', 'area_de_estudios', 'area']);

        $colCarreraEsp  = $this->pickColumn($cols, [
            'carrera_especifica',
            'carrera_específica',
            'carrera',
            'titulo_grado',
            'titulo',
            'grado',
            'nombre_titulo'
        ]);

        $colCarreraGen  = $this->pickColumn($cols, ['carrera_generica', 'carrera_general', 'carrera_gen']);

        $orderCol = $this->pickColumn($cols, ['id_tbl_cv_estudios_academicos','id','created_at','updated_at']);

        $q = $this->fromTable($table)->where('id_tbl_empleados', $empleadoId)->limit(1);
        if ($orderCol) $q->orderByDesc($orderCol);

        $q->addSelect(DB::raw(($colInstitucion ? $this->qCol($colInstitucion) : "''") . " as institucion"));
        $q->addSelect(DB::raw(($colPais        ? $this->qCol($colPais)        : "''") . " as pais"));
        $q->addSelect(DB::raw(($colNivel       ? $this->qCol($colNivel)       : "''") . " as nivel"));
        $q->addSelect(DB::raw(($colCedula      ? $this->qCol($colCedula)      : "''") . " as cedula"));
        $q->addSelect(DB::raw(($colArea        ? $this->qCol($colArea)        : "''") . " as area_estudios"));
        $q->addSelect(DB::raw(($colCarreraEsp  ? $this->qCol($colCarreraEsp)  : "''") . " as titulo_grado"));
        $q->addSelect(DB::raw(($colCarreraGen  ? $this->qCol($colCarreraGen)  : "''") . " as carrera_generica"));

        $r = $q->first();
        if (!$r) return [];

        return [
            'institucion'      => (string)($r->institucion ?? ''),
            'pais'             => (string)($r->pais ?? ''),
            'nivel'            => (string)($r->nivel ?? ''),
            'cedula'           => trim((string)($r->cedula ?? '')),
            'area_estudios'    => (string)($r->area_estudios ?? ''),
            'titulo_grado'     => (string)($r->titulo_grado ?? ''),     // Carrera específica
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

        $orderCol = $this->pickColumn($cols, ['id_tbl_cv_cursos_capacitaciones','id','created_at','updated_at']);

        $q = $this->fromTable($table)->where('id_tbl_empleados', $empleadoId)->limit(5);
        if ($orderCol) $q->orderByDesc($orderCol);

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

    // =========================
    //        HELPERS
    // =========================

    private function fmtFecha($value): string
    {
        if (!$value) return '';
        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string)$value;
        }
    }

    private function pickColumn(array $cols, array $candidates): ?string
    {
        $colsLower = array_map('strtolower', $cols);
        foreach ($candidates as $c) {
            $idx = array_search(strtolower($c), $colsLower, true);
            if ($idx !== false) return $cols[$idx];
        }
        return null;
    }

    private function resolveTable(array $baseNames): string
    {
        $schemas = ['public', 'cv', 'administracion'];
        $driver = DB::getDriverName();

        foreach ($baseNames as $name) {
            if ($this->tableExists($name, $driver)) return $name;

            foreach ($schemas as $sch) {
                $full = "{$sch}.{$name}";
                if ($this->tableExists($full, $driver)) return $full;
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
                    "select column_name from information_schema.columns where table_schema = ? and table_name = ?",
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