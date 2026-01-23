<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>CV - {{ $empleado->curp }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color:#111; }
        .row { width:100%; }
        .col { display:inline-block; vertical-align:top; }
        .h1 { font-size: 16px; font-weight: 700; margin: 0 0 6px 0; }
        .h2 { font-size: 13px; font-weight: 700; margin: 14px 0 6px 0; padding-bottom:4px; border-bottom:1px solid #ddd; }
        .box { border: 1px solid #ddd; border-radius: 6px; padding: 10px; margin-bottom: 10px; }
        .label { font-weight: 700; color:#333; }
        .muted { color:#666; }
        table { width:100%; border-collapse: collapse; }
        td, th { border:1px solid #ddd; padding:6px; }
        th { background:#f3f3f3; text-align:left; }
        .small { font-size: 11px; }
    </style>
</head>
<body>

    <div class="box">
        <div class="h1">CURRÍCULUM VITAE</div>

        <div class="row">
            <div class="col" style="width:70%;">
                <div><span class="label">NOMBRE:</span> {{ $fullName }}</div>
                <div><span class="label">CURP:</span> {{ strtoupper($empleado->curp) }}</div>
                <div><span class="label">CORREO:</span> {{ $empleado->correo ?? '' }}</div>
            </div>
            <div class="col" style="width:29%; text-align:right;">
                <div class="small muted">Generado: {{ $hoy }}</div>
                @if(!empty($empleado->folio_cv))
                    <div class="small"><span class="label">FOLIO:</span> {{ $empleado->folio_cv }}</div>
                @endif
            </div>
        </div>

        <div style="margin-top:10px;">
            <div><span class="label">PUESTO ACTUAL:</span> {{ $empleado->puesto_actual ?? '' }}</div>
            <div><span class="label">FECHA INICIO PUESTO:</span> {{ $fechaInicioPuesto }}</div>
            <div><span class="label">ÁREA ADSCRIPCIÓN:</span> {{ $empleado->area_adscripcion ?? '' }}</div>
        </div>
    </div>

    <div class="h2">EXPERIENCIA LABORAL (máx. 3)</div>
    <table>
        <thead>
            <tr>
                <th style="width:18%;">Periodo</th>
                <th style="width:20%;">Sector</th>
                <th style="width:20%;">Puesto</th>
                <th style="width:22%;">Institución</th>
                <th style="width:20%;">Campo (100 chars)</th>
            </tr>
        </thead>
        <tbody>
        @forelse($experiencias as $x)
            <tr>
                <td>{{ $x['inicio'] ?? '' }} - {{ $x['fin'] ?? '' }}</td>
                <td>{{ $x['sector'] ?? '' }}</td>
                <td>{{ $x['puesto'] ?? '' }}</td>
                <td>{{ $x['institucion'] ?? '' }}</td>
                <td>{{ $x['campo'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">Sin registros</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="h2">ESTUDIOS ACADÉMICOS</div>
    <div class="box">
        <div><span class="label">Institución:</span> {{ $estudio['institucion'] ?? '' }}</div>
        <div><span class="label">País:</span> {{ $estudio['pais'] ?? '' }}</div>
        <div><span class="label">Nivel:</span> {{ $estudio['nivel'] ?? '' }}</div>
        <div><span class="label">Cédula:</span> {{ $estudio['cedula'] ?? '' }}</div>
        <div><span class="label">Área de estudios:</span> {{ $estudio['area'] ?? '' }}</div>
        <div><span class="label">Carrera específica:</span> {{ $estudio['carrera_especifica'] ?? '' }}</div>
        <div><span class="label">Carrera genérica:</span> {{ $estudio['carrera_generica'] ?? '' }}</div>
    </div>

    <div class="h2">CURSOS Y CAPACITACIONES (máx. 5)</div>
    <table>
        <thead>
            <tr>
                <th style="width:25%;">Periodo</th>
                <th style="width:45%;">Curso</th>
                <th style="width:30%;">Institución</th>
            </tr>
        </thead>
        <tbody>
        @forelse($cursos as $c)
            <tr>
                <td>{{ $c['periodo'] ?? '' }}</td>
                <td>{{ $c['nombre'] ?? '' }}</td>
                <td>{{ $c['institucion'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">Sin registros</td></tr>
        @endforelse
        </tbody>
    </table>

</body>
</html>
