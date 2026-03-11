<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AIチャット（音声対応）</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<h1>AIチャット</h1>

<div id="inputArea">
    <input type="text" id="message" placeholder="メッセージを入力">
    <button id="sendBtn">送信</button>
    <button id="resetBtn">リセット</button>
</div>

<hr>

<div id="posts-container"></div>

<audio id="aiVoice" controls style="display:block; margin-top:10px;"></audio>

<script>
const routes = {
    messages: "{{ route('chat.messages') }}",
    send: "{{ route('chat.send') }}",
    reset: "{{ route('chat.reset') }}"
};

const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

const postsContainer = document.getElementById('posts-container');
const messageInput = document.getElementById('message');
const sendBtn = document.getElementById('sendBtn');
const resetBtn = document.getElementById('resetBtn');

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function renderMessages(messages) {
    postsContainer.innerHTML = '';

    messages.forEach(msg => {
        const wrapper = document.createElement('div');
        wrapper.className = 'message-wrapper ' + msg.role;

        const div = document.createElement('div');
        div.className = 'post ' + msg.role;
        div.innerHTML = `
            <strong>${msg.role === "ai" ? "AI友達" : "あなた"}</strong>
            <small>(${msg.created_at})</small>
            <p>${escapeHtml(msg.content || '').replace(/\n/g,'<br>')}</p>
        `;

        wrapper.appendChild(div);

        const status = document.createElement("div");
        status.className = "read-status";
        status.innerText = Number(msg.is_read) === 1 ? "既読" : "未読";
        wrapper.appendChild(status);

        postsContainer.appendChild(wrapper);
    });

    postsContainer.scrollTop = 0;
}

async function fetchMessages() {
    try {
        const res = await fetch(routes.messages);
        const messages = await res.json();
        renderMessages(messages);
    } catch (e) {
        console.error("メッセージ取得失敗:", e);
    }
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function sendMessage() {
    const msg = messageInput.value.trim();
    if (!msg) return;
    messageInput.value = '';

    postsContainer.prepend(createUserMessageElement(msg));

    const aiWrapper = document.createElement('div');
    aiWrapper.className = 'message-wrapper ai';
    aiWrapper.id = 'ai-temp';
    aiWrapper.innerHTML = `<div class="post ai typing"><p>AI入力中</p></div>`;
    postsContainer.prepend(aiWrapper);

    const typingText = aiWrapper.querySelector('p');
    let dotCount = 0;
    const interval = setInterval(() => {
        dotCount = (dotCount + 1) % 4;
        typingText.textContent = 'AI入力中' + '.'.repeat(dotCount);
    }, 400);

    const res = await fetch(routes.send, {
        method: 'POST',
        headers: {
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':csrfToken
        },
        body: JSON.stringify({comment: msg})
    });

    const result = await res.json();

    /* ★★★★★ ここが修正ポイント ★★★★★ */
    if (result.voiceBase64) {
        const aiAudio = document.getElementById('aiVoice');
        aiAudio.src = "data:audio/wav;base64," + result.voiceBase64;
        aiAudio.load();
        aiAudio.play().catch(e => console.error("再生エラー:", e));
    }


    clearInterval(interval);
    aiWrapper.remove();

    await fetchMessages();
}

async function resetChat() {
    if (!confirm("チャットをリセットしますか？")) return;
    await fetch(routes.reset, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken}
    });
    await fetchMessages();
}

function createUserMessageElement(msg, createdAt = null) {
    const wrapper = document.createElement('div');
    wrapper.className = 'message-wrapper user';

    const div = document.createElement('div');
    div.className = 'post user';

    const timeStr = createdAt ? createdAt : new Date().toLocaleTimeString();

    div.innerHTML = `
        <strong>あなた</strong>
        <small>(${timeStr})</small>
        <p>${escapeHtml(msg).replace(/\n/g,'<br>')}</p>
    `;

    wrapper.appendChild(div);

    const status = document.createElement("div");
    status.className = "read-status";
    status.innerText = "既読";
    wrapper.appendChild(status);

    div.style.display = 'inline-block';
    div.style.minWidth = '130px';
    div.style.maxWidth = '60%';
    div.style.padding = '10px 15px';
    div.style.borderRadius = '12px';
    div.style.lineHeight = '1.5';
    div.style.wordBreak = 'break-word';
    div.style.backgroundColor = '#419df3';
    div.style.color = '#fff';
    div.style.marginBottom = '6px';

    return wrapper;
}

sendBtn.addEventListener('click', sendMessage);
messageInput.addEventListener('keyup', e => { if(e.key==='Enter') sendMessage(); });
resetBtn.addEventListener('click', resetChat);

fetchMessages();
</script>
</body>
</html>