<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Process;

class CvWordPdfService
{
    public function generate(array $data): string
    {
        $tplPath = storage_path('app/templates/cv_template.docx');
        if (!file_exists($tplPath)) {
            throw new \RuntimeException("No existe plantilla: {$tplPath}");
        }

        $outDir = storage_path('app/tmp/'.uniqid('cv_', true));
        @mkdir($outDir, 0775, true);

        $docxOut = $outDir.'/cv.docx';

        $t = new TemplateProcessor($tplPath);

        // ====== Campos simples ======
        $t->setValue('fullName', $data['fullName'] ?? '');
        $t->setValue('puesto', $data['puesto'] ?? '');
        $t->setValue('fechaInicioPuesto', $data['fechaInicioPuesto'] ?? '');

        // ====== Experiencia (fijo 3) ======
        for ($i=1; $i<=3; $i++) {
            $row = $data['experiencias'][$i-1] ?? [];
            $t->setValue("exp{$i}_puesto", $row['puesto'] ?? '');
            $t->setValue("exp{$i}_institucion", $row['institucion'] ?? '');
            $t->setValue("exp{$i}_sector", $row['sector'] ?? '');
            $t->setValue("exp{$i}_inicio", $row['inicio'] ?? '');
            $t->setValue("exp{$i}_fin", $row['fin'] ?? '');
            $t->setValue("exp{$i}_campo", $row['campo'] ?? '');
        }

        // ====== Académico ======
        $t->setValue('est_institucion', $data['est']['institucion'] ?? '');
        $t->setValue('est_pais', $data['est']['pais'] ?? '');
        $t->setValue('nivel', $data['est']['nivel'] ?? '');
        $t->setValue('grado_avance', $data['est']['grado_avance'] ?? '');
        $t->setValue('area_estudios', $data['est']['area_estudios'] ?? '');
        $t->setValue('titulo_grado', $data['est']['titulo_grado'] ?? '');
        $t->setValue('carrera_generica', $data['est']['carrera_generica'] ?? '');

        // ====== Cursos (fijo 5) ======
        for ($i=1; $i<=5; $i++) {
            $row = $data['cursos'][$i-1] ?? [];
            $t->setValue("curso{$i}_periodo", $row['periodo'] ?? '');
            $t->setValue("curso{$i}_nombre", $row['nombre'] ?? '');
            $t->setValue("curso{$i}_institucion", $row['institucion'] ?? '');
        }

        $t->saveAs($docxOut);

        // Convertir DOCX -> PDF con LibreOffice (soffice)
        $p = new Process([
            'soffice', '--headless', '--nologo', '--nofirststartwizard',
            '--convert-to', 'pdf', '--outdir', $outDir, $docxOut
        ]);
        $p->setTimeout(90);
        $p->run();

        if (!$p->isSuccessful()) {
            throw new \RuntimeException("Error LibreOffice: ".$p->getErrorOutput());
        }

        $pdfOut = $outDir.'/cv.pdf';
        if (!file_exists($pdfOut)) {
            // a veces genera con nombre base del docx
            $pdfs = glob($outDir.'/*.pdf') ?: [];
            if (!$pdfs) throw new \RuntimeException("No se generó PDF en {$outDir}");
            $pdfOut = $pdfs[0];
        }

        return $pdfOut;
    }
}