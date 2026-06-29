<?php

namespace App\Mail;

use App\Mail\BrevoMailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RecoveryCodeMail extends BrevoMailable
{
    public function __construct(public string $codigo, public string $nombreUsuario) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de Recuperación de Contraseña',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recuperar-password',
            with: [
                'codigo' => $this->codigo,
                'nombreUsuario' => $this->nombreUsuario,
            ],
        );
    }
}

