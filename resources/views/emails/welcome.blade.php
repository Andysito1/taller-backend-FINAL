<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido al Taller</title>
    <!-- <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background-color: #f53003; color: #ffffff; padding: 30px text-align: center; }
        .content { padding: 30px; color: #333333; line-height: 1.6; }
        .footer { background-color: #f9f9f9; color: #777777; padding: 20px; text-align: center; font-size: 12px; }
        .button { display: inline-block; padding: 12px 25px; background-color: #f53003; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; margin-bottom: 15px; border: 3px solid #ffffff; }
    </style> -->
</head>
<body>
    <div class="container">
        <div class="header">
            @if($usuario->avatar)
                <img src="{{ $usuario->avatar }}" alt="Avatar" class="avatar">
            @endif
            <h1>¡Bienvenido, {{ $usuario->nombre }}!</h1>
        </div>
        <div class="content">
            <p>Estamos encantados de tenerte con nosotros. Has completado tu registro exitosamente a través de <strong>Google</strong>.</p>
            <p>En nuestro taller, podrás gestionar tus vehículos, ver el seguimiento en tiempo real de tus reparaciones y recibir notificaciones de tus mantenimientos.</p>
            
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}" class="button">Ir a mi Panel</a>
            </div>

            <p>Si tienes alguna duda, simplemente responde a este correo. ¡Estamos para ayudarte!</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Taller Mecánico Profesional. Todos los derechos reservados.<br>
            Este es un correo automático, por favor no lo respondas directamente.
        </div>
    </div>
</body>
</html>