<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\Cv\Empleado;
use App\Models\Cv\CvExperienciaLaboral;
use App\Models\Cv\CvEstudiosAcademicos;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

            $sheetMain->setCellValue("J{$rowMain}", $this->excelText($emp->area_adscripcion));

            $sheetMain->setCellValue("K{$rowMain}", $this->excelText($est?->nivel));
            $sheetMain->setCellValue("L{$rowMain}", $this->excelText($est?->carrera_generica));

            $sheetMain->setCellValue("M{$rowMain}", $tablaId);

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

                $sheetExp->setCellValue("A{$rowExp}", $tablaId);

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