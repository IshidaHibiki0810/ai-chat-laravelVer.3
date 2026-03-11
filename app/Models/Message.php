<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';

    public $timestamps = true;

    const UPDATED_AT = null; // updated_atは使用しない

    protected $fillable = [
        'user_id',
        'role',
        'content',
        'voice_file',
        'is_read',
        'read_at'
    ];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->timezone('Asia/Tokyo')->format('Y-m-d H:i:s');
    }
}