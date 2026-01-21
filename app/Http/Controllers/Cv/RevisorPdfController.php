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

    /**
     * PDF individual por ID (revisor)
     */
    public function pdfPorEmpleadoId($id)
    {
        $empleado = Empleado::findOrFail($id);

        // Si quieres restringir solo aprobados:
        // if ((int)$empleado->estatus_cv !== 3) abort(403, 'Solo CV aprobados generan PDF');

        $pdfPath = $this->service->generarPdfPorEmpleado($empleado);

        $curp = strtoupper(trim((string)$empleado->curp));
        return response()->download($pdfPath, "FICHA_CURRICULAR_{$curp}.pdf")
            ->deleteFileAfterSend(true);
    }

    /**
     * PDF individual por CURP (revisor)
     */
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

    /**
     * ZIP masivo de aprobados
     */
    public function zipAprobados()
    {
        $empleados = Empleado::query()
            ->where('estatus_cv', 3)
            ->orderBy('id_tbl_empleados')
            ->get();

        $tmpDir = config('cvpdf.tmp_dir');
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);

        $stamp = Carbon::now()->format('Ymd_His');
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . "APROBADOS_{$stamp}.zip";

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($empleados as $e) {
            $pdfPath = $this->service->generarPdfPorEmpleado($e);

            $curp = strtoupper(trim((string)$e->curp));
            $zip->addFile($pdfPath, "FICHA_CURRICULAR_{$curp}.pdf");

            @unlink($pdfPath);
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
