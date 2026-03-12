<?php

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\Cv\Empleado;
use App\Services\Cv\CvFichaPdfService;
use App\Services\Cv\CvFolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use ZipArchive;

class RevisorPdfController extends Controller
{
    public function pdfPorEmpleadoId(int $id, CvFichaPdfService $svc, CvFolioService $folioSvc)
    {
        $empleado = Empleado::query()
            ->with(['puesto'])
            ->findOrFail($id);

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

    public function zipAprobados(Request $request, CvFichaPdfService $svc, CvFolioService $folioSvc)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $now = Carbon::now();

        $ejercicio = (int)($request->query('ejercicio', $now->year));
        $trimestre = (int)($request->query('trimestre', $this->trimestreActual($now)));

        if ($ejercicio < 2000 || $ejercicio > 2100) {
            $ejercicio = (int)$now->year;
        }

        if (!in_array($trimestre, [1, 2, 3, 4], true)) {
            $trimestre = $this->trimestreActual($now);
        }

        [$inicioTrim, $finTrim] = $this->periodoPorTrimestre($ejercicio, $trimestre);
        $inicioTrim = $inicioTrim->copy()->startOfDay();
        $finTrim    = $finTrim->copy()->endOfDay();

        $campoFecha = (string)$request->query('campo_fecha', 'actualizado');
        $colFecha = match ($campoFecha) {
            'creado' => 'creado_en',
            default  => 'actualizado_en',
        };

        $fechaInicioParam = $request->query('fecha_inicio');
        $fechaFinParam    = $request->query('fecha_fin');

        $iniFiltroFinal = $inicioTrim->copy();
        $finFiltroFinal = $finTrim->copy();

        if ($fechaInicioParam && $fechaFinParam) {
            try {
                $iniManual = Carbon::createFromFormat('Y-m-d', (string)$fechaInicioParam)->startOfDay();
                $finManual = Carbon::createFromFormat('Y-m-d', (string)$fechaFinParam)->endOfDay();

                if ($iniManual->gt($iniFiltroFinal)) {
                    $iniFiltroFinal = $iniManual;
                }

                if ($finManual->lt($finFiltroFinal)) {
                    $finFiltroFinal = $finManual;
                }
            } catch (\Throwable $e) {
                return response('Rango de fechas inválido. Usa formato YYYY-MM-DD.', 422, [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ]);
            }
        }

        if ($iniFiltroFinal->gt($finFiltroFinal)) {
            return response('No hay CV aprobados en el rango seleccionado.', 404, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $empleados = Empleado::query()
            ->with(['puesto'])
            ->where('estatus_cv', 3)
            ->whereNotNull($colFecha)
            ->whereBetween($colFecha, [$iniFiltroFinal, $finFiltroFinal])
            ->orderBy('id_tbl_empleados')
            ->get([
                'id_tbl_empleados',
                'curp',
                'nombre',
                'primer_apellido',
                'segundo_apellido',
                'folio_cv',
                'id_puesto',
                'puesto_actual',
            ]);

        if ($empleados->isEmpty()) {
            return response('No hay CV aprobados para descargar en el periodo seleccionado.', 404, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $tmpDir = config('cvpdf.tmp_dir', storage_path('app/tmp'));
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new RuntimeException("No se pudo crear tmp_dir: {$tmpDir}");
        }

        File::ensureDirectoryExists($tmpDir);

        $zipName = 'CV_APROBADOS_' . $ejercicio . '_T' . $trimestre . '_' . date('Ymd_His') . '.zip';
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;

        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new ZipArchive();
        $zipResult = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($zipResult !== true) {
            throw new RuntimeException("No se pudo crear ZIP: {$zipPath}. Código: {$zipResult}");
        }

        $pdfGenerados = [];
        $agregados = 0;
        $errores = [];

        foreach ($empleados as $emp) {
            try {
                $emp->setAttribute('puesto_actual', $emp->puesto_label);

                $pdfPath = $svc->generarPdfPorEmpleado($emp);

                if (!is_file($pdfPath)) {
                    throw new RuntimeException("No existe el PDF generado: {$pdfPath}");
                }

                $pdfGenerados[] = $pdfPath;

                $consec = $folioSvc->parseConsecutivo($emp->folio_cv);

                if ($consec > 0) {
                    $zipInsideName = "{$consec}.pdf";
                } else {
                    $curp = strtoupper(trim((string)$emp->curp));
                    $zipInsideName = "CV_{$curp}.pdf";
                }

                if (!$zip->addFile($pdfPath, $zipInsideName)) {
                    throw new RuntimeException("No se pudo agregar al ZIP: {$zipInsideName}");
                }

                $agregados++;
            } catch (\Throwable $e) {
                $errores[] = [
                    'empleado_id' => $emp->id_tbl_empleados,
                    'curp' => $emp->curp,
                    'error' => $e->getMessage(),
                ];

                Log::error('Error al generar/agregar PDF al ZIP', [
                    'empleado_id' => $emp->id_tbl_empleados,
                    'curp' => $emp->curp,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $zip->close();

        foreach ($pdfGenerados as $pdf) {
            if (is_file($pdf)) {
                @unlink($pdf);
            }
        }

        if ($agregados === 0) {
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }

            throw new RuntimeException(
                "No se pudo generar ningún PDF para el ZIP.\n" .
                "Errores: " . json_encode($errores, JSON_UNESCAPED_UNICODE)
            );
        }

        if (!is_file($zipPath)) {
            throw new RuntimeException("El archivo ZIP no se generó: {$zipPath}");
        }

        if (filesize($zipPath) <= 0) {
            @unlink($zipPath);
            throw new RuntimeException("El ZIP se generó vacío.");
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