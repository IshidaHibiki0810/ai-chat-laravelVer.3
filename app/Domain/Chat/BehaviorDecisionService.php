<?php

namespace App\Domain\Chat;

use App\Repositories\MessageRepository;

class BehaviorDecisionService
{
    private MessageRepository $messageRepository;

    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * AIの振る舞いを決定する
     */
    public function decide(int $userId, array $state): string
    {
        $angry = $state['emotion_angry'] ?? 0;

        // 怒りが低ければ通常返信
        if ($angry < 90) {
            return 'NORMAL';
        }

        // 直近3件取得
        $recent = $this->messageRepository
            ->getRecentUserMessages($userId, 3);

        if ($recent->count() < 3) {
            return 'NORMAL';
        }

        // 同一文連投なら既読無視
        if ($recent->unique()->count() === 1) {
            return 'READ_ONLY';
        }

        return 'NORMAL';
    }
}