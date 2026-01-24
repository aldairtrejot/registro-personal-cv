<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\Cv\Empleado;
use App\Models\Cv\CvExperienciaLaboral;
use App\Models\Cv\CvEstudiosAcademicos;
use App\Models\Cv\CvCursosCapacitaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReporteCvController extends Controller
{
    public function exportTerminados(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        // 🔥 SOLO APROBADOS
        $empleados = Empleado::query()
            ->where('estatus_cv', 3)
            ->get();

        $filename = 'Datos_CV_Publico_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        // IDs para cruzar con tablas CV
        $ids = $empleados->pluck('id_tbl_empleados')->all();

        $experiencias = CvExperienciaLaboral::query()
            ->whereIn('id_tbl_empleados', $ids)
            ->orderBy('id_tbl_empleados')
            ->orderBy('orden')
            ->get();

        $estudios = CvEstudiosAcademicos::query()
            ->whereIn('id_tbl_empleados', $ids)
            ->get()
            ->keyBy('id_tbl_empleados');

        $cursos = CvCursosCapacitaciones::query()
            ->whereIn('id_tbl_empleados', $ids)
            ->orderBy('id_tbl_empleados')
            ->orderBy('orden')
            ->get();

        $empleadosById = $empleados->keyBy('id_tbl_empleados');

        // ==========================
        //   CREAR ARCHIVO EXCEL
        // ==========================
        $spreadsheet = new Spreadsheet();

        // Hoja 1: FICHA CURRICULAR
        $sheetFicha = $spreadsheet->getActiveSheet();
        $sheetFicha->setTitle('FICHA CURRICULAR');

        // Hoja 2: EXPERIENCIA LABORAL
        $sheetExp = $spreadsheet->createSheet();
        $sheetExp->setTitle('EXPERIENCIA LABORAL');

        // Hoja 3: ESTUDIOS
        $sheetEst = $spreadsheet->createSheet();
        $sheetEst->setTitle('ESTUDIOS');

        // Hoja 4: CURSOS
        $sheetCur = $spreadsheet->createSheet();
        $sheetCur->setTitle('CURSOS');

        // Hoja 5: Listas (oculta)
        $sheetListas = $spreadsheet->createSheet();
        $sheetListas->setTitle('Listas');
        $sheetListas->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $sheetListas->setCellValue('A1', 'ESPECIFICAS'); // mínimo para que exista la hoja

        // ==========================
        //   HEADERS EXACTOS (template)
        // ==========================
        // FICHA: A..H y I vacío (pero el filtro del template es A1:I1)
        $sheetFicha->fromArray([[
            'CURP',
            'RFC',
            'Nombre (s)',
            'Primer Apellido',
            'Segundo Apellido',
            'Puesto actual ',
            'Fecha de inicio',
            'Área de Adscrición',
        ]], null, 'A1');

        $sheetExp->fromArray([[
            'RFC',
            'Periodo: dia /mes/año de inicio',
            'Periodo: dia/ mes/año de término',
            'SECTOR (PÚBLICO o PRIVADO)',
            'Denominación de la institución o empresa (Nombre completo) ',
            'Cargo o puesto desempeñado',
            'Campo de experiencia En 100 caracteres',
        ]], null, 'A1');

        $sheetEst->fromArray([[
            'RFC',
            'Institución',
            'País',
            'Nivel de máximo de  estudios concluidos y comprobado',
            'Número de cédula',
            'Carrera Especifica',
            'Carrera Génerica',
            'Área de Estudios',
        ]], null, 'A1');

        $sheetCur->fromArray([[
            'RFC',
            'PERIODO',
            'NOMBRE CURSO O CAPACITACIÓN',
            'NOMBRE DE LA INSTITUCIÓN',
        ]], null, 'A1');

        // ==========================
        //   LAYOUT (anchos / alturas) igual al template
        // ==========================
        $this->applyLayoutFicha($sheetFicha);
        $this->applyLayoutExperiencia($sheetExp);
        $this->applyLayoutEstudios($sheetEst);
        $this->applyLayoutCursos($sheetCur);

        // ==========================
        //   AUTO FILTER igual al template
        // ==========================
        $sheetFicha->setAutoFilter('A1:H1');
        $sheetExp->setAutoFilter('A1:G400');
        $sheetEst->setAutoFilter('A1:H1');
        // CURSOS: el template NO tiene autofilter

        // ==========================
        //   VALIDACIONES igual (clave) al template
        // ==========================
        // Rangos enormes como template (1..1048576). Si te preocupa performance, bájalo a 5000.
        $this->addTextLengthValidation($sheetFicha, 'A1:A1048576', 'equal', '18'); // CURP
        $this->addTextLengthValidation($sheetFicha, 'B1:B1048576', 'equal', '13'); // RFC
        $this->addDateBetweenValidation($sheetFicha, 'G1:G1048576', '3654', '46022'); // fechas

        $this->addTextLengthValidation($sheetExp, 'A1:A1048576', 'equal', '13'); // RFC
        $this->addDateBetweenValidation($sheetExp, 'B1:C1048576', '3654', '46022'); // fechas
        $this->addTextLengthValidation($sheetExp, 'G1:G1048576', 'lessThanOrEqual', '100'); // 100 chars

        $this->addTextLengthValidation($sheetEst, 'A1:A1048576', 'equal', '13'); // RFC
        $this->addTextLengthValidation($sheetCur, 'A1:A1048576', 'equal', '13'); // RFC

        // Formato fecha para columnas con fecha
        $sheetFicha->getStyle('G:G')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheetExp->getStyle('B:C')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

        // ==========================
        //   LLENAR DATOS
        // ==========================
        // FICHA
        $r = 2;
        foreach ($empleados as $e) {
            $curp = $this->normalizeCurp($e->curp);
            $rfc13 = $this->rfc13FromCurp($curp); // 13 chars

            $sheetFicha->setCellValue("A{$r}", $this->excelText($curp));
            $sheetFicha->setCellValue("B{$r}", $this->excelText($rfc13));
            $sheetFicha->setCellValue("C{$r}", $this->excelText($e->nombre));
            $sheetFicha->setCellValue("D{$r}", $this->excelText($e->primer_apellido));
            $sheetFicha->setCellValue("E{$r}", $this->excelText($e->segundo_apellido));
            $sheetFicha->setCellValue("F{$r}", $this->excelText($e->puesto_actual));

            $this->setExcelDate($sheetFicha, "G{$r}", $e->fecha_inicio_puesto);

            $sheetFicha->setCellValue("H{$r}", $this->excelText($e->area_adscripcion));
            // I la dejamos vacía
            $r++;
        }

        // EXPERIENCIA
        $r = 2;
        foreach ($experiencias as $exp) {
            $emp = $empleadosById->get($exp->id_tbl_empleados);
            if (!$emp) continue;

            $curp = $this->normalizeCurp($emp->curp);
            $rfc13 = $this->rfc13FromCurp($curp);

            $sheetExp->setCellValue("A{$r}", $this->excelText($rfc13));
            $this->setExcelDate($sheetExp, "B{$r}", $exp->fecha_inicio);
            $this->setExcelDate($sheetExp, "C{$r}", $exp->fecha_termino);
            $sheetExp->setCellValue("D{$r}", $this->excelText($exp->sector));
            $sheetExp->setCellValue("E{$r}", $this->excelText($exp->institucion));
            $sheetExp->setCellValue("F{$r}", $this->excelText($exp->puesto));

            $campo = (string)($exp->campo_experiencia ?? '');
            $sheetExp->setCellValue("G{$r}", $this->excelText(mb_substr($campo, 0, 100)));

            $r++;
        }

        // ESTUDIOS (1 registro por empleado, como tu modelo actual)
        $r = 2;
        foreach ($empleados as $e) {
            $est = $estudios->get($e->id_tbl_empleados);
            if (!$est) continue;

            $curp = $this->normalizeCurp($e->curp);
            $rfc13 = $this->rfc13FromCurp($curp);

            $sheetEst->setCellValue("A{$r}", $this->excelText($rfc13));
            $sheetEst->setCellValue("B{$r}", $this->excelText($est->institucion));
            $sheetEst->setCellValue("C{$r}", $this->excelText($est->pais));
            $sheetEst->setCellValue("D{$r}", $this->excelText($est->nivel));
            $sheetEst->setCellValue("E{$r}", $this->excelText($est->numero_cedula));
            $sheetEst->setCellValue("F{$r}", $this->excelText($est->carrera_especifica));
            $sheetEst->setCellValue("G{$r}", $this->excelText($est->carrera_generica));
            $sheetEst->setCellValue("H{$r}", $this->excelText($est->area_estudios));

            $r++;
        }

        // CURSOS
        $r = 2;
        foreach ($cursos as $curso) {
            $emp = $empleadosById->get($curso->id_tbl_empleados);
            if (!$emp) continue;

            $curp = $this->normalizeCurp($emp->curp);
            $rfc13 = $this->rfc13FromCurp($curp);

            $sheetCur->setCellValue("A{$r}", $this->excelText($rfc13));
            $sheetCur->setCellValue("B{$r}", $this->excelText($curso->periodo)); // el template no fuerza fecha aquí
            $sheetCur->setCellValue("C{$r}", $this->excelText($curso->nombre_curso));
            $sheetCur->setCellValue("D{$r}", $this->excelText($curso->institucion));
            $r++;
        }

        // ==========================
        //   DESCARGAR
        // ==========================
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ==========================
    //   LAYOUT HELPERS (igual al template)
    // ==========================
    private function applyLayoutFicha(Worksheet $s): void
    {
        $this->styleHeader($s, 'A1:H1', 16.5);

        $widths = [
            'A' => 27.5703125,
            'B' => 19.7109375,
            'C' => 29.5703125,
            'D' => 20.140625,
            'E' => 22.28515625,
            'F' => 58.85546875,
            'G' => 19.28515625,
            'H' => 107.28515625,
        ];
        foreach ($widths as $col => $w) {
            $s->getColumnDimension($col)->setWidth($w);
        }

        $s->getStyle('A:I')->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function applyLayoutExperiencia(Worksheet $s): void
    {
        $this->styleHeader($s, 'A1:G1', 51.75);

        $widths = [
            'A' => 26.5703125,
            'B' => 51.28515625,
            'C' => 47.5703125,
            'D' => 36.140625,
            'E' => 138.85546875,
            'F' => 142.140625,
            'G' => 206.28515625,
        ];
        foreach ($widths as $col => $w) {
            $s->getColumnDimension($col)->setWidth($w);
        }

        $s->getStyle('A:G')->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function applyLayoutEstudios(Worksheet $s): void
    {
        $this->styleHeader($s, 'A1:H1', 39.75);

        $widths = [
            'A' => 17.7109375,
            'B' => 61.0,
            'C' => 29.7109375,
            'D' => 39.85546875,
            'E' => 35.7109375,
            'F' => 34.0,
            'G' => 34.85546875,
            'H' => 50.85546875,
        ];
        foreach ($widths as $col => $w) {
            $s->getColumnDimension($col)->setWidth($w);
        }

        $s->getStyle('A:H')->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function applyLayoutCursos(Worksheet $s): void
    {
        $this->styleHeader($s, 'A1:D1', 26.25);

        $widths = [
            'A' => 17.7109375,
            'B' => 34.140625,
            'C' => 42.5703125,
            'D' => 69.7109375,
        ];
        foreach ($widths as $col => $w) {
            $s->getColumnDimension($col)->setWidth($w);
        }

        $s->getStyle('A:D')->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function styleHeader(Worksheet $s, string $range, float $rowHeight): void
    {
        // Estilo “similar” (el template usa theme, pero esto queda oficial/legible)
        $s->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10312B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $s->getRowDimension(1)->setRowHeight($rowHeight);
    }

    // ==========================
    //   VALIDATION HELPERS
    // ==========================
    private function addTextLengthValidation(Worksheet $sheet, string $range, string $operator, string $formula1): void
    {
        $dv = new DataValidation();
        $dv->setType(DataValidation::TYPE_TEXTLENGTH);
        $dv->setErrorStyle(DataValidation::STYLE_STOP);
        $dv->setAllowBlank(true);
        $dv->setShowInputMessage(true);
        $dv->setShowErrorMessage(true);
        $dv->setErrorTitle('Dato inválido');
        $dv->setError('El valor no cumple con la longitud requerida.');
        $dv->setPromptTitle('Validación');
        $dv->setPrompt('Captura un valor con la longitud requerida.');
        $dv->setOperator($operator);
        $dv->setFormula1($formula1);

        // Aplicar al rango (solo set en la primera celda y luego clonar)
        [$start, $end] = explode(':', $range);
        $sheet->getCell($start)->setDataValidation(clone $dv);
        $sheet->setDataValidation($range, $dv);
    }

    private function addDateBetweenValidation(Worksheet $sheet, string $range, string $minSerial, string $maxSerial): void
    {
        $dv = new DataValidation();
        $dv->setType(DataValidation::TYPE_DATE);
        $dv->setErrorStyle(DataValidation::STYLE_STOP);
        $dv->setAllowBlank(true);
        $dv->setShowInputMessage(true);
        $dv->setShowErrorMessage(true);
        $dv->setErrorTitle('Fecha inválida');
        $dv->setError('La fecha está fuera del rango permitido.');
        $dv->setPromptTitle('Fecha');
        $dv->setPrompt('Captura una fecha válida.');
        $dv->setOperator(DataValidation::OPERATOR_BETWEEN);
        $dv->setFormula1($minSerial);
        $dv->setFormula2($maxSerial);

        $sheet->setDataValidation($range, $dv);
    }

    // ==========================
    //   VALUE HELPERS
    // ==========================
    private function setExcelDate(Worksheet $sheet, string $cell, $value): void
    {
        if (empty($value)) {
            $sheet->setCellValue($cell, null);
            return;
        }

        try {
            $dt = Carbon::parse($value)->startOfDay();
            $sheet->setCellValue($cell, ExcelDate::PHPToExcel($dt));
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        } catch (\Throwable $e) {
            $sheet->setCellValue($cell, $this->excelText((string)$value));
        }
    }

    private function normalizeCurp(?string $curp): string
    {
        if (!$curp) return '';
        $curp = strtoupper(trim($curp));
        $curp = preg_replace('/\s+/', '', $curp);
        return $curp ?: '';
    }

    /**
     * RFC de 13 basado en CURP: primeros 10 (4 letras + YYMMDD) + 'XXX'
     * (cumple longitud 13; no es RFC oficial).
     */
    private function rfc13FromCurp(?string $curp): string
    {
        $curp = $this->normalizeCurp($curp);
        if (strlen($curp) < 10) return '';
        return substr($curp, 0, 10) . 'XXX';
    }

    /**
     * Previene “Excel injection” (= + - @)
     */
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
}
