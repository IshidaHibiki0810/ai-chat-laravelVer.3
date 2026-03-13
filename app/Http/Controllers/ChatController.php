<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Application\Chat\ChatApplicationService;
use App\Domain\Emotion\EmotionDomainService;
use App\Repositories\MessageRepository;
use App\Domain\Memory\MemoryDomainService;

class ChatController extends Controller
{
    private ChatApplicationService $chatApplicationService;
    private EmotionDomainService $emotionDomainService;
    private MessageRepository $messageRepository;
    private MemoryDomainService $memoryService;

    public function __construct(
        ChatApplicationService $chatApplicationService,
        EmotionDomainService $emotionDomainService,
        MessageRepository $messageRepository,
        MemoryDomainService $memoryService
    ) {
        $this->chatApplicationService = $chatApplicationService;
        $this->emotionDomainService = $emotionDomainService;
        $this->messageRepository = $messageRepository;
        $this->memoryService = $memoryService;
    }

    /**
     * チャット画面表示
     */
    public function index()
    {
        return view('chat');
    }

    /**
     * ユーザー送信
     */
        public function send(Request $request)
        {
            $userId = 1;
            $userMessage = trim($request->input('comment'));

            $result = $this->chatApplicationService->handle($userId, $userMessage);

            if (is_array($result)) {

                if (isset($result['reply'])) {
                    $replyText = $result['reply'];
                } else {
                    return response()->json($result);
                }

            } else {
                $replyText = $result;
            }

            return response()->json([
                'reply' => $replyText
            ]);
        }

        public function messages()
        {
            $userId = session('user_id', 1);

            $this->messageRepository->markAiAsRead($userId);

            $messages = $this->messageRepository->getMessagesDesc($userId);

            // 配列にして JSON で返す
            return response()->json($messages->toArray());
        }

    /**
     * ユーザー既読処理
     */
    public function read()
    {
        $userId = session('user_id', 1);

        $this->messageRepository->markUserAsRead($userId);

        return response()->json(['status' => 'ok']);
    }

    /**
     * リセット
     */
    public function reset()
    {

        $userId = session('user_id', 1);

        $this->messageRepository->deleteAllByUser($userId);

        $aiState = $this->emotionDomainService->resetState($userId);

        $this->memoryService->clearMemoriesByUser($userId);

        return response()->json([
            'status' => 'ok',
            'ai_state' => $aiState
        ]);
    }
}