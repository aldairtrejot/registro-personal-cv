<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\Cv\Empleado;
use App\Services\Cv\CvFichaPdfService;
use Illuminate\Support\Carbon;
use ZipArchive;

class RevisorPdfController extends Controller
{
    public function __construct(private CvFichaPdfService $service) {}

    public function pdfPorEmpleadoId($id)
    {
        $empleado = Empleado::findOrFail($id);

        $pdfPath = $this->service->generarPdfPorEmpleado($empleado);

        $curp = strtoupper(trim((string)$empleado->curp));
        return response()->download($pdfPath, "FICHA_CURRICULAR_{$curp}.pdf")
            ->deleteFileAfterSend(true);
    }

    public function pdfPorCurp(string $curp)
    {
        $curp = strtoupper(trim($curp));

        $empleado = Empleado::query()
            ->whereRaw('UPPER(curp) = ?', [$curp])
            ->firstOrFail();

        $pdfPath = $this->service->generarPdfPorEmpleado($empleado);

        return response()->download($pdfPath, "FICHA_CURRICULAR_{$curp}.pdf")
            ->deleteFileAfterSend(true);
    }

    public function zipAprobados()
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $empleados = Empleado::query()
            ->where('estatus_cv', 3)
            ->orderBy('id_tbl_empleados')
            ->get();

        if ($empleados->isEmpty()) {
            return response()->json(['message' => 'No hay empleados aprobados.'], 404);
        }

        $tmpDir = config('cvpdf.tmp_dir');

        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            return response()->json(['message' => "No se pudo crear tmp_dir: {$tmpDir}"], 500);
        }

        $stamp = Carbon::now()->format('Ymd_His');
        $zipName = "CV_APROBADOS_{$stamp}.zip";
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;

        $zip = new ZipArchive();

        $openResult = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            return response()->json([
                'message' => 'No se pudo crear el ZIP. Revisa que PHP tenga habilitado ZipArchive (ext-zip).',
                'zipPath' => $zipPath,
                'openResult' => $openResult,
            ], 500);
        }

        try {
            foreach ($empleados as $e) {
                try {
                    $pdfPath = $this->service->generarPdfPorEmpleado($e);

                    $curp = strtoupper(trim((string)$e->curp));
                    $curpSafe = preg_replace('/[^A-Z0-9]/', '', $curp) ?: (string)$e->id_tbl_empleados;

                    $insideName = "FICHA_CURRICULAR_{$curpSafe}.pdf";

                    if (is_file($pdfPath)) {
                        $zip->addFile($pdfPath, $insideName);
                        @unlink($pdfPath); // limpiamos PDF temporal
                    }
                } catch (\Throwable $ex) {
                    // si uno falla, seguimos con los demás
                    continue;
                }
            }
        } finally {
            $zip->close();
        }

        if (!is_file($zipPath)) {
            return response()->json([
                'message' => 'El ZIP no se generó en disco. Revisa permisos y ext-zip.',
                'zipPath' => $zipPath,
            ], 500);
        }

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }
}
