<?php

namespace App\Mail;

use App\Services\BrevoMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

abstract class BrevoMailable extends Mailable
{
    use Queueable, SerializesModels;

    abstract public function envelope(): Envelope;

    abstract public function content(): Content;

    public function sendUsingBrevo(BrevoMailer $brevo): void
    {
        $content = $this->content();
        $view = $content->view;
        $data = $content->with ?? [];

        $to = $this->to[0]['address'] ?? null;
        if (! $to) {
            throw new \RuntimeException('BrevoMailable: no hay destinatario (to) definido');
        }

        $subject = $this->envelope()->subject;
        $brevo->sendViewToBrevo($to, $subject, $view, $data);
    }
}

