<?php

namespace App\Application\Chat;

use App\Repositories\MessageRepository;
use App\Domain\Emotion\EmotionDomainService;
use App\Services\AiGeneratorService;
use App\Services\VoiceSpeaker;
use App\Domain\Chat\BehaviorDecisionService;
use App\Domain\Memory\MemoryDomainService;

class ChatApplicationService
{
    private EmotionDomainService $emotionDomainService;
    private AiGeneratorService $aiGenerator;
    private VoiceSpeaker $voiceSpeaker;
    private BehaviorDecisionService $behaviorDecision;
    private MessageRepository $messageRepository;
    private MemoryDomainService $memoryService;

    public function __construct(
        EmotionDomainService $emotionDomainService,
        AiGeneratorService $aiGenerator,
        VoiceSpeaker $voiceSpeaker,
        BehaviorDecisionService $behaviorDecision,
        MessageRepository $messageRepository,
        MemoryDomainService $memoryService
    ) {
        $this->emotionDomainService = $emotionDomainService;
        $this->aiGenerator = $aiGenerator;
        $this->voiceSpeaker = $voiceSpeaker;
        $this->behaviorDecision = $behaviorDecision;
        $this->messageRepository = $messageRepository;
        $this->memoryService = $memoryService;
    }

    /**
     * メイン処理：ユーザー発言 → AI返信生成
     */
    public function handle(int $userId, string $msg): array
    {
        if (!$msg) {
            return ['status' => 'error', 'message' => 'コメントなし'];
        }

        // ==========================
        // ① ユーザー発言保存
        // ==========================
        $this->messageRepository->saveUserMessage($userId, $msg);

        // ==========================
        // ①-2 ユーザー発話をメモリにも保存
        // ==========================
        $this->memoryService->storeMemory('user', $msg, ['user_id' => $userId]);

        // ==========================
        // ② 感情処理
        // ==========================
        $emotionResult = $this->emotionDomainService->handleUserMessage($msg, $userId);

        $status = is_array($emotionResult) ? ($emotionResult['status'] ?? null) : null;

        if ($status === 'NO_REPLY') {
            return ['status' => 'NO_REPLY', 'userMessage' => $msg];
        }

        if ($status === 'DOT_ONLY') {
            return [
                'status' => 'DOT_ONLY',
                'userMessage' => $msg,
                'comment'  => $emotionResult['reply'] ?? ''
            ];
        }

        // ==========================
        // ③ 現在の感情取得
        // ==========================
        $state = $this->emotionDomainService->getState($userId);

        // ==========================
        // ④ 既読無視判定
        // ==========================
        $decision = $this->behaviorDecision->decide($userId, $state);

        if ($decision === 'READ_ONLY') {
            if (!empty($state['is_blocked'])) {
                return ['status' => 'BLOCKED', 'userMessage' => $msg];
            }

            return [
                'status' => 'READ_ONLY',
                'userMessage' => $msg,
                'comment' => null,
                'voiceBase64' => null
            ];
        }

        // ==========================
        // ⑤ 過去の会話を取得して文脈作成
        // ==========================
        $recentMemories = $this->memoryService->getRecentMemories(10, ['user_id' => $userId]);

        $contextMessages = $recentMemories->map(function ($memory) {
            return [
                'role' => $memory->role === 'ai' ? 'assistant' : $memory->role,
                'content' => $memory->content
            ];
        })->toArray();

        // 最新ユーザー発話も追加
        $contextMessages[] = ['role' => 'user', 'content' => $msg];

        // ==========================
        // ⑥ AI返信生成
        // ==========================
        $aiReply = $this->aiGenerator->generate($contextMessages, $state);

        // ==========================
        // ⑦ AI感情更新
        // ==========================
        $this->emotionDomainService->handleAiReply($userId);

        // ==========================
        // ⑧ 音声生成
        // ==========================
        $voiceBase64 = $this->voiceSpeaker->generateVoiceBase64($aiReply, $userId);

        // ==========================
        // ⑨ AI返信保存
        // ==========================
        $this->messageRepository->saveAiMessage(
            $userId,
            $aiReply,
            $voiceBase64 ? 'base64' : null
        );

        // ==========================
        // ⑩ AI返信をメモリにも保存
        // ==========================
        $this->memoryService->storeMemory('ai', $aiReply, ['user_id' => $userId]);

        return [
            'status' => 'AI_REPLY_OK',
            'userMessage' => $msg,
            'comment' => $aiReply,
            'voiceBase64' => $voiceBase64
        ];
    }
}