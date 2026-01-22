<?php

namespace App\Services\Cv;

use App\Models\Cv\Empleado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CvFichaPdfService
{
    public function generarPdfPorEmpleado(Empleado $empleado): string
    {
        $tmpDir = config('cvpdf.tmp_dir', storage_path('app/tmp'));
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new RuntimeException("No se pudo crear tmp_dir: {$tmpDir}");
        }

        $id = $empleado->id_tbl_empleados ?? $empleado->getKey();

        $fullName = trim(
            ($empleado->nombre ?? '') . ' ' .
            ($empleado->primer_apellido ?? '') . ' ' .
            ($empleado->segundo_apellido ?? '')
        );

        $hoy = now()->format('d/m/Y H:i');

        $fechaInicioPuesto = $empleado->fecha_inicio_puesto
            ? $this->fmtFecha($empleado->fecha_inicio_puesto)
            : '';

        // EXPERIENCIAS (máx 3)
        $experienciasRows = DB::table('tbl_cv_experiencia_laboral')
            ->where('id_tbl_empleados', $id)
            ->orderByDesc('id_tbl_cv_experiencia_laboral')
            ->limit(3)
            ->get();

        $experiencias = $experienciasRows->map(function ($r) {
            return [
                'inicio'      => $r->fecha_inicio ? $this->fmtFecha($r->fecha_inicio) : '',
                'fin'         => $r->fecha_termino ? $this->fmtFecha($r->fecha_termino) : 'ACTUAL',
                'sector'      => $this->mapSector($r->sector ?? ''),
                'puesto'      => $r->puesto ?? '',
                'institucion' => $r->institucion ?? '',
                'campo'       => $r->campo_experiencia ?? '',
            ];
        })->toArray();

        // ESTUDIO (1 registro)
        $est = DB::table('tbl_cv_estudios_academicos')
            ->where('id_tbl_empleados', $id)
            ->orderByDesc('id_tbl_cv_estudios_academicos')
            ->first();

        $estudio = [
            'institucion'        => $est->institucion ?? '',
            'pais'               => $est->pais ?? '',
            'nivel'              => $est->nivel ?? '',
            'cedula'             => $est->numero_cedula ?? '',
            'area'               => $est->area_estudios ?? '',
            'carrera_especifica' => $est->carrera_especifica ?? '',
            'carrera_generica'   => $est->carrera_generica ?? '',
        ];

        // CURSOS (máx 5)
        $cursosRows = DB::table('tbl_cv_cursos_capacitaciones')
            ->where('id_tbl_empleados', $id)
            ->orderByDesc('id_tbl_cv_cursos_capacitaciones')
            ->limit(5)
            ->get();

        $cursos = $cursosRows->map(function ($r) {
            $inicio = $r->fecha_inicio ? $this->fmtFecha($r->fecha_inicio) : ''
            ;
            $fin = $r->fecha_fin ? $this->fmtFecha($r->fecha_fin) : '';

            $periodo = trim($inicio . ($fin ? " - {$fin}" : ''));

            return [
                'periodo'     => $periodo ?: ($r->periodo ?? ''),
                'nombre'      => $r->nombre_curso ?? ($r->curso ?? ''),
                'institucion' => $r->institucion ?? '',
            ];
        })->toArray();

        $data = [
            'empleado'           => $empleado,
            'fullName'           => $fullName,
            'hoy'                => $hoy,
            'fechaInicioPuesto'  => $fechaInicioPuesto,
            'experiencias'       => $experiencias,
            'estudio'            => $estudio,
            'cursos'             => $cursos,
        ];

        $curp = strtoupper(trim((string)($empleado->curp ?? 'SIN_CURP')));
        $file = $tmpDir . DIRECTORY_SEPARATOR . 'CV_' . $curp . '_' . uniqid() . '.pdf';

        Pdf::loadView('revisor.cv-pdf', $data)
            ->setPaper('letter', 'portrait')
            ->save($file);

        return $file;
    }

    private function fmtFecha(string $fecha): string
    {
        // acepta "YYYY-MM-DD" o datetime
        try {
            return \Carbon\Carbon::parse($fecha)->format('d/m/Y');
        } catch (\Throwable $e) {
            return $fecha;
        }
    }

    private function mapSector(string $sector): string
    {
        $s = strtolower(trim($sector));
        if ($s === 'publico' || $s === 'público') return 'Público';
        if ($s === 'privado') return 'Privado';
        return $sector ? ucfirst($sector) : '';
    }
}
