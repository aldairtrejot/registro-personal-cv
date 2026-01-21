<?php

return [
    'template_path' => base_path(env('CV_TEMPLATE_PATH', 'storage/app/templates/cv_template.docx')),

    // quitamos comillas por si viene "..."
    'soffice_path' => trim(env('SOFFICE_PATH', 'soffice'), '"'),

    'tmp_dir' => storage_path('app/tmp'),
    'pdf_dir' => storage_path('app/cv_pdfs'),
];
