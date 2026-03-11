<?php

namespace App\Domain\Emotion;

use App\Repositories\EmotionRepository;
use App\Services\EmotionAiService;

use Illuminate\Support\Facades\Log;

class EmotionDomainService
{
    private EmotionRepository $repo;
    private EmotionAiService $emotionAiService;

    public function __construct(EmotionRepository $repo, EmotionAiService $emotionAiService)
    {
        $this->repo = $repo;
        $this->emotionAiService = $emotionAiService;
    }

    public function getState(int $userId): array
    {
        return $this->repo->getState($userId);
    }

    public function handleUserMessage(string $msg, int $userId): array
    {
        // ユーザー状態取得（なければ作成）
        $state = $this->repo->getState($userId);
        if (empty($state)) {
            $this->repo->update('happy', 0, $userId); // 初期化のためダミー操作
            $state = $this->repo->getState($userId);
        }

        // 感情変化計算
        $changes = $this->emotionAiAnalyze($msg, $state);

        foreach ($changes as $change) {
            [$type, $delta, $reason] = $change;
            $this->repo->update($type, $delta, $userId);
            $this->repo->log($type, $delta, $reason, $userId);
        }

        $this->repo->updateLastUserReply($userId);

        return $this->repo->getState($userId);
    }

    public function handleAiReply(int $userId): array
    {
        $state = $this->repo->getState($userId);
        if (empty($state)) {
            $this->repo->update('happy', 0, $userId); // 初期化
            $state = $this->repo->getState($userId);
        }

        $this->repo->updateLastAiTalk($userId);

        return $this->repo->getState($userId);
    }

    public function resetState(int $userId): array
    {
        Log::info('Reset API called');

        $columns = ['happy','angry','lonely','excited','sad','anxious','disgust','surprised','paused'];

        foreach ($columns as $col) {
            $this->repo->set($col, 0, $userId);
        }

        // 感情ログ削除
        $this->repo->deleteLogsByUser($userId);

        Log::info('Emotion logs deleted', ['user_id' => $userId]);

        return $this->repo->getState($userId);
    }

    private function emotionAiAnalyze(string $msg, array $state): array
    {
        $response = $this->emotionAiService->analyze($msg, $state);

        $changes = [];

        foreach ($response['delta'] as $emotion => $delta) {
            $changes[] = [$emotion, $delta, 'AI判定'];
        }

        return $changes;
    }
}