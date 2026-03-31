/**
 * Chat Message Handler
 * Gerencia envio de mensagens de forma robusta
 */

export class ChatMessageHandler {
  constructor(config = {}) {
    this.config = {
      retryAttempts: 3,
      retryDelay: 500,
      ...config
    };
    
    this.messageInput = null;
    this.sendBtn = null;
    this.firebaseReady = false;
    this.callbacks = {};
  }
  
  /**
   * Inicializar com elementos do DOM
   */
  init(messageInput, sendBtn) {
    this.messageInput = messageInput;
    this.sendBtn = sendBtn;
    console.log('[MessageHandler] Inicializado', {
      hasInput: !!messageInput,
      hasBtn: !!sendBtn
    });
  }
  
  /**
   * Registrar callback para envio
   */
  onSend(callback) {
    this.callbacks.send = callback;
  }
  
  /**
   * Registrar callback para validação
   */
  onValidate(callback) {
    this.callbacks.validate = callback;
  }
  
  /**
   * Enviar mensagem com retry
   */
  async send(attemptNumber = 1) {
    console.log(`[MessageHandler] Tentativa ${attemptNumber}/${this.config.retryAttempts}`);
    
    // Validação
    if (this.callbacks.validate) {
      const isValid = await this.callbacks.validate();
      if (!isValid) {
        console.warn('[MessageHandler] Validação falhou');
        return false;
      }
    }
    
    // Enviar
    if (this.callbacks.send) {
      try {
        const result = await this.callbacks.send();
        console.log('[MessageHandler] Mensagem enviada com sucesso');
        return result;
      } catch (error) {
        console.error(`[MessageHandler] Erro ao enviar:`, error);
        
        // Retry logic
        if (attemptNumber < this.config.retryAttempts) {
          console.log(`[MessageHandler] Aguardando ${this.config.retryDelay}ms antes de retry...`);
          await new Promise(resolve => setTimeout(resolve, this.config.retryDelay));
          return this.send(attemptNumber + 1);
        } else {
          console.error('[MessageHandler] Falha após ' + this.config.retryAttempts + ' tentativas');
          alert('Erro ao enviar mensagem. Tente novamente.');
          return false;
        }
      }
    }
  }
  
  /**
   * Obter texto do input
   */
  getMessageText() {
    if (!this.messageInput) return '';
    return this.messageInput.value.trim();
  }
  
  /**
   * Limpar input
   */
  clearInput() {
    if (this.messageInput) {
      this.messageInput.value = '';
    }
  }
  
  /**
   * Desabilitar input (enquanto envia)
   */
  disableInput() {
    if (this.messageInput) this.messageInput.disabled = true;
    if (this.sendBtn) this.sendBtn.disabled = true;
    if (this.sendBtn) this.sendBtn.style.opacity = '0.5';
  }
  
  /**
   * Habilitar input
   */
  enableInput() {
    if (this.messageInput) this.messageInput.disabled = false;
    if (this.sendBtn) this.sendBtn.disabled = false;
    if (this.sendBtn) this.sendBtn.style.opacity = '1';
  }
}

export default ChatMessageHandler;
