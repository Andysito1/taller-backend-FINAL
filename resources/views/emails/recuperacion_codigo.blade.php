<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de recuperación</title>
    <style>
        body { margin: 0; padding: 0; background: #0f0f10; font-family: Arial, Helvetica, sans-serif; }
        .wrapper { width: 100%; background: linear-gradient(180deg, #0b0b0c 0%, #1b0b0b 100%); padding: 32px 16px; }
        .card { max-width: 640px; margin: 0 auto; background: #121214; border: 1px solid #2a1111; border-radius: 18px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #8a0000 0%, #d10000 100%); color: #fff; padding: 32px; text-align: center; }
        .content { padding: 32px; color: #f3f4f6; line-height: 1.7; }
        .code { display: inline-block; padding: 16px 24px; background: #f3f4f6; color: #111827; border-radius: 14px; font-size: 30px; font-weight: 700; letter-spacing: 6px; margin: 16px 0; }
        .footer { padding: 20px 32px 32px; color: #9ca3af; font-size: 12px; }
        .accent { color: #ff3b30; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>Recuperación de contraseña</h1>
                <p>Xtreme Performance</p>
            </div>
            <div class="content">
                <p>Hola <strong>{{ $nombreUsuario }}</strong>,</p>
                <p>Usa este código para continuar con el cambio de tu contraseña:</p>

                <div style="text-align:center;">
                    <div class="code">{{ $codigo }}</div>
                </div>

                <p>Este código tiene una validez limitada. Si no solicitaste esta acción, puedes ignorar este correo.</p>
                <p class="accent">Equipo Xtreme Performance</p>
            </div>
            <div class="footer">
                Correo automático enviado por Xtreme Performance.
            </div>
        </div>
    </div>
</body>
</html>