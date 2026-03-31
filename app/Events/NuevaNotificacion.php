<?php

namespace App\Events; // Asegúrate de que esta carpeta exista: app/Events

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevaNotificacion implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;
    private $channelName;

    public function __construct($data, $channelName = 'notificaciones-channel')
    {
        // $data debe contener: titulo, mensaje, tipo, fecha
        $this->data = $data ?? [];
        $this->channelName = $channelName;
    }

    public function broadcastOn()
    {
        // Nombre del canal público
        return new Channel($this->channelName);
    }

    public function broadcastAs()
    {
        // Nombre del evento que escucha la app
        return 'nuevo-evento';
    }
}
