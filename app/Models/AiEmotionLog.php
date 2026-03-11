<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiEmotionLog extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'ai_emotion_log'; // 既存テーブル名

    protected $fillable = [
        'user_id',
        'emotion_type',
        'delta',
        'reason',
        'created_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}