<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto listo</title>
</head>
<body style="margin:0;padding:0;background:#080808;font-family:Arial,Helvetica,sans-serif;color:#ffffff;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#080808;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:660px;background:#171717;border:1px solid #303030;border-radius:10px;overflow:hidden;">
                    <tr>
                        <td style="background:#000000;padding:30px;border-bottom:5px solid #d32f2f;">
                            <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#ff5252;font-weight:bold;">Servicio completado</div>
                            <h1 style="margin:10px 0 0;font-size:31px;line-height:38px;color:#ffffff;">Tu auto esta listo para salir a pista</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px;color:#eeeeee;">
                            <p style="margin:0 0 18px;font-size:16px;line-height:24px;">Hola {{ $usuario->nombre ?? 'cliente' }},</p>
                            <p style="margin:0 0 22px;font-size:16px;line-height:25px;color:#d8d8d8;">Terminamos el servicio de tu {{ $vehiculo->marca ?? 'vehiculo' }} {{ $vehiculo->modelo ?? '' }} con placa <strong style="color:#ffffff;">{{ $vehiculo->placa ?? 'N/A' }}</strong>. Nuestro equipo realizo el control final de Xtreme Performance.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0b0b0c;border:1px solid #d32f2f;border-radius:8px;">
                                <tr>
                                    <td style="padding:20px;">
                                        <h2 style="margin:0 0 14px;font-size:20px;color:#ff6b6b;">Ficha de salida</h2>
                                        <p style="margin:0 0 8px;color:#ffffff;">&#10003; Pruebas de control completadas</p>
                                        <p style="margin:0 0 8px;color:#ffffff;">&#10003; Revision visual y tecnica aprobada</p>
                                        <p style="margin:0 0 8px;color:#ffffff;">&#10003; Servicio registrado: {{ $order->titulo ?? 'Orden de servicio' }}</p>
                                        <p style="margin:0;color:#ffffff;">&#10003; Vehiculo listo para entrega</p>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px;background:#202024;border-radius:8px;">
                                <tr>
                                    <td style="padding:18px;">
                                        <h3 style="margin:0 0 10px;font-size:18px;color:#ffffff;">Recojo del vehiculo</h3>
                                        <p style="margin:0 0 6px;color:#cfcfcf;"><strong style="color:#ff6b6b;">Horario:</strong> {{ $horarioRecojo }}</p>
                                        <p style="margin:0;color:#cfcfcf;"><strong style="color:#ff6b6b;">Direccion:</strong> {{ $direccionTaller }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 30px;background:#0f0f10;color:#858585;font-size:12px;">Gracias por confiar en Xtreme Performance.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
