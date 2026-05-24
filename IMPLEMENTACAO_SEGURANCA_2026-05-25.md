# 🔐 Implementação de Segurança de Dados e Fluxo de Aprovação

**Data:** 25 de maio de 2026  
**Commit:** e0bc379  
**Desenvolvedor:** Arquiteto de Software Sênior

---

## 📋 Resumo Executivo

Implementação completa de 4 requisitos críticos de segurança e governança de dados no CRM Terreiro, com foco em:
- ✅ Proteção de dados pessoais com fluxo de aprovação
- ✅ Restrição de acesso baseado em role (RBAC)
- ✅ Marca d'água anti-captura de tela
- ✅ Interface amigável para listas vazias

---

## 🎯 Requisitos Implementados

### 1. FLUXO DE PERFIL COM APROVAÇÃO (usuarios.php)

**Para Usuários Comuns (role='user'):**
- Interface restrita mostrando apenas seu próprio perfil
- Botão "Solicitar Alterações" abre formulário simples
- Campos editáveis: Nome, Email (envio para `profile_pending_changes`)
- Alerta exibindo status das alterações pendentes
- Link para visualizar detalhes das solicitações em revisão

**Para Administradores:**
- Acesso completo à lista de todos os usuários
- Botões Editar/Excluir funcionam normalmente
- Sem restrições de visualização

**Fluxo Técnico:**
```
Usuário Comum → Clica "Solicitar Alterações" 
  → Modal com campos (nome, email)
  → Envia para POST api/profile-changes.php?action=request
  → Salva em `profile_pending_changes` com status='pending'
  → Exibe: "✓ Alterações enviadas para aprovação"
```

**Segurança:**
- Validação de role (`$currentUserRole === 'user'`)
- Prepared statements com PDO
- HTML entities (escapeHtml()) em exibições
- Força ID de sessão, ignora URL maliciosa

---

### 2. ALERTA DE PENDÊNCIAS NO DASHBOARD (dashboard.php)

**Apenas para Administrador:**

Banner elegante no topo (Logo após header):
- **Cor:** Gradiente amber-50 → orange-50, borda amber-200
- **Ícone:** Exclamation Triangle (Font Awesome)
- **Conteúdo:**
  - Contagem: "X Alteração(ões) de Perfil Aguardando Aprovação"
  - Descrição: "Usuários enviaram solicitações de alteração..."
  - Link: "Revisar alterações →" (aponta para usuarios.php)
  - Botão fechar (remove do DOM)

**Exemplo:**
```
⚠️ 3 Alterações de Perfil Aguardando Aprovação
   Usuários enviaram solicitações de alteração de perfil 
   que estão pendentes de sua revisão.
   Revisar alterações →
```

**Implementação:**
```php
$pendencyCount = 0;
if ($isAdmin) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count FROM profile_pending_changes 
         WHERE status = 'pending'"
    );
    $stmt->execute();
    $pendencyCount = (int)($stmt->fetch()['count'] ?? 0);
}

// Renderiza banner apenas se $pendencyCount > 0
```

---

### 3. RESTRIÇÃO DE ACESSO AOS RELATÓRIOS (relatorios.php)

**Para Usuários Comuns (role='user'):**
- Visualizam apenas relatórios **já gerados** pelo admin
- **OCULTO:** Botão "Filtrar"
- **OCULTO:** Botão "Exportar CSV"
- **Exibido:** Mensagem "Relatórios de trabalhos são gerados apenas pelo administrador"
- **Interface Lista Vazia:** SVG de pasta vazia + texto descritivo

**Para Administradores:**
- Acesso completo
- Botões de filtro e exportação visíveis e funcionais
- Seção de "Geração" de relatórios ativa

**Mensagem Diferenciada por Role:**
```javascript
// Para usuário comum
"Nenhum relatório de trabalhos foi gerado para o período solicitado. 
 Relatórios são gerados pelo administrador."

// Para admin
"Nenhum relatório de trabalhos foi gerado para o período solicitado. 
 Crie filtros diferentes e tente novamente."
```

---

### 4. MARCA D'ÁGUA DE SEGURANÇA (relatorios.php)

**Objetivo:** Inibir capturas de tela não autorizadas

**Implementação CSS:**
```css
body::before {
    content: '<?= htmlspecialchars($currentUserName) ?>';
    position: fixed;
    width: 100%;
    height: 100%;
    opacity: 0.08;
    font-size: 4rem;
    transform: rotate(-45deg);
    z-index: 5;
    pointer-events: none;
    /* ... mais estilos */
}
```

**Características:**
- ✅ Nome do usuário logado (de `$_SESSION['name']` ou `$_SESSION['nome']`)
- ✅ Opacity 0.08 (muito sutil, não atrapalha leitura)
- ✅ Z-index 5 (abaixo do conteúdo principal)
- ✅ Rotação -45 graus (diagonalmente)
- ✅ Fonte grande (4rem) e peso leve (300)
- ✅ Cobre toda a viewport (fixed, width/height 100%)
- ✅ user-select: none (não selecionável)

**Resultado Visual:**
Quando um usuário tira screenshot da página, o nome dele aparece repetidamente de forma sutil no fundo, marcando a captura como rastreável.

---

## 🗄️ Estrutura de Banco de Dados

### Tabela: `profile_pending_changes`

```sql
CREATE TABLE profile_pending_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                    -- FK users.id
    name VARCHAR(255) NULL,                  -- Novo nome solicitado
    email VARCHAR(255) NULL,                 -- Novo email solicitado
    foto_perfil VARCHAR(512) NULL,           -- Foto nova
    password_hash VARCHAR(255) NULL,         -- Hash da senha nova
    other_data JSON NULL,                    -- Campos customizados
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,              -- Quando foi revisado
    reviewed_by INT NULL,                    -- FK users.id (admin que revisou)
    review_notes TEXT NULL,                  -- Motivo de rejeição
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Índices para Performance:**
- `idx_user_id` (buscar alterações por usuário)
- `idx_status` (listar pendências)
- `idx_requested_at` (ordenar cronologicamente)

**Foreign Keys:**
- `user_id` → users.id (ON DELETE CASCADE)
- `reviewed_by` → users.id (ON DELETE SET NULL)

---

## 🔧 Arquivos Modificados

| Arquivo | Mudanças |
|---------|----------|
| `usuarios.php` | +191 linhas, -43 linhas (fluxo restrito para comum) |
| `dashboard.php` | +22 linhas (lógica e banner de alerta) |
| `relatorios.php` | +104 linhas (marca d'água, restrições, lista vazia) |
| `api/auto_migrate.php` | +30 linhas (migração automática de tabela) |
| **NEW:** `api/migrate-profile-changes.php` | +35 linhas (script de migração standalone) |
| **NEW:** `database/migrate_2026_05_25_profile_pending_changes.sql` | Arquivo SQL de migração |

---

## 🔒 Segurança Implementada

### Prepared Statements
✅ Todas as queries usam prepared statements (PDO)
```php
$stmt = $pdo->prepare("SELECT ... FROM profile_pending_changes WHERE user_id = ?");
$stmt->execute([$userId]);
```

### Validação de Role
✅ Verificação de sessão em cada ponto de entrada
```php
if ($currentUserRole !== 'admin') {
    // Redirecionar ou restringir
}
```

### HTML Escaping
✅ Prevenção de XSS em exibições
```javascript
function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text || '').replace(/[&<>"']/g, m => map[m]);
}
```

### Marca d'água
✅ Uso de htmlspecialchars() no PHP
```php
content: '<?= htmlspecialchars($currentUserName) ?>'
```

---

## 📱 Interface do Usuário

### 1️⃣ Usuário Comum em `usuarios.php`

```
┌─────────────────────────────────────┐
│        Meu Perfil                   │
├─────────────────────────────────────┤
│                                     │
│ ⏳ Alterações Aguardando Aprovação  │
│    Você enviou alterações ao seu   │
│    perfil que estão aguardando...   │
│    Ver detalhes →                   │
│                                     │
├─────────────────────────────────────┤
│                                     │
│ Nome: João Silva                    │
│ Email: joao@example.com             │
│ Telefone: (11) 99999-9999           │
│                                     │
│                [Solicitar Alterações]│
│                                     │
└─────────────────────────────────────┘
```

### 2️⃣ Admin em `dashboard.php`

```
┌──────────────────────────────────────────┐
│ ⚠️ 3 Alterações de Perfil Aguardando     │
│    Aprovação                            │
│                                         │
│ Usuários enviaram solicitações...       │
│ Revisar alterações →                [×]│
└──────────────────────────────────────────┘
```

### 3️⃣ Usuário Comum em `relatorios.php` (Lista Vazia)

```
┌──────────────────────────────────────────┐
│                                         │
│              📁                         │
│                                         │
│     Nenhum relatório disponível         │
│                                         │
│  Nenhum relatório de trabalhos foi      │
│  gerado para o período solicitado.      │
│  Relatórios são gerados pelo admin.     │
│                                         │
└──────────────────────────────────────────┘
```

---

## 🚀 Como Usar

### Para Usuários Comuns

1. **Acessar perfil pessoal:**
   - Vá para "Usuários" → Vê apenas seu próprio perfil

2. **Solicitar alterações:**
   - Clique em "Solicitar Alterações"
   - Preencha Nome e Email (deixe em branco se não quiser alterar)
   - Clique em "Enviar"
   - Receberá confirmação: "✓ Alterações enviadas para aprovação"

3. **Acompanhar status:**
   - Volte para "Usuários"
   - Veja o alerta "Alterações Aguardando Aprovação"
   - Clique em "Ver detalhes" para ver o que foi solicitado

### Para Administradores

1. **Dashboard:**
   - Vê banner com contagem de pendências
   - Clica em "Revisar alterações" ou vai direto para `usuarios.php`

2. **Revisar alterações:**
   - Acessa `usuarios.php`
   - Clica em "Usuários" no menu → "Aprovar/Rejeitar Pendências"
   - Visualiza cada solicitação
   - Aprova (atualiza users) ou Rejeita (com nota)

3. **Gerar relatórios:**
   - Acesso completo ao sistema de relatórios
   - Pode gerar, filtrar e exportar normalmente

---

## 🧪 Testes Recomendados

### Teste 1: Restrição de Acesso
```bash
# Como usuário comum
1. Acessa usuarios.php
2. Verifica que VÊ apenas seu perfil (não a lista)
3. Clica "Solicitar Alterações"
4. Preenche nome/email
5. Envia e vê confirmação ✓
```

### Teste 2: Banner no Dashboard
```bash
# Como admin (com pendências criadas no Teste 1)
1. Acessa dashboard.php
2. Vê banner amarelo no topo: "X Alterações..."
3. Clica no link → vai para usuarios.php
4. Fecha o alerta com [×]
```

### Teste 3: Restrição de Relatórios
```bash
# Como usuário comum
1. Acessa relatorios.php
2. Vê que botão "Filtrar" está OCULTO
3. Vê mensagem: "Relatórios são gerados apenas..."
4. Marca d'água visível no fundo (nome do usuário)
5. Tira screenshot → nome aparece na imagem ✓
```

### Teste 4: Migração Automática
```bash
# Primeira vez que um usuário comum acessa uma página
1. auto_migrate.php roda silenciosamente
2. Tabela profile_pending_changes é criada
3. Sem erro na página ✓
```

---

## 📚 Documentação Técnica

### Session Variables Used

```php
$_SESSION['user_id']      // ID do usuário (int)
$_SESSION['user_role']    // 'admin', 'staff', ou 'user'
$_SESSION['nome']         // Nome do usuário (string)
$_SESSION['name']         // Alternativa (string)
$_SESSION['_auto_migrated'] // Flag de migração (bool)
```

### API Endpoints Utilizados

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `api/users.php?action=list` | GET | Listar usuários (restrito a próprio ou admin) |
| `api/profile-changes.php?action=list` | GET | Listar alterações pendentes (admin only) |
| `api/profile-changes.php` | POST `action=request` | Enviar solicitação de alteração |
| `api/profile-changes.php` | POST `action=approve` | Aprovar alteração (admin) |
| `api/profile-changes.php` | POST `action=reject` | Rejeitar alteração (admin) |

---

## 🎨 Tailwind CSS Classes Utilizados

- ✅ `bg-amber-50 border-amber-200` – Banner de alerta
- ✅ `text-amber-900 text-amber-700` – Texto de alerta
- ✅ `rounded-2xl p-6 shadow-lg` – Card de perfil
- ✅ `grid grid-cols-1 md:grid-cols-2` – Layout responsivo
- ✅ `flex items-center justify-between` – Alinhamento flexbox
- ✅ `hidden` / `flex` – Show/hide condicional

**Sem uso de Bootstrap!** ✅ Apenas Tailwind CSS nativo.

---

## ⚠️ Notas Importantes

### Progressão Gradual
Se um usuário comum não tiver JavaScript habilitado:
- Formulário de "Solicitar Alterações" não abre
- Mas o back-end já está pronto para receber (graceful degradation)

### Performance
- Migração automática roda apenas 1x por sessão (flag `_auto_migrated`)
- Contagem de pendências usa INDEX `idx_status`
- Sem N+1 queries, todas otimizadas

### Compatibilidade
- Funciona em PHP 7.4+
- PDO necessário (disponível no projeto)
- MySQL 5.7+ (ENUM, JSON suportado)

---

## 📞 Suporte

Para questões sobre implementação:
1. Verificar logs em `/storage/logs/php_errors.log`
2. Consultar arquivo de migração: `database/migrate_2026_05_25_profile_pending_changes.sql`
3. Revisar script auto-migrate em `api/auto_migrate.php`

---

**Implementação concluída com sucesso! 🎉**
