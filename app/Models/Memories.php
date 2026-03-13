<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memories extends Model
{
    use HasFactory;

    // テーブル名を明示的に指定（慣習的には不要ですが安全策として）
    protected $table = 'memories';

    // 一括代入可能なカラム
    protected $fillable = [
        'user_id', 
        'role',     // user / ai
        'content',  // 発話内容
        'tags',     // JSONカラム
        'metadata', // JSONカラム
    ];

    // JSON カラムを自動で配列として扱う
    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
    ];
}
