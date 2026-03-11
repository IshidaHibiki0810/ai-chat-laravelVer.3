<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $table = 'users'; // 既存テーブル名に合わせる

    protected $fillable = ['name'];

    // リレーション
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function aiState()
    {
        return $this->hasOne(AiState::class);
    }

    public function aiEmotionLogs()
    {
        return $this->hasMany(AiEmotionLog::class);
    }
}