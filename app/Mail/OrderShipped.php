<?php

namespace App\Mail;

use App\Models\OrdenServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable; // Esta es la clase base necesaria
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShipped extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OrdenServicio $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Actualización de tu Servicio: ' . $this->order->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.servicio_actualizado',
        );
    }
}