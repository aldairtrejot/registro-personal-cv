<?php

$resolvePath = function (?string $path, string $fallback) {
    $path = trim((string)($path ?? ''));

    if ($path === '') {
        return $fallback;
    }

    $path = str_replace('\\', '/', $path);

    // Ruta absoluta Windows
    if (preg_match('/^[A-Za-z]:\//', $path)) {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    // Ruta absoluta Linux / Unix
    if (str_starts_with($path, '/')) {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    // Ruta relativa => resolver desde base_path
    return base_path(str_replace('/', DIRECTORY_SEPARATOR, $path));
};

$tmpDir = $resolvePath(
    env('CVPDF_TMP_DIR'),
    storage_path('app/tmp')
);

$pdfDir = $resolvePath(
    env('CVPDF_PDF_DIR'),
    storage_path('app/tmp/pdf')
);

$loProfileDir = $resolvePath(
    env('CVPDF_LO_PROFILE_DIR'),
    storage_path('app/tmp/lo_profile')
);

$templateDocx = $resolvePath(
    env('CVPDF_TEMPLATE_DOCX_PATH') ?: env('CV_TEMPLATE_PATH'),
    storage_path('app/templates/cv_template.docx')
);

$soffice = trim((string)(
    env('LIBREOFFICE_BINARY')
    ?: env('CVPDF_SOFFICE')
    ?: env('SOFFICE_PATH')
    ?: ''
));

if ($soffice !== '' && $soffice !== 'soffice') {
    $soffice = str_replace('\\', '/', $soffice);
    $soffice = $resolvePath($soffice, $soffice);
}

return [
    'tmp_dir' => $tmpDir,
    'pdf_dir' => $pdfDir,
    'lo_profile_dir' => $loProfileDir,

    'template_docx_path' => $templateDocx,
    'template_path' => $templateDocx,

    'soffice_path' => $soffice,

    // Compatibilidad legacy
    'template_pdf' => env('CVPDF_TEMPLATE_PDF_PATH')
        ? $resolvePath(env('CVPDF_TEMPLATE_PDF_PATH'), storage_path('app/templates/cv_template.pdf'))
        : storage_path('app/templates/cv_template.pdf'),
];