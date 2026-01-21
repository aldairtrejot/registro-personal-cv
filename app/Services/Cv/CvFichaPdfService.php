<?php

namespace App\Services\Cv;

use App\Models\Cv\Empleado;
use App\Models\Cv\CvExperienciaLaboral;
use App\Models\Cv\CvEstudiosAcademicos;
use App\Models\Cv\CvCursosCapacitaciones;
use Carbon\Carbon;
use RuntimeException;
use Symfony\Component\Process\Process;

class CvFichaPdfService
{
    public function __construct(private WordBookmarkFiller $filler) {}

    public function generarPdfPorEmpleado(Empleado $empleado): string
    {
        $template = config('cvpdf.template_path');
        $tmpDir   = config('cvpdf.tmp_dir');
        $soffice  = config('cvpdf.soffice_path');

        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new RuntimeException("No se pudo crear tmp_dir: {$tmpDir}");
        }

        $curp  = $this->normalizeCurp($empleado->curp);
        $stamp = Carbon::now()->format('Ymd_His_u');

        $docmOut = $tmpDir . DIRECTORY_SEPARATOR . "CV_{$curp}_{$stamp}.docm";
        $pdfOut  = $tmpDir . DIRECTORY_SEPARATOR . "CV_{$curp}_{$stamp}.pdf";

        $repl = $this->buildReplacements($empleado);

        // 1) Rellenar Word
        $this->filler->fill($template, $repl, $docmOut);

        // 2) Convertir a PDF
        $this->convertToPdfWindows($soffice, $docmOut, $tmpDir);

        $generated = $tmpDir . DIRECTORY_SEPARATOR . pathinfo($docmOut, PATHINFO_FILENAME) . ".pdf";
        if (!is_file($generated)) {
            throw new RuntimeException("No se generó el PDF. Esperado: {$generated}");
        }

        @rename($generated, $pdfOut);
        @unlink($docmOut);

        return $pdfOut;
    }

    private function buildReplacements(Empleado $e): array
    {
        $exp = CvExperienciaLaboral::query()
            ->where('id_tbl_empleados', $e->id_tbl_empleados)
            ->orderBy('orden')
            ->limit(3)
            ->get()
            ->values();

        $est = CvEstudiosAcademicos::query()
            ->where('id_tbl_empleados', $e->id_tbl_empleados)
            ->first();

        $cur = CvCursosCapacitaciones::query()
            ->where('id_tbl_empleados', $e->id_tbl_empleados)
            ->orderBy('orden')
            ->limit(5)
            ->get()
            ->values();

        $fullName = trim(implode(' ', array_filter([
            $e->nombre,
            $e->primer_apellido,
            $e->segundo_apellido,
        ])));

        $r = [
            // ✅ Para evitar duplicado en la línea NOMBRE si tu Word trae 2 marcadores,
            // llenamos solo Ap_Materno con el nombre completo y dejamos Nombre vacío:
            'Ap_Materno'   => $fullName,
            'Nombre'       => '',

            'puestoactual' => (string)($e->puesto_actual ?? ''),
            'Fecha_Inicio' => $this->fmtDate($e->fecha_inicio_puesto),

            // ✅ Si agregas marcador FOLIO en Word:
            // 'FOLIO' => (string)($e->folio_cv ?? ''),
        ];

        // Experiencias 1..3
        for ($i = 1; $i <= 3; $i++) {
            $x = $exp->get($i - 1);
            $campo = mb_substr((string)($x?->campo_experiencia ?? ''), 0, 100);

            $r["Exp{$i}Puesto"]   = $x?->puesto ?? '';
            $r["Exp{$i}Empresa"]  = $x?->institucion ?? '';
            $r["Exp{$i}Sector"]   = $this->fmtSector($x?->sector);
            $r["Exp{$i}P_Inicio"] = $this->fmtDate($x?->fecha_inicio);
            $r["Exp{$i}P_Fin"]    = $this->fmtDate($x?->fecha_termino);

            // Si tu Word tiene doble marcador en campo de Exp1/Exp2:
            if ($i === 1 || $i === 2) {
                $r["Exp{$i}Denominacion"] = $campo;
                $r["Exp{$i}Experiencia"]  = '';
            } else {
                $r["Exp3Experiencia"] = $campo;
            }
        }

        // Académico
        $r['Acad1Institución']   = $est?->institucion ?? '';
        $r['Acad1País']          = $est?->pais ?? '';
        $r['Acad1N_estudios']    = $est?->nivel ?? '';
        $r['Acad1Cédula']        = $est?->numero_cedula ?? '';
        $r['Acad1Área_Estudios'] = $est?->area_estudios ?? '';
        $r['Acad1C_Específica']  = $est?->carrera_especifica ?? '';
        $r['Acad1C_Genérica']    = $est?->carrera_generica ?? '';

        // Cursos 1..5
        for ($i = 1; $i <= 5; $i++) {
            $c = $cur->get($i - 1);
            $r["Curso{$i}Periodo"]     = $c?->periodo ?? '';
            $r["Curso{$i}Nombre"]      = $c?->nombre_curso ?? '';
            $r["Curso{$i}Institución"] = $c?->institucion ?? '';
        }

        return $r;
    }

    /**
     * ✅ Conversión robusta Windows:
     * - autodetecta soffice si config/env no existe
     * - evita problemas de comillas usando Process con argumentos
     * - usa perfil aislado para evitar locks
     */
    private function convertToPdfWindows(string $soffice, string $inputDoc, string $outDir): void
    {
        $inputDoc = $this->normalizeWinPath($inputDoc);
        $outDir   = $this->normalizeWinPath($outDir);

        if (!is_file($inputDoc)) {
            throw new RuntimeException("No existe el archivo a convertir: {$inputDoc}");
        }

        if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
            throw new RuntimeException("No se pudo crear outDir: {$outDir}");
        }

        $bin = $this->resolveSofficeBinary($soffice);

        // Perfil aislado (evita “profile locked”)
        $profileDir = $outDir . DIRECTORY_SEPARATOR . ".lo-profile";
        if (!is_dir($profileDir)) {
            @mkdir($profileDir, 0775, true);
        }
        $profileUri = $this->toFileUri($profileDir); // file:///C:/...

        $args = [
            $bin,
            "--headless",
            "--nologo",
            "--nofirststartwizard",
            "--invisible",
            "-env:UserInstallation={$profileUri}",
            "--convert-to", "pdf",
            "--outdir", $outDir,
            $inputDoc,
        ];

        $process = new Process($args);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                "LibreOffice falló ({$process->getExitCode()}). STDERR: "
                . $process->getErrorOutput()
                . " STDOUT: "
                . $process->getOutput()
                . " BIN: {$bin}"
            );
        }
    }

    /**
     * Resuelve el binario soffice:
     * 1) Usa config/env si existe y el archivo existe
     * 2) Busca por `where` (si está en PATH)
     * 3) Prueba rutas típicas
     */
    private function resolveSofficeBinary(?string $soffice): string
    {
        $soffice = trim((string)$soffice, "\"' ");

        if ($soffice !== '' && is_file($soffice)) {
            return $soffice;
        }

        // 1) donde esté en PATH
        foreach (['soffice.com', 'soffice.exe'] as $cmd) {
            $found = @shell_exec("where {$cmd} 2>NUL");
            if ($found) {
                $lines = preg_split("/\r\n|\n|\r/", trim($found));
                if (!empty($lines[0]) && is_file($lines[0])) {
                    return trim($lines[0]);
                }
            }
        }

        // 2) rutas típicas
        $candidates = [
            'C:\Program Files\LibreOffice\program\soffice.com',
            'C:\Program Files\LibreOffice\program\soffice.exe',
            'C:\Program Files (x86)\LibreOffice\program\soffice.com',
            'C:\Program Files (x86)\LibreOffice\program\soffice.exe',
        ];

        foreach ($candidates as $p) {
            if (is_file($p)) return $p;
        }

        throw new RuntimeException(
            "No se encontró LibreOffice (soffice). " .
            "Instálalo o configura SOFFICE_PATH con la ruta REAL a soffice.com/soffice.exe."
        );
    }

    private function toFileUri(string $path): string
    {
        // C:\algo -> file:///C:/algo
        $p = str_replace('\\', '/', $path);
        $p = preg_replace('/^([A-Za-z]):\//', '$1:/', $p);
        return "file:///" . ltrim($p, '/');
    }

    private function normalizeWinPath(string $path): string
    {
        $path = trim($path, "\"' ");
        return $path;
    }

    private function fmtDate($value): string
    {
        if (!$value) return '';
        try { return Carbon::parse($value)->format('d/m/Y'); }
        catch (\Throwable $e) { return ''; }
    }

    private function fmtSector(?string $sector): string
    {
        $s = strtolower((string)$sector);
        if ($s === 'publico') return 'PUBLICO';
        if ($s === 'privado') return 'PRIVADO';
        return strtoupper((string)$sector);
    }

    private function normalizeCurp(?string $curp): string
    {
        $c = strtoupper(trim((string)$curp));
        $c = preg_replace('/\s+/', '', $c);
        return $c ?: 'SIN_CURP';
    }
}
