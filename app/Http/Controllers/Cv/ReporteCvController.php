<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\Cv\Empleado;
use App\Models\Cv\CvExperienciaLaboral;
use App\Models\Cv\CvEstudiosAcademicos;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\NamedRange;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ReporteCvController extends Controller
{
    public function exportPorEstatus(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $status = trim((string) $request->query('status', ''));
        $busqueda = mb_strtolower(trim((string) $request->query('q', '')), 'UTF-8');

        $statusMap = [
            'sin_estatus' => 0,
            'edicion' => 1,
            'enviado' => 2,
            'aprobado' => 3,
            'rechazado' => 4,
        ];

        if ($status !== '' && !array_key_exists($status, $statusMap)) {
            return response('Estatus inválido.', 422, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $query = DB::table('profesionalizacion.tbl_empleados as e')
            ->leftJoin('profesionalizacion.cat_puestos as p', 'e.id_puesto', '=', 'p.id_puesto')
            ->leftJoin('profesionalizacion.cat_unidades as u', 'e.id_unidad_adscripcion', '=', 'u.id_unidad')
            ->select([
                'e.curp',
                'e.nombre',
                'e.primer_apellido',
                'e.segundo_apellido',
                'e.correo',
                'p.nombre as puesto_catalogo',
                'e.puesto_actual',
                'e.fecha_inicio_puesto',
                'u.nombre_unidad as unidad_adscripcion',
                'e.area_adscripcion',
                'e.estatus_cv',
                'e.folio_cv',
                'e.folio_generado_en',
                'e.motivo_rechazo_cv',
                'e.creado_en',
                'e.actualizado_en',
            ]);

        if ($status !== '') {
            $query->where('e.estatus_cv', $statusMap[$status]);
        }

        if ($busqueda !== '') {
            $like = "%{$busqueda}%";
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(e.curp) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(e.nombre) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(e.primer_apellido) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(e.segundo_apellido) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(e.correo) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(e.puesto_actual) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(e.area_adscripcion) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(p.nombre) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(u.nombre_unidad) LIKE ?', [$like]);
            });
        }

        $empleados = $query
            ->orderBy('e.nombre')
            ->orderBy('e.primer_apellido')
            ->orderBy('e.segundo_apellido')
            ->get();

        if ($empleados->isEmpty()) {
            return response('No hay empleados para exportar con los filtros seleccionados.', 404, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte por estatus');

        $headers = [
            'CURP',
            'Nombre completo',
            'Correo electrónico',
            'Puesto de catálogo',
            'Puesto actual',
            'Fecha de inicio en el puesto',
            'Unidad de adscripción',
            'Área de adscripción',
            'Estatus del CV',
            'Folio del CV',
            'Fecha de generación del folio',
            'Motivo de rechazo',
            'Fecha de registro',
            'Fecha de última actualización',
        ];

        $sheet->fromArray([$headers], null, 'A1');

        $widths = [
            'A' => 22,
            'B' => 42,
            'C' => 34,
            'D' => 38,
            'E' => 38,
            'F' => 22,
            'G' => 42,
            'H' => 42,
            'I' => 18,
            'J' => 18,
            'K' => 24,
            'L' => 48,
            'M' => 22,
            'N' => 24,
        ];

        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $this->styleHeaderReporteEstatus($sheet, 'A1:N1');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:N1');

        $row = 2;

        foreach ($empleados as $empleado) {
            $nombreCompleto = $this->nombreCompletoEmpleado(
                $empleado->nombre,
                $empleado->primer_apellido,
                $empleado->segundo_apellido
            );

            $sheet->setCellValue("A{$row}", $this->textoReporte($empleado->curp));
            $sheet->setCellValue("B{$row}", $this->textoReporte($nombreCompleto));
            $sheet->setCellValue("C{$row}", $this->textoReporte($empleado->correo));
            $sheet->setCellValue("D{$row}", $this->textoReporte($empleado->puesto_catalogo));
            $sheet->setCellValue("E{$row}", $this->textoReporte($empleado->puesto_actual));
            $this->setFechaExcel($sheet, "F{$row}", $empleado->fecha_inicio_puesto, false);
            $sheet->setCellValue("G{$row}", $this->textoReporte($empleado->unidad_adscripcion));
            $sheet->setCellValue("H{$row}", $this->textoReporte($empleado->area_adscripcion));
            $sheet->setCellValue("I{$row}", $this->estatusCvDescripcion($empleado->estatus_cv));
            $sheet->setCellValue("J{$row}", $this->textoReporte($empleado->folio_cv));
            $this->setFechaExcel($sheet, "K{$row}", $empleado->folio_generado_en, true);
            $sheet->setCellValue("L{$row}", $this->textoReporte($empleado->motivo_rechazo_cv));
            $this->setFechaExcel($sheet, "M{$row}", $empleado->creado_en, true);
            $this->setFechaExcel($sheet, "N{$row}", $empleado->actualizado_en, true);

            $this->applyThinBorder($sheet, "A{$row}:N{$row}");
            $row++;
        }

        $sheet->getStyle("A2:N" . max(2, $row - 1))
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);

        $suffix = $status !== '' ? $status : 'todos';
        $filename = 'reporte_cv_por_estatus_' . $suffix . '_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function exportTerminados(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $now = Carbon::now();

        // ============================================================
        // 1) Parámetros (ejercicio/trimestre)
        // ============================================================
        $ejercicio = (int)($request->query('ejercicio', $now->year));
        $trimestre = (int)($request->query('trimestre', $this->trimestreActual($now)));

        if ($ejercicio < 2000 || $ejercicio > 2100) $ejercicio = (int)$now->year;
        if (!in_array($trimestre, [1, 2, 3, 4], true)) $trimestre = $this->trimestreActual($now);

        [$inicioTrim, $finTrim] = $this->periodoPorTrimestre($ejercicio, $trimestre);
        $inicioTrim = $inicioTrim->copy()->startOfDay();
        $finTrim    = $finTrim->copy()->endOfDay();

        // Fecha actualización (en el excel) = fecha de descarga
        $fechaActualizacionExcel = $now->copy()->startOfDay();

        // ============================================================
        // 2) Campo de fecha para filtrar (TU TABLA: creado_en/actualizado_en)
        // ============================================================
        // actualizado (default) = actualizado_en
        // creado = creado_en
        $campoFecha = (string)$request->query('campo_fecha', 'actualizado'); // 'creado' | 'actualizado'
        $colFecha = match ($campoFecha) {
            'creado' => 'creado_en',
            default  => 'actualizado_en',
        };

        // ============================================================
        // 3) (Opcional) rango manual extra: fecha_inicio/fecha_fin (YYYY-MM-DD)
        //     -> se intersecta con el trimestre
        // ============================================================
        $fechaInicioParam = $request->query('fecha_inicio');
        $fechaFinParam    = $request->query('fecha_fin');

        $iniFiltroFinal = $inicioTrim->copy();
        $finFiltroFinal = $finTrim->copy();

        if ($fechaInicioParam && $fechaFinParam) {
            try {
                $iniManual = Carbon::createFromFormat('Y-m-d', (string)$fechaInicioParam)->startOfDay();
                $finManual = Carbon::createFromFormat('Y-m-d', (string)$fechaFinParam)->endOfDay();

                // Intersección con el trimestre
                if ($iniManual->gt($iniFiltroFinal)) $iniFiltroFinal = $iniManual;
                if ($finManual->lt($finFiltroFinal)) $finFiltroFinal = $finManual;

            } catch (\Throwable $e) {
                return response('Rango de fechas inválido. Usa formato YYYY-MM-DD.', 422, [
                    'Content-Type' => 'text/plain; charset=UTF-8'
                ]);
            }
        }

        // Si no hay traslape
        if ($iniFiltroFinal->gt($finFiltroFinal)) {
            return response('No hay CV aprobados en el rango seleccionado.', 404, [
                'Content-Type' => 'text/plain; charset=UTF-8'
            ]);
        }

        // ============================================================
        // 4) Traer datos (SOLO APROBADOS) + FILTRO OBLIGATORIO POR TRIMESTRE
        // ============================================================
        $empleados = Empleado::query()
            ->with(['puesto'])
            ->where('estatus_cv', 3)
            ->whereNotNull($colFecha) // por si actualizado_en viene null
            ->whereBetween($colFecha, [$iniFiltroFinal, $finFiltroFinal]) // ✅ clave
            ->orderBy('id_tbl_empleados')
            ->get();

        if ($empleados->isEmpty()) {
            return response('No hay CV aprobados para exportar.', 404, [
                'Content-Type' => 'text/plain; charset=UTF-8'
            ]);
        }

        $ids = $empleados->pluck('id_tbl_empleados')->all();

        $experienciasByEmp = CvExperienciaLaboral::query()
            ->whereIn('id_tbl_empleados', $ids)
            ->orderBy('id_tbl_empleados')
            ->orderBy('orden')
            ->get()
            ->groupBy('id_tbl_empleados');

        $estudiosByEmp = CvEstudiosAcademicos::query()
            ->whereIn('id_tbl_empleados', $ids)
            ->get()
            ->keyBy('id_tbl_empleados');

        // ============================================================
        // 5) Crear Excel
        // ============================================================
        $spreadsheet = new Spreadsheet();

        $sheetMain = $spreadsheet->getActiveSheet();
        $sheetMain->setTitle('Reporte de Formatos');

        $hidden1 = new Worksheet($spreadsheet, 'Hidden_1');
        $hidden2 = new Worksheet($spreadsheet, 'Hidden_2');
        $hidden3 = new Worksheet($spreadsheet, 'Hidden_3');

        $spreadsheet->addSheet($hidden1);
        $spreadsheet->addSheet($hidden2);
        $spreadsheet->addSheet($hidden3);

        $hidden1->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $hidden2->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $hidden3->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        $sheetExp = new Worksheet($spreadsheet, 'Tabla_334596');
        $spreadsheet->addSheet($sheetExp);

        // ============================================================
        // 6) Catálogos hidden
        // ============================================================
        $hidden1->setCellValue('A1', 'Hombre');
        $hidden1->setCellValue('A2', 'Mujer');

        $niveles = [
            'Ninguno', 'Primaria', 'Secundaria', 'Bachillerato',
            'Carrera técnica', 'Licenciatura', 'Maestría',
            'Especialización', 'Doctorado', 'Posdoctorado',
        ];
        $r = 1;
        foreach ($niveles as $n) {
            $hidden2->setCellValue("A{$r}", $n);
            $r++;
        }

        $hidden3->setCellValue('A1', 'Si');
        $hidden3->setCellValue('A2', 'No');

        // ============================================================
        // 7) NamedRanges
        // ============================================================
        $spreadsheet->addNamedRange(new NamedRange('Hidden_18',  $hidden1, '$A$1:$A$2'));
        $spreadsheet->addNamedRange(new NamedRange('Hidden_210', $hidden2, '$A$1:$A$10'));
        $spreadsheet->addNamedRange(new NamedRange('Hidden_314', $hidden3, '$A$1:$A$2'));

        // ============================================================
        // 8) Encabezados main
        // ============================================================
        $headersMain = [
            'Ejercicio',
            'Fecha de inicio del periodo que se informa',
            'Fecha de término del periodo que se informa',
            'Denominación de puesto (Redactados con perspectiva de género)',
            'Denominación del cargo',
            'Nombre(s)',
            'Primer apellido',
            'Segundo apellido',
            'ESTE CRITERIO APLICA A PARTIR DEL 01/04/2023 -> Sexo (catálogo)',
            'Área de adscripción',
            'Nivel máximo de estudios concluido y comprobable (catálogo)',
            'Carrera genérica, en su caso',
            "Experiencia laboral \nTabla_334596",
            'Hipervínculo al documento que contenga la trayectoria (Redactados con perspectiva de género)',
            'Sanciones Administrativas definitivas aplicadas por la autoridad competente (catálogo)',
            'Hipervínculo a la resolución donde se observe la aprobación de la sanción',
            'Área(s) responsable(s) que genera(n), posee(n), publica(n) y actualizan la información',
            'Fecha de actualización',
            'Nota',
        ];
        $sheetMain->fromArray([$headersMain], null, 'A1');

        $widthsMain = [
            'A' => 8.0,'B' => 36.42578125,'C' => 38.5703125,'D' => 56.28515625,'E' => 90.0,
            'F' => 28.5703125,'G' => 13.5703125,'H' => 15.42578125,'I' => 58.140625,'J' => 70.85546875,
            'K' => 53.0,'L' => 56.85546875,'M' => 46.0,'N' => 81.5703125,'O' => 74.0,'P' => 62.85546875,
            'Q' => 73.140625,'R' => 20.0,'S' => 8.0,
        ];
        foreach ($widthsMain as $col => $w) $sheetMain->getColumnDimension($col)->setWidth($w);

        $sheetMain->getRowDimension(1)->setRowHeight(26.25);
        $this->styleHeaderMain($sheetMain, 'A1:S1');

        // ============================================================
        // 9) Encabezados experiencia
        // ============================================================
        $headersExp = [
            'ID',
            'Periodo: mes/año de inicio',
            'Periodo: mes/año de término',
            'Denominación de la institución o empresa',
            'Cargo o puesto desempeñado',
            'Campo de experiencia',
        ];
        $sheetExp->fromArray([$headersExp], null, 'A1');

        $widthsExp = [
            'A' => 7.0,'B' => 28.5703125,'C' => 31.140625,'D' => 60.28515625,'E' => 54.85546875,'F' => 60.5703125,
        ];
        foreach ($widthsExp as $col => $w) $sheetExp->getColumnDimension($col)->setWidth($w);

        $this->styleHeaderExp($sheetExp, 'A1:F1');

        // ============================================================
        // 10) Validaciones (listas)
        // ============================================================
        $maxRowValid = 5000;
        $this->applyListValidation($sheetMain, "I2:I{$maxRowValid}", '=Hidden_18');
        $this->applyListValidation($sheetMain, "K2:K{$maxRowValid}", '=Hidden_210');
        $this->applyListValidation($sheetMain, "O2:O{$maxRowValid}", '=Hidden_314');

        // ============================================================
        // 11) Llenar datos
        // ============================================================
        $rowMain = 2;
        $rowExp  = 2;
        $tablaId = 1;

        $areaResponsable = 'Coordinación de Recursos Humanos';

        foreach ($empleados as $emp) {
            $est = $estudiosByEmp->get($emp->id_tbl_empleados);

            // puesto base (comportamiento actual)
            $puesto = $emp->puesto_label ?? $emp->puesto_actual ?? null;

            // ✅ MODIFICACIÓN SOLICITADA:
            // D = puesto con perspectiva de género (si existe relación)
            // E = puesto normal (sin género)
            $puestoGenero = $this->puestoConGeneroSiExiste($puesto);

            // ============================================================
            // ✅ CAMBIO NUEVO: El "ID" de Excel debe ser el folio (solo número)
            // - Hoja 1: Columna M
            // - Hoja 2: Columna A
            // ============================================================
            $folioId = (int)($emp->folio_cv ?? 0);

            // Fallback por seguridad (no debería pasar si estatus_cv=3 siempre trae folio)
            $idExcel = $folioId > 0 ? $folioId : $tablaId;

            $sheetMain->getRowDimension($rowMain)->setRowHeight(16.5);

            $sheetMain->setCellValue("A{$rowMain}", $ejercicio);

            // Visual del periodo del trimestre
            $sheetMain->setCellValue("B{$rowMain}", ExcelDate::PHPToExcel($inicioTrim->copy()->startOfDay()));
            $sheetMain->setCellValue("C{$rowMain}", ExcelDate::PHPToExcel($finTrim->copy()->startOfDay()));

            $sheetMain->setCellValue("D{$rowMain}", $this->excelText($puestoGenero));
            $sheetMain->setCellValue("E{$rowMain}", $this->excelText($puesto));

            $sheetMain->setCellValue("F{$rowMain}", $this->excelText($emp->nombre));
            $sheetMain->setCellValue("G{$rowMain}", $this->excelText($emp->primer_apellido));
            $sheetMain->setCellValue("H{$rowMain}", $this->excelText($emp->segundo_apellido));

            $sheetMain->setCellValue("I{$rowMain}", $this->sexoDesdeCurp($emp->curp));

            // ✅ CAMBIO SOLICITADO (columna J: Área de adscripción):
            // - Si viene "X - Y" y Y != "N/A" -> mostrar Y
            // - Si viene "X - N/A" -> mostrar X
            $sheetMain->setCellValue("J{$rowMain}", $this->excelText(
                $this->areaAdscripcionFormato($emp->area_adscripcion)
            ));

            $sheetMain->setCellValue("K{$rowMain}", $this->excelText($est?->nivel));
            $sheetMain->setCellValue("L{$rowMain}", $this->excelText($est?->carrera_generica));

            // ✅ AQUÍ ES DONDE VA EL FOLIO EN HOJA 1 (Columna M)
            $sheetMain->setCellValue("M{$rowMain}", $idExcel);

            $sheetMain->setCellValue("N{$rowMain}", null);
            $sheetMain->setCellValue("O{$rowMain}", 'No');
            $sheetMain->setCellValue("P{$rowMain}", null);

            $sheetMain->setCellValue("Q{$rowMain}", $areaResponsable);

            $sheetMain->setCellValue("R{$rowMain}", ExcelDate::PHPToExcel($fechaActualizacionExcel));
            $sheetMain->setCellValue("S{$rowMain}", null);

            // ✅ FORMATO FECHAS dd/mm/yyyy (como pediste)
            $sheetMain->getStyle("B{$rowMain}:C{$rowMain}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $sheetMain->getStyle("R{$rowMain}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');

            $this->applyThinBorder($sheetMain, "A{$rowMain}:S{$rowMain}");

            // Experiencias
            $exps = $experienciasByEmp->get($emp->id_tbl_empleados, collect());
            foreach ($exps as $exp) {
                $sheetExp->getRowDimension($rowExp)->setRowHeight(16.5);

                // ✅ AQUÍ ES DONDE VA EL FOLIO EN HOJA 2 (Columna A)
                $sheetExp->setCellValue("A{$rowExp}", $idExcel);

                $ini = $this->toCarbonSafe($exp->fecha_inicio);
                $finExp = $this->toCarbonSafe($exp->fecha_termino);

                $sheetExp->setCellValue("B{$rowExp}", $ini ? ExcelDate::PHPToExcel($ini->startOfDay()) : null);
                $sheetExp->setCellValue("C{$rowExp}", $finExp ? ExcelDate::PHPToExcel($finExp->startOfDay()) : null);

                $sheetExp->setCellValue("D{$rowExp}", $this->excelText($exp->institucion));
                $sheetExp->setCellValue("E{$rowExp}", $this->excelText($exp->puesto));

                $campo = (string)($exp->campo_experiencia ?? '');
                $sheetExp->setCellValue("F{$rowExp}", $this->excelText(mb_substr($campo, 0, 200)));

                // ✅ dd/mm/yyyy también aquí
                $sheetExp->getStyle("B{$rowExp}:C{$rowExp}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                $this->applyThinBorder($sheetExp, "A{$rowExp}:F{$rowExp}");

                $rowExp++;
            }

            $rowMain++;
            $tablaId++;
        }

        // ============================================================
        // 12) Descargar
        // ============================================================
        $filename = '17_LGT_Art_70_Fr_XVII_' . $ejercicio . '_T' . $trimestre . '_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    private function trimestreActual(Carbon $now): int
    {
        $m = (int)$now->month;
        if ($m <= 3) return 1;
        if ($m <= 6) return 2;
        if ($m <= 9) return 3;
        return 4;
    }

    private function nombreCompletoEmpleado($nombre, $primerApellido, $segundoApellido): string
    {
        $partes = [
            trim((string) ($nombre ?? '')),
            trim((string) ($primerApellido ?? '')),
            trim((string) ($segundoApellido ?? '')),
        ];

        $partes = array_values(array_filter($partes, fn ($parte) => $parte !== ''));

        return implode(' ', $partes);
    }

    private function textoReporte($value): string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : 'Sin información';
    }

    private function estatusCvDescripcion($estatus): string
    {
        return match ((int) ($estatus ?? 0)) {
            1 => 'En edición',
            2 => 'Enviado',
            3 => 'Aprobado',
            4 => 'Rechazado',
            default => 'Sin estatus',
        };
    }

    private function setFechaExcel(Worksheet $sheet, string $cell, $value, bool $withTime): void
    {
        if (!$value) {
            $sheet->setCellValue($cell, 'Sin información');
            return;
        }

        try {
            $fecha = $value instanceof Carbon ? $value : Carbon::parse($value);
        } catch (\Throwable $e) {
            $sheet->setCellValue($cell, $this->textoReporte($value));
            return;
        }

        $sheet->setCellValue($cell, ExcelDate::PHPToExcel($fecha));
        $sheet->getStyle($cell)
            ->getNumberFormat()
            ->setFormatCode($withTime ? 'dd/mm/yyyy hh:mm' : 'dd/mm/yyyy');
    }

    private function styleHeaderReporteEstatus(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '006341'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(30);
    }

    private function periodoPorTrimestre(int $ejercicio, int $trimestre): array
    {
        return match ($trimestre) {
            1 => [Carbon::create($ejercicio, 1, 1)->startOfDay(), Carbon::create($ejercicio, 3, 31)->startOfDay()],
            2 => [Carbon::create($ejercicio, 4, 1)->startOfDay(), Carbon::create($ejercicio, 6, 30)->startOfDay()],
            3 => [Carbon::create($ejercicio, 7, 1)->startOfDay(), Carbon::create($ejercicio, 9, 30)->startOfDay()],
            4 => [Carbon::create($ejercicio, 10, 1)->startOfDay(), Carbon::create($ejercicio, 12, 31)->startOfDay()],
        };
    }

    private function sexoDesdeCurp(?string $curp): ?string
    {
        $curp = strtoupper(trim((string)($curp ?? '')));
        if (strlen($curp) !== 18) return null;
        $ch = $curp[10] ?? null;
        return match ($ch) {
            'H' => 'Hombre',
            'M' => 'Mujer',
            default => null,
        };
    }

    private function toCarbonSafe($v): ?Carbon
    {
        if (!$v) return null;
        if ($v instanceof Carbon) return $v;

        $s = trim((string)$v);

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $s)) {
            try { return Carbon::createFromFormat('d/m/Y', $s); } catch (\Throwable $e) {}
        }

        try { return Carbon::parse($s); } catch (\Throwable $e) {}
        return null;
    }

    private function excelText($v)
    {
        if ($v === null) return null;
        if (!is_string($v)) return $v;

        $t = ltrim($v);
        if ($t !== '' && preg_match('/^[=\+\-@]/', $t)) {
            return "'" . $v;
        }
        return $v;
    }

    private function styleHeaderMain(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => false,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E1E1E1'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }

    private function styleHeaderExp(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '333333'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }

    private function applyThinBorder(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }

    private function applyListValidation(Worksheet $sheet, string $range, string $formula): void
    {
        $dv = new DataValidation();
        $dv->setType(DataValidation::TYPE_LIST);
        $dv->setErrorStyle(DataValidation::STYLE_STOP);
        $dv->setAllowBlank(true);
        $dv->setShowInputMessage(true);
        $dv->setShowErrorMessage(true);
        $dv->setShowDropDown(true);
        $dv->setFormula1($formula);

        $sheet->setDataValidation($range, $dv);
    }

    // ============================================================
    // ✅ CAMBIO SOLICITADO (columna J: Área de adscripción):
    // - Si viene "X - Y" y Y != "N/A" -> mostrar Y
    // - Si viene "X - N/A" -> mostrar X
    // ============================================================
    private function areaAdscripcionFormato(?string $texto): ?string
    {
        if ($texto === null) return null;

        $s = str_replace("\xC2\xA0", ' ', $texto);  // NBSP
        $s = trim($s);
        if ($s === '') return null;

        // Soporta "-", "–", "—" y separa solo en 2 partes (antes/después del primer separador)
        $parts = preg_split('/\s*[–—-]\s*/u', $s, 2);

        if (!$parts || count($parts) < 2) {
            return $s;
        }

        $left  = trim((string)$parts[0]);
        $right = trim((string)$parts[1]);

        // Detecta N/A (ignora espacios y mayúsculas)
        if ($right !== '' && mb_strtoupper($right, 'UTF-8') === 'N/A') {
            return $left !== '' ? $left : null;
        }

        // Caso normal: devolver lo que viene después del guion
        return $right !== '' ? $right : ($left !== '' ? $left : null);
    }

    // ============================================================
    // ✅ MODIFICACIÓN SOLICITADA (sin Excel / sin BD):
    // Catálogo pequeño hardcodeado.
    // D: Denominación de puesto (con perspectiva de género) -> usa mapeo si existe
    // E: Denominación del cargo -> siempre el puesto normal
    // ============================================================
    private function puestoConGeneroSiExiste(?string $puesto): ?string
    {
        if ($puesto === null) return null;

        // Claves normalizadas (sin acentos, sin dobles espacios, uppercase)
        $map = [
            'JEFE DEPARTAMENTAL' => 'JEFE (A) DEPARTAMENTAL',
            'JEFE DE AREA ADMINISTRATIVA' => 'JEFE (A) DE AREA ADMINISTRATIVA',
            'SUPERVISOR DE PROCESOS' => 'SUPERVISOR (A) DE PROCESOS',
            'SUPERVISOR ACCION COMUNITARIA REGIONAL' => 'SUPERVISOR (A) ACCION COMUNITARIA REGIONAL',
            'SUPERVISOR CONSERVACION REGIONAL' => 'SUPERVISOR (A) CONSERVACION REGIONAL',
            'SUPERVISOR ADMINISTRATIVO REGIONAL' => 'SUPERVISOR (A) ADMINISTRATIVO REGIONAL',
            'JEFE DE OFICINA' => 'JEFE (A) DE OFICINA',
            'JEFE DE AREA ENFERMERIA' => 'JEFE (A) AREA ENFERMERIA',
            'JEFE DE AREA MEDICA' => 'JEFE (A) AREA MEDICA',
            'JEFE AREA ENFERMERIA' => 'JEFE (A) AREA ENFERMERIA',
        ];

        $key = $this->normKey($puesto);

        return $map[$key] ?? $puesto;
    }

    private function normKey(string $s): string
    {
        $s = str_replace("\xC2\xA0", ' ', $s);  // NBSP
        $s = trim($s);
        $s = preg_replace('/\s+/u', ' ', $s);  // colapsa espacios

        // Quita acentos para que "ÁREA" == "AREA"
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) {
            $s = $t;
        }

        return mb_strtoupper($s, 'UTF-8');
    }
}
