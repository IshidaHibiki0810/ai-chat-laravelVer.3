<?php

namespace App\Domain\Memory;

use App\Infrastructure\OpenAiClient;

class MemoryExtractor
{
    private OpenAiClient $client;

    public function __construct(OpenAiClient $client)
    {
        $this->client = $client;
    }

    public function extract(string $text, ?string $lastAiMessage = null): ?array
    {
        // ----------------------
        // ① 高速ルール抽出
        // ----------------------

        if (preg_match('/名前は(.+?)です/', $text, $matches)) {
            return [
                'tag' => 'name',
                'content' => trim($matches[1])
            ];
        }
        // 趣味
        if (preg_match('/趣味は(.+?)です/', $text, $matches)) {
            return [
                'tag' => 'hobby',
                'content' => trim($matches[1])
            ];
        }

        // 好き
        if (preg_match('/(.+?)が好き/', $text, $matches)) {
            return [
                'tag' => 'like',
                'content' => trim($matches[1])
            ];
        }

        // 嫌い
        if (preg_match('/(.+?)が嫌い/', $text, $matches)) {
            return [
                'tag' => 'dislike',
                'content' => trim($matches[1])
            ];
        }

        // 食べ物
        if (preg_match('/好きな食べ物は(.+?)です/', $text, $matches)) {
            return [
                'tag' => 'food',
                'content' => trim($matches[1])
            ];
        }

        // ----------------------
        // ② 文脈推測
        // ----------------------

        if ($lastAiMessage && str_contains($lastAiMessage, '名前') && mb_strlen($text) <= 20) {
            return [
                'tag' => 'name',
                'content' => trim($text)
            ];
        }

        if ($lastAiMessage && str_contains($lastAiMessage, '趣味') && mb_strlen($text) <= 20) {
            return [
                'tag' => 'hobby',
                'content' => trim($text)
            ];
        }

        if ($lastAiMessage && str_contains($lastAiMessage, '好き') && mb_strlen($text) <= 20) {
            return [
                'tag' => 'like',
                'content' => trim($text)
            ];
        }

        if ($lastAiMessage && str_contains($lastAiMessage, '嫌い') && mb_strlen($text) <= 20) {
            return [
                'tag' => 'dislike',
                'content' => trim($text)
            ];
        }

        if ($lastAiMessage && str_contains($lastAiMessage, '好きな食べ物') && mb_strlen($text) <= 20) {
            return [
                'tag' => 'food',
                'content' => trim($text)
            ];
        }


        // ----------------------
        // ③ AI抽出
        // ----------------------

        $messages = [
            [
                'role' => 'system',
                'content' => "ユーザーの発言から覚えるべき情報を抽出してください。

                必ずJSONのみで返してください。
                文章は一切書かないこと。

                例
                {\"tag\":\"hobby\",\"content\":\"ゲーム\"}

                tag候補
                name
                hobby
                food
                job
                school
                like
                dislike

                覚える必要がない場合は null を返してください。"
            ],
            [
                'role' => 'user',
                'content' => $text
            ]
        ];

        $response = $this->client->chat($messages);

        $data = json_decode($response, true);

        return $data ?: null;
    }
}