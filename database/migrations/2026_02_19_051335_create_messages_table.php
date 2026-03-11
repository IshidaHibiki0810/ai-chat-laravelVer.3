<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id(); // int(10) UNSIGNED
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['user', 'ai']);
            $table->text('content');
            $table->string('voice_file')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('is_read')->default(0);
            $table->timestamp('read_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};