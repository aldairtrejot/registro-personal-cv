<?php

namespace App\Services\Cv;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TCPDF;

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

        $page1 = [
            'hoy'               => Carbon::now()->format('d/m/Y'),
            'fullName'          => $fullName ?: 'SIN NOMBRE',
            'puesto'            => (string)($empleado->puesto_actual ?? $empleado->puesto ?? $empleado->nombre_puesto ?? ''),
            'fechaInicioPuesto' => $this->fmtFecha($empleado->fecha_inicio_puesto ?? $empleado->fecha_ingreso ?? null),
        ];

        // EXPERIENCIAS (3)
        $experiencias = $this->cargarExperiencias($empleadoId);
        for ($i = 1; $i <= 3; $i++) {
            $e = $experiencias[$i - 1] ?? [];

            $page1["exp{$i}_puesto"]      = (string)($e['puesto'] ?? '');
            $page1["exp{$i}_institucion"] = (string)($e['institucion'] ?? '');
            $page1["exp{$i}_sector"]      = (string)($e['sector'] ?? '');
            $page1["exp{$i}_inicio"]      = $this->fmtFecha($e['inicio'] ?? null);
            $page1["exp{$i}_fin"]         = $this->fmtFecha($e['fin'] ?? null);
            $page1["exp{$i}_campo"]       = (string)($e['campo'] ?? '');
        }

        // ESTUDIOS (1)
        $est = $this->cargarEstudio($empleadoId);

        // ✅ REGLA: si hay cédula => "TITULADO", si no => vacío
        $cedula = trim((string)($est['cedula'] ?? ''));
        $gradoAvance = $cedula !== '' ? 'TITULADO' : '';

        $page1 = array_merge($page1, [
            'est_institucion'  => (string)($est['institucion'] ?? ''),
            'est_pais'         => (string)($est['pais'] ?? ''),
            'nivel'            => (string)($est['nivel'] ?? ''),
            'grado_avance'     => $gradoAvance,

            // ✅ Mapeos solicitados
            // Área de Estudios -> area_estudios
            'area_estudios'    => (string)($est['area_estudios'] ?? ''),

            // Nombre del título, grado o certificado -> Carrera Específica
            // (aquí mantenemos la llave titulo_grado porque así se imprime en el PDF)
            'titulo_grado'     => (string)($est['titulo_grado'] ?? ''),

            // Carrera Genérica -> carrera_generica
            'carrera_generica' => (string)($est['carrera_generica'] ?? ''),
        ]);

        // CURSOS (5) - page2
        $cursos = $this->cargarCursos($empleadoId);
        $page2 = $page1;

        for ($i = 1; $i <= 5; $i++) {
            $c = $cursos[$i - 1] ?? [];
            $page2["curso{$i}_periodo"]     = (string)($c['periodo'] ?? '');
            $page2["curso{$i}_nombre"]      = (string)($c['nombre'] ?? '');
            $page2["curso{$i}_institucion"] = (string)($c['institucion'] ?? '');
        }

        return $this->generarConFondoImagen(
            [1 => $page1, 2 => $page2],
            config('cvpdf.template_images', [])
        );
    }

    private function generarConFondoImagen(array $fieldsByPage, array $templateImages): string
    {
        $tmpDir = config('cvpdf.tmp_dir', storage_path('app/tmp'));
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);

        if (empty($templateImages[1]) || !file_exists($templateImages[1])) {
            throw new \RuntimeException("No existe template_images[1]. Revisar ruta: " . ($templateImages[1] ?? 'NULL'));
        }
        if (empty($templateImages[2]) || !file_exists($templateImages[2])) {
            throw new \RuntimeException("No existe template_images[2]. Revisar ruta: " . ($templateImages[2] ?? 'NULL'));
        }

        $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        for ($page = 1; $page <= 2; $page++) {
            $pdf->AddPage();
            $pdf->Image($templateImages[$page], 0, 0, 215.9, 279.4);

            $coords = config("cvpdf.coords.page{$page}", []);
            $this->renderFields($pdf, $coords, $fieldsByPage[$page] ?? []);
        }

        $out = $tmpDir . DIRECTORY_SEPARATOR . 'cv_' . time() . '_' . mt_rand(1000, 9999) . '.pdf';
        $pdf->Output($out, 'F');
        return $out;
    }

    private function renderFields(TCPDF $pdf, array $coords, array $fields): void
    {
        foreach ($coords as $key => $meta) {
            $val = trim((string)($fields[$key] ?? ''));
            if ($val === '') continue;

            $x = (float)($meta['x'] ?? 0);
            $y = (float)($meta['y'] ?? 0);
            $w = (float)($meta['w'] ?? 60);
            $h = (float)($meta['h'] ?? 5);
            $size = (int)($meta['size'] ?? 10);
            $align = (string)($meta['align'] ?? 'L');

            $pdf->SetFont('helvetica', '', $size);
            $pdf->SetXY($x, $y);
            $pdf->MultiCell($w, $h, $val, 0, $align, false, 1, $x, $y);
        }
    }

    // =========================
    //       DATA LOADERS
    // =========================

    private function cargarExperiencias(int $empleadoId): array
    {
        // 🔥 FIX: encuentra la tabla real (con schema si aplica)
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

        // ✅ CÉDULA (para regla TITULADO)
        $colCedula      = $this->pickColumn($cols, [
            'cedula',
            'numero_cedula',
            'no_cedula',
            'cedula_profesional',
            'cedula_prof'
        ]);

        // ✅ Área de Estudios
        $colArea        = $this->pickColumn($cols, ['area_estudios', 'area_de_estudios', 'area']);

        // ✅ Carrera Específica (Nombre del título, grado o certificado)
        // (La imprimimos con la llave "titulo_grado" por compatibilidad con coords/page1)
        $colCarreraEsp  = $this->pickColumn($cols, [
            'carrera_especifica',
            'carrera_específica',
            'carrera',
            'titulo_grado',
            'titulo',
            'grado',
            'nombre_titulo'
        ]);

        // ✅ Carrera Genérica
        $colCarreraGen  = $this->pickColumn($cols, ['carrera_generica', 'carrera_general', 'carrera_gen']);

        $orderCol = $this->pickColumn($cols, ['id_tbl_cv_estudios_academicos','id','created_at','updated_at']);

        $q = $this->fromTable($table)->where('id_tbl_empleados', $empleadoId)->limit(1);
        if ($orderCol) $q->orderByDesc($orderCol);

        $q->addSelect(DB::raw(($colInstitucion ? $this->qCol($colInstitucion) : "''") . " as institucion"));
        $q->addSelect(DB::raw(($colPais        ? $this->qCol($colPais)        : "''") . " as pais"));
        $q->addSelect(DB::raw(($colNivel       ? $this->qCol($colNivel)       : "''") . " as nivel"));

        // ✅ cedula para la regla
        $q->addSelect(DB::raw(($colCedula      ? $this->qCol($colCedula)      : "''") . " as cedula"));

        $q->addSelect(DB::raw(($colArea        ? $this->qCol($colArea)        : "''") . " as area_estudios"));
        $q->addSelect(DB::raw(($colCarreraEsp  ? $this->qCol($colCarreraEsp)  : "''") . " as titulo_grado"));
        $q->addSelect(DB::raw(($colCarreraGen  ? $this->qCol($colCarreraGen)  : "''") . " as carrera_generica"));

        $r = $q->first();
        if (!$r) return [];

        // ✅ Calcula grado_avance según cédula
        $cedula = trim((string)($r->cedula ?? ''));
        $gradoAvance = $cedula !== '' ? 'TITULADO' : '';

        return [
            'institucion'      => (string)($r->institucion ?? ''),
            'pais'             => (string)($r->pais ?? ''),
            'nivel'            => (string)($r->nivel ?? ''),

            // ✅ para regla TITULADO
            'cedula'           => $cedula,
            'grado_avance'     => $gradoAvance,

            // ✅ mapeos solicitados
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

        // ✅ trae periodo si existe, si no devuelve ''
        $q->addSelect(DB::raw(($colPeriodo     ? $this->qCol($colPeriodo)     : "''") . " as periodo"));
        $q->addSelect(DB::raw(($colNombre      ? $this->qCol($colNombre)      : "''") . " as nombre"));
        $q->addSelect(DB::raw(($colInstitucion ? $this->qCol($colInstitucion) : "''") . " as institucion"));
        $q->addSelect(DB::raw(($colInicio      ? $this->qCol($colInicio)      : "NULL") . " as inicio"));
        $q->addSelect(DB::raw(($colFin         ? $this->qCol($colFin)         : "NULL") . " as fin"));

        return $q->get()->map(function ($r) {
            $ini = $this->fmtFecha($r->inicio ?? null);
            $fin = $this->fmtFecha($r->fin ?? null);
            $periodoFechas = trim($ini . ($fin ? " - {$fin}" : ''));

            // ✅ prioridad: periodo capturado en BD, si viene vacío usa inicio-fin
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

    /**
     * Encuentra una tabla existente aunque esté en schema (public/cv/administracion).
     * Devuelve nombre listo para usar en FROM (ej: "public.tbl_x" o "tbl_x").
     */
    private function resolveTable(array $baseNames): string
    {
        $schemas = ['public', 'cv', 'administracion'];

        $driver = DB::getDriverName();

        foreach ($baseNames as $name) {
            // 1) sin schema
            if ($this->tableExists($name, $driver)) return $name;

            // 2) con schema
            foreach ($schemas as $sch) {
                $full = "{$sch}.{$name}";
                if ($this->tableExists($full, $driver)) return $full;
            }
        }

        throw new \RuntimeException('No se encontró ninguna tabla válida. Probé: ' . implode(', ', $baseNames));
    }

    private function tableExists(string $name, string $driver): bool
    {
        // Schema::hasTable no es confiable con schema.table en postgres, por eso to_regclass
        if ($driver === 'pgsql') {
            $r = DB::selectOne("select to_regclass(?) as reg", [$name]);
            return !empty($r?->reg);
        }

        // otros motores
        if (str_contains($name, '.')) {
            // si no es pgsql y trae schema, intenta solo el nombre final
            $parts = explode('.', $name);
            return Schema::hasTable(end($parts));
        }

        return Schema::hasTable($name);
    }

    /**
     * Builder FROM que soporta schema.table (pgsql) sin romper quoting.
     */
    private function fromTable(string $table)
    {
        if (str_contains($table, '.')) {
            return DB::query()->from(DB::raw($table));
        }
        return DB::table($table);
    }

    /**
     * Column quoting simple para raw selects
     */
    private function qCol(string $col): string
    {
        // Solo devuelve el nombre (sin comillas) porque DB::raw ya lo encapsula en SQL.
        // Si tus columnas tienen mayúsculas raras o espacios (raro), aquí sí habría que quote.
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

            // sin schema
            $plain = str_contains($table, '.') ? explode('.', $table)[1] : $table;
            return Schema::getColumnListing($plain);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
