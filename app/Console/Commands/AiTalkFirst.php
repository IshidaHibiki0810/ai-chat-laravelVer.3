<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AiTalkFirst extends Command
{
    protected $signature = 'ai:talk-first';
    protected $description = 'AIが寂しいときに先に話しかけるコマンド';

    public function handle()
    {
        $userId = 1; // 仮ユーザー（後でAuth対応）

        $aiState = DB::table('ai_state')->where('user_id', $userId)->first();

        if (!$aiState) {
            $this->info("[AI_TALK_FIRST] aiState not found for userId={$userId}");
            return;
        }

        $lonely = (int)$aiState->emotion_lonely;
        $lastTalk = $aiState->last_ai_talk_at;

        $canTalk = false;
        if ($lonely >= 60) {
            if ($lastTalk === null || strtotime($lastTalk) < time() - 5) {
                $canTalk = true;
            }
        }

        if (!$canTalk) {
            $this->info("[AI_TALK_FIRST] cannot talk yet");
            return;
        }

        $messages = [
            "ねえ、今ちょっと時間ある？",
            "なんとなく話したくなっちゃってさ",
            "最近どう？",
            "少しだけでも話せたら嬉しいな"
        ];

        $aiReply = $messages[array_rand($messages)];

        // messagesテーブルに保存
        DB::table('messages')->insert([
            'user_id' => $userId,
            'role' => 'ai',
            'content' => $aiReply,
            'created_at' => now(),
        ]);

        // ai_state更新
        DB::table('ai_state')->where('user_id', $userId)->update([
            'last_ai_talk_at' => now(),
            'emotion_lonely' => max($aiState->emotion_lonely - 30, 0),
        ]);

        $this->info("[AI_TALK_FIRST] AI_TALKED: message='{$aiReply}'");
    }
}