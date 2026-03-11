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
    public function storeMemory(string $role, string $content, array $tags = [], array $metadata = []): Memories
    {
        return Memories::create([
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
    public function getRecentMemories(int $limit = 10, array $tags = [])
    {
        $query = Memories::orderBy('created_at', 'desc');

        if (!empty($tags)) {
            $query->whereJsonContains('tags', $tags);
        }

        return $query->take($limit)->get();
    }
}