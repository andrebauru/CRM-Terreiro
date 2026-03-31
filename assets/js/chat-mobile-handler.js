/**
 * Chat Mobile Handler
 * Gerencia comportamento específico de mobile
 */

export class ChatMobileHandler {
  constructor(config = {}) {
    this.config = {
      mobileBreakpoint: 768,
      ...config
    };
    
    this.isMobile = window.innerWidth < this.config.mobileBreakpoint;
    this.selectedUserId = null;
  }
  
  /**
   * Atualizar seleção de usuário
   */
  selectUser(userId) {
    this.selectedUserId = userId;
    console.log('[MobileHandler] Usuário selecionado:', userId);
  }
  
  /**
   * Limpar seleção
   */
  clearSelection() {
    this.selectedUserId = null;
    console.log('[MobileHandler] Seleção limpa');
  }
  
  /**
   * Verificar se está em mobile
   */
  checkMobile() {
    const wasMobile = this.isMobile;
    this.isMobile = window.innerWidth < this.config.mobileBreakpoint;
    
    if (wasMobile !== this.isMobile) {
      console.log(`[MobileHandler] Modo mudou para ${this.isMobile ? 'mobile' : 'desktop'}`);
    }
    
    return this.isMobile;
  }
  
  /**
   * Scroll para o final das mensagens (mobile)
   */
  scrollToBottom(element) {
    if (!element || !this.isMobile) return;
    
    setTimeout(() => {
      if (element) {
        element.scrollTop = element.scrollHeight;
      }
    }, 100);
  }
  
  /**
   * Esconder teclado (mobile)
   */
  hideKeyboard() {
    const inputs = document.querySelectorAll('input, textarea');
    inputs.forEach(input => {
      if (input === document.activeElement) {
        input.blur();
      }
    });
  }
}

export default ChatMobileHandler;
