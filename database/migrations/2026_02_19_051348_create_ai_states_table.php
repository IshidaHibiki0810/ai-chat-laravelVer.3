<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_state', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('emotion_happy')->default(0);
            $table->integer('emotion_angry')->default(0);
            $table->integer('emotion_lonely')->default(0);
            $table->integer('emotion_excited')->default(0);
            $table->integer('emotion_sad')->default(0);
            $table->integer('emotion_anxious')->default(0);
            $table->integer('emotion_disgust')->default(0);
            $table->integer('emotion_surprised')->default(0);
            $table->integer('emotion_paused')->default(0);
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamp('last_ai_talk_at')->nullable();
            $table->timestamp('last_user_reply_at')->nullable();
            $table->boolean('is_blocked')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->integer('ignore_streak')->default(0);
            $table->timestamp('last_user_message_at')->nullable();
            $table->timestamps(); // created_at, updated_at 自動

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_state');
    }
};