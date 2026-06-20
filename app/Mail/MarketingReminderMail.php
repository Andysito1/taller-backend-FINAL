<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MarketingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Usuario $usuario,
        public array $contenido
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->contenido['asunto'] ?? 'Tu auto nos espera - Xtreme Performance',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recordatorio-marketing',
            with: [
                'usuario' => $this->usuario,
                'contenido' => $this->contenido,
            ],
        );
    }
}
