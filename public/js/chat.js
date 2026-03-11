document.addEventListener('DOMContentLoaded', () => {

    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatContainer = document.getElementById('chat-container');

    // メッセージ表示関数
    function addMessage(role, text, voiceBase64=null) {
        const div = document.createElement('div');
        div.className = role === 'ai' ? 'chat-ai' : 'chat-user';
        div.textContent = text;
        chatContainer.appendChild(div);

        // 音声再生
        if (voiceBase64) {
            const audio = new Audio('data:audio/wav;base64,' + voiceBase64);
            audio.play();
        }

        // 自動スクロール
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // メッセージ取得
    async function fetchMessages() {
        try {
            const res = await fetch('/chat/messages');
            const messages = await res.json();
            chatContainer.innerHTML = ''; // 再描画
            messages.reverse().forEach(msg => {
                addMessage(msg.role, msg.content);
            });
        } catch (e) {
            console.error(e);
        }
    }

    // ユーザー既読更新
    async function markUserRead() {
        try {
            await fetch('/chat/user-read', { method:'POST' });
        } catch(e) { console.error(e); }
    }

    // フォーム送信
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const comment = chatInput.value.trim();
        if (!comment) return;

        // ユーザー投稿表示
        addMessage('user', comment);
        chatInput.value = '';

        try {
            const formData = new FormData();
            formData.append('comment', comment);

            const res = await fetch('/chat/send', { method:'POST', body:formData });
            const data = await res.json();

            if (data.status === 'AI_REPLY_OK') {
                addMessage('ai', data.comment, data.voiceBase64);
            } else if (data.status === 'DOT_ONLY') {
                addMessage('ai', data.reply);
            }

        } catch(e) {
            console.error(e);
        }
    });

    // 最初のメッセージ読み込み
    fetchMessages();
    markUserRead();

    // 2秒ごとに更新（リアルタイム風）
    setInterval(fetchMessages, 2000);

});