<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable; 
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Usuario $usuario)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Bienvenido a nuestro Taller Mecánico!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome', // Asegúrate de crear esta vista en resources/views/emails/welcome.blade.php
        );
    }
}