<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LonelyTick extends Command
{
    protected $signature = 'ai:lonely-tick';
    protected $description = 'AIの孤独感を時間経過で更新するコマンド';

    public function handle()
    {
        $userId = 1; // 仮ユーザー
        $aiState = DB::table('ai_state')->where('id', 1)->first();

        if (!$aiState || $aiState->emotion_paused) {
            return;
        }

        // ① 基本孤独増加
        if (!$aiState->last_interaction_at || strtotime($aiState->last_interaction_at) < time() - 30) {
            DB::table('ai_state')->where('id', 1)->update([
                'emotion_lonely' => min($aiState->emotion_lonely + 1, 100),
                'last_interaction_at' => now(),
            ]);
        }

        // ② 既読無視判定
        if ($aiState->last_ai_talk_at) {
            $ignored = !$aiState->last_user_reply_at
                || strtotime($aiState->last_user_reply_at) < strtotime($aiState->last_ai_talk_at);

            if ($ignored && strtotime($aiState->last_ai_talk_at) < time() - 60) {
                DB::table('ai_state')->where('id', 1)->update([
                    'emotion_lonely' => min($aiState->emotion_lonely + 5, 100),
                ]);
            }
        }

        // ③ 会話が少ない（5分で2件以下）
        $count = DB::table('posts')->where('created_at', '>', now()->subMinutes(5))->count();
        if ($count <= 2) {
            DB::table('ai_state')->where('id', 1)->update([
                'emotion_lonely' => min($aiState->emotion_lonely + 3, 100),
            ]);
        }
    }
}