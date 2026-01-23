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
            'hoy' => ['x' => 160, 'y' => 18, 'w' => 45, 'h' => 4, 'size' => 9, 'align' => 'R'],

            // Datos servidor público
            'fullName'         => ['x' => 46, 'y' => 47, 'w' => 160, 'h' => 5, 'size' => 11],
            'puesto'           => ['x' => 58, 'y' => 52, 'w' => 160, 'h' => 5, 'size' => 11],
            'fechaInicioPuesto'=> ['x' => 59, 'y' => 57, 'w' => 80,  'h' => 5, 'size' => 11],

            // Experiencia laboral (3 bloques x 6 líneas)
            // Bloque 1
            'exp1_puesto'      => ['x' => 46, 'y' => 74.1, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp1_institucion' => ['x' => 46, 'y' => 77, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp1_sector'      => ['x' => 46, 'y' => 85.81, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp1_inicio'      => ['x' => 46, 'y' => 90.58, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp1_fin'         => ['x' => 46, 'y' => 95.34, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp1_campo'       => ['x' => 46, 'y' => 100.10,'w' => 160, 'h' => 5, 'size' => 10],

            // Bloque 2
            'exp2_puesto'      => ['x' => 46, 'y' => 107.33, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp2_institucion' => ['x' => 46, 'y' => 112.27, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp2_sector'      => ['x' => 46, 'y' => 117.03, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp2_inicio'      => ['x' => 46, 'y' => 121.80, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp2_fin'         => ['x' => 46, 'y' => 126.56, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp2_campo'       => ['x' => 46, 'y' => 131.50, 'w' => 160, 'h' => 5, 'size' => 10],

            // Bloque 3
            'exp3_puesto'      => ['x' => 46, 'y' => 141.02, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp3_institucion' => ['x' => 46, 'y' => 145.78, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp3_sector'      => ['x' => 46, 'y' => 150.72, 'w' => 160, 'h' => 5, 'size' => 10],
            'exp3_inicio'      => ['x' => 46, 'y' => 155.49, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp3_fin'         => ['x' => 46, 'y' => 160.25, 'w' => 80,  'h' => 5, 'size' => 10],
            'exp3_campo'       => ['x' => 46, 'y' => 165.01, 'w' => 160, 'h' => 5, 'size' => 10],

            // Información académica (7 líneas)
            // Nota: aquí conviene empezar más a la derecha porque no hay línea punteada
            'est_institucion'  => ['x' => 70, 'y' => 180.36, 'w' => 135, 'h' => 5, 'size' => 10],
            'est_pais'         => ['x' => 70, 'y' => 185.12, 'w' => 135, 'h' => 5, 'size' => 10],
            'nivel'            => ['x' => 70, 'y' => 189.88, 'w' => 135, 'h' => 5, 'size' => 10],
            'grado_avance'     => ['x' => 70, 'y' => 194.82, 'w' => 135, 'h' => 5, 'size' => 10],
            'area_estudios'    => ['x' => 70, 'y' => 199.58, 'w' => 135, 'h' => 5, 'size' => 10],
            'titulo_grado'     => ['x' => 70, 'y' => 204.35, 'w' => 135, 'h' => 5, 'size' => 10],
            'carrera_generica' => ['x' => 70, 'y' => 209.11, 'w' => 135, 'h' => 5, 'size' => 10],
        ],

        'page2' => [
            // Cursos/Capacitaciones (5 bloques x 3 líneas)
            // Para no encimarte con el label largo, aquí empezamos más a la derecha
            'curso1_periodo'     => ['x' => 95, 'y' => 41.89,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso1_nombre'      => ['x' => 95, 'y' => 46.65,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso1_institucion' => ['x' => 95, 'y' => 51.59,  'w' => 110, 'h' => 5, 'size' => 10],

            'curso2_periodo'     => ['x' => 95, 'y' => 65.88,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso2_nombre'      => ['x' => 95, 'y' => 70.82,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso2_institucion' => ['x' => 95, 'y' => 76.28,  'w' => 110, 'h' => 5, 'size' => 10],

            'curso3_periodo'     => ['x' => 95, 'y' => 87.40,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso3_nombre'      => ['x' => 95, 'y' => 92.86,  'w' => 110, 'h' => 5, 'size' => 10],
            'curso3_institucion' => ['x' => 95, 'y' => 98.51,  'w' => 110, 'h' => 5, 'size' => 10],

            'curso4_periodo'     => ['x' => 95, 'y' => 109.45, 'w' => 110, 'h' => 5, 'size' => 10],
            'curso4_nombre'      => ['x' => 95, 'y' => 114.92, 'w' => 110, 'h' => 5, 'size' => 10],
            'curso4_institucion' => ['x' => 95, 'y' => 120.56, 'w' => 110, 'h' => 5, 'size' => 10],

            'curso5_periodo'     => ['x' => 95, 'y' => 131.50, 'w' => 110, 'h' => 5, 'size' => 10],
            'curso5_nombre'      => ['x' => 95, 'y' => 137.14, 'w' => 110, 'h' => 5, 'size' => 10],
            'curso5_institucion' => ['x' => 95, 'y' => 142.61, 'w' => 110, 'h' => 5, 'size' => 10],
        ],
    ],
];
