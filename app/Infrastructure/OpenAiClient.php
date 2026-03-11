<?php

namespace App\Infrastructure;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiClient
{
    public function chat(array $messages): string
    {
        $apiKey = config('services.openai.key');

        if (!$apiKey) {
            Log::error('OpenAI API Key is missing.');
            return 'AI返信取得失敗';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'max_tokens' => 150,
        ]);


        if (!$response->successful()) {
            Log::error('OpenAI API Error: ' . $response->body());
            return 'AI返信取得失敗';
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? 'AI返信取得失敗';
    }

    public function chatJson(array $messages): string
    {
        $apiKey = config('services.openai.key');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [

            'model' => 'gpt-4o-mini',

            'messages' => $messages,

            'response_format' => [
                'type' => 'json_object'
            ],
            'temperature' => 0.2
        ]);

        if (!$response->successful()) {
            Log::error('OpenAI API Error: ' . $response->body());
            return '{}';
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? '{}';
    }
}