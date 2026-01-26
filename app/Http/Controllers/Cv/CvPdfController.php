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
        $templatePath = config('cvpdf.template_path');
        $soffice = config('cvpdf.soffice_path');

        $tmpDir = config('cvpdf.tmp_dir');
        $pdfDir = config('cvpdf.pdf_dir');

        File::ensureDirectoryExists($tmpDir);
        File::ensureDirectoryExists($pdfDir);

        if (!File::exists($templatePath)) {
            abort(500, "No se encontró la plantilla: {$templatePath}");
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

        $folio = $empleado->folio_cv ?: '';

        $tp->setValue('FOLIO', $this->safe($folio));
        $tp->setValue('NOMBRE', $this->safe(trim("{$empleado->nombre} {$empleado->primer_apellido} {$empleado->segundo_apellido}")));

        // ✅ AQUÍ VA EL PUESTO CORRECTO
        $tp->setValue('PUESTO_ACTUAL', $this->safe($empleado->puesto_label));

        $tp->setValue('FECHA_INICIO', $this->fmtFecha($empleado->fecha_inicio_puesto));

        for ($i = 1; $i <= 3; $i++) {
            $exp = $experiencias[$i - 1] ?? null;

            $tp->setValue("EXP{$i}_PUESTO", $this->safe($exp?->puesto));
            $tp->setValue("EXP{$i}_INST", $this->safe($exp?->institucion));
            $tp->setValue("EXP{$i}_SECTOR", $this->safe($exp?->sector ? strtoupper($exp->sector) : ''));
            $tp->setValue("EXP{$i}_FINI", $this->fmtFecha($exp?->fecha_inicio));
            $tp->setValue("EXP{$i}_FFIN", $this->fmtFecha($exp?->fecha_termino));
            $tp->setValue("EXP{$i}_CAMPO", $this->safe($exp?->campo_experiencia));
        }

        $tp->setValue('INST', $this->safe($estudios?->institucion));
        $tp->setValue('PAIS', $this->safe($estudios?->pais));
        $tp->setValue('NIVEL', $this->safe($estudios?->nivel));
        $tp->setValue('CEDULA', $this->safe($estudios?->numero_cedula));
        $tp->setValue('CARRERA_ESP', $this->safe($estudios?->carrera_especifica));
        $tp->setValue('CARRERA_GEN', $this->safe($estudios?->carrera_generica));
        $tp->setValue('AREA_EST', $this->safe($estudios?->area_estudios));

        for ($i = 1; $i <= 5; $i++) {
            $c = $cursos[$i - 1] ?? null;
            $tp->setValue("CUR{$i}_PERIODO", $this->safe($c?->periodo));
            $tp->setValue("CUR{$i}_NOMBRE", $this->safe($c?->nombre_curso));
            $tp->setValue("CUR{$i}_INST", $this->safe($c?->institucion));
        }

        $baseName = 'cv_' . ($empleado->curp ?: $empleado->id_tbl_empleados) . '_' . Carbon::now()->format('Ymd_His');
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

        if (!$process->isSuccessful()) {
            @unlink($docxPath);
            abort(500, 'Error al convertir a PDF: ' . $process->getErrorOutput());
        }

        $pdfPath = $pdfDir . DIRECTORY_SEPARATOR . $baseName . '.pdf';

        @unlink($docxPath);

        if (!File::exists($pdfPath)) {
            abort(500, 'No se generó el PDF.');
        }

        return $pdfPath;
    }

    private function safe($v): string
    {
        $v = (string)($v ?? '');
        $v = preg_replace("/[\\x00-\\x1F\\x7F]/u", '', $v);
        return $v;
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
