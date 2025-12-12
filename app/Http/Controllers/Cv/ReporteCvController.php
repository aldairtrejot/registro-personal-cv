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
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReporteCvController extends Controller
{
    /**
     * Descargar reporte de empleados que tienen CV APROBADO.
     * Solo estatus_cv = 3.
     * Genera un archivo Excel (.xlsx) con varias hojas:
     *  - Datos personales
     *  - Resumen registro CV
     *  - Experiencias laborales
     *  - Estudios académicos
     *  - Cursos y capacitaciones
     */
    public function exportTerminados(Request $request)
    {
        // 🔥 SOLO APROBADOS
        $empleados = Empleado::query()
            ->where('estatus_cv', 3) // 3 = Aprobado
            ->get();

        $filename = 'reporte_cv_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        if ($empleados->isEmpty()) {
            // Excel con una sola hoja y mensaje
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Sin datos');
            $sheet->setCellValue('A1', 'No hay empleados con CV aprobado.');

            // Ajustar texto y ancho
            $this->ajustarHoja($sheet);

            $writer = new Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        // IDs de empleados para cruzar con las demás tablas
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

        // Agrupamos experiencias por empleado para sacar el "sector principal"
        $experienciasPorEmpleado = $experiencias->groupBy('id_tbl_empleados');

        $statusMap = [
            1 => 'En edición',
            2 => 'Enviado',
            3 => 'Aprobado',
            4 => 'Rechazado',
        ];

        // ==========================
        //   CREAR ARCHIVO EXCEL
        // ==========================
        $spreadsheet = new Spreadsheet();

        /*
         |==========================================================
         |  HOJA 1: DATOS PERSONALES / INFORMACIÓN GENERAL
         |==========================================================
         */
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Datos personales');

        $hoja1 = [];
        $hoja1[] = [
            'CURP',
            'CORREO',
            'NOMBRE',
            'PATERNO',
            'MATERNO',
            'PUESTO',
            'CARRERA ESPECÍFICA',
            'CARRERA GENÉRICA',
            'ÁREA DE ESTUDIO',
            'UNIDAD',
            'COORDINACION',
            'NIVEL ESTUDIO',
            'SECTOR',
            'ESTATUS REGISTRO',
            // ❌ Se quitó "FECHA REGISTRO"
        ];

        foreach ($empleados as $e) {
            // Separar UNIDAD y COORDINACIÓN desde area_adscripcion
            $unidad = '';
            $coord = '';

            if (!empty($e->area_adscripcion)) {
                $parts = array_map('trim', explode('-', $e->area_adscripcion, 2));
                $unidad = $parts[0] ?? '';
                $coord = $parts[1] ?? '';
            }

            /** @var \App\Models\Cv\CvEstudiosAcademicos|null $est */
            $est = $estudios->get($e->id_tbl_empleados);

            // experiencia principal para "sector"
            $expCollection = $experienciasPorEmpleado->get($e->id_tbl_empleados);
            $expPrincipal = $expCollection ? $expCollection->first() : null;
            $sectorPrincipal = $expPrincipal ? $expPrincipal->sector : '';

            $estatusLabel = $statusMap[(int) $e->estatus_cv] ?? '';

            $correo = $e->correo ?? $e->correo_electronico ?? $e->email ?? '';

            $hoja1[] = [
                $e->curp,
                $correo,
                $e->nombre,
                $e->primer_apellido,
                $e->segundo_apellido,
                $e->puesto_actual,
                $est->carrera_especifica ?? '',
                $est->carrera_generica ?? '',
                $est->area_estudios ?? '',
                $unidad,
                $coord,
                $est->nivel ?? '',
                $sectorPrincipal,
                $estatusLabel,
            ];
        }

        $sheet1->fromArray($hoja1, null, 'A1');
        $this->estilizarEncabezadoYWrap($sheet1);

        /*
         |==========================================================
         |  HOJA 2: RESUMEN REGISTRO CV
         |==========================================================
         */
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Resumen registro');

        $hoja2 = [];
        $hoja2[] = [
            // ❌ Se quitó "FECHA REGISTRO"
            'CURP',
            'PUESTO ACTUAL',
            'PUESTO ESPECIFICO',
            'FECHA INGRESO',
            'UNIDAD',
            'COORDINACION',
        ];

        foreach ($empleados as $e) {
            $unidad = '';
            $coord = '';

            if (!empty($e->area_adscripcion)) {
                $parts = array_map('trim', explode('-', $e->area_adscripcion, 2));
                $unidad = $parts[0] ?? '';
                $coord = $parts[1] ?? '';
            }

            $hoja2[] = [
                $e->curp,
                $e->puesto_actual,
                // Si tienes un campo separado de "puesto específico", cámbialo aquí.
                $e->puesto_actual,
                $e->fecha_inicio_puesto,
                $unidad,
                $coord,
            ];
        }

        $sheet2->fromArray($hoja2, null, 'A1');
        $this->estilizarEncabezadoYWrap($sheet2);

        /*
         |==========================================================
         |  HOJA 3: EXPERIENCIAS LABORALES
         |==========================================================
         */
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Experiencias');

        $hoja3 = [];
        $hoja3[] = [
            'CURP',
            'INICIO',
            'FIN',
            'SECTOR',
            'INSTITUCIÓN',
            'CARGO',
            'EXPERIENCIA',
        ];

        foreach ($experiencias as $exp) {
            /** @var \App\Models\Cv\Empleado|null $emp */
            $emp = $empleados->firstWhere('id_tbl_empleados', $exp->id_tbl_empleados);
            if (!$emp) {
                continue;
            }

            $hoja3[] = [
                $emp->curp,
                $exp->fecha_inicio,
                $exp->fecha_termino,
                $exp->sector,
                $exp->institucion,
                $exp->puesto,
                $exp->campo_experiencia,
            ];
        }

        $sheet3->fromArray($hoja3, null, 'A1');
        $this->estilizarEncabezadoYWrap($sheet3);

        /*
         |==========================================================
         |  HOJA 4: ESTUDIOS ACADÉMICOS
         |==========================================================
         */
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Estudios');

        $hoja4 = [];
        $hoja4[] = [
            'CURP',
            'INSTITUCIÓN',
            'PAÍS',
            'NIVEL DE ESTUDIOS',
            'NÚMERO DE CÉDULA',
            'CARRERA ESPECÍFICA',
            'CARRERA GENÉRICA',
            'ÁREA DE ESTUDIOS',
        ];

        foreach ($empleados as $e) {
            /** @var \App\Models\Cv\CvEstudiosAcademicos|null $est */
            $est = $estudios->get($e->id_tbl_empleados);

            if (!$est) {
                continue;
            }

            $hoja4[] = [
                $e->curp,
                $est->institucion,
                $est->pais,
                $est->nivel,
                $est->numero_cedula,
                $est->carrera_especifica,
                $est->carrera_generica,
                $est->area_estudios,
            ];
        }

        $sheet4->fromArray($hoja4, null, 'A1');
        $this->estilizarEncabezadoYWrap($sheet4);

        /*
         |==========================================================
         |  HOJA 5: CURSOS Y CAPACITACIONES
         |==========================================================
         */
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Cursos');

        $hoja5 = [];
        $hoja5[] = [
            'CURP',
            'PERIODO',
            'NOMBRE CURSO O CAPACITACIÓN',
            'NOMBRE DE LA INSTITUCIÓN',
        ];

        foreach ($cursos as $curso) {
            /** @var \App\Models\Cv\Empleado|null $emp */
            $emp = $empleados->firstWhere('id_tbl_empleados', $curso->id_tbl_empleados);
            if (!$emp) {
                continue;
            }

            $hoja5[] = [
                $emp->curp,
                $curso->periodo,
                $curso->nombre_curso,
                $curso->institucion,
            ];
        }

        $sheet5->fromArray($hoja5, null, 'A1');
        $this->estilizarEncabezadoYWrap($sheet5);

        // ==========================
        //   DESCARGAR ARCHIVO
        // ==========================
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Aplica estilo a encabezados (fila 1) y wrap text a toda la hoja.
     * - Encabezado: fondo #10312B, letras blancas, negritas, centrado.
     * - Columnas: autoSize.
     */
    private function estilizarEncabezadoYWrap($sheet): void
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        if ($highestRow < 1) {
            return;
        }

        // Estilo encabezado
        $headerRange = 'A1:' . $highestColumn . '1';

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10312B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Wrap text para todo el contenido
        $sheet
            ->getStyle('A1:' . $highestColumn . $highestRow)
            ->getAlignment()
            ->setWrapText(true);

        // AutoSize columnas
        foreach (range('A', $highestColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Altura del header
        $sheet->getRowDimension(1)->setRowHeight(25);
    }

    /**
     * Ajusta ancho y wrapText en una hoja sencilla (sin encabezados especiales).
     */
    private function ajustarHoja($sheet): void
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $sheet
            ->getStyle('A1:' . $highestColumn . $highestRow)
            ->getAlignment()
            ->setWrapText(true);

        foreach (range('A', $highestColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
