<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotControllerTest extends TestCase
{
    public function test_it_returns_a_chatbot_reply_from_groq(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Ofrecemos mantenimiento y pintura premium.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/chatbot/message', [
            'message' => '¿Qué servicios ofrecen?',
            'history' => [],
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'groq')
            ->assertJsonPath('reply', 'Ofrecemos mantenimiento y pintura premium.');
    }
}
