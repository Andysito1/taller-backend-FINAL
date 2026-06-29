<?php

namespace App\Mail;

use App\Mail\BrevoMailable;
use App\Models\Usuario;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordResetMail extends BrevoMailable
{
    public function __construct(
        public Usuario $usuario,
        public string $resetUrl
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperación de contraseña - Xtreme Performance',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
        );
    }
}

