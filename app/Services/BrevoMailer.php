<?php

namespace App\Services;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BrevoMailer
{
    public function sendRawEmail(array $payload): array
    {
        $apiKey = env('BREVO_API_KEY');
        if (! $apiKey) {
            throw new \RuntimeException('BREVO_API_KEY no está configurada en el .env');
        }

        $to = $payload['to'] ?? null;
        $subject = $payload['subject'] ?? null;
        $htmlContent = $payload['html'] ?? null;

        if (! $to || ! $subject || ! $htmlContent) {
            throw new \InvalidArgumentException('payload inválido. Requiere to, subject, html');
        }

        $fromAddress = $payload['from']['address'] ?? env('MAIL_FROM_ADDRESS');
        $fromName = $payload['from']['name'] ?? env('MAIL_FROM_NAME', '');

        if (! $fromAddress) {
            throw new \RuntimeException('MAIL_FROM_ADDRESS no está configurada en el .env');
        }

        $body = [
            'sender' => [
                'name' => $fromName,
                'email' => $fromAddress,
            ],
            'to' => [
                [
                    'email' => $to,
                ],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            Log::error('Brevo HTTP error (cURL)', [
                'errno' => $errno,
                'error' => $error,
                'httpCode' => $httpCode,
            ]);

            throw new \RuntimeException('Error cURL enviando email a Brevo: ' . $error);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            Log::error('Brevo API error', [
                'httpCode' => $httpCode,
                'response' => $response,
            ]);

            throw new \RuntimeException('Brevo API error: HTTP ' . $httpCode . ' - ' . (string) $response);
        }

        return [
            'httpCode' => $httpCode,
            'response' => json_decode((string) $response, true),
        ];
    }

    public function sendMailable(string $to, Mailable $mailable): array
    {
        $content = $mailable->content();
        $view = $content->view;
        $data = $content->with ?? [];
        $subject = $mailable->envelope()->subject;

        return $this->sendViewToBrevo($to, $subject, $view, $data);
    }

    public function sendRecoveryCode(string $to, string $subject, string $html): array
    {
        return $this->sendRawEmail([
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS'),
                'name' => env('MAIL_FROM_NAME', 'Xtreme Performance'),
            ],
        ]);
    }

    public function sendViewToBrevo(string $to, string $subject, string $view, array $data = []): array
    {
        $html = view($view, $data)->render();
        $html = (string) $html;

        // Brevo acepta HTML. No usamos CSS externo por compatibilidad.
        $html = Str::of($html)->replace('"', '"')->toString();

        return $this->sendRecoveryCode($to, $subject, $html);
    }
}

