<?php

namespace App\Services;

use App\Infrastructure\VoiceVoxClient;
use App\Domain\Emotion\EmotionDomainService;

class VoiceSpeaker
{
    private EmotionDomainService $emotionDomainService;
    private VoiceVoxClient $voiceVoxClient;

    public function __construct(
        EmotionDomainService $emotionDomainService,
        VoiceVoxClient $voiceVoxClient
    ) {
        $this->emotionDomainService = $emotionDomainService;
        $this->voiceVoxClient = $voiceVoxClient;
    }

    public function generateVoiceBase64(string $text, int $userId): ?string
    {
        $state = $this->emotionDomainService->getState($userId);

        $speaker = $this->decideSpeaker($state);

        $binary = $this->voiceVoxClient->synthesize($text, $speaker);

        return $binary ? base64_encode($binary) : null;
    }

    private function decideSpeaker(array $state): int
    {
        $angry = $state['emotion_angry'] ?? 0;
        $happy = $state['emotion_happy'] ?? 0;

        if ($angry >= 70) return 7;
        if ($happy >= 70) return 1;

        return 3;
    }
}