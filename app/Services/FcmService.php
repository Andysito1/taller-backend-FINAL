<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function __construct()
    {
        // Eliminamos la inyección directa para evitar el BindingResolutionException
    }

    public function enviarNotificacion($token, $titulo, $cuerpo, array $data = []): bool
    {
        if (empty($token)) return false;

        $messaging = app('firebase.messaging');
        $notification = Notification::create($titulo, $cuerpo);

        // Aseguramos que todos los valores en 'data' sean strings para evitar errores en Flutter
        $dataPayload = array_map(function($value) {
            return is_null($value) ? "" : (string)$value;
        }, $data);

        // Importante: Añadimos títulos y mensajes al payload por si la app está en foreground
        $dataPayload['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';
        
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification($notification)
            ->withData($dataPayload);

        try {
            $messaging->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error("Error enviando FCM: " . $e->getMessage());
            return false;
        }
    }
}