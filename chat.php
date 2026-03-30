<?php
$pageTitle = 'CRM Terreiro - Chat Interno';
$activePage = 'chat';
require_once __DIR__ . '/app/views/partials/tw-head.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserName = (string)($_SESSION['user_name'] ?? ($_SESSION['user_email'] ?? ('Usuário #' . $currentUserId)));

$chatUsers = [];
try {
  $stmt = db()->prepare('SELECT id, name, email, foto_perfil FROM users WHERE id <> ? AND is_active = 1 ORDER BY name');
    $stmt->execute([$currentUserId]);
    $chatUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    try {
    $stmt = db()->prepare('SELECT id, name, email, foto_perfil FROM users WHERE id <> ? ORDER BY name');
        $stmt->execute([$currentUserId]);
        $chatUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e2) {
        $chatUsers = [];
    }
}
?>
<body class="bg-[#0f0b16] font-sans text-slate-100 overflow-hidden">
  <div class="h-screen flex overflow-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
      <!-- Header do Chat -->
      <header class="shrink-0 px-4 py-3 bg-[#160d25] border-b border-fuchsia-400/20 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <h1 class="text-lg font-black text-pink-300">Chat Interno</h1>
          <span class="text-xs text-pink-100/50">•</span>
          <span class="text-xs text-pink-200"><?= htmlspecialchars($currentUserName) ?></span>
        </div>
      </header>

      <!-- Container Principal do Chat -->
      <div class="flex-1 flex flex-col overflow-hidden min-h-0">
        <!-- Header do Chat Geral -->
        <div id="chatHeader" class="shrink-0 px-4 py-3 border-b border-fuchsia-400/20 bg-[#160d25] flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-pink-500 to-fuchsia-600 flex items-center justify-center text-xs font-bold text-white shrink-0">
              <i class="fa-solid fa-comments text-sm"></i>
            </div>
            <div class="min-w-0">
              <div class="font-semibold text-pink-200">Chat Geral</div>
              <div id="typingIndicator" class="text-[11px] text-pink-300/80 hidden">Alguém está digitando...</div>
            </div>
          </div>
          <div id="chatStatus" class="text-xs text-fuchsia-200/70 shrink-0">🟢 Ativo</div>
        </div>

          <!-- Área de Mensagens -->
          <div id="chatMessages" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 flex flex-col-reverse"></div>

          <!-- Rodapé: Input de Mensagem (Fixo) -->
          <div class="shrink-0 border-t border-fuchsia-400/20 bg-[#160d25] p-3 space-y-2">

            <div id="uploadProgressWrap" class="hidden">
              <div class="flex items-center justify-between text-xs text-pink-100/70 mb-1">
                <span>Upload...</span>
                <span id="uploadProgressText">0%</span>
              </div>
              <div class="h-1 rounded bg-white/10 overflow-hidden">
                <div id="uploadProgressBar" class="h-1 bg-gradient-to-r from-pink-500 to-fuchsia-500 w-0 transition-all"></div>
              </div>
            </div>

            <div class="flex gap-2">
              <div class="flex-1">
                <textarea id="messageInput" rows="2" placeholder="Mensagem..." class="w-full resize-none rounded-lg bg-[#241635] border border-fuchsia-500/30 px-3 py-2 text-sm text-pink-100 placeholder:text-pink-200/40 focus:outline-none focus:ring-2 focus:ring-pink-500/60"></textarea>
              </div>
              <div class="flex flex-col gap-2">
                <button id="emojiBtn" class="h-10 w-10 rounded-lg bg-[#241635] border border-fuchsia-500/30 hover:bg-[#301d47] flex items-center justify-center" title="Emoji"><i class="fa-regular fa-face-smile text-sm"></i></button>
                <label class="h-10 w-10 rounded-lg bg-[#241635] border border-fuchsia-500/30 hover:bg-[#301d47] cursor-pointer flex items-center justify-center" title="Anexo">
                  <i class="fa-solid fa-paperclip text-sm"></i>
                  <input id="fileInput" type="file" class="hidden" accept="image/*,video/*,audio/*" />
                </label>
                <button id="recordBtn" class="h-10 px-2 rounded-lg bg-[#241635] border border-fuchsia-500/30 hover:bg-[#301d47] text-[10px] font-semibold" title="Áudio">🎤</button>
                <button id="sendBtn" class="h-10 w-10 rounded-lg bg-pink-600 hover:bg-pink-500 flex items-center justify-center" title="Enviar"><i class="fa-solid fa-paper-plane text-sm"></i></button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    initSensitivePageProtection('chat');

    const CURRENT_USER = {
      id: Number(<?= (int)$currentUserId ?>),
      name: <?= json_encode($currentUserName, JSON_UNESCAPED_UNICODE) ?>
    };

    const usersListEl = document.getElementById('chatUsersList');
    const searchEl = document.getElementById('chatUserSearch');
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
    const typingIndicatorEl = document.getElementById('typingIndicator');
    const chatStatusEl = document.getElementById('chatStatus');

    let unsubscribeMessages = null;
    let unsubscribeTyping = null;
    let typingResetTimer = null;
    let typingStaleTimer = null;
    let pendingFile = null;
    let pendingAudioBlob = null;
    let mediaRecorder = null;
    let recordingChunks = [];
    const TYPING_TTL_MS = 5000;
    const DEBUG = true;
    const GENERAL_CHAT_ID = 'general';

    function log(...args) {
      if (DEBUG) console.log('[CHAT]', ...args);
    }

    function initials(name) {
      const n = String(name || '').trim();
      if (!n) return '??';
      const parts = n.split(/\s+/).slice(0, 2).map((p) => p.charAt(0).toUpperCase());
      return parts.join('');
    }

    function avatarHtml(user, className = 'h-10 w-10 rounded-full object-cover') {
      if (user && user.foto_perfil) {
        return `<img src="${esc(user.foto_perfil)}" class="${className}" alt="${esc(user.name || 'Usuário')}" />`;
      }
      return `<div class="${className} bg-fuchsia-500/25 flex items-center justify-center text-xs font-bold text-pink-100">${esc(initials((user && user.name) ? user.name : ''))}</div>`;
    }

    function esc(v) {
      return String(v || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
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
      log('General chat mode - no user list needed');
    }

    function renderMessages(docs) {
      log('renderMessages called with', docs.length, 'messages');
      if (!docs.length) {
        messagesEl.innerHTML = '<div class="text-center text-sm text-pink-100/50">Nenhuma mensagem ainda. Comece a conversa! 💬</div>';
        return;
      }

      const html = docs.map((msg) => {
        const mine = Number(msg.senderId) === CURRENT_USER.id;
        const bubbleClass = mine
          ? 'ml-auto bg-gradient-to-r from-pink-600 to-fuchsia-600 text-white rounded-b-2xl rounded-tl-2xl'
          : 'mr-auto bg-[#2a1b3f] border border-fuchsia-500/25 text-pink-50 rounded-b-2xl rounded-tr-2xl';

        let body = '';
        if (!mine) {
          body += `<div class="text-xs text-pink-200/90 font-semibold mb-1">${esc(msg.senderName || `Usuário #${msg.senderId}`)}</div>`;
        }

        if (msg.type === 'image' && msg.mediaUrl) {
          body += `<a href="${esc(msg.mediaUrl)}" target="_blank" rel="noopener"><img src="${esc(msg.mediaUrl)}" class="rounded-lg max-h-64 object-cover" loading="lazy" /></a>`;
        } else if (msg.type === 'video' && msg.mediaUrl) {
          body += `<video controls class="rounded-lg max-h-72 w-full"><source src="${esc(msg.mediaUrl)}" /></video>`;
        } else if (msg.type === 'audio' && msg.mediaUrl) {
          body += `<audio controls class="w-full"><source src="${esc(msg.mediaUrl)}" /></audio>`;
        } else if (msg.mediaUrl) {
          body += `<a href="${esc(msg.mediaUrl)}" target="_blank" rel="noopener" class="underline text-pink-100">📎 ${esc(msg.mediaName || 'Arquivo')}</a>`;
        }

        if (msg.text) {
          body += `<div class="whitespace-pre-wrap break-words text-sm${msg.mediaUrl ? ' mt-1' : ''}">${esc(msg.text)}</div>`;
        }

        const date = msg.createdAt && typeof msg.createdAt.toDate === 'function'
          ? msg.createdAt.toDate()
          : (msg.createdAtMs ? new Date(msg.createdAtMs) : new Date());

        return `
          <div class="flex ${mine ? 'justify-end' : 'justify-start'}">
            <div class="max-w-xs px-3 py-2 ${bubbleClass}">
              ${body}
              <div class="text-[11px] mt-1 opacity-80 text-right">${formatTime(date)}</div>
            </div>
          </div>
        `;
      }).reverse().join('');

      messagesEl.innerHTML = html;
      messagesEl.scrollTop = messagesEl.scrollHeight;
      log('Messages rendered, scrolled to bottom');
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
      log('General chat - conversation with all users');
      initGeneralChat();
    }

    async function initGeneralChat() {
      log('Initializing general chat');
      try {
        await ensureFirebaseReady();
        loadGeneralChatMessages();
      } catch (e) {
        console.error('Error initializing general chat:', e);
        chatStatusEl.textContent = '✗ Erro ao conectar';
      }
    }

    async function loadGeneralChatMessages() {
      if (unsubscribeMessages) {
        log('Unsubscribing from previous messages listener');
        unsubscribeMessages();
        unsubscribeMessages = null;
      }

      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        const messagesRef = f.collection(window.db, 'conversations', GENERAL_CHAT_ID, 'messages');
        const q = f.query(messagesRef, f.orderBy('createdAt', 'asc'), f.limit(500));

        unsubscribeMessages = f.onSnapshot(q, (snapshot) => {
          log('Messages snapshot received:', snapshot.docs.length, 'messages');
          const rows = snapshot.docs.map((d) => ({ id: d.id, ...d.data() }));
          renderMessages(rows);
          chatStatusEl.textContent = '🟢 Ativo';
        }, (err) => {
          console.error('Messages listener error:', err);
          chatStatusEl.textContent = '✗ Erro';
        });

        setupTypingListener();
      } catch (e) {
        console.error('Error loading messages:', e);
        chatStatusEl.textContent = '✗ Indisponível';
      }
    }

    async function setupTypingListener() {
      if (unsubscribeTyping) {
        unsubscribeTyping();
        unsubscribeTyping = null;
      }

      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        const typingRef = f.collection(window.db, 'conversations', GENERAL_CHAT_ID, 'typing');
        const q = f.query(typingRef, f.where('isTyping', '==', true));

        unsubscribeTyping = f.onSnapshot(q, (snapshot) => {
          const hasTyping = snapshot.docs.length > 0;
          log('Typing snapshot:', snapshot.docs.length, 'users typing');
          
          if (hasTyping) {
            typingIndicatorEl.classList.remove('hidden');
            if (typingStaleTimer) clearTimeout(typingStaleTimer);
            typingStaleTimer = setTimeout(() => {
              typingIndicatorEl.classList.add('hidden');
            }, TYPING_TTL_MS + 1000);
          } else {
            typingIndicatorEl.classList.add('hidden');
          }
        }, () => {
          typingIndicatorEl.classList.add('hidden');
        });
      } catch (e) {
        console.error('Error setting up typing listener:', e);
      }
    }

    async function setTyping(isTyping) {
      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        const typingDocRef = f.doc(window.db, 'conversations', GENERAL_CHAT_ID, 'typing', String(CURRENT_USER.id));
        if (isTyping) {
          await f.setDoc(typingDocRef, {
            isTyping: true,
            userId: CURRENT_USER.id,
            userName: CURRENT_USER.name,
            at: f.serverTimestamp(),
            atMs: Date.now(),
          }, { merge: true });
          return;
        }
        await f.deleteDoc(typingDocRef);
      } catch (_) {}
    }

    async function sendMessage(payload = {}) {
      log('sendMessage called with payload:', payload);
      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        const messagesRef = f.collection(window.db, 'conversations', GENERAL_CHAT_ID, 'messages');

        const base = {
          senderId: CURRENT_USER.id,
          senderName: CURRENT_USER.name,
          conversationId: GENERAL_CHAT_ID,
          createdAt: f.serverTimestamp(),
          createdAtMs: Date.now(),
        };

        const doc = await f.addDoc(messagesRef, { ...base, ...payload });
        log('Message sent with ID:', doc.id);
      } catch (e) {
        console.error('sendMessage error:', e);
        throw e;
      }
    }

    async function uploadAndSendFile(fileOrBlob, typeHint = 'file') {
      if (!fileOrBlob) return;

      log('uploadAndSendFile started:', fileOrBlob.name, 'type:', typeHint);
      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        const ext = (fileOrBlob.name && fileOrBlob.name.split('.').pop()) || (typeHint === 'audio' ? 'webm' : 'bin');
        const path = `chat_uploads/${GENERAL_CHAT_ID}/${Date.now()}_${Math.random().toString(36).slice(2)}.${ext}`;
        log('Upload path:', path);
        const storageRef = f.ref(window.storage, path);

        const uploadTask = f.uploadBytesResumable(storageRef, fileOrBlob, {
          contentType: fileOrBlob.type || 'application/octet-stream'
        });

        await new Promise((resolve, reject) => {
          uploadTask.on('state_changed', (snap) => {
            const pct = (snap.bytesTransferred / Math.max(1, snap.totalBytes)) * 100;
            log('Upload progress:', Math.round(pct) + '%');
            setUploadProgress(pct);
          }, reject, resolve);
        });

        const url = await f.getDownloadURL(uploadTask.snapshot.ref);
        log('Upload complete, URL:', url.substring(0, 50) + '...');
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
        messageInput.focus();
        await setTyping(false);
        clearMediaPreview();
        setUploadProgress(100);
      } catch (e) {
        console.error('uploadAndSendFile error:', e);
        throw e;
      }
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
      log('Send button clicked');
      const text = messageInput.value.trim();
      if (pendingFile) {
        log('Sending file...');
        try {
          await uploadAndSendFile(pendingFile);
        } catch (e) {
          console.error(e);
          alert('Falha ao enviar anexo.');
        }
        return;
      }

      if (!text) {
        log('Input is empty, ignoring');
        return;
      }

      try {
        log('Sending text message:', text.substring(0, 50));
        await sendMessage({ type: 'text', text });
        messageInput.value = '';
        messageInput.focus();
        await setTyping(false);
        log('Message sent successfully');
      } catch (e) {
        console.error(e);
        alert('Falha ao enviar mensagem.');
      }
    });

    messageInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        log('Enter pressed without Shift');
        e.preventDefault();
        sendBtn.click();
      }
    });

    messageInput.addEventListener('input', async () => {
      const hasText = messageInput.value.trim().length > 0;
      if (!hasText) {
        if (typingResetTimer) {
          clearTimeout(typingResetTimer);
          typingResetTimer = null;
        }
        await setTyping(false);
        return;
      }

      await setTyping(true);
      if (typingResetTimer) {
        clearTimeout(typingResetTimer);
      }
      typingResetTimer = setTimeout(() => {
        setTyping(false);
      }, 1200);
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

    // Initialize general chat
    log('Initializing general chat');
    initGeneralChat();

    window.addEventListener('beforeunload', () => {
      log('Page unloading, clearing typing status');
      setTyping(false);
    });

    log('Chat initialized successfully');
  </script>
</body>
</html>
