<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function message(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:1'],
            'history' => ['nullable', 'array'],
        ]);

        $history = is_array($data['history'] ?? null) ? $data['history'] : [];
        $userMessage = trim($data['message']);

        $messages = $this->buildMessages($history, $userMessage);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey(),
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $this->model(),
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 250,
        ]);

        if ($response->failed()) {
            return response()->json([
                'reply' => 'Lo siento, no pude responder en este momento. Puedes escribirnos directamente o contactarnos para recibir ayuda personalizada.',
                'provider' => 'groq-fallback',
                'status' => 'fallback',
            ], 200);
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        $reply = is_string($content) ? trim($content) : $this->fallbackReply();

        return response()->json([
            'reply' => $reply,
            'provider' => 'groq',
            'status' => 'ok',
        ]);
    }

    private function buildMessages(array $history, string $message): array
    {
        $messages = [[
            'role' => 'system',
            'content' => 'Eres el asistente virtual de Xtreme Performance, un taller automotriz especializado en mantenimiento, traccionamiento, planchado y pintura premium. Responde en español, de forma amable y breve. Ayuda al cliente a entender los servicios, orienta sobre cuál podría convenirle y, si no tienes certeza, invita a contactar con el equipo. Nunca inventes datos ni prometas tiempos o precios si no te los dieron.',
        ]];

        foreach ($history as $item) {
            if (!isset($item['role'], $item['content'])) {
                continue;
            }

            $role = $item['role'] === 'assistant' ? 'assistant' : 'user';
            $messages[] = [
                'role' => $role,
                'content' => (string) $item['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        return $messages;
    }

    private function fallbackReply(): string
    {
        return 'Hola, soy el asistente de Xtreme Performance. Puedo orientarte sobre nuestros servicios de mantenimiento, traccionamiento, planchado y pintura premium. Si deseas, cuéntame qué necesita tu vehículo y te ayudo a identificar la mejor opción.';
    }

    private function apiKey(): string
    {
        return (string) config('services.groq.api_key', env('GROQ_API_KEY', ''));
    }

    private function model(): string
    {
        return (string) config('services.groq.model', env('GROQ_MODEL', 'llama-3.3-70b-versatile'));
    }
}
