<?php

namespace App\Http\Controllers\Auth\Recover;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TemplateMailController extends Controller
{
    public function contentMail($user, $password)
    {
        $loginUrl = route('login');
        // require('other.php');
        return '
    <!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Sistema de Profesionalización IMSS BIENESTAR</title>
</head>

<body
    style="margin:0; padding:20px; background-color:#f9f9f9; font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif;">
    <table align="center" cellpadding="0" cellspacing="0" width="600"
        style="background-color:#ffffff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); overflow:hidden;">
        <tr style="background-color:#7b1f32;">
            <td style="padding:20px 30px;">
                <h1 style="color:#fff; font-size:20px; margin:0;">Promoción por profesionalización 2025</h1>
                <p style="color:#c9a54b; font-size:14px; margin:5px 0 0;">Restablecimiento de contraseña</p>
            </td>
        </tr>
        <tr>
            <td style="padding:30px; color:#333333;">
                <p style="font-size:16px; line-height:1.6;">
                    Hemos recibido la notificación para el restablecimiento de su contraseña. A continuación,
                    se detallan los datos de acceso:
                </p>

                <table width="100%" cellpadding="8" cellspacing="0" style="margin-top:20px; font-size:16px;">
                    <tr>
                        <td width="45%" style="font-weight:bold; color:#7b1f32;">Usuario:</td>
                        <td style="color:#000000">' . $user . '</td>
                    </tr>
                    <tr>
                        <td width="45%" style="font-weight:bold; color:#7b1f32;">Contraseña:</td>
                        <td style="color:#000000">' . $password . '</td>
                    </tr>
                </table>

                <p style="font-size:16px; line-height:1.6; margin-top:30px;">
                    Puede acceder al sistema haciendo clic en el siguiente enlace:
                    <a href="' . $loginUrl . '">Acceder al sistema</a>
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:20px; background-color:#f2f2f2; text-align:center;">
                <p style="font-size:13px; color:#777777;">
                    Este es un correo de notificación automática. Por favor, no responda a este mensaje.
                </p>
            </td>
        </tr>
    </table>
    <br><br>
</body>

</html>';
    }
}
