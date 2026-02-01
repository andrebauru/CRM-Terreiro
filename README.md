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

## Guia Completo de Implantação (Deployment)

Esta seção oferece um guia passo a passo para hospedar o sistema em um servidor online, como uma hospedagem compartilhada (Hostinger, Hostgator, etc.).

### Passo 1: Preparando o Banco de Dados no Servidor

Antes de enviar os arquivos, você precisa criar o banco de dados e o usuário que o sistema irá utilizar.

1.  **Acesse o Painel de Controle:** Faça login no painel da sua hospedagem (geralmente cPanel).
2.  **Encontre a Seção de Banco de Dados:** Procure por "Banco de Dados MySQL", "MySQL Databases" ou algo similar.
3.  **Crie um Novo Banco de Dados:**
    *   Haverá um campo para "Criar Novo Banco de Dados".
    *   Digite um nome para o seu banco (ex: `crm_terreiro`).
    *   Anote o nome completo, que geralmente inclui um prefixo (ex: `usuario_cpanel_crm_terreiro`). **Este será o seu `DB_NAME`**.
4.  **Crie um Novo Usuário de Banco de Dados:**
    *   Na mesma página, encontre a seção "Usuários MySQL".
    *   Crie um novo usuário (ex: `user_crm`).
    *   Use o gerador de senhas para criar uma senha forte e segura.
    *   Anote o nome de usuário completo (ex: `usuario_cpanel_user_crm`). **Este será o seu `DB_USER`**.
    *   Anote a senha. **Esta será a sua `DB_PASS`**.
5.  **Adicione o Usuário ao Banco de Dados:**
    *   Encontre a seção "Adicionar Usuário ao Banco de Dados".
    *   Selecione o usuário (`user_crm`) e o banco de dados (`crm_terreiro`) que você acabou de criar.
    *   Clique em "Adicionar".
    *   Na tela seguinte, marque a caixa "TODOS OS PRIVILÉGIOS" (`ALL PRIVILEGES`) e salve as alterações.
6.  **Importe a Estrutura do Banco:**
    *   Volte ao painel principal e procure por "phpMyAdmin".
    *   No phpMyAdmin, selecione o banco de dados que você criou na coluna da esquerda.
    *   Clique na aba "Importar".
    *   Clique em "Escolher arquivo" e selecione o arquivo `database/schema.sql` do seu computador.
    *   Clique em "Executar" no final da página. As tabelas do sistema serão criadas.
    *   (Opcional) Se quiser dados de exemplo, repita o processo de importação com o arquivo `database/seed.sql`.

### Passo 2: Enviando os Arquivos para o Servidor

Você pode usar um cliente FTP (como FileZilla) ou o Gerenciador de Arquivos do seu painel de controle.

1.  **Conecte-se ao Servidor:** Use as credenciais de FTP/SFTP fornecidas pela sua hospedagem.
2.  **Navegue até a Pasta Raiz:** A pasta principal para sites geralmente se chama `public_html` ou `www`.
3.  **Escolha o Método de Upload:**
    *   **Método A (Recomendado - Se puder alterar o Document Root):**
        1.  Dentro de `public_html`, crie uma nova pasta para o projeto (ex: `crm`).
        2.  Envie **todos** os arquivos e pastas do projeto para dentro da pasta `crm`.
    *   **Método B (Hospedagem sem acesso ao Document Root):**
        1.  Envie o **conteúdo** da pasta `public/` local (o `index.php`, `.htaccess` e a pasta `static`) diretamente para dentro de `public_html`.
        2.  Crie uma pasta **no mesmo nível** que a `public_html` (ou seja, fora dela) com um nome como `crm_core`.
        3.  Envie o **restante** dos arquivos do projeto (`app`, `database`, `storage`, `vendor`, `composer.json`, etc.) para dentro da pasta `crm_core`.

### Passo 3: Configurando o Ambiente (`.env`)

1.  **Crie o Arquivo `.env`:**
    *   No servidor, dentro da pasta principal do seu projeto (em `crm` ou `crm_core`, dependendo do método acima), crie um novo arquivo chamado `.env`.
    *   Copie o conteúdo do arquivo `.env.example` do seu computador e cole no `.env` do servidor.
2.  **Edite as Variáveis de Ambiente:**
    *   `APP_ENV`: Mude de `development` para `production`.
    *   `BASE_URL`: Coloque a URL completa do seu site (ex: `https://www.meusite.com`).
    *   `DB_HOST`: Geralmente é `localhost`, mas confirme com seu provedor de hospedagem.
    *   `DB_NAME`: O nome completo do banco de dados que você anotou.
    *   `DB_USER`: O nome de usuário completo que você anotou.
    *   `DB_PASS`: A senha que você anotou.
    *   `CSRF_TOKEN_SECRET`: **MUITO IMPORTANTE:** Substitua o valor por uma string longa, aleatória e segura. Você pode usar um gerador online de senhas para criar uma com mais de 32 caracteres.

### Passo 4: Configuração Final do Servidor

1.  **Ajuste de Caminhos (Apenas para o Método B):**
    *   Se você usou o Método B, abra o `index.php` que está em `public_html` e edite as duas primeiras linhas de `require` para apontar para a pasta `crm_core`:
        ```php
        // Altere de:
        require __DIR__ . '/../vendor/autoload.php';
        require __DIR__ . '/../app/config.php';
        
        // Para:
        require __DIR__ . '/../crm_core/vendor/autoload.php';
        require __DIR__ . '/../crm_core/app/config.php';
        ```
2.  **Configure o Document Root (Apenas para o Método A):**
    *   No seu painel de controle, encontre a seção "Domínios" ou "Subdomínios".
    *   Selecione o domínio que você está usando e procure a opção de alterar a "Raiz do Documento" (`Document Root`).
    *   Altere o caminho para a pasta `public` do seu projeto. Ex: `public_html/crm/public`.
3.  **Verifique as Permissões:**
    *   Certifique-se de que a pasta `storage` e suas subpastas (`logs`, `uploads`) tenham permissão de escrita pelo servidor (geralmente permissão `755` ou `775` para pastas é suficiente, mas verifique a documentação do seu host se houver erros de permissão).

### Passo 5: Teste Final

Abra seu navegador e acesse o domínio. O sistema deve carregar. Tente fazer login com os usuários padrão do `seed.sql`:
-   **Admin:** `admin@crm-terreiro.local` / `password123`
-   **Staff:** `staff@crm-terreiro.local` / `password123`

Se encontrar um "Erro Interno do Servidor" (Erro 500), verifique os logs de erro no seu painel de hospedagem para diagnosticar o problema, que geralmente está relacionado a permissões de pasta ou erros no `.htaccess` ou `.env`.



