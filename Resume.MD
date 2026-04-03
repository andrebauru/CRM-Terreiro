# Resume — Consolidação de Documentação do CRM Terreiro

Este arquivo consolida os conteúdos dos arquivos `.MD` da raiz do projeto (exceto `README.md`), conforme solicitado.

---

## Fontes consolidadas

- `ATUALIZACAO_MODULO_FINANCEIRO.md`
- `FIREBASE_CORS_FIX.md`
- `INTEGRACAO_SENDGRID.md`
- `GEMINI.md`

---

## 1) Atualização do Módulo Financeiro (Split, Recibos, Integração com Trabalhos)

### O que foi implementado
- Módulo financeiro com **split de ganhos**.
- Cálculo automático de imposto **Gensen Choshu (10.21%)**.
- Geração de **recibos bilíngues (PT/JP) em PDF**.
- Integração com `trabalhos` para **prefill** de dados.
- Fluxo de pagamento com **regeneração de recibo** ao marcar como pago.
- Resumo financeiro no dashboard para médium.

### Migração / atualização
- Rodar `php migrate.php` (recomendado).
- Alternativa por auto-migração via `api/auto_migrate.php`.
- Script SQL disponível em `database/migrate_2026_03_24_financial_split.sql`.

### Principais arquivos
- Novos:
  - `app/Helpers/FinanceSplit.php`
  - `app/Helpers/FinancialReceipt.php`
  - `ryoushuusho.php`
  - `database/migrate_2026_03_24_financial_split.sql`
- Atualizados:
  - `api/financeiro.php`
  - `financeiro.php`
  - `api/trabalhos.php`
  - `trabalhos.php`
  - `api/dashboard.php`
  - `dashboard.php`
  - `migrate.php`
  - `api/auto_migrate.php`

### Estruturas de dados relevantes
- `medium_configs` (percentuais por médium)
- `financial_transactions` (transações, status, imposto, recibo)

### Observações operacionais
- Recibos em `recibos/YYYY/MM/`.
- Permissões: admin (total), user/médium (próprios registros).
- Em caso de erro de geração de PDF: validar Dompdf / `composer install`.

---

## 2) Correção CORS Firebase Storage

### Problema
Erros CORS ao enviar mídia para `firebasestorage.googleapis.com` a partir do domínio do CRM.

### Soluções documentadas
1. **Atualizar regras do Firebase Storage** (recomendado).
2. Configurar CORS diretamente no bucket via `gsutil cors set`.
3. Fallback com upload por backend (`api/upload.php`).

### Checklist de validação
- Regras publicadas no Firebase.
- Cache do navegador limpo.
- Upload testado com status 200 na aba Network.
- Sem erro de CORS no console.

### Notas mobile
- Considerar timeout maior e retry de upload em conexões instáveis.

---

## 3) Integração SendGrid (Notificações)

### Objetivo
Disparar e-mail automaticamente para mudanças em **Avisos** e **Giras**.

### Itens implementados
- Campos na página de configurações:
  - `notification_email`
  - `sendgrid_api_key`
- Helper novo: `app/Helpers/SendGridNotifier.php`
- Disparo em `create/update/delete` de:
  - `api/avisos.php`
  - `api/giras.php`

### Segurança e comportamento
- API key não é devolvida em GET (apenas indicador `has_sendgrid_api_key`).
- Se configuração faltar, fluxo segue sem quebrar (degradação graciosa).
- Falhas de envio são registradas em log.

### Migração
- Colunas adicionadas em `settings`.
- Script: `database/migrate_2026_03_26_sendgrid_notifications.sql`.
- Coberto também por auto-migração.

---

## 4) Contexto Geral do Projeto (CRM Terreiro)

### Visão geral
Sistema de gestão para terreiro com módulos de:
- Filhos da casa
- Trabalhos
- Mensalidades
- Atendimentos
- Usuários
- Relatórios
- Financeiro

### Stack
- Backend: PHP 8.2+ (PDO)
- Frontend: HTML + Tailwind + Font Awesome
- Banco: MySQL 8
- Auth: sessão PHP

### Convenções
- APIs retornam JSON no padrão `{ ok: true|false, ... }`.
- Uso de helpers globais para modal, data e moeda.
- Estratégia de schema com auto-criação/migração quando necessário.

---

## Changelog da consolidação

- Arquivo criado para centralizar documentação operacional e técnica.
- Conteúdo agrupado por tema para facilitar manutenção.
- Arquivos markdown redundantes da raiz foram removidos após consolidação.
