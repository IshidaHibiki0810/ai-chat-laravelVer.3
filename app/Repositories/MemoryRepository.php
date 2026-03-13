<?php

namespace App\Repositories;

use App\Models\Memories;

class MemoryRepository
{
    public function getByUserId(int $userId)
    {
        return Memories::where('user_id', $userId)->get();
    }

    public function findByTag(int $userId, string $tag)
    {
        return Memories::where('user_id', $userId)
            ->where('tags', $tag)
            ->first();
    }

    public function saveOrUpdate(int $userId, string $tag, string $content)
    {
        $memory = $this->findByTag($userId, $tag);

        if ($memory) {
            $memory->content = $content;
            $memory->save();
        } else {
            Memories::create([
                'user_id' => $userId,
                'tags' => $tag,
                'content' => $content
            ]);
        }
    }
}