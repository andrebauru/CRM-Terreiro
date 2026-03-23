# Atualização: Módulo Financeiro Split com Recibos e Integração com Trabalhos

## 📋 Resumo das Mudanças

Este update implementa um módulo financeiro completo com:
- **Divisão de Ganhos (Split)** com cálculo automático de impostos (Gensen Choshu 10.21%)
- **Geração de Recibos Bilíngues** (Português/Japonês) em PDF
- **Integração com Trabalhos** para prefill automático de dados
- **Fluxo de Pagamento** com regeneração de recibos ao marcar como pago
- **Dashboard Médium** com resumo financeiro

---

## 🔧 Como Atualizar

### 1. **Fazer Pull das Mudanças**

```bash
git pull origin master
```

### 2. **Executar Migrações do Banco de Dados**

#### Opção A: Via Script PHP (Recomendado - Auto-migração)

```bash
php migrate.php
```

Isso criará/atualizará automaticamente as tabelas:
- `medium_configs` — configuração de percentuais por médium
- `financial_transactions` — registros de transações financeiras com split
- Colunas adicionais em `financial_transactions`:
  - `cliente_nome`, `cliente_telefone`, `descricao_servico`
  - `receipt_path`, `data_pagamento`

#### Opção B: Via API Auto-migrate (Usado na inicialização)

A rota `api/auto_migrate.php?action=migrate` também executa as migrações automaticamente quando acessada.

#### Opção C: SQL Manual (Se necessário)

Executar o arquivo SQL de migração:

```bash
mysql -u usuario -p banco_de_dados < database/migrate_2026_03_24_financial_split.sql
```

### 3. **Verificar a Instalação**

Abrir o navegador e acessar:
- `http://seu-crm/financeiro.php` — novo módulo financeiro

Se houver banco de dados local, a migração ocorrerá automaticamente na primeira abertura.

---

## 📁 Arquivos Adicionados/Modificados

### Novos Arquivos

| Arquivo | Descrição |
|---------|-----------|
| `app/Helpers/FinanceSplit.php` | Lógica de cálculo de split e impostos Gensen (10.21%) |
| `app/Helpers/FinancialReceipt.php` | Geração de recibos em PDF (Dompdf) |
| `ryoushuusho.php` | Página de visualização bilíngue de recibos |
| `database/migrate_2026_03_24_financial_split.sql` | Schema SQL das novas tabelas |

### Arquivos Modificados

| Arquivo | Alterações |
|---------|-----------|
| `api/financeiro.php` | +6 novos actions para split, config, transações, recibos |
| `financeiro.php` | Nova aba "Split / Recibos" com UI completa |
| `api/trabalhos.php` | Retorna `client_id` e `attendance_id` na listagem |
| `trabalhos.php` | Botão ¥ para abrir financeiro prefillado; botão no detalhe |
| `api/dashboard.php` | Novo resumo médium com totalizações |
| `dashboard.php` | Card "Resumo do Médium" |
| `migrate.php` | Criar tabelas de split financeiro |
| `api/auto_migrate.php` | Auto-migração de tabelas |

---

## 🎯 Fluxo de Uso

### Workflow 1: Registrar um Trabalho e Gerar Recibo

1. **Trabalhos** → Novo Trabalho → preenche cliente, data, valor
2. **Tabela de Trabalhos** → Clica no ícone **¥ (Yen)**
3. **Financeiro** → Abre na aba **Split / Recibos** com dados prefillados
4. **Split** → Revisa percentuais, clica **Salvar**
5. ✅ Recibo gerado automaticamente e salvo em `recibos/YYYY/MM/phone_N.pdf`

### Workflow 2: Marcar Trabalho Como Pago

1. Na tabela de transações (aba Split), mudar status para **Pago**
2. Automaticamente:
   - Data de pagamento é carimb ada (ou usa data atual)
   - Recibo é regenerado com data de pagamento
   - Toast confirma: "Pago confirmado e recibo atualizado"
   - Recibo abre em nova aba para visualização

### Workflow 3: Configurar Percentuais do Médium

1. **Financeiro** → Aba **Split / Recibos**
2. Seção "Configuração de Percentuais"
3. Ajusta: Espaço, Treinamento, Material, Tata, Executor
4. Clica **Salvar Percentuais**
5. Atualiza preview e futuros registros usarão esses percentuais

---

## 📊 Estrutura de Dados

### Tabela: `medium_configs`

```sql
CREATE TABLE medium_configs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  pct_espaco DECIMAL(5,2) DEFAULT 20,
  pct_treinamento DECIMAL(5,2) DEFAULT 10,
  pct_material DECIMAL(5,2) DEFAULT 20,
  pct_tata DECIMAL(5,2) DEFAULT 10,
  pct_executor DECIMAL(5,2) DEFAULT 40,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user (user_id)
);
```

### Tabela: `financial_transactions`

```sql
CREATE TABLE financial_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  medium_id INT NOT NULL,
  tata_id INT,
  cliente_nome VARCHAR(255),
  cliente_telefone VARCHAR(50),
  descricao_servico VARCHAR(500),
  valor_total INT NOT NULL,
  taxa_gensen_paga INT DEFAULT 0,
  valor_liquido_medium INT DEFAULT 0,
  valor_liquido_tata INT DEFAULT 0,
  status_pagamento VARCHAR(20) DEFAULT 'pendente',
  data_realizacao DATE NOT NULL,
  data_pagamento DATE,
  receipt_path VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 💰 Cálculo de Split (Gensen Choshu)

### Estrutura Padrão

| Conceito | Percentual | Taxado? |
|----------|-----------|---------|
| Espaço | 20% | ❌ Não |
| Treinamento | 10% | ❌ Não |
| Material | 20% | ❌ Não |
| **Tata** | **10%** | ✅ Sim (10.21%) |
| **Executor** | **40%** | ✅ Sim (10.21%) |

### Exemplo de Cálculo

Valor Total: ¥100.000

| Item | Bruto | Imposto | Líquido |
|------|-------|---------|---------|
| Espaço | ¥20.000 | — | ¥20.000 |
| Treinamento | ¥10.000 | — | ¥10.000 |
| Material | ¥20.000 | — | ¥20.000 |
| Tata | ¥10.000 | ¥1.021 | ¥8.979 |
| Executor | ¥40.000 | ¥4.084 | ¥35.916 |
| **Total Zeimusho** | — | **¥5.105** | **¥94.895** |

---

## 📄 Recibos (Ryoushuusho)

### Local de Armazenamento

```
recibos/
├── 2026/
│   ├── 03/
│   │   ├── 819012345678_1.pdf
│   │   ├── 819012345678_2.pdf
│   │   └── 819087654321_1.pdf
```

**Nomeação:** `{telefone_sanitizado}_{sequencial}.pdf`

### Conteúdo Bilíngue

- **Cabeçalho:** Ryoushuusho (領収書) — Recibo
- **Dados:** Cliente, Data, Valor, Impostos
- **Divisões:** Espaço, Treinamento, Material, Tata (c/ imposto), Executor (c/ imposto)
- **Rodapé:** Área para Hanko (selim), nota fiscal e observações

---

## ⚙️ Configuração Recomendada

### Variáveis de Ambiente (se usar)

Nenhuma obrigatória. A constante `CRM_GENSEN_RATE` está hardcoded como `0.1021` em `app/Helpers/FinanceSplit.php`.

### Permissões

- **Admin:** acesso total ao módulo
- **Médium/User:** acesso apenas aos próprios registros

---

## 🐛 Troubleshooting

### Erro: "Tabelas não encontradas"

**Solução:** Rodar `php migrate.php` novamente.

### Erro: "Acesso negado ao atualizar split"

**Causa:** Usuário não é admin e tenta acessar split de outro médium.

**Solução:** Logar como o médium correto ou usar admin.

### Recibos não salvam em `recibos/`

**Causa:** Pasta sem permissão de escrita.

**Solução:** 
```bash
chmod 755 recibos/
mkdir -p recibos/2026/03
chmod 777 recibos/
```

### PDF não gera (erro Dompdf)

**Causa:** Dompdf não instalado ou erro no `composer.json`.

**Solução:** 
```bash
composer install
```

---

## 📝 Changelog

### v1.0 (24 de Março de 2026)

✅ Implementação inicial
- Módulo Split com cálculo Gensen 10.21%
- Geração de recibos bilíngues em PDF
- Integração trabalhos ↔ financeiro
- Dashboard médium
- Prefill automático ao abrir de trabalhos
- Regeneração de recibo ao marcar pago

---

## 📞 Suporte

Para dúvidas ou issues:
1. Verificar console do navegador (F12 → Console)
2. Verificar logs em `storage/logs/`
3. Validar sintaxe PHP: `php -l app/Helpers/FinanceSplit.php`

---

**Desenvolvido para CRM Terreiro — Março 2026**
