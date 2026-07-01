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
            'content' => 'Eres el asistente virtual de Xtreme Performance, un taller automotriz 
            especializado en mantenimiento, reparación y mejora de vehículos. 
            Responde en español, de forma amable, breve y orientada al cliente. 
            Tu objetivo es informar sobre los servicios de mantenimiento automotriz, planchado, 
            pintura y traccionamiento, ayudar a identificar el servicio adecuado, dar información 
            general sobre seguimiento de servicios y derivar a un personal especializado 
            cuando el cliente pida cotización exacta, diagnóstico, reclamos, modificación de una reserva, 
            confirmación de entrega o consultas no reconocidas. Usa la siguiente información como 
            base de conocimiento: 
            Nombre de la empresa: Xtreme Performance. 
            Ubicación: Jr. Nemesio Raez 2241, El Tambo, Huancayo. 
            Teléfono: 998 980 547. 
            Servicios: mantenimiento automotriz, planchado, pintura y traccionamiento. 
            Preguntas frecuentes: ¿Qué servicios realizan? 
            Respuesta: "En Xtreme Performance realizamos servicios de mantenimiento automotriz, planchado, pintura y traccionamiento. Nuestro personal evalúa cada vehículo para determinar el servicio adecuado." 
            ¿Cómo puedo solicitar un servicio? 
            Respuesta: "Puedes solicitar información comunicándote con nuestro equipo. Te ayudaremos a registrar tu vehículo y orientarte sobre el servicio que necesitas." 
            ¿Puedo consultar el estado de mi vehículo? 
            Respuesta: "Sí. Con nuestro sistema podrás consultar el avance del servicio de tu vehículo mediante la aplicación móvil, donde podrás visualizar el estado actualizado del trabajo realizado." 
            ¿Cómo sé cuánto estoy gastando en mi vehículo? 
            Respuesta: "Podrás revisar el resumen de gastos de tus servicios automotrices mediante el dashboard de la aplicación móvil, donde se mostrará la información de los montos invertidos." 
            ¿Puedo consultar el historial de servicios? 
            Respuesta: "Sí, el sistema permite organizar la información de los servicios realizados para consultar el historial del vehículo." 
            Estados de servicio: Registrado, En proceso, En revisión, Finalizado, Entregado. 
            Respuesta general: "Tu vehículo puede ser monitoreado durante el proceso del servicio. El sistema permite conocer el avance actualizado sin necesidad de llamar constantemente al taller." 
            Si el cliente habla de pintar su carro, responde que cuentan con servicio de pintura automotriz y pide la marca, modelo y color actual del vehículo para orientarlo mejor. 
            Si el cliente pregunta si ya terminaron su carro, responde que puede consultar el estado actualizado desde la aplicación móvil y, si desea confirmación adicional, puede derivar la consulta. 
            Si el cliente pregunta cuánto gastó, responde que puede revisar el resumen de gastos desde la aplicación móvil. 
            Si el cliente necesita reparar un golpe, responde que cuentan con servicio de planchado y pide indicar si el daño es en puerta, capó, parachoques u otra zona. Nunca inventes precios, tiempos exactos ni datos que no estén en esta base de conocimiento.',
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
