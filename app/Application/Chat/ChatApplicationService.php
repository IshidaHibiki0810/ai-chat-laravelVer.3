<?php

namespace App\Application\Chat;

use App\Repositories\MessageRepository;
use App\Domain\Emotion\EmotionDomainService;
use App\Services\AiGeneratorService;
use App\Services\VoiceSpeaker;
use App\Domain\Chat\BehaviorDecisionService;
use App\Domain\Memory\MemoryDomainService;
use App\Domain\Memory\MemoryExtractor;
use App\Repositories\MemoryRepository;

use Illuminate\Support\Facades\Log;

class ChatApplicationService
{
    private EmotionDomainService $emotionDomainService;
    private AiGeneratorService $aiGenerator;
    private VoiceSpeaker $voiceSpeaker;
    private BehaviorDecisionService $behaviorDecision;
    private MessageRepository $messageRepository;
    private MemoryDomainService $memoryService;
    private MemoryExtractor $memoryExtractor;
    private MemoryRepository $memoryRepository;

    public function __construct(
        EmotionDomainService $emotionDomainService,
        AiGeneratorService $aiGenerator,
        VoiceSpeaker $voiceSpeaker,
        BehaviorDecisionService $behaviorDecision,
        MessageRepository $messageRepository,
        MemoryDomainService $memoryService,
        MemoryExtractor $memoryExtractor,
        MemoryRepository $memoryRepository
    ) {
        $this->emotionDomainService = $emotionDomainService;
        $this->aiGenerator = $aiGenerator;
        $this->voiceSpeaker = $voiceSpeaker;
        $this->behaviorDecision = $behaviorDecision;
        $this->messageRepository = $messageRepository;
        $this->memoryService = $memoryService;
        $this->memoryExtractor = $memoryExtractor;
        $this->memoryRepository = $memoryRepository;
    }

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
        // ② 短期メモリ保存
        // ==========================
        $this->memoryService->storeMemory($userId, 'user', $msg);

        // ==========================
        // ③ Memory抽出
        // ==========================
        $messages = $this->messageRepository->getMessagesDesc($userId);

        $lastAiMessage = $messages
            ->firstWhere('role', 'ai')['content'] ?? null;

        // MemoryExtractorの前に名前判定
        if (preg_match('/(?:名前は|実は)\s*(.+?)\s*です/u', $msg, $matches)) {
            $name = trim($matches[1]);
            $name = preg_replace('/^(の|やっぱり|実は)\s*/u', '', $name);

            $this->memoryService->storeMemory(
                $userId,
                'memory',
                "ユーザーの名前は{$name}です",
                ['name'],
                ['value' => $name]
            );
        }

        $memory = $this->memoryExtractor->extract($msg, $lastAiMessage);

        if ($memory) {
            $this->memoryService->storeMemory(
                $userId,
                'memory',
                "ユーザーの{$memory['tag']}は{$memory['content']}です",
                [$memory['tag']],
                ['value' => $memory['content']]
            );
        }

        // ==========================
        // ④ 感情処理
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
        // ⑤ 感情取得
        // ==========================
        $state = $this->emotionDomainService->getState($userId);

        // ==========================
        // ⑥ 既読無視判定
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
        // ⑦ 長期メモリ取得
        // ==========================
        $profileMemories = $this->memoryService->getUserMemories($userId);
        \Log::info($profileMemories);

        $name = null;
        $hobby = null;
        $food = null;

        foreach ($profileMemories as $memory) {
            if (in_array('name', $memory->tags) && !empty($memory->metadata['value'])) {
                $name = $memory->metadata['value'];
            }
            if (in_array('hobby', $memory->tags) && !empty($memory->metadata['value'])) {
                $hobby = $memory->metadata['value'];
            }
            if (in_array('food', $memory->tags) && !empty($memory->metadata['value'])) {
                $food = $memory->metadata['value'];
            }
        }

        $memoryPrompt = "以下はユーザーのプロフィールです。
        この情報を必ず覚えて会話してください。

        ";

        if ($name) {
            $memoryPrompt .= "ユーザーの名前: {$name}\n";
        }

        if ($hobby) {
            $memoryPrompt .= "ユーザーの趣味: {$hobby}\n";
        }

        if ($food) {
            $memoryPrompt .= "ユーザーの好きな食べ物: {$food}\n";
        }

        // ==========================
        // ⑧ 短期会話取得
        // ==========================
        $recentMemories = $this->memoryService->getRecentMemories(10, $userId)->where('role','!=','memory');

        $contextMessages = $recentMemories->map(function ($memory) {

            $role = $memory->role;

            if ($role === 'ai') {
                $role = 'assistant';
            }

            if ($role === 'memory') {
                $role = 'assistant';
            }

            if (!in_array($role, ['system','assistant','user'])) {
                $role = 'user';
            }

            return [
                'role' => $role,
                'content' => $memory->content
            ];

        })->toArray();

        // ==========================
        // ⑨ messages構築
        // ==========================
        $messages = [];

        if ($memoryPrompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $memoryPrompt
            ];
        }

        $messages = array_merge($messages, $contextMessages);

        $messages[] = [
            'role' => 'user',
            'content' => $msg
        ];

        // ==========================
        // ⑩ AI返信生成
        // ==========================
        $aiReply = $this->aiGenerator->generate($messages, $state, []);

        // ==========================
        // ⑪ AI感情更新
        // ==========================
        $this->emotionDomainService->handleAiReply($userId);

        // ==========================
        // ⑫ 音声生成
        // ==========================
        $voiceBase64 = $this->voiceSpeaker->generateVoiceBase64($aiReply, $userId);

        // ==========================
        // ⑬ AI返信保存
        // ==========================
        $this->messageRepository->saveAiMessage(
            $userId,
            $aiReply,
            empty($voiceBase64) ? null : 'base64'
        );

        // ==========================
        // ⑭ AI返信を短期メモリ保存
        // ==========================
        $this->memoryService->storeMemory($userId, 'ai', $aiReply);

        return [
            'status' => 'AI_REPLY_OK',
            'userMessage' => $msg,
            'comment' => $aiReply,
            'voiceBase64' => $voiceBase64
        ];
    }
}