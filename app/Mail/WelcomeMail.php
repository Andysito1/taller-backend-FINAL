<?php

namespace App\Mail;

use App\Mail\BrevoMailable;
use App\Models\Usuario;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeMail extends BrevoMailable
{
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
            view: 'emails.welcome',
        );
    }
}

