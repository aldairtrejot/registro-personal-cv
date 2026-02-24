<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\Cv\Empleado;
use App\Services\Cv\CvFichaPdfService;
use App\Services\Cv\CvFolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use RuntimeException;
use ZipArchive;

class RevisorPdfController extends Controller
{
    public function pdfPorEmpleadoId(int $id, CvFichaPdfService $svc, CvFolioService $folioSvc)
    {
        $empleado = Empleado::query()->with(['puesto'])->findOrFail($id);

        // ✅ compat: si el service usa puesto_actual
        $empleado->setAttribute('puesto_actual', $empleado->puesto_label);

        return $this->downloadPdf($empleado, $svc, $folioSvc);
    }

    public function pdfPorCurp(string $curp, CvFichaPdfService $svc, CvFolioService $folioSvc)
    {
        $curp = strtoupper(trim($curp));

        $empleado = Empleado::query()
            ->with(['puesto'])
            ->whereRaw('UPPER(curp) = ?', [$curp])
            ->firstOrFail();

        $empleado->setAttribute('puesto_actual', $empleado->puesto_label);

        return $this->downloadPdf($empleado, $svc, $folioSvc);
    }

    /**
     * ✅ ZIP de aprobados filtrado por trimestre (misma lógica del Excel)
     *
     * Query params soportados:
     * - ejercicio=2026
     * - trimestre=1|2|3|4
     * - campo_fecha=actualizado|creado   (default: actualizado)
     * - fecha_inicio=YYYY-MM-DD (opcional, se intersecta con el trimestre)
     * - fecha_fin=YYYY-MM-DD    (opcional, se intersecta con el trimestre)
     */
    public function zipAprobados(Request $request, CvFichaPdfService $svc, CvFolioService $folioSvc)
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

        // ============================================================
        // 2) Campo de fecha para filtrar (creado_en / actualizado_en)
        // ============================================================
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
        // 4) Traer aprobados filtrados por trimestre/rango (misma lógica que Excel)
        // ============================================================
        $empleados = Empleado::query()
            ->with(['puesto'])
            ->where('estatus_cv', 3)
            ->whereNotNull($colFecha) // por si actualizado_en viene null
            ->whereBetween($colFecha, [$iniFiltroFinal, $finFiltroFinal])
            ->orderBy('id_tbl_empleados')
            ->get(['id_tbl_empleados', 'curp', 'nombre', 'primer_apellido', 'segundo_apellido', 'folio_cv', 'id_puesto', 'puesto_actual']);

        if ($empleados->isEmpty()) {
            abort(404, 'No hay CV aprobados para descargar en el periodo seleccionado.');
        }

        // ============================================================
        // 5) Preparar ZIP
        // ============================================================
        $tmpDir = config('cvpdf.tmp_dir', storage_path('app/tmp'));
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new RuntimeException("No se pudo crear tmp_dir: {$tmpDir}");
        }

        $zipName = 'CV_APROBADOS_' . $ejercicio . '_T' . $trimestre . '_' . date('Ymd_His') . '.zip';
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("No se pudo crear ZIP: {$zipPath}");
        }

        $pdfGenerados = [];

        // ============================================================
        // 6) Generar PDFs + agregar al ZIP
        // ============================================================
        foreach ($empleados as $emp) {
            // ✅ compat: si el service usa puesto_actual
            $emp->setAttribute('puesto_actual', $emp->puesto_label);

            $pdfPath = $svc->generarPdfPorEmpleado($emp);
            $pdfGenerados[] = $pdfPath;

            $consec = $folioSvc->parseConsecutivo($emp->folio_cv);
            if ($consec > 0) {
                $zipInsideName = "{$consec}.pdf";
            } else {
                $curp = strtoupper(trim((string)$emp->curp));
                $zipInsideName = "CV_{$curp}.pdf";
            }

            $zip->addFile($pdfPath, $zipInsideName);
        }

        $zip->close();

        // Limpieza PDFs temporales
        foreach ($pdfGenerados as $p) {
            if (is_file($p)) @unlink($p);
        }

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    private function downloadPdf(Empleado $empleado, CvFichaPdfService $svc, CvFolioService $folioSvc)
    {
        $pdfPath = $svc->generarPdfPorEmpleado($empleado);

        $consec = $folioSvc->parseConsecutivo($empleado->folio_cv);

        if ($consec > 0) {
            $filename = "{$consec}.pdf";
        } else {
            $curp = strtoupper(trim((string)$empleado->curp));
            $filename = "CV_{$curp}.pdf";
        }

        return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
    }

    // ===============================
    // Helpers de trimestre (igual idea que tu Excel)
    // ===============================
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
}