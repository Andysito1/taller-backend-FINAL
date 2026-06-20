<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio Xtreme Performance</title>
</head>
<body style="margin:0;padding:0;background:#050505;font-family:Arial,Helvetica,sans-serif;color:#ffffff;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#050505;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#161616;border:1px solid #2e2e2e;border-radius:10px;overflow:hidden;">
                    <tr>
                        <td style="background:#000000;padding:28px 30px;border-bottom:5px solid #d32f2f;">
                            <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#ff5252;font-weight:bold;">Taller automotriz</div>
                            <h1 style="margin:10px 0 0;font-size:30px;line-height:36px;color:#ffffff;">Los mecanicos extranan tu auto</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px;color:#eeeeee;">
                            <p style="margin:0 0 18px;font-size:16px;line-height:24px;">Hola {{ $usuario->nombre ?? 'cliente' }},</p>
                            <p style="margin:0 0 18px;font-size:17px;line-height:27px;color:#ffffff;font-weight:bold;">¡Los mecánicos extrañan tu auto! En Xtreme Performance estamos comprometidos con seguir mejorando continuamente nuestros servicios y tu vehículo no es la excepción...</p>
                            <p style="margin:0 0 26px;font-size:16px;line-height:24px;color:#cfcfcf;">{{ $contenido['mensaje'] ?? 'Agenda una revision y mantengamos tu vehiculo listo para la ruta, con rendimiento, seguridad y estilo.' }}</p>
                            <table role="presentation" cellpadding="0" cellspacing="0" align="center">
                                <tr>
                                    <td align="center" bgcolor="#d32f2f" style="border-radius:6px;">
                                        <a href="{{ $contenido['cta_url'] ?? config('app.frontend_url', config('app.url')) }}" style="display:inline-block;padding:14px 26px;color:#ffffff;text-decoration:none;font-weight:800;font-size:15px;letter-spacing:.5px;">{{ $contenido['cta_texto'] ?? 'Agendar cita' }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 30px;background:#0f0f10;color:#858585;font-size:12px;">Xtreme Performance - Potencia, control y confianza para tu vehiculo.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
