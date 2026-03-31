/**
 * Chat UI Manager
 * Gerencia visibilidade de elementos e estado da UI
 */

export class ChatUIManager {
  constructor(config = {}) {
    this.config = {
      mobileBreakpoint: 768,
      ...config
    };
    
    this.isMobile = window.innerWidth < this.config.mobileBreakpoint;
    this.sidebarOpen = false;
    
    this.elements = {
      // Wrapper
      chatWrapper: null,
      toggleBtn: null,
      
      // Sidebar
      sidebar: null,
      usersList: null,
      
      // Main chat area
      conversationArea: null,
      messagesContainer: null,
      composer: null,
      messageInput: null,
      sendBtn: null,
      
      // Headers
      chatHeader: null,
      
      // Other
      mediaPreview: null,
      backBtn: null,
    };
    
    this.state = {
      selectedUserId: null,
      currentChatMode: null,
      isDesktop: !this.isMobile,
    };
    
    this.initElements();
    this.setupResizeListener();
  }
  
  initElements() {
    this.elements.chatWrapper = document.getElementById('chatWrapper');
    this.elements.toggleBtn = document.getElementById('toggleCrmSidebar');
    this.elements.sidebar = document.querySelector('.chat-sidebar');
    this.elements.usersList = document.getElementById('chatUsersList');
    this.elements.conversationArea = document.getElementById('chatConversationArea');
    this.elements.messagesContainer = document.querySelector('.chat-messages');
    this.elements.composer = document.getElementById('chatComposer');
    this.elements.messageInput = document.getElementById('messageInput');
    this.elements.sendBtn = document.getElementById('sendBtn');
    this.elements.chatHeader = document.querySelector('.chat-header');
    this.elements.mediaPreview = document.getElementById('mediaPreview');
    this.elements.backBtn = document.getElementById('chatBackBtn');
  }
  
  /**
   * Toggle sidebar da aplicação
   */
  toggleSidebar() {
    this.sidebarOpen = !this.sidebarOpen;
    
    if (!this.elements.chatWrapper) return;
    
    if (this.sidebarOpen) {
      this.elements.chatWrapper.classList.add('sidebar-open');
      console.log('[ChatUI] Sidebar aberta');
    } else {
      this.elements.chatWrapper.classList.remove('sidebar-open');
      console.log('[ChatUI] Sidebar fechada');
    }
  }
  
  /**
   * Mostrar conversa (desktop e mobile)
   */
  showConversation() {
    console.log('[ChatUI] Mostrando conversa');
    
    if (this.elements.conversationArea) {
      this.elements.conversationArea.classList.remove('hidden');
      this.elements.conversationArea.classList.add('flex');
    }
    
    if (this.elements.messagesContainer) {
      this.elements.messagesContainer.classList.remove('hidden');
      this.elements.messagesContainer.classList.add('flex');
    }
    
    if (this.elements.composer) {
      this.elements.composer.classList.remove('hidden');
      this.elements.composer.classList.add('flex');
    }
    
    // Mobile: esconder sidebar
    if (this.isMobile && this.elements.sidebar) {
      this.elements.sidebar.classList.add('hidden');
      this.elements.sidebar.classList.remove('flex');
    }
    
    // Auto-focus no input
    setTimeout(() => {
      if (this.elements.messageInput) {
        this.elements.messageInput.focus();
        console.log('[ChatUI] Input focused');
      }
    }, 100);
  }
  
  /**
   * Esconder conversa e voltar para lista de contatos
   */
  hideConversation() {
    console.log('[ChatUI] Escondendo conversa');
    
    if (this.elements.conversationArea) {
      this.elements.conversationArea.classList.add('hidden');
      this.elements.conversationArea.classList.remove('flex');
    }
    
    if (this.elements.messagesContainer) {
      this.elements.messagesContainer.classList.add('hidden');
      this.elements.messagesContainer.classList.remove('flex');
    }
    
    if (this.elements.composer) {
      this.elements.composer.classList.add('hidden');
      this.elements.composer.classList.remove('flex');
    }
    
    // Mobile: mostrar sidebar
    if (this.isMobile && this.elements.sidebar) {
      this.elements.sidebar.classList.remove('hidden');
      this.elements.sidebar.classList.add('flex');
    }
    
    // Limpar seleção
    this.state.selectedUserId = null;
    this.state.currentChatMode = null;
  }
  
  /**
   * Atualizar visibilidade baseado no state
   */
  updateVisibility() {
    if (this.state.isDesktop) {
      // Desktop: sempre mostrar ambos
      if (this.elements.sidebar) {
        this.elements.sidebar.classList.remove('hidden');
        this.elements.sidebar.classList.add('flex');
      }
      if (this.elements.conversationArea) {
        this.elements.conversationArea.classList.remove('hidden');
        this.elements.conversationArea.classList.add('flex');
      }
      console.log('[ChatUI] Desktop view: ambos visíveis');
    } else {
      // Mobile: mostrar conversa ou sidebar baseado em selectedUserId
      if (this.state.selectedUserId) {
        this.showConversation();
      } else {
        this.hideConversation();
      }
    }
  }
  
  /**
   * Focar no input de mensagem
   */
  focusMessageInput() {
    if (this.elements.messageInput) {
      setTimeout(() => {
        this.elements.messageInput.focus();
      }, 50);
    }
  }
  
  /**
   * Limpar input de mensagem
   */
  clearMessageInput() {
    if (this.elements.messageInput) {
      this.elements.messageInput.value = '';
      this.focusMessageInput();
    }
  }
  
  /**
   * Detectar mudanças de tamanho de tela
   */
  setupResizeListener() {
    let resizeTimeout;
    
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        const wasDesktop = this.state.isDesktop;
        this.state.isDesktop = window.innerWidth >= this.config.mobileBreakpoint;
        this.isMobile = !this.state.isDesktop;
        
        if (wasDesktop !== this.state.isDesktop) {
          console.log(`[ChatUI] Modo mudou para ${this.state.isDesktop ? 'desktop' : 'mobile'}`);
          this.updateVisibility();
        }
      }, 250);
    });
  }
  
  /**
   * Configurar listeners
   */
  setupListeners(callbacks = {}) {
    // Toggle sidebar
    if (this.elements.toggleBtn) {
      this.elements.toggleBtn.addEventListener('click', () => {
        this.toggleSidebar();
        if (callbacks.onSidebarToggle) {
          callbacks.onSidebarToggle(this.sidebarOpen);
        }
      });
    }
    
    // Back button (mobile)
    if (this.elements.backBtn) {
      this.elements.backBtn.addEventListener('click', () => {
        this.hideConversation();
        if (callbacks.onBack) {
          callbacks.onBack();
        }
      });
    }
    
    // Send button - com focus automático após envio
    if (this.elements.sendBtn) {
      const originalClick = this.elements.sendBtn.onclick;
      this.elements.sendBtn.addEventListener('click', async (e) => {
        if (callbacks.onSend) {
          await callbacks.onSend(e);
        }
        // Auto-focus após enviar
        setTimeout(() => this.focusMessageInput(), 100);
      });
    }
  }
}

export default ChatUIManager;
