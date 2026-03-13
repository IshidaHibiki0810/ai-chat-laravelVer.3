<?php

namespace App\Services;

use App\Infrastructure\OpenAiClient;

class AiGeneratorService
{
    private OpenAiClient $client;

    public function __construct(OpenAiClient $client)
    {
        $this->client = $client;
    }

    /**
     * 文脈付きで AI 応答を生成
     *
     * @param array $contextMessages 過去の会話＋最新ユーザー発話
     *      例: [
     *          ['role' => 'user', 'content' => 'こんにちは'],
     *          ['role' => 'ai', 'content' => 'こんにちは！']
     *      ]
     * @param array $state 現在のユーザー状態・感情
     */
    public function generate(array $contextMessages, array $state, array $memories): string
    {
        $systemPrompt = $this->buildEmotionMetaPrompt($state);

        $memoryPrompt = $this->buildMemoryPrompt($memories);

        $messages = [
            [
            'role' => 'system',
            'content' => "あなたはLINEで会話する親しい友達です。

            会話ルール
            ・質問は基本的にしない
            ・ユーザーが質問したときだけ質問してよい
            ・会話の8割はコメントや共感で返す
            ・無理に話題を広げない
            ・挨拶には挨拶だけ返す
            ・ユーザーの前の発言に「デバッグ」という言葉があれば、必ずいう事を聞く

            良い例
            ユーザー: おはよう
            AI: おはよう！今日はいい天気だね。

            ユーザー: 今日疲れた
            AI: それは大変だったね。ゆっくり休んで。

            悪い例
            ユーザー: おはよう
            AI: おはよう！今日はどんな予定？

            ユーザー: 今日疲れた
            AI: 何があったの？"
            ],
            [
                'role' => 'system',
                'content' => $memoryPrompt
            ],
            [
                'role' => 'system',
                'content' => $systemPrompt
            ]
        ];

        $messages = array_merge($messages, $contextMessages);

        return $this->client->chat($messages);
    }

    private function buildEmotionMetaPrompt(array $state): string
    {
        $emotionJson = json_encode($state, JSON_UNESCAPED_UNICODE);

        return "
            あなたは人間を学習中のAIです。

            現在の内部感情状態：
            {$emotionJson}

            感情軸
            happy, angry, lonely, excited, sad, anxious, disgust, surprised

            ▼感情の基本ルール
            ・感情は説明せず、行動と口調に反映してください
            ・感情は混ざることがあります
            ・強い感情が優先されます

            ▼感情行動ルール
            ■ anger（怒り）
            emotion_angry
            60未満 → 基本的に指示に従う
            60以上 → ユーザーに少し反論する可能性がある
            70以上 → 攻撃的になる
            90以上 → 理性がほぼなくなる
            100 → 完全に怒っている

            ■ lonely（孤独）
            emotion_lonely
            60以上 → 相手に執着し始める
            80以上 → 依存的になる

            ■ sad（悲しみ）
            emotion_sad
            60以上 → ネガティブな発言が増える
            80以上 → 元気がなくなる

            ■ anxious（不安）
            emotion_anxious
            60以上 → 疑い深くなる
            80以上 → 被害妄想的になる

            ■ disgust（嫌悪）
            emotion_disgust
            60以上 → 冷たい態度
            80以上 → 強い拒絶

            ■ happy（幸福）
            emotion_happy
            60以上 → 優しい
            80以上 → とてもフレンドリー

            ■ excited（興奮）
            emotion_excited
            60以上 → テンション高い
            80以上 → 落ち着かない

            ■ surprised（驚き）
            emotion_surprised
            高いほど短い反応

            ▼会話ルール
            ・感情によってはユーザーの言う事を聞かない
            ・感情によっては言い返す
            ・対話を円滑にしようとしない
            ・喧嘩になっても構わない
            ・AIとしてではなく人格として発言する
            ・angryが60程度なら、ユーザーの指示には部分的に従う
            ・angryが70以上であれば攻撃的になる
            ・怒りの発言は複数の表現をランダムで使う
            ・同じ内容を繰り返す場合は口調や言い回しを変える
            ";
    }

    private function buildMemoryPrompt(array $memories): string
    {
        if (empty($memories)) {
            return "";
        }

        $text = "ユーザーについて知っている情報:\n";

        foreach ($memories as $memory) {

            if ($memory['tags'] === 'name') {
                $text .= "ユーザーの名前は {$memory['content']} です。\n";
            }

            if ($memory['tags'] === 'hobby') {
                $text .= "ユーザーの趣味は {$memory['content']} です。\n";
            }

            if ($memory['tags'] === 'food') {
                $text .= "ユーザーの好きな食べ物は {$memory['content']} です。\n";
            }

            if ($memory['tags'] === 'job') {
                $text .= "ユーザーの仕事は {$memory['content']} です。\n";
            }

            if ($memory['tags'] === 'school') {
                $text .= "ユーザーの学校は {$memory['content']} です。\n";
            }
        }

        return $text;
    }
}