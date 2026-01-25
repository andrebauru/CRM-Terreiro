# CRM-Terreiro

Este é um projeto de CRM (Customer Relationship Management) construído com PHP 8.2+ e MySQL 8, seguindo uma arquitetura MVC simples. O objetivo é fornecer uma solução leve e eficiente para gerenciar clientes, serviços e tarefas.

## Stack Tecnológica

-   **Backend:** PHP 8.2+
-   **Banco de Dados:** MySQL 8 com PDO para prepared statements
-   **Frontend:** Tabler (Bootstrap 5) para uma interface responsiva e moderna

## Funcionalidades Principais

-   **Autenticação:** Login/Logout baseado em sessão com controle de acesso por roles (admin/staff) e CSRF.
-   **Gestão de Clientes:** CRUD completo para clientes, incluindo notas.
-   **Gestão de Serviços:** CRUD de serviços oferecidos.
-   **Gestão de Tarefas (Jobs):** CRUD de tarefas com status, prioridade e canal, incluindo notas e anexos.
-   **Dashboard:** Visão geral com estatísticas básicas.
-   **Uploads Seguros:** Anexos de tarefas com validação de tipo, tamanho, renomeação UUID e armazenamento organizado.

## Estrutura do Projeto

-   `public/`: Ponto de entrada da aplicação (`index.php`) e assets públicos.
-   `app/`: Contém a lógica principal da aplicação (Controllers, Models, Views, configurações).
-   `database/`: Scripts SQL para schema, seed e dump do banco de dados.
-   `storage/`: Armazenamento de uploads (`uploads/`), logs (`logs/`) e outros arquivos gerados.
-   `.forge/`: Ferramentas e históricos de automação (ex: `history.json`).

## Instalação e Configuração

1.  **Clone o repositório:**
    `git clone [URL_DO_REPOSITORIO]`
    `cd CRM-Terreiro`

2.  **Configuração do Ambiente:**
    Crie um arquivo `.env` na raiz do projeto, copiando e preenchendo as informações de `.env.example`.
    `cp .env.example .env`

3.  **Configuração do Banco de Dados:**
    Crie um banco de dados MySQL (`crm_terreiro` ou o nome que preferir).
    Importe `database/schema.sql` e `database/seed.sql` para popular o banco de dados.

4.  **Servidor Web:**
    Configure seu servidor web (Apache/Nginx) para apontar a raiz do documento para a pasta `public/`.
    Alternativamente, você pode usar o servidor web embutido do PHP:
    `php -S localhost:8000 -t public`

5.  **Acesse a Aplicação:**
    Abra seu navegador e acesse `http://localhost:8000` (ou o endereço configurado).

## Direitos Autorais

Direitos Autorais: Andre Silva
