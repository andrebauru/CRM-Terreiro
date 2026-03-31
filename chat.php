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
<link rel="stylesheet" href="assets/css/chat-style.css">
<body class="bg-slate-950 font-sans text-slate-100 overflow-hidden">
  <style>
    /* Viewport fixo - Suporte adicional */
    html, body {
      height: 100%;
      width: 100%;
      margin: 0;
      padding: 0;
      overflow: hidden;
    }

    /* Scroll smooth para mensagens */
    .chat-messages {
      scroll-behavior: smooth;
    }

    /* Glow effect para balões */
    .glow-pink {
      box-shadow: 0 0 0 1px rgba(244, 114, 182, 0.35), 0 0 24px rgba(236, 72, 153, 0.42), 0 12px 30px rgba(236, 72, 153, 0.30);
    }

    /* Animação de digitação com bounce */
    @keyframes typing {
      0%, 60%, 100% {
        opacity: 0.5;
        transform: translateY(0);
      }
      30% {
        opacity: 1;
        transform: translateY(-10px);
      }
    }

    .typing-dot {
      animation: typing 1.4s infinite;
    }

    /* Garantir cor branca nos balões */
    .chat-bubble,
    .chat-bubble-text,
    .message-bubble-text {
      color: #ffffff !important;
      word-break: break-word;
      min-height: 20px;
    }

    /* Customização de scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    ::-webkit-scrollbar-track {
      background: rgba(15, 23, 42, 0.5);
    }

    ::-webkit-scrollbar-thumb {
      background: rgba(236, 72, 153, 0.5);
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: rgba(236, 72, 153, 0.7);
    }
  </style>
  <!-- Fixed-Height Viewport Container - Estilo WhatsApp Web com CSS Externo -->
  <div class="chat-wrapper">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <!-- Chat Area Main Container -->
    <main class="chat-main">
      <!-- Top Header (CRM Title) -->
      <header class="h-auto flex-shrink-0 px-4 py-3 bg-slate-900/75 backdrop-blur-xl border-b border-fuchsia-400/20 flex items-center justify-between shadow-[0_8px_30px_rgba(0,0,0,.25)]">
        <div class="flex items-center gap-2">
          <h1 class="text-lg font-black text-pink-300">Chat Interno</h1>
          <span class="text-xs text-pink-100/50">•</span>
          <span class="text-xs text-pink-200"><?= htmlspecialchars($currentUserName) ?></span>
        </div>
      </header>

      <!-- Chat Container -->
      <div id="chat-container" class="chat-sidebar">
        
        <!-- Sidebar: Contatos (Esquerda - 20%) -->
        <header class="chat-sidebar-header">
          <div class="mb-3 flex items-center justify-between gap-2">
            <div>
              <div class="text-sm font-semibold text-white">Conversas</div>
              <div class="text-xs text-slate-400">Histórico em tempo real</div>
            </div>
            <div class="rounded-full border border-emerald-400/30 bg-emerald-500/10 px-2 py-1 text-[11px] text-emerald-300">Online</div>
          </div>
          <input id="chatUserSearch" type="text" placeholder="Pesquisar contato ou e-mail..." class="w-full rounded-2xl bg-[#334155] border border-slate-600 px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-pink-500/60" />
        </header>
        <div id="chatUsersList" class="chat-sidebar-content"></div>

        <!-- Chat Area (Direita - 80%, Coluna com Header/Messages/Input) -->
        <section id="chatConversationArea" class="chat-main hidden">
          
          <!-- Chat Header (70px fixed height) -->
          <header class="chat-header">
            <div class="chat-header-content">
              <button id="chatBackBtn" class="chat-back-button" title="Voltar">
                <i class="fa-solid fa-arrow-left"></i>
              </button>
              <div id="chatAvatarWrap" class="chat-header-avatar">--</div>
              <div class="chat-header-info">
                <div id="chatWithName" class="chat-header-name">Selecione um chat</div>
                <div class="chat-header-status">
                  <span id="chatStatusDot" class="chat-header-status-dot"></span>
                  <span id="chatStatus" class="shrink-0">Aguardando conversa</span>
                  <span id="typingIndicator" class="hidden text-pink-300/80 items-center gap-1">
                    <span>Digitando</span>
                    <span class="inline-flex gap-0.5">
                      <span class="h-1 w-1 rounded-full bg-pink-300 typing-indicator-dot"></span>
                      <span class="h-1 w-1 rounded-full bg-pink-300 typing-indicator-dot" style="animation-delay: 120ms;"></span>
                      <span class="h-1 w-1 rounded-full bg-pink-300 typing-indicator-dot" style="animation-delay: 240ms;"></span>
                    </span>
                  </span>
                </div>
              </div>
            </div>
            <div class="hidden md:flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-pink-100/60">
              <i class="fa-solid fa-lock"></i>
              Chat seguro
            </div>
          </header>

          <!-- Empty State -->
          <div id="chatEmptyState" class="chat-messages-empty hidden">
            <div class="chat-messages-empty-box">
              <div class="chat-messages-empty-icon">
                <i class="fa-solid fa-comments"></i>
              </div>
              <div class="chat-messages-empty-title">Seu chat premium está pronto</div>
              <div class="chat-messages-empty-text">Abra uma conversa à esquerda para carregar as últimas mensagens.</div>
            </div>
          </div>

          <!-- Messages Container (CRITICAL: flex-1 overflow-y-auto) -->
          <div id="messages-container" class="chat-messages hidden">
            <div id="chatMessagesInner" class="chat-messages-inner"></div>
          </div>

          <!-- Input Footer -->
          <div id="chatComposer" class="chat-composer hidden">
            <div id="mediaPreview" class="composer-media-preview"></div>

            <div id="uploadProgressWrap" class="composer-progress">
              <div class="composer-progress-label">
                <span>Enviando mídia</span>
                <span id="uploadProgressText">0%</span>
              </div>
              <div class="composer-progress-bar">
                <div id="uploadProgressBar" class="composer-progress-fill"></div>
              </div>
            </div>

            <div class="composer-input-area">
              <div class="composer-button-group">
                <button id="emojiBtn" class="composer-icon-button" title="Emoji"><i class="fa-regular fa-face-smile"></i></button>
                <label class="composer-icon-button cursor-pointer" title="Anexo">
                  <i class="fa-solid fa-paperclip"></i>
                  <input id="fileInput" type="file" class="hidden" accept="image/*,video/*,audio/*" />
                </label>
              </div>
              <textarea id="messageInput" rows="1" placeholder="Digite uma mensagem..." class="composer-textarea"></textarea>
              <div class="composer-button-group">
                <button id="recordBtn" class="composer-icon-button" title="Áudio"><i class="fa-solid fa-microphone"></i></button>
                <button id="sendBtn" class="composer-send-button" title="Enviar"><i class="fa-solid fa-paper-plane"></i></button>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script type="module">
    console.log('🎯 Chat inicializado - script iniciado');
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
        'foto_perfil' => (string)($u['foto_perfil'] ?? ''),
      ];
    }, $chatUsers), JSON_UNESCAPED_UNICODE) ?>;

    const usersListEl = document.getElementById('chatUsersList');
    const chatUsersPanelEl = document.getElementById('chat-container'); // Sidebar container
    const chatConversationAreaEl = document.getElementById('chatConversationArea');
    const chatBackBtn = document.getElementById('chatBackBtn');
    const searchEl = document.getElementById('chatUserSearch');
    const chatWithNameEl = document.getElementById('chatWithName');
    const chatAvatarWrapEl = document.getElementById('chatAvatarWrap');
    const chatEmptyStateEl = document.getElementById('chatEmptyState');
    const chatComposerEl = document.getElementById('chatComposer');
    const messagesEl = document.getElementById('messages-container');
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
    const chatStatusDotEl = document.getElementById('chatStatusDot');
    const messagesInnerEl = document.getElementById('chatMessagesInner');

    let unsubscribeMessages = null;
    let unsubscribeTyping = null;
    let unsubscribePresence = null;
    let typingResetTimer = null;
    let typingStaleTimer = null;
    let pendingFile = null;
    let pendingAudioBlob = null;
    let mediaRecorder = null;
    let recordingChunks = [];
    const TYPING_TTL_MS = 5000;
    const ONLINE_TTL_MS = 70000;
    const DEBUG = true;
    const GENERAL_CHAT_ID = 'general';
    let currentChatUser = null;
    let currentChatMode = null; // 'general' ou 'private'
    let selectedUserId = null;
    let presenceHeartbeatTimer = null;
    const userPresenceMap = new Map();
    let currentFcmToken = null;

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

    function getDayLabel(date) {
      const now = new Date();
      const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
      const t = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      const diffDays = Math.round((t - d) / 86400000);
      if (diffDays === 0) return 'Hoje';
      if (diffDays === 1) return 'Ontem';
      return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function formatLastSeen(ts) {
      if (!ts) return 'Offline';
      const date = ts instanceof Date ? ts : new Date(ts);
      const diff = Date.now() - date.getTime();
      if (diff < ONLINE_TTL_MS) return 'Online agora';
      return `Visto ${date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`;
    }

    function isUserOnline(userId) {
      const presence = userPresenceMap.get(String(userId));
      if (!presence) return false;
      const lastActiveMs = Number(presence.lastActiveMs || 0);
      return !!lastActiveMs && (Date.now() - lastActiveMs) < ONLINE_TTL_MS;
    }

    function getUserPresenceLabel(userId) {
      const presence = userPresenceMap.get(String(userId));
      if (!presence) return 'Offline';
      return formatLastSeen(Number(presence.lastActiveMs || 0));
    }

    function updateHeaderPresence(isOnline, label) {
      if (chatStatusDotEl) {
        chatStatusDotEl.className = `inline-block h-2.5 w-2.5 rounded-full ${isOnline ? 'bg-emerald-400 shadow-[0_0_12px_rgba(74,222,128,.65)]' : 'bg-slate-500'}`;
      }
      chatStatusEl.textContent = label;
    }

    function scrollMessagesToBottom(force = false) {
      if (!messagesEl) {
        log('ERROR: messagesEl not found!');
        return;
      }
      
      // Se force=true, sempre scrolla. Caso contrário, verifica se está perto do final
      const nearBottom = (messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight) < 120;
      
      if (force || nearBottom) {
        log('Scrolling to bottom. Force:', force, 'NearBottom:', nearBottom);
        // Usar requestAnimationFrame para garantir que o scroll aconteça APÓS o DOM terminar
        requestAnimationFrame(() => {
          messagesEl.scrollTop = messagesEl.scrollHeight;
          // Também usar scrollTo como fallback
          messagesEl.scrollTo({ 
            top: messagesEl.scrollHeight, 
            behavior: force ? 'auto' : 'smooth' 
          });
        });
      }
    }

    function clearMediaPreview() {
      pendingFile = null;
      pendingAudioBlob = null;
      if (!mediaPreviewEl) return;
      mediaPreviewEl.classList.remove('visible');
      mediaPreviewEl.classList.add('hidden');
      mediaPreviewEl.innerHTML = '';
      fileInput.value = '';
    }

    function showConversationArea() {
      if (chatEmptyStateEl) chatEmptyStateEl.classList.add('hidden');
      if (messagesEl) {
        messagesEl.classList.remove('hidden');
        messagesEl.classList.add('visible');
      }
      if (chatComposerEl) {
        chatComposerEl.classList.remove('hidden');
        chatComposerEl.classList.add('visible');
      }
      selectedUserId = currentChatUser ? currentChatUser.id : null;
      setMobileView(true);
      scrollMessagesToBottom(true);
    }

    function setMobileView(inConversation) {
      if (!chatUsersPanelEl || !chatConversationAreaEl) {
        log('ERROR: chatUsersPanelEl or chatConversationAreaEl not found!');
        return;
      }
      
      const isDesktop = window.innerWidth >= 768;
      
      if (isDesktop) {
        // Desktop: sempre mostrar ambos lado a lado
        chatUsersPanelEl.classList.remove('hidden');
        chatConversationAreaEl.classList.remove('hidden');
        chatConversationAreaEl.classList.add('visible');
        log('Desktop view: showing both panels');
        return;
      }

      // Mobile: mostrar lista ou chat baseado no estado (apenas se houver seleção)
      const shouldShowConversation = inConversation === true || (inConversation === undefined && selectedUserId);
      
      if (shouldShowConversation) {
        chatUsersPanelEl.classList.add('hidden');
        chatConversationAreaEl.classList.remove('hidden');
        chatConversationAreaEl.classList.add('visible');
        log('Mobile view: showing conversation, selectedUserId:', selectedUserId);
      } else {
        chatUsersPanelEl.classList.remove('hidden');
        chatConversationAreaEl.classList.add('hidden');
        chatConversationAreaEl.classList.remove('visible');
        log('Mobile view: showing contacts list');
      }
    }

    // Event listener para botão de voltar (mobile only)
    function setupBackButton() {
      if (!chatBackBtn) {
        log('ERROR: chatBackBtn not found!');
        return;
      }
      chatBackBtn.addEventListener('click', () => {
        log('Back button clicked');
        selectedUserId = null;
        currentChatUser = null;
        currentChatMode = null;
        if (unsubscribeMessages) {
          unsubscribeMessages();
          unsubscribeMessages = null;
        }
        if (unsubscribeTyping) {
          unsubscribeTyping();
          unsubscribeTyping = null;
        }
        if (messagesEl) messagesEl.innerHTML = '';
        if (chatEmptyStateEl) chatEmptyStateEl.classList.remove('hidden');
        if (messagesEl) messagesEl.classList.add('hidden');
        if (chatComposerEl) chatComposerEl.classList.add('hidden');
        setMobileView(false); // Mostrar lista de contatos
        renderUsersList('');
      });
    }

    function setUploadProgress(percent) {
      uploadWrap.classList.remove('hidden');
      uploadWrap.classList.add('visible');
      const p = Math.max(0, Math.min(100, percent));
      uploadBar.style.width = `${p}%`;
      uploadText.textContent = `${Math.round(p)}%`;
      if (p >= 100) {
        setTimeout(() => {
          uploadWrap.classList.add('hidden');
          uploadWrap.classList.remove('visible');
          uploadBar.style.width = '0%';
          uploadText.textContent = '0%';
        }, 500);
      }
    }

    function renderUsersList(filter = '') {
      log('renderUsersList called, filter:', filter);
      const term = String(filter || '').toLowerCase();
      
      // Renderizar opção de Chat Geral primeiro
      let html = `
        <button data-chat-mode="general" class="chat-user-btn w-full rounded-2xl border px-3 py-3 text-left transition ${currentChatMode === 'general' ? 'border-pink-400/70 bg-gradient-to-r from-pink-600/25 to-fuchsia-600/20 shadow-[0_8px_22px_rgba(236,72,153,.18)]' : 'border-fuchsia-500/15 bg-[#1b112b] hover:bg-[#241635]'}">
          <div class="flex items-center gap-3 min-w-0">
            <div class="relative h-12 w-12 rounded-full bg-gradient-to-br from-pink-500 to-fuchsia-600 flex items-center justify-center text-sm font-bold text-white shrink-0 shadow-lg shadow-fuchsia-900/30">
              <i class="fa-solid fa-comments text-sm"></i>
              <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-[#1b112b] bg-emerald-400"></span>
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-2">
                <div class="font-semibold text-pink-100 truncate text-sm">Chat Geral</div>
                <div class="text-[11px] text-pink-100/45">Ao vivo</div>
              </div>
              <div class="text-xs text-pink-100/60 truncate">Canal principal da equipe</div>
            </div>
          </div>
        </button>
      `;

      const rows = USERS.filter((u) => {
        const text = `${u.name} ${u.email}`.toLowerCase();
        return text.includes(term);
      });

      if (rows.length === 0 && !term) {
        usersListEl.innerHTML = html + '<div class="rounded-2xl border border-dashed border-fuchsia-500/20 p-4 text-sm text-pink-100/60">Nenhum outro usuário disponível.</div>';
        attachUserListeners();
        return;
      }

      html += rows.map((u) => {
        const active = currentChatMode === 'private' && currentChatUser && currentChatUser.id === u.id;
        const online = isUserOnline(u.id);
        const presenceLabel = getUserPresenceLabel(u.id);
        return `
          <button data-user-id="${u.id}" class="chat-user-btn w-full rounded-2xl border px-3 py-3 text-left transition ${active ? 'border-pink-400/70 bg-gradient-to-r from-pink-600/20 to-fuchsia-600/15 shadow-[0_8px_22px_rgba(236,72,153,.18)]' : 'border-fuchsia-500/15 bg-[#1b112b] hover:bg-[#241635]'}">
            <div class="flex items-center gap-3 min-w-0">
              <div class="relative shrink-0">
                ${avatarHtml(u, 'h-12 w-12 rounded-full object-cover shrink-0 ring-2 ring-white/5')}
                <span class="absolute bottom-0 right-0 h-3.5 w-3.5 rounded-full border-2 border-[#1b112b] ${online ? 'bg-emerald-400' : 'bg-slate-500'}"></span>
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                  <div class="font-semibold text-pink-100 truncate text-sm">${esc(u.name || ('Usuário #' + u.id))}</div>
                  <div class="text-[11px] ${online ? 'text-emerald-300' : 'text-pink-100/35'}">${online ? 'Online' : 'Off'}</div>
                </div>
                <div class="text-xs text-pink-100/60 truncate">${esc(u.email || '')}</div>
                <div class="mt-1 text-[11px] ${online ? 'text-emerald-300/90' : 'text-pink-100/40'} truncate">${esc(presenceLabel)}</div>
              </div>
            </div>
          </button>
        `;
      }).join('');

      usersListEl.innerHTML = html;
      attachUserListeners();
    }

    function attachUserListeners() {
      // Chat Geral button
      const generalBtn = usersListEl.querySelector('[data-chat-mode="general"]');
      if (generalBtn) {
        generalBtn.addEventListener('click', () => {
          log('General chat clicked');
          currentChatMode = 'general';
          currentChatUser = null;
          selectedUserId = GENERAL_CHAT_ID;
          openGeneralChat();
          setMobileView(true);
        });
      }

      // User buttons
      document.querySelectorAll('[data-user-id]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = Number(btn.getAttribute('data-user-id') || 0);
          const user = USERS.find((u) => u.id === id);
          if (user) {
            log('User clicked:', user.name);
            currentChatMode = 'private';
            currentChatUser = user;
            selectedUserId = String(user.id);
            openPrivateChat(user);
            setMobileView(true);
          }
        });
      });
    }

    function renderMessages(docs) {
      log('renderMessages called with', docs.length, 'messages');
      if (!messagesInnerEl) {
        log('ERROR: messagesInnerEl not found!');
        return;
      }

      // Limpar container ANTES de renderizar
      messagesInnerEl.innerHTML = '';

      if (!docs.length) {
        const emptyMsg = document.createElement('div');
        emptyMsg.className = 'flex min-h-full items-center justify-center text-center text-sm text-pink-100/50';
        emptyMsg.textContent = 'Nenhuma mensagem ainda. Comece a conversa! 💬';
        messagesInnerEl.appendChild(emptyMsg);
        scrollMessagesToBottom(true);
        return;
      }

      let lastDateKey = '';

      docs.forEach((doc) => {
        // Extrair dados de forma robusta
        const data = doc.data ? doc.data() : (doc || {});
        // Campo principal: text (via Firestore)
        const content = data.text || "";
        
        log('Renderizando mensagem:', { senderId: data.senderId, text: content.substring(0, 50) });
        
        if (!content) {
          log('⚠️ Mensagem vazia ou sem campo text:', data);
          return; // Pular mensagens vazias
        }

        const mine = Number(data.senderId) === CURRENT_USER.id;

        // Extrair data da mensagem
        const date = data.timestamp && typeof data.timestamp.toDate === 'function'
          ? data.timestamp.toDate()
          : (data.createdAt && typeof data.createdAt.toDate === 'function'
            ? data.createdAt.toDate()
            : (data.createdAtMs ? new Date(data.createdAtMs) : new Date()));

        // Separador de data
        const dateKey = `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
        if (dateKey !== lastDateKey) {
          const separator = document.createElement('div');
          separator.className = 'message-date-separator';
          const sepText = document.createElement('span');
          sepText.className = 'message-date-separator-text';
          sepText.textContent = getDayLabel(date);
          separator.appendChild(sepText);
          messagesInnerEl.appendChild(separator);
          lastDateKey = dateKey;
        }

        // Container da mensagem
        const row = document.createElement('div');
        row.className = `message-row ${mine ? 'sent' : 'received'}`;

        // Balão de mensagem
        const bubble = document.createElement('div');
        bubble.className = `message-bubble ${mine ? 'sent' : 'received'}`;
        
        // IMPORTANTE: Usar textContent para injetar texto de forma segura
        bubble.textContent = content;
        
        // Horário e status de leitura
        const timeEl = document.createElement('div');
        const timeStr = formatTime(date);
        const isRead = !!(data.readAt || data.read_at || data.read === true);
        
        timeEl.className = `message-meta ${mine ? 'sent' : 'received'}`;
        
        // Criar span de tempo
        const timeSpan = document.createElement('span');
        timeSpan.textContent = timeStr;
        timeEl.appendChild(timeSpan);
        
        if (mine) {
          // Check duplo para mensagens enviadas
          const checkFirst = document.createElement('i');
          checkFirst.className = `fa-solid fa-check message-check-icon ${isRead ? 'read' : ''}`;
          timeEl.appendChild(checkFirst);
          
          const checkSecond = document.createElement('i');
          checkSecond.className = `fa-solid fa-check message-check-icon ${isRead ? 'read' : ''}`;
          timeEl.appendChild(checkSecond);
        }
        
        bubble.appendChild(timeEl);
        row.appendChild(bubble);
        messagesInnerEl.appendChild(row);
      });

      // Autoscroll para o final imediatamente após render com requestAnimationFrame
      requestAnimationFrame(() => {
        scrollMessagesToBottom(true);
      });
      
      log('Messages rendered successfully');
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

    async function setupPresenceTracking() {
      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        const myPresenceRef = f.doc(window.db, 'chat_presence', String(CURRENT_USER.id));

        const publishPresence = async (state = 'online') => {
          await f.setDoc(myPresenceRef, {
            userId: CURRENT_USER.id,
            userName: CURRENT_USER.name,
            state,
            lastActiveAt: f.serverTimestamp(),
            lastActiveMs: Date.now(),
          }, { merge: true });
        };

        await publishPresence('online');

        if (presenceHeartbeatTimer) {
          clearInterval(presenceHeartbeatTimer);
        }
        presenceHeartbeatTimer = setInterval(() => {
          publishPresence(document.hidden ? 'away' : 'online').catch((err) => console.warn('Presence heartbeat failed:', err));
        }, 30000);

        if (unsubscribePresence) {
          unsubscribePresence();
          unsubscribePresence = null;
        }

        const presenceRef = f.collection(window.db, 'chat_presence');
        unsubscribePresence = f.onSnapshot(presenceRef, (snapshot) => {
          userPresenceMap.clear();
          snapshot.docs.forEach((snap) => {
            userPresenceMap.set(String(snap.id), snap.data() || {});
          });
          renderUsersList(searchEl.value);
          if (currentChatMode === 'private' && currentChatUser) {
            updateHeaderPresence(isUserOnline(currentChatUser.id), getUserPresenceLabel(currentChatUser.id));
          }
        }, (err) => {
          console.warn('Presence listener error:', err);
        });

        document.addEventListener('visibilitychange', () => {
          publishPresence(document.hidden ? 'away' : 'online').catch(() => {});
        });
      } catch (e) {
        console.warn('Presence tracking unavailable:', e);
      }
    }

    async function setupPushNotifications() {
      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        if (!window.firebaseMessaging || !f.getToken) {
          console.warn('FCM não suportado neste navegador/ambiente.');
          return;
        }
        if (!('Notification' in window)) {
          console.warn('Notificações não suportadas neste navegador.');
          return;
        }

        let permission = Notification.permission;
        if (permission === 'default') {
          permission = await Notification.requestPermission();
        }
        if (permission !== 'granted') {
          console.warn('Permissão de notificação não concedida.');
          return;
        }

        const vapidKey = window.__FCM_VAPID_KEY || window.__crmSettings?.fcm_vapid_key || '';
        if (!vapidKey) {
          console.warn('Defina __FCM_VAPID_KEY para habilitar token FCM web.');
          return;
        }

        if (!('serviceWorker' in navigator)) {
          console.warn('Service Worker não suportado neste navegador.');
          return;
        }

        const swRegistration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');

        const token = await f.getToken(window.firebaseMessaging, {
          vapidKey,
          serviceWorkerRegistration: swRegistration,
        });
        if (!token) {
          console.warn('Token FCM não retornado.');
          return;
        }

        if (currentFcmToken === token) return;
        currentFcmToken = token;

        const userDocRef = f.doc(window.db, 'users', String(CURRENT_USER.id));
        await f.setDoc(userDocRef, {
          fcm_token: token,
          user_id: CURRENT_USER.id,
          updatedAt: f.serverTimestamp(),
          userAgent: navigator.userAgent || 'unknown',
        }, { merge: true });

        if (f.onMessage) {
          f.onMessage(window.firebaseMessaging, (payload) => {
            console.log('🔔 Push recebida (foreground):', payload?.notification?.title || payload);
          });
        }

        console.log('✅ Token FCM salvo em users/{userId}.fcm_token');
      } catch (err) {
        console.warn('Falha ao configurar notificações push:', err);
      }
    }

    async function openConversation(user) {
      log('Legacy function - use openGeneralChat or openPrivateChat');
    }

    async function openGeneralChat() {
      log('Opening general chat');
      showConversationArea();
      chatWithNameEl.textContent = 'Chat Geral';
      chatAvatarWrapEl.innerHTML = '<div class="h-11 w-11 rounded-full bg-gradient-to-br from-pink-500 to-fuchsia-600 flex items-center justify-center text-xs font-bold text-white"><i class="fa-solid fa-comments text-xs"></i></div>';
      typingIndicatorEl.classList.add('hidden');
      updateHeaderPresence(true, 'Canal geral online');

      if (unsubscribeMessages) {
        log('Unsubscribing from previous messages listener');
        unsubscribeMessages();
        unsubscribeMessages = null;
      }
      if (unsubscribeTyping) {
        log('Unsubscribing from previous typing listener');
        unsubscribeTyping();
        unsubscribeTyping = null;
      }

      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        const chatId = GENERAL_CHAT_ID;
        console.log('💬 Abrindo chat com ID:', chatId);
        const messagesRef = f.collection(window.db, 'conversations', GENERAL_CHAT_ID, 'messages');
        const q = f.query(
          messagesRef,
          f.where('conversationId', '==', GENERAL_CHAT_ID),
          f.orderBy('timestamp', 'asc'),
          f.limitToLast(200)
        );

        unsubscribeMessages = f.onSnapshot(q, (snapshot) => {
          console.log('Buscando mensagens para:', chatId);
          log('Messages snapshot received:', snapshot.docs.length, 'messages');
          renderMessages(snapshot.docs);
          if (messagesEl) {
            messagesEl.scrollTo({ top: messagesEl.scrollHeight, behavior: 'smooth' });
          }
          updateHeaderPresence(true, 'Canal geral online');
        }, (err) => {
          console.error('Messages listener error:', err);
          updateHeaderPresence(false, 'Erro ao carregar');
        });

        setupGeneralTypingListener();
      } catch (e) {
        console.error('Error opening general chat:', e);
        updateHeaderPresence(false, 'Indisponível');
      }
    }

    async function openPrivateChat(user) {
      log('Opening private chat with:', user.id, user.name);
      showConversationArea();
      chatWithNameEl.textContent = user.name || `Usuário #${user.id}`;
      chatAvatarWrapEl.innerHTML = avatarHtml(user, 'h-11 w-11 rounded-full object-cover');
      typingIndicatorEl.classList.add('hidden');
      updateHeaderPresence(isUserOnline(user.id), isUserOnline(user.id) ? 'Online agora' : 'Conectando histórico...');

      if (unsubscribeMessages) {
        log('Unsubscribing from previous messages listener');
        unsubscribeMessages();
        unsubscribeMessages = null;
      }
      if (unsubscribeTyping) {
        log('Unsubscribing from previous typing listener');
        unsubscribeTyping();
        unsubscribeTyping = null;
      }

      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        const convo = conversationId(CURRENT_USER.id, user.id);
        console.log('💬 Abrindo chat com ID:', convo);
        log('Conversation ID:', convo);
        const messagesRef = f.collection(window.db, 'conversations', convo, 'messages');
        const q = f.query(
          messagesRef,
          f.where('conversationId', '==', convo),
          f.orderBy('timestamp', 'asc'),
          f.limitToLast(200)
        );
        const typingDocRef = f.doc(window.db, 'conversations', convo, 'typing', String(user.id));

        unsubscribeMessages = f.onSnapshot(q, (snapshot) => {
          console.log('Buscando mensagens para:', convo);
          log('Messages snapshot received:', snapshot.docs.length, 'messages');
          renderMessages(snapshot.docs);
          if (messagesEl) {
            messagesEl.scrollTo({ top: messagesEl.scrollHeight, behavior: 'smooth' });
          }
          updateHeaderPresence(isUserOnline(user.id), getUserPresenceLabel(user.id));
        }, (err) => {
          console.error('Messages listener error:', err);
          updateHeaderPresence(false, 'Erro ao carregar');
        });

        unsubscribeTyping = f.onSnapshot(typingDocRef, (snap) => {
          const data = snap.exists() ? snap.data() : null;
          const isTyping = !!(data && data.isTyping);
          if (!isTyping) {
            if (typingStaleTimer) {
              clearTimeout(typingStaleTimer);
              typingStaleTimer = null;
            }
            typingIndicatorEl.classList.add('hidden');
            typingIndicatorEl.classList.remove('inline-flex');
            updateHeaderPresence(isUserOnline(user.id), getUserPresenceLabel(user.id));
            return;
          }

          let baseMs = 0;
          if (data.at && typeof data.at.toDate === 'function') {
            baseMs = data.at.toDate().getTime();
          } else {
            baseMs = Number(data.atMs || 0);
          }

          const nowMs = Date.now();
          const expired = !baseMs || (nowMs - baseMs > TYPING_TTL_MS);
          if (expired) {
            typingIndicatorEl.classList.add('hidden');
            typingIndicatorEl.classList.remove('inline-flex');
            updateHeaderPresence(isUserOnline(user.id), getUserPresenceLabel(user.id));
            return;
          }

          typingIndicatorEl.classList.remove('hidden');
          typingIndicatorEl.classList.add('inline-flex');
          updateHeaderPresence(true, 'Digitando...');
          if (typingStaleTimer) {
            clearTimeout(typingStaleTimer);
          }
          const remaining = Math.max(300, TYPING_TTL_MS - (nowMs - baseMs));
          typingStaleTimer = setTimeout(() => {
            typingIndicatorEl.classList.add('hidden');
            typingIndicatorEl.classList.remove('inline-flex');
            updateHeaderPresence(isUserOnline(user.id), getUserPresenceLabel(user.id));
          }, remaining);
        }, () => {
          typingIndicatorEl.classList.add('hidden');
          typingIndicatorEl.classList.remove('inline-flex');
          updateHeaderPresence(isUserOnline(user.id), getUserPresenceLabel(user.id));
        });
      } catch (e) {
        console.error('Error opening private chat:', e);
        updateHeaderPresence(false, 'Indisponível');
      }
    }

    async function setupGeneralTypingListener() {
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
            typingIndicatorEl.classList.add('inline-flex');
            if (typingStaleTimer) clearTimeout(typingStaleTimer);
            typingStaleTimer = setTimeout(() => {
              typingIndicatorEl.classList.add('hidden');
              typingIndicatorEl.classList.remove('inline-flex');
            }, TYPING_TTL_MS + 1000);
          } else {
            typingIndicatorEl.classList.add('hidden');
            typingIndicatorEl.classList.remove('inline-flex');
          }
        }, () => {
          typingIndicatorEl.classList.add('hidden');
          typingIndicatorEl.classList.remove('inline-flex');
        });
      } catch (e) {
        console.error('Error setting up general typing listener:', e);
      }
    }

    async function setTyping(isTyping) {
      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        
        if (currentChatMode === 'general') {
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
        } else if (currentChatMode === 'private' && currentChatUser) {
          const convo = conversationId(CURRENT_USER.id, currentChatUser.id);
          const typingDocRef = f.doc(window.db, 'conversations', convo, 'typing', String(CURRENT_USER.id));
          if (isTyping) {
            await f.setDoc(typingDocRef, {
              isTyping: true,
              userId: CURRENT_USER.id,
              at: f.serverTimestamp(),
              atMs: Date.now(),
            }, { merge: true });
            return;
          }
          await f.deleteDoc(typingDocRef);
        }
      } catch (_) {}
    }

    async function sendMessage(payload = {}) {
      log('📤 sendMessage chamado com payload:', payload);
      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        
        if (currentChatMode === 'general') {
          log('📤 Modo GERAL - enviando para conversationId=general');
          console.log('Enviando para ID:', GENERAL_CHAT_ID);
          const messagesRef = f.collection(window.db, 'conversations', GENERAL_CHAT_ID, 'messages');
          const base = {
            text: '',
            senderId: CURRENT_USER.id,
            senderName: CURRENT_USER.name,
            receiverId: null,
            conversationId: GENERAL_CHAT_ID,
            timestamp: f.serverTimestamp(),
            createdAt: f.serverTimestamp(),
            createdAtMs: Date.now(),
          };
          const doc = await f.addDoc(messagesRef, { ...base, ...payload });
          console.log('Mensagem gravada no Firestore!');
          log('✅ Mensagem geral enviada com ID:', doc.id);
        } else if (currentChatMode === 'private' && currentChatUser) {
          log('📤 Modo PRIVADO - enviando para usuário:', currentChatUser.name);
          const convo = conversationId(CURRENT_USER.id, currentChatUser.id);
          console.log('Enviando para ID:', currentChatUser.id);
          const messagesRef = f.collection(window.db, 'conversations', convo, 'messages');
          const base = {
            text: '',
            senderId: CURRENT_USER.id,
            senderName: CURRENT_USER.name,
            receiverId: currentChatUser.id,
            conversationId: convo,
            timestamp: f.serverTimestamp(),
            createdAt: f.serverTimestamp(),
            createdAtMs: Date.now(),
          };
          const doc = await f.addDoc(messagesRef, { ...base, ...payload });
          console.log('Mensagem gravada no Firestore!');
          log('✅ Mensagem privada enviada com ID:', doc.id);
        }
      } catch (e) {
        console.error('❌ Erro em sendMessage:', e);
        throw e;
      }
    }

    async function uploadAndSendFile(fileOrBlob, typeHint = 'file') {
      if (!fileOrBlob) return;

      console.log('📤 uploadAndSendFile iniciado:', fileOrBlob.name, 'tipo:', typeHint);
      try {
        await ensureFirebaseReady();
        const f = window.firebaseFns;
        const ext = (fileOrBlob.name && fileOrBlob.name.split('.').pop()) || (typeHint === 'audio' ? 'webm' : 'bin');
        const chatId = currentChatMode === 'general' 
          ? GENERAL_CHAT_ID 
          : (currentChatUser ? conversationId(CURRENT_USER.id, currentChatUser.id) : null);
        
        if (!chatId) {
          console.error('❌ Nenhum chat selecionado para upload');
          alert('Selecione um chat para enviar arquivo.');
          return;
        }
        
        const path = `chat_uploads/${chatId}/${Date.now()}_${Math.random().toString(36).slice(2)}.${ext}`;
        console.log('📂 Caminho de upload:', path);
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
        console.log('✅ Upload completo, URL:', url.substring(0, 50) + '...');
        const mime = String(fileOrBlob.type || '');
        const messageType = typeHint === 'audio'
          ? 'audio'
          : (mime.startsWith('image/') ? 'image' : (mime.startsWith('video/') ? 'video' : 'file'));

        await sendMessage({
          type: messageType,
          file_type: messageType,
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
        mediaHtml += `<img src="${esc(url)}" class="rounded-lg max-h-56 w-full object-cover" />`;
      } else if (mime.startsWith('video/')) {
        mediaHtml += `<video controls class="rounded-lg max-h-56 w-full"><source src="${esc(url)}" /></video>`;
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
      mediaPreviewEl.classList.add('visible');
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
      console.log('🔘 Botão enviar clicado');
      const text = messageInput.value.trim();
      
      if (pendingFile) {
        console.log('📎 Enviando arquivo pendente');
        try {
          await uploadAndSendFile(pendingFile);
        } catch (e) {
          console.error('❌ Erro ao enviar anexo:', e);
          alert('Falha ao enviar anexo.');
        }
        return;
      }

      if (!text) {
        console.log('⚠️ Input vazio, ignorando');
        return;
      }

      try {
        console.log('✉️ Enviando mensagem de texto:', text.substring(0, 30) + '...');
        await sendMessage({ type: 'text', text });
        messageInput.value = '';
        messageInput.focus();
        await setTyping(false);
        console.log('✅ Mensagem enviada com sucesso');
      } catch (e) {
        console.error('❌ Erro ao enviar mensagem:', e);
        alert('Falha ao enviar mensagem.');
      }
    });

    messageInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        console.log('⏎ Enter pressionado - enviando mensagem');
        e.preventDefault();
        e.stopPropagation();
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

    searchEl.addEventListener('input', () => {
      renderUsersList(searchEl.value);
    });

    // Setup back button - mobile only
    setupBackButton();

    // Detectar resize APENAS se a largura mudar (mobile orientation)
    // Não reagir a mudanças de altura (que acontece quando teclado abre)
    // Track last width to detect only significant width changes (ignore keyboard height changes)
    let lastWidth = window.innerWidth;
    
    window.addEventListener('resize', () => {
      const currentWidth = window.innerWidth;
      
      // Só processar se a LARGURA mudou significativamente (mais de 50px)
      // Isso ignora mudanças de altura causadas pela abertura do teclado no mobile
      if (Math.abs(currentWidth - lastWidth) > 50) {
        log('Screen width changed:', lastWidth, '->', currentWidth);
        lastWidth = currentWidth;
        setMobileView(!!selectedUserId);
      }
    });

    if (chatBackBtn) {
      chatBackBtn.addEventListener('click', () => {
        log('Back button clicked from event listener');
        selectedUserId = null;
        setMobileView(false);
      });
    }

    // Initialize
    log('Initializing chat');
    setMobileView(false);
    setupPresenceTracking();
    setupPushNotifications();
    renderUsersList('');
    if (USERS.length === 0) {
      log('No other users available, initializing general chat');
      currentChatMode = 'general';
      currentChatUser = null;
      openGeneralChat();
      renderUsersList('');
    }

    window.addEventListener('beforeunload', () => {
      log('Page unloading, clearing typing status');
      if (presenceHeartbeatTimer) clearInterval(presenceHeartbeatTimer);
      setTyping(false);
    });

    console.log('✅ Chat completamente inicializado - aguardando interações');
    console.log('%c🎯 Dicas para debugging:', 'color: #FF1493; font-size: 14px; font-weight: bold;');
    console.log('%c📤 Enviar mensagem: veja logs "sendMessage chamado", "Mensagem enviada"', 'color: #00FF00');
    console.log('%c📎 Upload de arquivo: veja logs "uploadAndSendFile iniciado", "Upload completo"', 'color: #00FF00');
    console.log('%c⏎ Enter: log "Enter pressionado" - enviando mensagem', 'color: #00FF00');
    console.log('%c🔘 Botão: log "Botão enviar clicado"', 'color: #00FF00');
    console.log('Sistema de Chat Pronto');
    log('Chat initialized successfully');
  </script>
</body>
</html>
