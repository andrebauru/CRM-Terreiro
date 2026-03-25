# Integração SendGrid - Notificações por E-mail

## Resumo
Implementada a integração automática com a API SendGrid para enviar notificações por e-mail sempre que houver alterações nas abas de **Avisos** e **Giras**.

## Alterações Realizadas

### 1. Campos de Configuração
Na página **Configurações** (`configuracoes.php`), foram adicionados dois novos campos:

- **E-mail para notificações**: Endereço de e-mail que receberá as notificações
- **API Key SendGrid**: Chave de autenticação para a API SendGrid

> A API Key não é exibida na resposta de GET, apenas confirmado que está cadastrada (segurança)

### 2. Helper de Notificação
Novo arquivo: `app/Helpers/SendGridNotifier.php`

Função: `sendGridNotifyBoard(PDO $pdo, string $section, string $action, string $title, string $details = '')`

**Funcionalidade:**
- Busca automaticamente as credenciais (e-mail e API key) da tabela `settings`
- Se não estiverem configuradas, a função retorna sem erro (degradação graciosa)
- Envia e-mail via HTTPS (cURL ou fallback stream context)
- Formata HTML com cabeçalho destacado "**Quadro de Avisos**" e seção em vermelho (ex: "**AVISOS**")
- Suporta ações: `create`, `update`, `delete`

### 3. Disparos Automáticos

#### 3a. Avisos (`api/avisos.php`)
- **CREATE**: Ao criar um novo aviso, envia e-mail com título e mensagem
- **UPDATE**: Ao editar um aviso, notifica mudanças
- **DELETE**: Ao remover um aviso, registra no e-mail qual foi deletado

#### 3b. Giras (`api/giras.php`)
- **CREATE**: Ao criar nova gira, envia detalhes (tipo, data de realização, plataforma, descrição)
- **UPDATE**: Ao atualizar gira, notifica as mudanças
- **DELETE**: Ao remover gira, captura e envia os dados antes da exclusão

### 4. Estrutura do E-mail
```
Quadro de Avisos                   (Título destacado)
─────────────────
AVISOS / GIRAS                     (Seção em VERMELHO #dc2626)
Ação: Criado | Atualizado | Removido
Título: [Título do aviso/gira]
Detalhes: [Mensagem/descrição com quebras de linha preservadas]
```

### 5. Tabela de Configurações (Schema Update)
Novas colunas:
```sql
ALTER TABLE settings
ADD COLUMN notification_email VARCHAR(255) NULL AFTER logo_path,
ADD COLUMN sendgrid_api_key TEXT NULL AFTER notification_email;
```

Script de migração: `database/migrate_2026_03_26_sendgrid_notifications.sql`

### 6. Auto-migração
Os arquivos `api/auto_migrate.php` e `api/settings.php` já garantem a criação das colunas automaticamente ao acessar qualquer página protegida ou ao salvar configurações.

## Como Configurar

1. **Obter API Key SendGrid**:
   - Acesse https://sendgrid.com
   - Crie uma conta ou faça login
   - Gere uma chave de API (Settings → API Keys)

2. **Configurar no CRM**:
   - Acesse **Configurações**
   - Preencha **E-mail para notificações** (seu e-mail corporativo)
   - Cole a **API Key SendGrid** no campo correspondente
   - Clique em **Salvar**

3. **Validar**:
   - Crie um novo aviso ou gira
   - Verifique se o e-mail foi recebido na caixa de entrada configurada

## Detalhes Técnicos

### Tratamento de Erros
- Se API key ou e-mail não estiverem configurados, a função retorna silenciosamente (sem lançar exceção)
- Erros de envio são registrados em `storage/logs/php_errors.log`
- Fallbacks: curl primeiro, depois stream context como alternativa

### Segurança
- API Key não é exposta via API `GET /api/settings.php`
- Resposta indica apenas `has_sendgrid_api_key: true/false`
- E-mails são escapados e sanitizados

### Performance
- Envio é síncrono (bloqueia até receber resposta da SendGrid)
- Timeout de 20 segundos para evitar travamentos
- Se falhar, não afeta o fluxo de criação/atualização (apenas registra erro)

## Formato de E-mail Enviado

### Avisos
```
Quadro de Avisos
AVISOS
Ação: Criado
Título: [Novo aviso]
Detalhes: [Mensagem do aviso]
```

### Giras
```
Quadro de Avisos
GIRAS
Ação: Atualizado
Título: Festa de Orixás
Detalhes: 
  Data de realização: 2026-04-15
  Data de postagem: 2026-03-28
  Plataforma: Instagram
  Descrição: Celebração especial
```

## Arquivos Modificados
- `app/Helpers/SendGridNotifier.php` (novo)
- `api/settings.php` (persistência de credenciais)
- `api/avisos.php` (disparo em create/update/delete)
- `api/giras.php` (disparo em create/update/delete)
- `configuracoes.php` (campos de entrada)
- `migrate.php` (schema update)
- `api/auto_migrate.php` (auto-create columes)
- `database/migrate_2026_03_26_sendgrid_notifications.sql` (migration script)

## Próximas Melhorias (Opcional)
- [ ] Adicionar campo de "test" na configuração para validar credenciais
- [ ] Log de e-mails enviados (tabela `notification_logs`)
- [ ] Filtros/permissões por tipo de notificação
- [ ] Templates de e-mail customizáveis
- [ ] Envio assíncrono via fila (para não bloquear)

## Notas
- O campo de API Key usa `type="password"` por segurança (não exibe o texto)
- A senha já cadastrada não é exibida ao voltar à página (apenas confirmado que existe)
- Compatível com PHP 7.4+ (testes em PHP 8.1+)
