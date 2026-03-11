<?php

namespace App\Repositories;

use App\Models\Message;
use Illuminate\Support\Collection;

class MessageRepository
{
    /**
     * ユーザー発言保存
     */
    public function saveUserMessage(int $userId, string $content): Message
    {
        return Message::create([
            'user_id' => $userId,
            'role' => 'user',
            'content' => $content,
            'is_read' => 1,
            'created_at' => now()->timezone('Asia/Tokyo')
        ]);
    }

    /**
     * AI発言保存
     */
    public function saveAiMessage(
        int $userId,
        string $content,
        ?string $voiceFile = null
    ): Message {
        return Message::create([
            'user_id' => $userId,
            'role' => 'ai',
            'content' => $content,
            'voice_file' => $voiceFile,
            'is_read' => 0,
            'created_at' => now()->timezone('Asia/Tokyo')
        ]);
    }

    /**
     * 直近N件のユーザーメッセージ取得
     */
    public function getRecentUserMessages(
        int $userId,
        int $limit = 3
    ): Collection {
        return Message::where('user_id', $userId)
            ->where('role', 'user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->pluck('content');
    }

    /**
     * AI未読を既読にする
     */
    public function markAiAsRead(int $userId): int
    {
        return Message::where('user_id', $userId)
            ->where('role', 'ai')
            ->where('is_read', 0)
            ->update([
                'is_read' => 1,
                'read_at' => now()
            ]);
    }

    /**
     * ユーザー未読を既読にする
     */
    public function markUserAsRead(int $userId): int
    {
        return Message::where('user_id', $userId)
            ->where('role', 'user')
            ->where('is_read', 0)
            ->update([
                'is_read' => 1,
                'read_at' => now()
            ]);
    }

    /**
     * メッセージ一覧取得（降順）
     */
    public function getMessagesDesc(int $userId): Collection
    {
        return Message::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get(['role', 'content', 'created_at', 'is_read']);
    }

    /**
     * 全削除
     */
    public function deleteAllByUser(int $userId): int
    {
        return Message::where('user_id', $userId)->delete();
    }
}