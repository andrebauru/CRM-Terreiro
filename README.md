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
-   **Uploads Seguros:** Anexos de tarefas com validação robusta de tipo MIME, uso de UUIDs para nomes de arquivos, compressão otimizada de imagens, permissões de diretório restritas (`0755`) e limite de tamanho de até 6MB.

## Estrutura do Projeto

-   `public/`: Ponto de entrada da aplicação (`index.php`) e assets públicos.
-   `app/`: Contém a lógica principal da aplicação (Controllers, Models, Views, configurações).
-   `database/`: Scripts SQL para schema, seed e dump do banco de dados.
    `storage/`: Armazenamento de uploads (`uploads/`), logs (`logs/`) e outros arquivos gerados.

## Instalação e Configuração

1.  **Clone o repositório:**
    `git clone SEU_REPOSITORIO_AQUI`
    `cd CRM-Terreiro`

2.  **Configuração do Ambiente:**
    Crie um arquivo `.env` na raiz do projeto, copiando e preenchendo as informações de `.env.example`.
    `cp .env.example .env`

3.  **Configuração do Banco de Dados:**
    Para configurar a conexão com o banco de dados, você precisará editar o arquivo `.env` na raiz do projeto. Este arquivo não é versionado no Git e contém informações sensíveis.

    No `.env`, defina as seguintes variáveis para o seu ambiente local ou de produção:

    `DB_HOST=seu_host_do_banco` (ex: `localhost`, `127.0.0.1` ou o endereço do servidor de banco de dados)
    `DB_NAME=nome_do_seu_banco_de_dados` (ex: `crm_terreiro`)
    `DB_USER=seu_usuario_do_banco` (ex: `root`)
    `DB_PASS=sua_senha_do_banco`

    Crie um banco de dados MySQL com o `nome_do_seu_banco_de_dados` (ex: `crm_terreiro`).
    Importe `database/schema.sql` e `database/seed.sql` para popular o banco de dados.

4.  **Configuração para Hospedagem Online (Exemplo com index.php na raiz):**
    Se você estiver hospedando a aplicação em um ambiente que exige que o ponto de entrada principal (`index.php`) esteja na raiz do projeto (como alguns hosts compartilhados), você pode usar o `index.php` fornecido na raiz.

    Certifique-se de que a variável `BASE_URL` no seu arquivo `.env` esteja configurada corretamente para a URL de produção da sua aplicação.

    `BASE_URL="https://seusite.com"`

5.  **Servidor Web:**
    Configure seu servidor web (Apache/Nginx) para apontar a raiz do documento para a pasta `public/`.
    Alternativamente, você pode usar o servidor web embutido do PHP:
    `php -S localhost:8000 -t public`

6.  **Acesse a Aplicação:**
    Abra seu navegador e acesse `http://localhost:8000` (ou o endereço configurado).

## Direitos Autorais

Direitos Autorais: Andre Silva

