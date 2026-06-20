<?php

namespace App\Mail;

use App\Models\OrdenServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShipped extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrdenServicio $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu auto esta listo - Xtreme Performance',
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing(['vehiculo.cliente.usuario', 'servicio', 'etapas']);

        return new Content(
            view: 'emails.auto-listo',
            with: [
                'order' => $this->order,
                'vehiculo' => $this->order->vehiculo,
                'usuario' => $this->order->vehiculo?->cliente?->usuario,
                'horarioRecojo' => 'Lunes a sabado de 8:00 a.m. a 6:00 p.m.',
                'direccionTaller' => 'Av. Principal 123, Huancayo',
            ],
        );
    }
}
