<?php

return [
    // Carpeta temporal para archivos intermedios
    'tmp_dir' => storage_path('app/tmp'),

    // Plantilla base (PDF)
    'template_pdf' => storage_path('app/templates/cv_template.pdf'),

    // Imágenes de fondo (1-indexed por compatibilidad con tu código)
    'template_images' => [
        1 => storage_path('app/templates/cv_template_page1.png'),
        2 => storage_path('app/templates/cv_template_page2.png'),
    ],

    /**
     * Coordenadas en mm (TCPDF en unidad "mm")
     * Ajustadas para tu plantilla "FICHA CURRICULAR" (las PNG que subiste).
     */
    'coords' => [
        'page1' => [
            // Encabezado
            //'hoy' => ['x' => 155, 'y' => 8, 'w' => 45, 'h' => 4, 'size' => 9, 'align' => 'R'],

            // Datos servidor público
            'fullName'         => ['x' => 46, 'y' => 47, 'w' => 160, 'h' => 5, 'size' => 11],
            'puesto'           => ['x' => 58, 'y' => 52, 'w' => 160, 'h' => 5, 'size' => 11],
            'fechaInicioPuesto'=> ['x' => 59, 'y' => 57, 'w' => 80,  'h' => 5, 'size' => 11],

            // Experiencia laboral (3 bloques x 6 líneas)
            // Bloque 1
            'exp1_puesto'      => ['x' => 46, 'y' => 74.1, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp1_institucion' => ['x' => 70, 'y' => 78.9, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp1_sector'      => ['x' => 44, 'y' => 83.5, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp1_inicio'      => ['x' => 60, 'y' => 88.5, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp1_fin'         => ['x' => 64, 'y' => 93.4, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp1_campo'       => ['x' => 72, 'y' => 98,'w' => 160, 'h' => 5, 'size' => 10],

            // Bloque 2
            'exp2_puesto'      => ['x' => 46, 'y' => 105.4, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp2_institucion' => ['x' => 70, 'y' => 110.1, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp2_sector'      => ['x' => 44, 'y' => 114.3, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp2_inicio'      => ['x' => 60, 'y' => 119.75, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp2_fin'         => ['x' => 64, 'y' => 124.5, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp2_campo'       => ['x' => 72, 'y' => 129.3, 'w' => 160, 'h' => 5, 'size' => 10],

            // Bloque 3
            'exp3_puesto'      => ['x' => 46, 'y' => 139.1, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp3_institucion' => ['x' => 70, 'y' => 143.9, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp3_sector'      => ['x' => 44, 'y' => 148, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp3_inicio'      => ['x' => 60, 'y' => 153.49, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp3_fin'         => ['x' => 64, 'y' => 158.1, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp3_campo'       => ['x' => 72, 'y' => 163.01, 'w' => 160, 'h' => 5, 'size' => 10],

            // Información académica (7 líneas)
            // Nota: aquí conviene empezar más a la derecha porque no hay línea punteada
            'est_institucion'  => ['x' => 53, 'y' => 178.3, 'w' => 135, 'h' => 5, 'size' => 10],
            'est_pais'         => ['x' => 38, 'y' => 183.12, 'w' => 135, 'h' => 5, 'size' => 10],
            'nivel'            => ['x' => 64, 'y' => 188, 'w' => 135, 'h' => 5, 'size' => 10],
            'grado_avance'     => ['x' => 64, 'y' => 192.52, 'w' => 135, 'h' => 5, 'size' => 10],
            'area_estudios'    => ['x' => 64, 'y' => 197.49, 'w' => 135, 'h' => 5, 'size' => 10],
            'titulo_grado'     => ['x' => 107, 'y' => 202.3, 'w' => 135, 'h' => 5, 'size' => 10],
            'carrera_generica' => ['x' => 64, 'y' => 207.1, 'w' => 135, 'h' => 5, 'size' => 10],
        ],

        'page2' => [
            // Cursos/Capacitaciones (5 bloques x 3 líneas)
            // Para no encimarte con el label largo, aquí empezamos más a la derecha
            'curso1_periodo'     => ['x' => 46, 'y' => 44.7,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso1_nombre'      => ['x' => 90, 'y' => 49.4,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso1_institucion' => ['x' => 80, 'y' => 54.4,  'w' => 110, 'h' => 5, 'size' => 10],

            'curso2_periodo'     => ['x' => 46, 'y' => 63.9,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso2_nombre'      => ['x' => 90, 'y' => 68.9,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso2_institucion' => ['x' => 80, 'y' => 74.28,  'w' => 110, 'h' => 5, 'size' => 10],

            'curso3_periodo'     => ['x' => 46, 'y' => 85.4,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso3_nombre'      => ['x' => 90, 'y' => 90.8,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso3_institucion' => ['x' => 80, 'y' => 96.4,  'w' => 110, 'h' => 5, 'size' => 10],

            'curso4_periodo'     => ['x' => 46, 'y' => 107.3, 'w' => 110, 'h' => 5, 'size' => 10],
            'curso4_nombre'      => ['x' => 90, 'y' => 112.92, 'w' => 110, 'h' => 5, 'size' => 10],
            'curso4_institucion' => ['x' => 80, 'y' => 118.56, 'w' => 110, 'h' => 5, 'size' => 10],

            'curso5_periodo'     => ['x' => 46, 'y' => 129.50, 'w' => 110, 'h' => 5, 'size' => 10],
            'curso5_nombre'      => ['x' => 90, 'y' => 134.9, 'w' => 110, 'h' => 5, 'size' => 10],
            'curso5_institucion' => ['x' => 80, 'y' => 140.5, 'w' => 110, 'h' => 5, 'size' => 10],
        ],
    ],
];
