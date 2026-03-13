<?php

namespace App\Domain\Memory;

use App\Models\Memories;

class MemoryDomainService
{
    /**
     * 会話を記憶する
     *
     * @param string $role 'user' or 'ai'
     * @param string $content 発話内容
     * @param array $tags 任意タグ
     * @param array $metadata 任意メタ情報
     * @return Memories
     */
    public function storeMemory(
        int $userId,
        string $role,
        string $content,
        array $tags = [],
        array $metadata = []
    ): Memories {

        // nameなどのタグがある場合
        if (!empty($tags)) {

            $tag = $tags[0]; // 今は1タグ前提

            $existing = Memories::where('user_id', $userId)
                ->whereJsonContains('tags', $tag)
                ->first();

            if ($existing) {

                $existing->content = $content;
                $existing->metadata = $metadata;
                $existing->save();

                return $existing;
            }
        }

        return Memories::create([
            'user_id' => $userId,
            'role' => $role,
            'content' => $content,
            'tags' => $tags,
            'metadata' => $metadata,
        ]);
    }
    /**
     * 過去の会話を取得する
     *
     * @param int $limit 取得件数
     * @param array $tags 特定タグで絞る場合
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentMemories(int $limit = 10, ?int $userId = null, array $tags = [])
    {
        $query = Memories::orderBy('created_at', 'desc');

        if ($userId !== null) { // 0 でも検索される
            $query->where('user_id', $userId);
        }

        if (!empty($tags)) {
            $query->whereJsonContains('tags', $tags);
        }

        return $query->take($limit)->get();
    }

    public function getUserMemories(int $userId)
    {
        return Memories::where('user_id', $userId)
            ->whereNotNull('tags')
            ->get();
    }

    /**
     * 指定ユーザーの過去会話を削除
     */
    public function clearMemoriesByUser(int $userId): void
    {
        Memories::where('user_id', $userId)->delete();
    }
}