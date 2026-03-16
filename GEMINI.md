# Contexto do Projeto: CRM Terreiro

Este documento fornece um contexto detalhado do projeto "CRM Terreiro", um sistema de gestão desenvolvido para terreiros de Umbanda e Quimbanda.

## 1. Visão Geral do Projeto

O CRM Terreiro é uma aplicação monolítica projetada para auxiliar na gestão de terreiros, controlando aspectos como:
- Filhos da casa e seus graus de iniciação.
- Trabalhos espirituais agendados e realizados.
- Controle de mensalidades e lançamentos financeiros.
- Registro de atendimentos e consulentes.
- Gestão de usuários e relatórios.

### Tecnologias Principais:
- **Backend:** PHP 8.2+ (PDO, sem uso de framework MVC).
- **Frontend:** HTML5, Tailwind CSS (via CDN) para estilização, e Font Awesome 6.5 para ícones.
- **Banco de Dados:** MySQL 8.
- **Autenticação:** Baseada em sessões PHP.

A aplicação é caracterizada por uma abordagem de "schema-on-access", onde as tabelas do banco de dados são criadas automaticamente na primeira vez que uma página PHP que as utiliza é acessada (`CREATE TABLE IF NOT EXISTS`).

## 2. Estrutura de Arquivos Principal

A estrutura do projeto segue um padrão com arquivos PHP e HTML na raiz para as principais funcionalidades, e uma pasta `app/` para componentes de backend (Controladores, Modelos, Helpers, Configurações).

```
CRM-Terreiro/
├── index.html               # Página de Login
├── dashboard.html/.php      # Dashboard com visão geral
│
├── filhos.html/.php         # Gestão de filhos da casa
├── quimbandeiro.html/.php   # Gestão de graus de iniciação
├── mensalidades.html/.php   # Controle de mensalidades
│
├── trabalhos.html/.php      # Agendamento e gestão de trabalhos
├── services.php             # Backend do catálogo de serviços
│
├── clientes.html            # Gestão de consulentes/clientes
├── clients.php
├── atendimentos.html        # Registro de atendimentos
├── attendances.php
│
├── usuarios.html            # Controle de usuários
├── users.php
├── relatorios.html          # Relatórios
├── reports.php
├── configuracoes.html       # Configurações do sistema
├── settings.php
│
├── db.php                   # Conexão PDO e helpers
├── backup.php               # Backup do banco de dados
├── migrate.php              # Migrações pontuais
│
├── app/                     # Lógica de Backend (Controladores, Modelos, Helpers, Helpers, Views)
│   ├── config.php
│   ├── database.php
│   ├── router.php
│   └── Controllers/
│   └── Helpers/
│   └── Models/
│   └── views/
│
├── database/                # Scripts SQL de schema e dados
│   ├── schema_completo.sql
│   └── ...
│
├── public/                  # Arquivos públicos e assets
│   ├── index.php            # Ponto de entrada do sistema
│   └── static/              # CSS, JS, Imagens
│
└── frontend/                # Projeto Frontend (Vite/React - legado, atualmente não utilizado ativamente no fluxo principal)
    ├── package.json
    ├── src/
    └── ...
```

## 3. Construção e Execução do Projeto

### Pré-requisitos
- **PHP:** Versão 8.2 ou superior, com a extensão `pdo_mysql` habilitada.
- **MySQL:** Versão 8 ou superior.
- **Servidor Web:** Apache, Nginx, ou o servidor embutido do PHP.

### Configuração do Banco de Dados

1.  **Clone o repositório:**
    ```bash
    git clone <url_do_repositorio>
    cd CRM-Terreiro
    ```
2.  **Crie o arquivo `.env`** a partir de `.env.example` e configure as credenciais do seu banco de dados:
    ```
    DB_HOST=localhost
    DB_NAME=crm_terreiro
    DB_USER=root
    DB_PASS=senha
    ```
    Alternativamente, as configurações podem ser feitas diretamente em `db.php`.
3.  **Crie o banco de dados MySQL:**
    ```sql
    CREATE DATABASE crm_terreiro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ```
4.  **Opcional: Importar schema manualmente:**
    ```bash
    mysql -u root -p crm_terreiro < database/schema_completo.sql
    ```
    *Note:* As tabelas serão criadas automaticamente na primeira requisição, então este passo não é estritamente necessário para o funcionamento básico.

### Executando a Aplicação

Para iniciar o servidor web embutido do PHP:
```bash
php -S localhost:8000 -t public/
```
Acesse a aplicação em `http://localhost:8000`.

### Credenciais Padrão (pós-execução de `migrate.php`)
- **Email:** `admin@terreiro.com`
- **Senha:** `123456`
- **Perfil:** `Administrador`
*Recomenda-se alterar a senha após o primeiro acesso.*

## 4. Convenções de Desenvolvimento

### Padrão de Resposta da API PHP
Todos os endpoints PHP retornam respostas em formato JSON com a seguinte estrutura:
- **Sucesso:**
    ```json
    { "ok": true, "data": [...] }
    { "ok": true, "id": 42 }
    ```
- **Erro:**
    ```json
    { "ok": false, "message": "Erro descritivo" }
    ```
Funções auxiliares para conexão com DB e respostas JSON são definidas em `db.php`.

### Padrões de Componentes e UI
- **FAB (Floating Action Button):** Um botão `+` fixo no canto inferior direito está presente em todas as páginas para ações rápidas.
- **Modais de Detalhe:** Clicar em qualquer linha de tabela geralmente abre um modal com detalhes e opções de edição/exclusão.
- **Valores Monetários:** Armazenados como `INT` em centavos (JPY) no backend e formatados como `¥x` no frontend.
- **Funções JavaScript Comuns:**
    - `toggleModal(el, show)`: Controla a visibilidade de modais.
    - `formatBRL(value)`: Formata valores de centavos para "¥x".
    - `parseBRL(value)`: Converte "¥x" para centavos.
    - `fmtDate(dateStr)`: Formata datas.
    - `loadBrand()`: Carrega nome e logo do terreiro das configurações.

### Fluxo de Criação de Dados
- Ao criar um novo "filho", o sistema automaticamente gera um registro em `mensalidades_lancamentos` e em `quimbandeiro_graus`.

## 5. Histórico de Versões e Changelog
O projeto passou por diversas iterações, incluindo uma fase com React no frontend (v1.0), que foi refatorada para uma abordagem de HTML/Tailwind mais direta na v2.0. A v3.0 introduziu funcionalidades chave como a gestão de graus de iniciação (Quimbandeiro), trabalhos espirituais e melhorias no controle de mensalidades.

Direitos Autorais: Andre Silva
