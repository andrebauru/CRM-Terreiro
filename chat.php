<?php
$pageTitle = 'CRM Terreiro - Chat Interno';
$activePage = 'chat';
require_once __DIR__ . '/app/views/partials/tw-head.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserName = (string)($_SESSION['user_name'] ?? ($_SESSION['user_email'] ?? ('Usuário #' . $currentUserId)));

$chatUsers = [];
try {
    $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id <> ? AND is_active = 1 ORDER BY name');
    $stmt->execute([$currentUserId]);
    $chatUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    try {
        $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id <> ? ORDER BY name');
        $stmt->execute([$currentUserId]);
        $chatUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e2) {
        $chatUsers = [];
    }
}
?>
<body class="bg-[#0f0b16] font-sans text-slate-100">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
          <h1 class="text-2xl md:text-3xl font-black text-pink-300">Chat Interno</h1>
          <p class="text-pink-100/70 text-sm">Conversas em tempo real com anexos, áudio e progresso de upload.</p>
        </div>
        <div class="text-xs bg-white/10 border border-pink-400/30 rounded-xl px-3 py-2">
          Conectado como <span class="font-bold text-pink-200"><?= htmlspecialchars($currentUserName) ?></span>
        </div>
      </header>

      <section class="rounded-3xl border border-fuchsia-500/30 bg-gradient-to-br from-[#1b1228] to-[#10091b] shadow-2xl overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] min-h-[70vh]">
          <aside class="border-r border-fuchsia-400/20 bg-[#140d22]">
            <div class="p-4 border-b border-fuchsia-400/20">
              <label class="block text-xs uppercase tracking-wide text-pink-200/70 mb-2">Pesquisar</label>
              <input id="chatUserSearch" type="text" placeholder="Nome ou e-mail" class="w-full rounded-xl bg-[#241635] border border-fuchsia-500/30 px-3 py-2 text-sm text-pink-100 placeholder:text-pink-200/40 focus:outline-none focus:ring-2 focus:ring-pink-500/60" />
            </div>
            <div id="chatUsersList" class="overflow-y-auto max-h-[62vh] p-2"></div>
          </aside>

          <div class="flex flex-col min-h-[70vh]">
            <div id="chatHeader" class="px-4 py-3 border-b border-fuchsia-400/20 bg-[#160d25] flex items-center justify-between">
              <div>
                <div id="chatWithName" class="font-bold text-pink-200">Selecione alguém para conversar</div>
                <div id="chatWithSub" class="text-xs text-pink-100/60">Nenhuma conversa selecionada</div>
              </div>
              <div id="chatStatus" class="text-xs text-fuchsia-200/70">Aguardando seleção</div>
            </div>

            <div id="chatMessages" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-[#0f0819]"></div>

            <div class="border-t border-fuchsia-400/20 bg-[#160d25] p-3">
              <div id="mediaPreview" class="hidden mb-3 rounded-xl border border-fuchsia-400/30 bg-black/30 p-3"></div>

              <div id="uploadProgressWrap" class="hidden mb-3">
                <div class="flex items-center justify-between text-xs text-pink-100/70 mb-1">
                  <span>Enviando arquivo...</span>
                  <span id="uploadProgressText">0%</span>
                </div>
                <div class="h-2 rounded bg-white/10 overflow-hidden">
                  <div id="uploadProgressBar" class="h-2 bg-gradient-to-r from-pink-500 to-fuchsia-500 w-0"></div>
                </div>
              </div>

              <div class="flex flex-col gap-2 md:flex-row md:items-end">
                <div class="flex-1">
                  <label class="text-xs text-pink-100/70 mb-1 block">Mensagem</label>
                  <textarea id="messageInput" rows="2" placeholder="Digite sua mensagem..." class="w-full resize-none rounded-xl bg-[#241635] border border-fuchsia-500/30 px-3 py-2 text-sm text-pink-100 placeholder:text-pink-200/40 focus:outline-none focus:ring-2 focus:ring-pink-500/60"></textarea>
                </div>
                <div class="flex gap-2">
                  <button id="emojiBtn" class="h-10 w-10 rounded-xl bg-[#241635] border border-fuchsia-500/30 hover:bg-[#301d47]" title="Emoji"><i class="fa-regular fa-face-smile"></i></button>
                  <label class="h-10 w-10 rounded-xl bg-[#241635] border border-fuchsia-500/30 hover:bg-[#301d47] grid place-items-center cursor-pointer" title="Anexar imagem ou vídeo">
                    <i class="fa-solid fa-paperclip"></i>
                    <input id="fileInput" type="file" class="hidden" accept="image/*,video/*,audio/*" />
                  </label>
                  <button id="recordBtn" class="h-10 px-3 rounded-xl bg-[#241635] border border-fuchsia-500/30 hover:bg-[#301d47] text-xs font-semibold" title="Gravar áudio">🎤 Áudio</button>
                  <button id="sendBtn" class="h-10 px-4 rounded-xl bg-pink-600 hover:bg-pink-500 font-bold">Enviar</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    initSensitivePageProtection('chat');

    const CURRENT_USER = {
      id: Number(<?= (int)$currentUserId ?>),
      name: <?= json_encode($currentUserName, JSON_UNESCAPED_UNICODE) ?>
    };

    const USERS = <?= json_encode(array_map(static function ($u) {
      return [
        'id' => (int)($u['id'] ?? 0),
        'name' => (string)($u['name'] ?? ''),
        'email' => (string)($u['email'] ?? ''),
      ];
    }, $chatUsers), JSON_UNESCAPED_UNICODE) ?>;

    const usersListEl = document.getElementById('chatUsersList');
    const searchEl = document.getElementById('chatUserSearch');
    const chatWithNameEl = document.getElementById('chatWithName');
    const chatWithSubEl = document.getElementById('chatWithSub');
    const chatStatusEl = document.getElementById('chatStatus');
    const messagesEl = document.getElementById('chatMessages');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const emojiBtn = document.getElementById('emojiBtn');
    const fileInput = document.getElementById('fileInput');
    const recordBtn = document.getElementById('recordBtn');
    const mediaPreviewEl = document.getElementById('mediaPreview');
    const uploadWrap = document.getElementById('uploadProgressWrap');
    const uploadBar = document.getElementById('uploadProgressBar');
    const uploadText = document.getElementById('uploadProgressText');

    let currentChatUser = null;
    let unsubscribeMessages = null;
    let pendingFile = null;
    let pendingAudioBlob = null;
    let mediaRecorder = null;
    let recordingChunks = [];

    function esc(v) {
      return String(v || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function conversationId(a, b) {
      const p = [Number(a), Number(b)].sort((x, y) => x - y);
      return `${p[0]}_${p[1]}`;
    }

    function formatTime(ts) {
      try {
        const date = ts instanceof Date ? ts : new Date(ts);
        return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
      } catch (_) {
        return '--:--';
      }
    }

    function clearMediaPreview() {
      pendingFile = null;
      pendingAudioBlob = null;
      mediaPreviewEl.classList.add('hidden');
      mediaPreviewEl.innerHTML = '';
      fileInput.value = '';
    }

    function setUploadProgress(percent) {
      uploadWrap.classList.remove('hidden');
      const p = Math.max(0, Math.min(100, percent));
      uploadBar.style.width = `${p}%`;
      uploadText.textContent = `${Math.round(p)}%`;
      if (p >= 100) {
        setTimeout(() => {
          uploadWrap.classList.add('hidden');
          uploadBar.style.width = '0%';
          uploadText.textContent = '0%';
        }, 500);
      }
    }

    function renderUsersList(filter = '') {
      const term = String(filter || '').toLowerCase();
      const rows = USERS.filter((u) => {
        const text = `${u.name} ${u.email}`.toLowerCase();
        return text.includes(term);
      });

      if (!rows.length) {
        usersListEl.innerHTML = '<div class="p-3 text-sm text-pink-100/60">Nenhum usuário encontrado.</div>';
        return;
      }

      usersListEl.innerHTML = rows.map((u) => {
        const active = currentChatUser && currentChatUser.id === u.id;
        return `
          <button data-user-id="${u.id}" class="chat-user-btn w-full text-left rounded-xl px-3 py-3 mb-2 border transition ${active ? 'bg-pink-600/20 border-pink-400/70' : 'bg-[#1f1330] border-fuchsia-500/20 hover:bg-[#2a1a40]'}">
            <div class="font-semibold text-pink-100">${esc(u.name || ('Usuário #' + u.id))}</div>
            <div class="text-xs text-pink-100/60 truncate">${esc(u.email || '')}</div>
          </button>
        `;
      }).join('');

      document.querySelectorAll('.chat-user-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = Number(btn.getAttribute('data-user-id') || 0);
          const user = USERS.find((u) => u.id === id);
          if (user) {
            openConversation(user);
          }
        });
      });
    }

    function renderMessages(docs) {
      if (!docs.length) {
        messagesEl.innerHTML = '<div class="text-center text-sm text-pink-100/50 mt-8">Sem mensagens ainda. Envie a primeira mensagem ✨</div>';
        return;
      }

      messagesEl.innerHTML = docs.map((msg) => {
        const mine = Number(msg.senderId) === CURRENT_USER.id;
        const bubbleClass = mine
          ? 'ml-auto bg-gradient-to-r from-pink-600 to-fuchsia-600 text-white'
          : 'mr-auto bg-[#2a1b3f] border border-fuchsia-500/25 text-pink-50';

        let body = '';
        if (msg.type === 'image' && msg.mediaUrl) {
          body += `<a href="${esc(msg.mediaUrl)}" target="_blank" rel="noopener"><img src="${esc(msg.mediaUrl)}" class="rounded-lg max-h-64 object-cover" /></a>`;
        } else if (msg.type === 'video' && msg.mediaUrl) {
          body += `<video controls class="rounded-lg max-h-72 w-full"><source src="${esc(msg.mediaUrl)}" /></video>`;
        } else if (msg.type === 'audio' && msg.mediaUrl) {
          body += `<audio controls class="w-full"><source src="${esc(msg.mediaUrl)}" /></audio>`;
        } else if (msg.mediaUrl) {
          body += `<a href="${esc(msg.mediaUrl)}" target="_blank" rel="noopener" class="underline text-pink-100">📎 ${esc(msg.mediaName || 'Arquivo')}</a>`;
        }

        if (msg.text) {
          body += `<div class="whitespace-pre-wrap break-words text-sm mt-1">${esc(msg.text)}</div>`;
        }

        const date = msg.createdAt && typeof msg.createdAt.toDate === 'function'
          ? msg.createdAt.toDate()
          : (msg.createdAtMs ? new Date(msg.createdAtMs) : new Date());

        return `
          <div class="max-w-[85%] md:max-w-[70%] px-3 py-2 rounded-2xl ${bubbleClass}">
            ${body}
            <div class="text-[11px] mt-1 opacity-80 text-right">${formatTime(date)}</div>
          </div>
        `;
      }).join('');

      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function ensureFirebaseReady() {
      return new Promise((resolve, reject) => {
        if (window.db && window.storage && window.firebaseFns) {
          resolve();
          return;
        }

        const timeout = setTimeout(() => reject(new Error('Firebase não inicializou.')), 10000);
        window.addEventListener('firebase-ready', () => {
          clearTimeout(timeout);
          resolve();
        }, { once: true });
      });
    }

    async function openConversation(user) {
      currentChatUser = user;
      renderUsersList(searchEl.value);

      chatWithNameEl.textContent = user.name || `Usuário #${user.id}`;
      chatWithSubEl.textContent = user.email || 'Sem e-mail';
      chatStatusEl.textContent = 'Conectando...';

      if (unsubscribeMessages) {
        unsubscribeMessages();
        unsubscribeMessages = null;
      }

      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        const convo = conversationId(CURRENT_USER.id, user.id);
        const messagesRef = f.collection(window.db, 'conversations', convo, 'messages');
        const q = f.query(messagesRef, f.orderBy('createdAt', 'asc'), f.limit(500));

        unsubscribeMessages = f.onSnapshot(q, (snapshot) => {
          const rows = snapshot.docs.map((d) => ({ id: d.id, ...d.data() }));
          renderMessages(rows);
          chatStatusEl.textContent = 'Em tempo real';
        }, (err) => {
          console.error(err);
          chatStatusEl.textContent = 'Erro de conexão';
        });
      } catch (e) {
        console.error(e);
        chatStatusEl.textContent = 'Firebase indisponível';
      }
    }

    async function sendMessage(payload = {}) {
      if (!currentChatUser) {
        alert('Selecione um usuário para conversar.');
        return;
      }

      await ensureFirebaseReady();
      const f = window.firebaseFns;
      const convo = conversationId(CURRENT_USER.id, currentChatUser.id);
      const messagesRef = f.collection(window.db, 'conversations', convo, 'messages');

      const base = {
        senderId: CURRENT_USER.id,
        senderName: CURRENT_USER.name,
        receiverId: currentChatUser.id,
        conversationId: convo,
        createdAt: f.serverTimestamp(),
        createdAtMs: Date.now(),
      };

      await f.addDoc(messagesRef, { ...base, ...payload });
    }

    async function uploadAndSendFile(fileOrBlob, typeHint = 'file') {
      if (!fileOrBlob || !currentChatUser) return;

      await ensureFirebaseReady();
      const f = window.firebaseFns;
      const ext = (fileOrBlob.name && fileOrBlob.name.split('.').pop()) || (typeHint === 'audio' ? 'webm' : 'bin');
      const convo = conversationId(CURRENT_USER.id, currentChatUser.id);
      const path = `chat_uploads/${convo}/${Date.now()}_${Math.random().toString(36).slice(2)}.${ext}`;
      const storageRef = f.ref(window.storage, path);

      const uploadTask = f.uploadBytesResumable(storageRef, fileOrBlob, {
        contentType: fileOrBlob.type || 'application/octet-stream'
      });

      await new Promise((resolve, reject) => {
        uploadTask.on('state_changed', (snap) => {
          const pct = (snap.bytesTransferred / Math.max(1, snap.totalBytes)) * 100;
          setUploadProgress(pct);
        }, reject, resolve);
      });

      const url = await f.getDownloadURL(uploadTask.snapshot.ref);
      const mime = String(fileOrBlob.type || '');
      const messageType = typeHint === 'audio'
        ? 'audio'
        : (mime.startsWith('image/') ? 'image' : (mime.startsWith('video/') ? 'video' : 'file'));

      await sendMessage({
        type: messageType,
        text: messageInput.value.trim() || '',
        mediaUrl: url,
        mediaMime: mime,
        mediaName: fileOrBlob.name || `audio_${Date.now()}.webm`,
      });

      messageInput.value = '';
      clearMediaPreview();
      setUploadProgress(100);
    }

    function buildPreviewForFile(file) {
      clearMediaPreview();
      pendingFile = file;
      const mime = String(file.type || '');
      const url = URL.createObjectURL(file);

      let mediaHtml = `<div class="text-sm text-pink-100 mb-2">${esc(file.name || 'Arquivo')}</div>`;
      if (mime.startsWith('image/')) {
        mediaHtml += `<img src="${esc(url)}" class="rounded-lg max-h-56" />`;
      } else if (mime.startsWith('video/')) {
        mediaHtml += `<video controls class="rounded-lg max-h-56"><source src="${esc(url)}" /></video>`;
      } else if (mime.startsWith('audio/')) {
        mediaHtml += `<audio controls class="w-full"><source src="${esc(url)}" /></audio>`;
      } else {
        mediaHtml += '<div class="text-xs text-pink-100/70">Arquivo pronto para envio.</div>';
      }

      mediaHtml += `
        <div class="flex gap-2 mt-3">
          <button id="sendPreviewBtn" class="px-3 py-1.5 rounded-lg bg-pink-600 hover:bg-pink-500 text-sm font-semibold">Enviar anexo</button>
          <button id="cancelPreviewBtn" class="px-3 py-1.5 rounded-lg border border-fuchsia-500/40 text-sm">Cancelar</button>
        </div>
      `;

      mediaPreviewEl.innerHTML = mediaHtml;
      mediaPreviewEl.classList.remove('hidden');

      document.getElementById('sendPreviewBtn').addEventListener('click', async () => {
        try {
          await uploadAndSendFile(pendingFile);
        } catch (e) {
          console.error(e);
          alert('Falha ao enviar anexo.');
        }
      });
      document.getElementById('cancelPreviewBtn').addEventListener('click', clearMediaPreview);
    }

    async function toggleRecording() {
      if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        return;
      }

      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        recordingChunks = [];
        mediaRecorder = new MediaRecorder(stream);

        mediaRecorder.ondataavailable = (e) => {
          if (e.data && e.data.size > 0) recordingChunks.push(e.data);
        };

        mediaRecorder.onstop = () => {
          stream.getTracks().forEach((t) => t.stop());
          pendingAudioBlob = new Blob(recordingChunks, { type: 'audio/webm' });
          const fakeFile = new File([pendingAudioBlob], `audio_${Date.now()}.webm`, { type: 'audio/webm' });
          buildPreviewForFile(fakeFile);
          recordBtn.textContent = '🎤 Áudio';
          recordBtn.classList.remove('bg-red-700', 'text-white');
        };

        mediaRecorder.start();
        recordBtn.textContent = '⏹ Parar';
        recordBtn.classList.add('bg-red-700', 'text-white');
      } catch (e) {
        console.error(e);
        alert('Não foi possível acessar o microfone.');
      }
    }

    sendBtn.addEventListener('click', async () => {
      const text = messageInput.value.trim();
      if (pendingFile) {
        try {
          await uploadAndSendFile(pendingFile);
        } catch (e) {
          console.error(e);
          alert('Falha ao enviar anexo.');
        }
        return;
      }

      if (!text) return;
      try {
        await sendMessage({ type: 'text', text });
        messageInput.value = '';
      } catch (e) {
        console.error(e);
        alert('Falha ao enviar mensagem.');
      }
    });

    messageInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendBtn.click();
      }
    });

    emojiBtn.addEventListener('click', () => {
      messageInput.value += ' 😊';
      messageInput.focus();
    });

    fileInput.addEventListener('change', () => {
      const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
      if (!file) return;
      buildPreviewForFile(file);
    });

    recordBtn.addEventListener('click', toggleRecording);

    searchEl.addEventListener('input', () => {
      renderUsersList(searchEl.value);
    });

    renderUsersList('');
    if (USERS.length) {
      openConversation(USERS[0]);
    } else {
      usersListEl.innerHTML = '<div class="p-3 text-sm text-pink-100/70">Nenhum outro usuário disponível no momento.</div>';
      messagesEl.innerHTML = '<div class="text-center text-sm text-pink-100/50 mt-8">Convide usuários ativos para começar a conversar.</div>';
    }
  </script>
</body>
</html>
