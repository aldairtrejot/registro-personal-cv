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

        if (!class_exists(ZipArchive::class)) {
            return response('La extensión ZIP de PHP no está instalada o habilitada.', 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $now = Carbon::now();

        $ejercicio = (int) $request->query('ejercicio', $now->year);
        $trimestre = (int) $request->query('trimestre', $this->trimestreActual($now));

        if ($ejercicio < 2000 || $ejercicio > 2100) {
            return response('Ejercicio inválido.', 422, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        if (!in_array($trimestre, [1, 2, 3, 4], true)) {
            return response('Trimestre inválido.', 422, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        [$inicioTrim, $finTrim] = $this->periodoPorTrimestre($ejercicio, $trimestre);

        $inicioTrim = $inicioTrim->copy()->startOfDay();
        $finTrim = $finTrim->copy()->endOfDay();

        /*
         * CRITERIO ÚNICO DE DESCARGA:
         * - Solo CV aprobados.
         * - Solo registros actualizados dentro del ejercicio/trimestre seleccionado.
         */
        $empleados = Empleado::query()
            ->with(['puesto'])
            ->where('estatus_cv', 3)
            ->whereNotNull('actualizado_en')
            ->whereBetween('actualizado_en', [$inicioTrim, $finTrim])
            ->orderBy('id_tbl_empleados')
            ->get();

        if ($empleados->isEmpty()) {
            return response('No hay CV aprobados para descargar en el periodo seleccionado.', 404, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $tmpDir = config('cvpdf.tmp_dir') ?: storage_path('app/tmp');

        try {
            File::ensureDirectoryExists($tmpDir, 0775, true);
        } catch (\Throwable $e) {
            Log::error('No se pudo crear tmp_dir para ZIP de aprobados', [
                'tmpDir' => $tmpDir,
                'error' => $e->getMessage(),
            ]);

            return response('No se pudo crear el directorio temporal para generar el ZIP.', 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        if (!is_writable($tmpDir)) {
            Log::error('tmp_dir no tiene permisos de escritura', [
                'tmpDir' => $tmpDir,
            ]);

            return response('El directorio temporal no tiene permisos de escritura.', 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $zipName = 'CV_APROBADOS_' . $ejercicio . '_T' . $trimestre . '_' . Carbon::now()->format('Ymd_His') . '.zip';
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;

        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new ZipArchive();
        $zipResult = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($zipResult !== true) {
            Log::error('No se pudo crear el ZIP de aprobados', [
                'zipPath' => $zipPath,
                'codigo' => $zipResult,
            ]);

            return response('No se pudo crear el archivo ZIP.', 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $pdfGenerados = [];
        $agregados = 0;
        $errores = [];
        $nombresUsados = [];

        foreach ($empleados as $emp) {
            try {
                $emp->setAttribute('puesto_actual', $emp->puesto_label);

                $pdfPath = $svc->generarPdfPorEmpleado($emp);

                if (!is_file($pdfPath)) {
                    throw new RuntimeException("No existe el PDF generado: {$pdfPath}");
                }

                $pdfGenerados[] = $pdfPath;

                $zipInsideName = $this->nombrePdfEmpleado($emp, $folioSvc);
                $zipInsideName = $this->nombreUnicoZip($zipInsideName, $nombresUsados, $emp);

                if (!$zip->addFile($pdfPath, $zipInsideName)) {
                    throw new RuntimeException("No se pudo agregar al ZIP: {$zipInsideName}");
                }

                $agregados++;
            } catch (\Throwable $e) {
                $errores[] = [
                    'empleado_id' => $emp->id_tbl_empleados ?? null,
                    'curp' => $emp->curp ?? null,
                    'error' => $e->getMessage(),
                ];

                Log::error('Error al generar/agregar PDF al ZIP de aprobados', [
                    'empleado_id' => $emp->id_tbl_empleados ?? null,
                    'curp' => $emp->curp ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $zip->close();

        /*
         * Después de cerrar el ZIP ya podemos borrar los PDF temporales.
         */
        foreach ($pdfGenerados as $pdf) {
            if (is_file($pdf)) {
                @unlink($pdf);
            }
        }

        if ($agregados === 0) {
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }

            Log::error('No se pudo generar ningún PDF para el ZIP de aprobados', [
                'ejercicio' => $ejercicio,
                'trimestre' => $trimestre,
                'fecha_inicio' => $inicioTrim->toDateTimeString(),
                'fecha_fin' => $finTrim->toDateTimeString(),
                'criterio_fecha' => 'actualizado_en',
                'errores' => $errores,
            ]);

            return response('No se pudo generar ningún PDF para el ZIP. Revisa storage/logs/laravel.log.', 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        if (!is_file($zipPath)) {
            Log::error('El archivo ZIP no se generó', [
                'zipPath' => $zipPath,
            ]);

            return response('El archivo ZIP no se generó.', 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        if (filesize($zipPath) <= 0) {
            @unlink($zipPath);

            Log::error('El ZIP se generó vacío', [
                'zipPath' => $zipPath,
            ]);

            return response('El ZIP se generó vacío.', 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        return response()
            ->download($zipPath, $zipName, [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ])
            ->deleteFileAfterSend(true);
    }

    private function downloadPdf(Empleado $empleado, CvFichaPdfService $svc, CvFolioService $folioSvc)
    {
        $pdfPath = $svc->generarPdfPorEmpleado($empleado);

        if (!is_file($pdfPath)) {
            throw new RuntimeException("No existe el PDF generado: {$pdfPath}");
        }

        $filename = $this->nombrePdfEmpleado($empleado, $folioSvc);

        return response()
            ->download($pdfPath, $filename)
            ->deleteFileAfterSend(true);
    }

    private function nombrePdfEmpleado(Empleado $empleado, CvFolioService $folioSvc): string
    {
        $consec = $folioSvc->parseConsecutivo($empleado->folio_cv);

        if ($consec > 0) {
            return "{$consec}.pdf";
        }

        $curp = strtoupper(trim((string) $empleado->curp));

        if ($curp !== '') {
            return "CV_{$curp}.pdf";
        }

        return 'CV_' . ($empleado->id_tbl_empleados ?? 'empleado') . '.pdf';
    }

    private function nombreUnicoZip(string $nombreOriginal, array &$nombresUsados, Empleado $empleado): string
    {
        $nombreOriginal = trim($nombreOriginal) !== '' ? trim($nombreOriginal) : 'CV_empleado.pdf';

        if (!isset($nombresUsados[$nombreOriginal])) {
            $nombresUsados[$nombreOriginal] = 1;
            return $nombreOriginal;
        }

        $nombresUsados[$nombreOriginal]++;

        $info = pathinfo($nombreOriginal);

        $base = $info['filename'] ?? 'CV_empleado';
        $ext = $info['extension'] ?? 'pdf';

        $idEmpleado = $empleado->id_tbl_empleados ?? $nombresUsados[$nombreOriginal];

        return $base . '_' . $idEmpleado . '.' . $ext;
    }

    private function trimestreActual(Carbon $now): int
    {
        $m = (int) $now->month;

        if ($m <= 3) {
            return 1;
        }

        if ($m <= 6) {
            return 2;
        }

        if ($m <= 9) {
            return 3;
        }

        return 4;
    }

    private function periodoPorTrimestre(int $ejercicio, int $trimestre): array
    {
        return match ($trimestre) {
            1 => [
                Carbon::create($ejercicio, 1, 1)->startOfDay(),
                Carbon::create($ejercicio, 3, 31)->endOfDay(),
            ],
            2 => [
                Carbon::create($ejercicio, 4, 1)->startOfDay(),
                Carbon::create($ejercicio, 6, 30)->endOfDay(),
            ],
            3 => [
                Carbon::create($ejercicio, 7, 1)->startOfDay(),
                Carbon::create($ejercicio, 9, 30)->endOfDay(),
            ],
            4 => [
                Carbon::create($ejercicio, 10, 1)->startOfDay(),
                Carbon::create($ejercicio, 12, 31)->endOfDay(),
            ],
            default => [
                Carbon::create($ejercicio, 1, 1)->startOfDay(),
                Carbon::create($ejercicio, 3, 31)->endOfDay(),
            ],
        };
    }
}