<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\Cv\Empleado;
use App\Models\Cv\CvExperienciaLaboral;
use App\Models\Cv\CvEstudiosAcademicos;
use App\Models\Cv\CvCursosCapacitaciones;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Process;
use ZipArchive;

class CvPdfController extends Controller
{
    public function pdfPorEmpleado($id)
    {
        $empleado = Empleado::findOrFail($id);
        $empleado->loadMissing(['puesto']);

        $pdfPath = $this->generarPdfEmpleado($empleado);

        $nombre = 'CV_' . ($empleado->curp ?? 'empleado') . '.pdf';
        return response()->download($pdfPath, $nombre)->deleteFileAfterSend(true);
    }

    public function pdfPorCurp($curp)
    {
        $empleado = Empleado::whereRaw('UPPER(curp) = ?', [strtoupper(trim($curp))])->firstOrFail();
        $empleado->loadMissing(['puesto']);

        $pdfPath = $this->generarPdfEmpleado($empleado);

        $nombre = 'CV_' . ($empleado->curp ?? 'empleado') . '.pdf';
        return response()->download($pdfPath, $nombre)->deleteFileAfterSend(true);
    }

    public function zipAprobados()
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $empleados = Empleado::query()
            ->with(['puesto'])
            ->where('estatus_cv', 3)
            ->orderBy('id_tbl_empleados')
            ->get();

        if ($empleados->isEmpty()) {
            abort(404, 'No hay empleados aprobados.');
        }

        $tmpDir = config('cvpdf.tmp_dir');
        $pdfDir = config('cvpdf.pdf_dir');

        File::ensureDirectoryExists($tmpDir);
        File::ensureDirectoryExists($pdfDir);

        $zipName = 'CV_APROBADOS_' . Carbon::now()->format('Ymd_His') . '.zip';
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el ZIP.');
        }

        foreach ($empleados as $e) {
            try {
                $pdfPath = $this->generarPdfEmpleado($e);
                $insideName = 'CV_' . ($e->curp ?? $e->id_tbl_empleados) . '.pdf';
                $zip->addFile($pdfPath, $insideName);
            } catch (\Throwable $ex) {
                continue;
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    private function generarPdfEmpleado(Empleado $empleado): string
    {
        $templatePath = config('cvpdf.template_docx_path') ?: config('cvpdf.template_path');
        $soffice = config('cvpdf.soffice_path', 'soffice');

        $tmpDir = config('cvpdf.tmp_dir');
        $pdfDir = config('cvpdf.pdf_dir');

        File::ensureDirectoryExists($tmpDir);
        File::ensureDirectoryExists($pdfDir);

        if (!File::exists($templatePath)) {
            abort(500, "No se encontró la plantilla DOCX: {$templatePath}");
        }

        // Traer relaciones CV
        $experiencias = CvExperienciaLaboral::where('id_tbl_empleados', $empleado->id_tbl_empleados)
            ->orderBy('orden')
            ->limit(3)
            ->get();

        $estudios = CvEstudiosAcademicos::where('id_tbl_empleados', $empleado->id_tbl_empleados)->first();

        $cursos = CvCursosCapacitaciones::where('id_tbl_empleados', $empleado->id_tbl_empleados)
            ->orderBy('orden')
            ->limit(5)
            ->get();

        $tp = new TemplateProcessor($templatePath);

        // ====== Campos principales (placeholders nuevos) ======
        $fullName = trim("{$empleado->nombre} {$empleado->primer_apellido} {$empleado->segundo_apellido}");
        $tp->setValue('fullName', $this->safe($fullName));
        $tp->setValue('puesto', $this->safe($empleado->puesto_label ?? $empleado->puesto_actual ?? ''));
        $tp->setValue('fechaInicioPuesto', $this->fmtFecha($empleado->fecha_inicio_puesto));

        // ====== Experiencias (3) ======
        for ($i = 1; $i <= 3; $i++) {
            $exp = $experiencias[$i - 1] ?? null;

            $tp->setValue("exp{$i}_puesto", $this->safe($exp?->puesto));
            $tp->setValue("exp{$i}_institucion", $this->safe($exp?->institucion));
            $tp->setValue("exp{$i}_sector", $this->safe($exp?->sector ? strtoupper($exp->sector) : ''));
            $tp->setValue("exp{$i}_inicio", $this->fmtFecha($exp?->fecha_inicio));
            $tp->setValue("exp{$i}_fin", $this->fmtFecha($exp?->fecha_termino));
            $tp->setValue("exp{$i}_campo", $this->safe($exp?->campo_experiencia));
        }

        // ====== Estudios ======
        $tp->setValue('est_institucion', $this->safe($estudios?->institucion));
        $tp->setValue('est_pais', $this->safe($estudios?->pais));
        $tp->setValue('nivel', $this->safe($estudios?->nivel));

        $cedula = trim((string)($estudios?->numero_cedula ?? ''));
        $tp->setValue('grado_avance', $cedula !== '' ? 'TITULADO' : '');

        $tp->setValue('area_estudios', $this->safe($estudios?->area_estudios));
        // titulo_grado en plantilla = carrera específica (según tu mapeo)
        $tp->setValue('titulo_grado', $this->safe($estudios?->carrera_especifica));
        $tp->setValue('carrera_generica', $this->safe($estudios?->carrera_generica));

        // ====== Cursos (5) ======
        for ($i = 1; $i <= 5; $i++) {
            $c = $cursos[$i - 1] ?? null;
            $tp->setValue("curso{$i}_periodo", $this->safe($c?->periodo));
            $tp->setValue("curso{$i}_nombre", $this->safe($c?->nombre_curso));
            $tp->setValue("curso{$i}_institucion", $this->safe($c?->institucion));
        }

        $baseName = 'cv_' . ($empleado->curp ?: $empleado->id_tbl_empleados) . '_' . Carbon::now()->format('Ymd_His_u') . '_' . mt_rand(1000, 9999);
        $docxPath = $tmpDir . DIRECTORY_SEPARATOR . $baseName . '.docx';

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
            abort(500, 'Error al convertir a PDF: ' . $process->getErrorOutput());
        }

        $pdfPath = $pdfDir . DIRECTORY_SEPARATOR . $baseName . '.pdf';

        if (!File::exists($pdfPath)) {
            $pdfs = glob($pdfDir . DIRECTORY_SEPARATOR . $baseName . '*.pdf') ?: [];
            if (!empty($pdfs)) $pdfPath = $pdfs[0];
        }

        if (!File::exists($pdfPath)) {
            abort(500, 'No se generó el PDF.');
        }

        return $pdfPath;
    }

    private function safe($v): string
    {
        $v = (string)($v ?? '');
        $v = preg_replace("/[\\x00-\\x1F\\x7F]/u", '', $v);
        return trim($v);
    }

    private function fmtFecha($v): string
    {
        if (!$v) return '';
        try {
            return Carbon::parse($v)->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string)$v;
        }
    }
}