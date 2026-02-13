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

        // ============================================================
        // 1) Defaults automáticos
        //    - ejercicio: año actual
        //    - trimestre: trimestre actual
        // ============================================================
        $now = Carbon::now();

        $ejercicio = (int)($request->query('ejercicio', $now->year));
        $trimestre = (int)($request->query('trimestre', $this->trimestreActual($now)));

        if ($ejercicio < 2000 || $ejercicio > 2100) {
            $ejercicio = (int)$now->year;
        }
        if (!in_array($trimestre, [1, 2, 3, 4], true)) {
            $trimestre = $this->trimestreActual($now);
        }

        [$inicio, $fin] = $this->periodoPorTrimestre($ejercicio, $trimestre);

        // Fecha actualización = fecha de descarga
        $fechaActualizacion = $now->copy()->startOfDay();

        // ============================================================
        // 2) Traer datos (solo aprobados)
        // ============================================================
        $empleados = Empleado::query()
            ->with(['puesto'])
            ->where('estatus_cv', 3)
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
        // 3) Crear Excel
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
        // 4) Catálogos hidden
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
        // 5) NamedRanges
        // ============================================================
        $spreadsheet->addNamedRange(new NamedRange('Hidden_18',  $hidden1, '$A$1:$A$2'));
        $spreadsheet->addNamedRange(new NamedRange('Hidden_210', $hidden2, '$A$1:$A$10'));
        $spreadsheet->addNamedRange(new NamedRange('Hidden_314', $hidden3, '$A$1:$A$2'));

        // ============================================================
        // 6) Encabezados main
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
            'A' => 8.0,
            'B' => 36.42578125,
            'C' => 38.5703125,
            'D' => 56.28515625,
            'E' => 90.0,
            'F' => 28.5703125,
            'G' => 13.5703125,
            'H' => 15.42578125,
            'I' => 58.140625,
            'J' => 70.85546875,
            'K' => 53.0,
            'L' => 56.85546875,
            'M' => 46.0,
            'N' => 81.5703125,
            'O' => 74.0,
            'P' => 62.85546875,
            'Q' => 73.140625,
            'R' => 20.0,
            'S' => 8.0,
        ];
        foreach ($widthsMain as $col => $w) {
            $sheetMain->getColumnDimension($col)->setWidth($w);
        }

        $sheetMain->getRowDimension(1)->setRowHeight(26.25);
        $this->styleHeaderMain($sheetMain, 'A1:S1');

        // ============================================================
        // 7) Encabezados experiencia
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
            'A' => 7.0,
            'B' => 28.5703125,
            'C' => 31.140625,
            'D' => 60.28515625,
            'E' => 54.85546875,
            'F' => 60.5703125,
        ];
        foreach ($widthsExp as $col => $w) {
            $sheetExp->getColumnDimension($col)->setWidth($w);
        }
        $this->styleHeaderExp($sheetExp, 'A1:F1');

        // ============================================================
        // 8) Validaciones (listas)
        // ============================================================
        $maxRowValid = 5000;
        $this->applyListValidation($sheetMain, "I2:I{$maxRowValid}", '=Hidden_18');
        $this->applyListValidation($sheetMain, "K2:K{$maxRowValid}", '=Hidden_210');
        $this->applyListValidation($sheetMain, "O2:O{$maxRowValid}", '=Hidden_314');

        // ============================================================
        // 9) Llenar datos
        // ============================================================
        $rowMain = 2;
        $rowExp  = 2;
        $tablaId = 1;

        $areaResponsable = 'Coordinación de Recursos Humanos';

        foreach ($empleados as $emp) {
            $est = $estudiosByEmp->get($emp->id_tbl_empleados);
            $puesto = $emp->puesto_label ?? $emp->puesto_actual ?? null;

            $sheetMain->getRowDimension($rowMain)->setRowHeight(16.5);

            $sheetMain->setCellValue("A{$rowMain}", $ejercicio);

            $sheetMain->setCellValue("B{$rowMain}", ExcelDate::PHPToExcel($inicio));
            $sheetMain->setCellValue("C{$rowMain}", ExcelDate::PHPToExcel($fin));

            $sheetMain->setCellValue("D{$rowMain}", $this->excelText($puesto));
            $sheetMain->setCellValue("E{$rowMain}", $this->excelText($puesto));

            $sheetMain->setCellValue("F{$rowMain}", $this->excelText($emp->nombre));
            $sheetMain->setCellValue("G{$rowMain}", $this->excelText($emp->primer_apellido));
            $sheetMain->setCellValue("H{$rowMain}", $this->excelText($emp->segundo_apellido));

            $sheetMain->setCellValue("I{$rowMain}", $this->sexoDesdeCurp($emp->curp));

            $sheetMain->setCellValue("J{$rowMain}", $this->excelText($emp->area_adscripcion));

            $sheetMain->setCellValue("K{$rowMain}", $this->excelText($est?->nivel));
            $sheetMain->setCellValue("L{$rowMain}", $this->excelText($est?->carrera_generica));

            $sheetMain->setCellValue("M{$rowMain}", $tablaId);

            $sheetMain->setCellValue("N{$rowMain}", null); // sin hipervínculo
            $sheetMain->setCellValue("O{$rowMain}", 'No'); // siempre No
            $sheetMain->setCellValue("P{$rowMain}", null); // sin hipervínculo

            $sheetMain->setCellValue("Q{$rowMain}", $areaResponsable);

            $sheetMain->setCellValue("R{$rowMain}", ExcelDate::PHPToExcel($fechaActualizacion));

            $sheetMain->setCellValue("S{$rowMain}", null);

            $sheetMain->getStyle("B{$rowMain}:C{$rowMain}")->getNumberFormat()->setFormatCode('mm-dd-yy');
            $sheetMain->getStyle("R{$rowMain}")->getNumberFormat()->setFormatCode('mm-dd-yy');

            $this->applyThinBorder($sheetMain, "A{$rowMain}:S{$rowMain}");

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

                $sheetExp->getStyle("B{$rowExp}:C{$rowExp}")->getNumberFormat()->setFormatCode('mm-dd-yy');

                $this->applyThinBorder($sheetExp, "A{$rowExp}:F{$rowExp}");

                $rowExp++;
            }

            $rowMain++;
            $tablaId++;
        }

        // ============================================================
        // 10) Descargar
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
}