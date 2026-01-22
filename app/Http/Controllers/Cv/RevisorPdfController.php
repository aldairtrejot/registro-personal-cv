<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\Cv\Empleado;
use App\Services\Cv\CvFichaPdfService;
use Illuminate\Http\Response;
use RuntimeException;
use ZipArchive;

class RevisorPdfController extends Controller
{
    public function pdfPorEmpleadoId(int $id, CvFichaPdfService $svc)
    {
        $empleado = Empleado::query()->findOrFail($id);
        return $this->downloadPdf($empleado, $svc);
    }

    public function pdfPorCurp(string $curp, CvFichaPdfService $svc)
    {
        $curp = strtoupper(trim($curp));

        $empleado = Empleado::query()
            ->whereRaw('UPPER(curp) = ?', [$curp])
            ->firstOrFail();

        return $this->downloadPdf($empleado, $svc);
    }

    public function zipAprobados(CvFichaPdfService $svc)
    {
        // En tu mapeo: 3 = aprobado
        $empleados = Empleado::query()
            ->where('estatus_cv', 3)
            ->orderBy('id_tbl_empleados')
            ->get(['id_tbl_empleados', 'curp', 'nombre', 'primer_apellido', 'segundo_apellido']);

        if ($empleados->isEmpty()) {
            abort(404, 'No hay CV aprobados para descargar.');
        }

        $tmpDir = config('cvpdf.tmp_dir', storage_path('app/tmp'));
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new RuntimeException("No se pudo crear tmp_dir: {$tmpDir}");
        }

        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . 'CV_APROBADOS_' . date('Ymd_His') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("No se pudo crear ZIP: {$zipPath}");
        }

        $pdfGenerados = [];

        foreach ($empleados as $emp) {
            $pdfPath = $svc->generarPdfPorEmpleado($emp);
            $pdfGenerados[] = $pdfPath;

            $curp = strtoupper(trim((string) $emp->curp));
            $nombre = trim(implode('_', array_filter([
                $emp->nombre,
                $emp->primer_apellido,
                $emp->segundo_apellido,
            ])));

            $nombre = preg_replace('/[^A-Za-z0-9_\-]/', '', $nombre);
            if ($nombre === '') $nombre = 'SIN_NOMBRE';

            $zipName = "CV_{$curp}_{$nombre}.pdf";
            $zip->addFile($pdfPath, $zipName);
        }

        $zip->close();

        // Limpia PDFs temporales
        foreach ($pdfGenerados as $p) {
            if (is_file($p)) @unlink($p);
        }

        return response()->download($zipPath, basename($zipPath))->deleteFileAfterSend(true);
    }

    private function downloadPdf(Empleado $empleado, CvFichaPdfService $svc)
    {
        $pdfPath = $svc->generarPdfPorEmpleado($empleado);

        $curp = strtoupper(trim((string) $empleado->curp));
        $filename = "CV_{$curp}.pdf";

        return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
    }
}
