<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corrección de registro de CV</title>
</head>
<body style="margin:0; padding:0; background:#f6f8fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <div style="max-width:640px; margin:0 auto; padding:24px;">
        <div style="background:#ffffff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden;">
            <div style="background:#006341; color:#ffffff; padding:18px 24px;">
                <h2 style="margin:0; font-size:20px;">Corrección de registro de CV</h2>
            </div>

            <div style="padding:24px;">
                <p style="margin-top:0;">
                    Hola
                    <strong>
                        {{ trim(($empleado->nombre ?? '') . ' ' . ($empleado->primer_apellido ?? '') . ' ' . ($empleado->segundo_apellido ?? '')) }}
                    </strong>,
                </p>

                <p>
                    Tu registro de CV fue <strong>rechazado</strong> durante el proceso de revisión.
                </p>

                <div style="margin:20px 0; padding:16px; background:#fef2f2; border:1px solid #fecaca; border-radius:10px;">
                    <div style="font-weight:bold; color:#991b1b; margin-bottom:8px;">Motivo de rechazo</div>
                    <div style="white-space:pre-wrap; color:#7f1d1d;">
                        {{ $motivo !== '' ? $motivo : 'No se proporcionó un motivo específico.' }}
                    </div>
                </div>

                <p>
                    Por favor, ingresa nuevamente al sistema para corregir tu información y volver a enviarla.
                </p>

                <p style="margin:24px 0;">
                    <a
                        href="{{ route('registro.wizard') }}"
                        target="_blank"
                        style="display:inline-block; background:#006341; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:10px; font-weight:bold;"
                    >
                        Ir al registro de CV
                    </a>
                </p>

                <p style="color:#6b7280; font-size:13px; margin-bottom:0;">
                    Este mensaje fue generado automáticamente. Si tienes dudas, contacta al área correspondiente.
                </p>
            </div>
        </div>
    </div>
</body>
</html>