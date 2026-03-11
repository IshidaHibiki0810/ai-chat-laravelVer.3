<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiState extends Model
{
    protected $table = 'ai_state';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'emotion_happy',
        'emotion_angry',
        'emotion_lonely',
        'emotion_excited',
        'emotion_sad',
        'emotion_anxious',
        'emotion_disgust',
        'emotion_surprised',
        'emotion_paused',
        'last_interaction_at',
        'last_ai_talk_at',
        'last_user_reply_at'
    ];
}