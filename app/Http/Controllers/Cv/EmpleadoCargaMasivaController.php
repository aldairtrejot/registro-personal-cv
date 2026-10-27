<?php

namespace App\Http\Controllers\Cv;

use App\Exceptions\Cv\CargaMasivaNoEncontradaException;
use App\Http\Controllers\Controller;
use App\Services\Cv\EmpleadoCargaMasivaService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EmpleadoCargaMasivaController extends Controller
{
    public function preview(Request $request, EmpleadoCargaMasivaService $service)
    {
        $data = $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:15360',
        ], [
            'archivo.required' => 'Selecciona un archivo Excel.',
            'archivo.file' => 'El archivo no es valido.',
            'archivo.mimes' => 'El archivo debe ser Excel (.xlsx o .xls).',
            'archivo.max' => 'El archivo no debe exceder 15 MB.',
        ]);

        try {
            $payload = $service->previsualizar($data['archivo'], $request->user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo analizar el archivo Excel.',
            ], 500);
        }

        return response()->json($this->payloadCliente($payload));
    }

    public function confirmar(Request $request, EmpleadoCargaMasivaService $service)
    {
        $data = $request->validate([
            'token' => 'required|string|max:80',
        ]);

        try {
            $payload = $service->confirmar($data['token'], $request->user());
        } catch (CargaMasivaNoEncontradaException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo confirmar la carga masiva.',
            ], 500);
        }

        return response()->json($this->payloadCliente($payload));
    }

    public function reporte(string $token, EmpleadoCargaMasivaService $service)
    {
        $payload = $service->cargarPayload($token);

        if (!$payload) {
            return response('No se encontro la vista previa de la carga.', 404, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $spreadsheet = $this->crearReporte($payload);
        $filename = 'resultado_carga_empleados_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    private function payloadCliente(array $payload): array
    {
        return [
            'token' => $payload['token'] ?? null,
            'archivo_original' => $payload['archivo_original'] ?? null,
            'advertencias' => $payload['advertencias'] ?? [],
            'resumen' => $payload['resumen'] ?? [],
            'resultados' => $payload['resultados'] ?? [],
            'confirmado' => (bool) ($payload['confirmado'] ?? false),
            'bitacora_id' => $payload['bitacora_id'] ?? null,
        ];
    }

    private function crearReporte(array $payload): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resultado');

        $headers = [
            'Fila',
            'Estado',
            'Motivo',
            'CURP',
            'Nombre(s)',
            'Primer apellido',
            'Segundo apellido',
        ];

        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006341']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $row = 2;
        foreach (($payload['resultados'] ?? []) as $registro) {
            $sheet->fromArray([[
                $registro['fila'] ?? null,
                $this->estadoReporte((string) ($registro['estado'] ?? '')),
                $registro['motivo'] ?? '',
                $registro['curp'] ?? '',
                $registro['nombre'] ?? '',
                $registro['primer_apellido'] ?? '',
                $registro['segundo_apellido'] ?? '',
            ]], null, "A{$row}");

            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        if ($row > 2) {
            $sheet->getStyle('A1:G' . ($row - 1))
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        $summary = $spreadsheet->createSheet();
        $summary->setTitle('Resumen');
        $summary->fromArray([
            ['Archivo', $payload['archivo_original'] ?? ''],
            ['Confirmado', !empty($payload['confirmado']) ? 'Si' : 'No'],
            ['Bitacora ID', $payload['bitacora_id'] ?? ''],
            ['Total', $payload['resumen']['total'] ?? 0],
            ['Validos pendientes', $payload['resumen']['validos'] ?? 0],
            ['Duplicados', $payload['resumen']['duplicados'] ?? 0],
            ['Rechazados', $payload['resumen']['rechazados'] ?? 0],
            ['Insertados', $payload['resumen']['insertados'] ?? 0],
        ], null, 'A1');
        $summary->getColumnDimension('A')->setAutoSize(true);
        $summary->getColumnDimension('B')->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function estadoReporte(string $estado): string
    {
        return match ($estado) {
            'valido' => 'Valido para insertar',
            'duplicado' => 'Duplicado',
            'rechazado' => 'Rechazado',
            'insertado' => 'Insertado',
            default => $estado,
        };
    }
}
