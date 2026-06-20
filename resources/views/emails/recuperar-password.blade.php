<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codigo de recuperacion</title>
</head>
<body style="margin:0;padding:0;background:#0b0b0d;font-family:Arial,Helvetica,sans-serif;color:#ffffff;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0b0b0d;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#151518;border:1px solid #2a2a2e;border-radius:10px;overflow:hidden;">
                    <tr>
                        <td style="background:#000000;padding:26px 30px;border-bottom:4px solid #d32f2f;">
                            <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#ff6b6b;font-weight:bold;">Xtreme Performance</div>
                            <h1 style="margin:10px 0 0;font-size:28px;line-height:34px;color:#ffffff;">Recupera tu acceso</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px;color:#ececec;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:24px;">Hola {{ $nombreUsuario ?? 'cliente' }},</p>
                            <p style="margin:0 0 22px;font-size:16px;line-height:24px;color:#cfcfd4;">Usa este codigo para restablecer tu contrasena. Por seguridad, mantenlo en privado y completalo dentro del tiempo indicado por la plataforma.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#070707;border:2px solid #d32f2f;border-radius:8px;">
                                <tr>
                                    <td align="center" style="padding:28px 16px;">
                                        <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#ff8a80;font-weight:bold;margin-bottom:10px;">Codigo de verificacion</div>
                                        <div style="font-size:44px;line-height:52px;letter-spacing:8px;color:#ffffff;font-weight:800;background:#d32f2f;display:inline-block;padding:12px 22px;border-radius:6px;">{{ $codigo }}</div>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:22px 0 0;font-size:13px;line-height:20px;color:#9ca3af;">Si no solicitaste este cambio, ignora este correo. Tu cuenta seguira protegida.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 30px;background:#101013;color:#777;font-size:12px;">Correo automatico de Xtreme Performance.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
