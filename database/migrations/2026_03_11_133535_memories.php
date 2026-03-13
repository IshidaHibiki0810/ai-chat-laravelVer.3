<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // memoriesテーブルを作成
        Schema::create('memories', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('user_id')->nullable(false); // NOT NULL, idの次に配置
            $table->string('role');
            $table->text('content');
            $table->text('tags')->nullable();
            $table->text('metadata')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memories');
    }
};