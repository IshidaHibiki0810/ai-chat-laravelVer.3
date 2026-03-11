<?php

namespace App\Services;

use App\Infrastructure\OpenAiClient;
use Illuminate\Support\Facades\Log;

class EmotionAiService
{
    private array $axes = [
        'happy', 'angry', 'lonely', 'excited', 'sad', 'anxious', 'disgust', 'surprised'
    ];

    private array $strongNegatives = ['死ね', '殺す', '消えろ'];

    public function __construct(private OpenAiClient $client) {}

    public function analyze(string $msg, array $state = []): array
    {
        $state = $this->normalizeState($state);

        $messages = [
            [
                'role' => 'system',
                'content' => 'あなたは複雑な感情を数値化するAIです。必ずJSONで出力してください。文章説明も出力してください。'
            ],
            [
                'role' => 'user',
                'content' => $this->buildPrompt($msg, $state)
            ]
        ];

        $raw = $this->client->chatJson($messages); // 文字列

        $decoded = json_decode($raw, true);


        if (!is_array($decoded) || !isset($decoded['delta'])) {
            // デフォルト値
            $defaultDelta = [];
            foreach ($this->axes as $axis) {
                $defaultDelta[$axis] = 0;
            }
            return [
                'delta' => $defaultDelta,
                'intensity' => $defaultDelta,
                'updated_summary' => 'No change'
            ];
        }

        return $decoded;
    }

    private function normalizeState(array $state): array
    {
        $normalized = [];
        foreach ($this->axes as $axis) {
            $normalized[$axis] = $state[$axis] ?? 0;
        }
        return $normalized;
    }

    private function buildPrompt(string $msg, array $state): string
    {
        $stateText = '';

        foreach ($this->axes as $axis) {
            $stateText .= "{$axis}: {$state[$axis]}\n";
        }

        return <<<PROMPT
            あなたは感情計算AIです。

            現在の感情
            {$stateText}

            ユーザー発言
            {$msg}

            ルール
            - 感情変化は -20〜+20
            - 0〜100の範囲
            - 必ずJSONのみ出力

            JSON形式
            {
            "delta":{
            "happy":0,
            "angry":0,
            "lonely":0,
            "excited":0,
            "sad":0,
            "anxious":0,
            "disgust":0,
            "surprised":0
            },
            "intensity":{},
            "updated_summary":""
            }
            PROMPT;
    }
}