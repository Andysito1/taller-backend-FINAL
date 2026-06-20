<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de servicio</title>
    <style>
        body { margin: 0; padding: 0; background: #050505; font-family: Arial, Helvetica, sans-serif; }
        .frame { width: 100%; background: linear-gradient(180deg, #050505 0%, #170000 100%); padding: 30px 16px; }
        .card { max-width: 680px; margin: 0 auto; background: #101114; border: 1px solid #2d0b0b; border-radius: 20px; overflow: hidden; }
        .banner { padding: 36px; background: linear-gradient(135deg, #8b0000 0%, #f01f1f 100%); color: #fff; }
        .banner h1 { margin: 0 0 8px; font-size: 30px; }
        .body { padding: 36px; color: #e5e7eb; line-height: 1.7; }
        .highlight { background: #1a0b0b; border-left: 4px solid #ef4444; padding: 16px 18px; border-radius: 12px; margin: 22px 0; }
        .button { display: inline-block; padding: 14px 24px; background: #e10600; color: #fff; text-decoration: none; border-radius: 12px; font-weight: 700; }
        .footer { padding: 0 36px 32px; color: #9ca3af; font-size: 12px; }
        .small { color: #ef4444; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="card">
            <div class="banner">
                <div class="small">Xtreme Performance</div>
                <h1>{{ $contenido['titulo'] }}</h1>
                <p>{{ $contenido['subtitulo'] }}</p>
            </div>
            <div class="body">
                <p>Hola <strong>{{ $usuario->nombre }}</strong>,</p>
                <div class="highlight">
                    {{ $contenido['mensaje'] }}
                </div>
                <p>Te esperamos para seguir cuidando el rendimiento, la seguridad y la presencia de tu vehículo.</p>
                <p style="text-align:center; margin: 28px 0;">
                    <a href="{{ $contenido['cta_url'] }}" class="button">{{ $contenido['cta_texto'] }}</a>
                </p>
            </div>
            <div class="footer">Si deseas dejar de recibir estos recordatorios, el administrador puede actualizar tu preferencia desde el panel interno.</div>
        </div>
    </div>
</body>
</html>