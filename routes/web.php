<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

// チャット画面表示
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');

// メッセージ一覧取得
Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages');

// メッセージ送信
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');

// チャットリセット
Route::post('/chat/reset', [ChatController::class, 'reset'])->name('chat.reset');

// メッセージ既読（必要なら）
Route::post('/chat/read', [ChatController::class, 'read'])->name('chat.read');

