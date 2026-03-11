@extends('layouts.app')

@section('content')
<h1>AIチャット</h1>

<form id="chat-form">
    <textarea name="comment" id="comment" rows="4"></textarea>
    <button type="submit">投稿</button>
    <button type="button" id="reset-btn">リセット</button>
</form>

<div id="ai-typing" style="display:none;">AI入力中...</div>
<div id="posts-container"></div>

<audio id="aiVoice" controls></audio>

<script src="{{ asset('js/chat.js') }}"></script>
@endsection