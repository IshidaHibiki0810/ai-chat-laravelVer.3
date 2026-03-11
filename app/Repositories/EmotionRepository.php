<?php

namespace App\Repositories;

use App\Models\AiState;
use App\Models\AiEmotionLog;

class EmotionRepository
{
    public function update(string $emotion, int $delta, int $userId): void
    {
        $state = AiState::find($userId);
        if (!$state) return;

        $emotion = trim($emotion);   // ← 重要
        $column = "emotion_" . $emotion;

        if (!array_key_exists($column, $state->getAttributes())) {
            return;
        }

        $newValue = max(0, min(100, $state->$column + $delta));

        $state->$column = $newValue;
        $state->save();
    }
    public function log(string $emotion, int $delta, string $reason, int $userId): void
    {
        AiEmotionLog::create([
            'user_id'     => $userId,
            'emotion_type'=> $emotion,
            'delta'       => $delta,
            'reason'      => $reason,
            'created_at'  => now()
        ]);
    }

    public function getState(int $userId): array
    {
        $state = AiState::find($userId);
        return $state ? $state->toArray() : [];
    }

    public function updateLastInteraction(int $userId): void
    {
        AiState::where('user_id', $userId)
            ->update(['last_interaction_at' => now()]);
    }

    public function updateLastAiTalk(int $userId): void
    {
        AiState::where('user_id', $userId)
            ->update(['last_ai_talk_at' => now()]);
    }

    public function updateLastUserReply(int $userId): void
    {
        AiState::where('user_id', $userId)
            ->update(['last_user_reply_at' => now()]);
    }

    public function set(string $emotion, int $value, int $userId): void
    {
        $state = AiState::find($userId);
        if (!$state) return;
        $column = "emotion_{$emotion}";
        $state->$column = $value;
        $state->save();
    }

    public function deleteLogsByUser(int $userId): void
    {
        \DB::table('ai_emotion_log')
            ->where('user_id', $userId)
            ->delete();
    }
}