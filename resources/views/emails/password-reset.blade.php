<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña</title>
    <style>
        body { margin: 0; padding: 0; background: #09090b; font-family: Arial, Helvetica, sans-serif; }
        .shell { width: 100%; background: radial-gradient(circle at top, #1f0000 0%, #09090b 55%, #000 100%); padding: 28px 16px; }
        .card { max-width: 680px; margin: 0 auto; background: #121214; border: 1px solid #2a1111; border-radius: 20px; overflow: hidden; box-shadow: 0 18px 60px rgba(0,0,0,0.45); }
        .hero { padding: 36px; background: linear-gradient(135deg, #5f0000 0%, #cc0000 100%); color: #fff; }
        .hero h1 { margin: 0 0 10px; font-size: 28px; }
        .body { padding: 36px; color: #e5e7eb; line-height: 1.7; }
        .button { display: inline-block; padding: 14px 24px; background: #e10600; color: #fff; text-decoration: none; border-radius: 12px; font-weight: 700; }
        .panel { background: #0f0f10; border: 1px solid #2a1111; border-radius: 14px; padding: 18px; margin: 22px 0; word-break: break-all; color: #f3f4f6; }
        .footer { padding: 0 36px 32px; color: #9ca3af; font-size: 12px; }
        .brand { color: #ff4d4f; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="card">
            <div class="hero">
                <h1>Recupera tu acceso</h1>
                <p>Xtreme Performance</p>
            </div>
            <div class="body">
                <p>Hola <strong>{{ $usuario->nombre }}</strong>,</p>
                <p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el botón para continuar desde el frontend:</p>

                <p style="text-align:center; margin: 28px 0;">
                    <a href="{{ $resetUrl }}" class="button">Restablecer contraseña</a>
                </p>

                <div class="panel">{{ $resetUrl }}</div>

                <p>Si no solicitaste este cambio, ignora este mensaje.</p>
                <p class="brand">Xtreme Performance</p>
            </div>
            <div class="footer">Este es un correo automático y fue enviado por seguridad de la cuenta.</div>
        </div>
    </div>
</body>
</html>