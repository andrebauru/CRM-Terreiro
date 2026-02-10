# CRM-Terreiro

Este é um projeto de CRM (Customer Relationship Management) em transição para uma arquitetura moderna. O backend é construído com PHP 8.2+ e MySQL 8, funcionando como uma API, enquanto o frontend está sendo migrado para React. O objetivo é fornecer uma solução leve, eficiente e moderna para gerenciar clientes, serviços e tarefas.

## Stack Tecnológica

-   **Backend:** PHP 8.2+ (servindo como API REST)
-   **Banco de Dados:** MySQL 8 com camada de portabilidade (PDO com fallback automático para MySQLi quando o driver pdo_mysql não estiver disponível)
-   **Frontend:** React (com Vite) e MUI (Material UI) para uma interface responsiva e moderna

## Funcionalidades Principais

-   **Autenticação:** Login/Logout baseado em sessão com controle de acesso por roles (admin/staff). A UI de autenticação está sendo migrada para React, consumindo a API de backend.
-   **Gestão de Clientes:** CRUD completo para clientes, com API JSON no backend PHP.
-   **Gestão de Serviços:** CRUD de serviços oferecidos, com API JSON no backend PHP.
-   **Gestão de Tarefas (Jobs):** CRUD de tarefas com status, prioridade e canal, incluindo notas e anexos, com API JSON no backend PHP.
-   **Dashboard:** Visão geral com estatísticas básicas. A UI do dashboard está sendo migrada para React.
-   **Uploads Seguros:** Anexos de tarefas com validação robusta de tipo MIME, uso de UUIDs para nomes de arquivos, compressão otimizada de imagens, permissões de diretório restritas (`0755`) e limite de tamanho de até 6MB.
-   **Troca de Tema (Claro/Escuro):** Funcionalidade para alternar entre temas claro e escuro, com preferência persistente via cookies, para uma experiência de usuário aprimorada.

## Estrutura do Projeto

-   `public/`: Ponto de entrada da aplicação PHP (`index.php`) para a API e assets públicos.
-   `app/`: Contém a lógica principal da aplicação PHP (Controllers, Models, Views legadas, configurações).
-   `database/`: Scripts SQL para schema, seed e dump do banco de dados.
-   `storage/`: Armazenamento de uploads (`uploads/`), logs (`logs/`) e outros arquivos gerados.
-   `frontend/`: Contém o novo projeto React (com Vite e MUI).

## Instalação e Configuração

### Backend (PHP)

1.  **Clone o repositório:**
    `git clone SEU_REPOSITORIO_AQUI`
    `cd CRM-Terreiro`

2.  **Configuração do Ambiente:**
    Crie um arquivo `.env` na raiz do projeto, copiando e preenchendo as informações de `.env.example`.
    `cp .env.example .env`

3.  **Configuração do Banco de Dados:**
    No `.env`, defina as seguintes variáveis para o seu ambiente local ou de produção:

    `DB_HOST=seu_host_do_banco` (ex: `localhost`, `127.0.0.1` ou o endereço do servidor de banco de dados)
    `DB_NAME=nome_do_seu_banco_de_dados` (ex: `crm_terreiro`)
    `DB_USER=seu_usuario_do_banco` (ex: `root`)
    `DB_PASS=sua_senha_do_banco`
    `CSRF_TOKEN_SECRET`: **MUITO IMPORTANTE:** Substitua o valor por uma string longa, aleatória e segura.

    Crie um banco de dados MySQL com o `nome_do_seu_banco_de_dados` (ex: `crm_terreiro`).
    Importe `database/schema.sql` e `database/seed.sql` para popular o banco de dados.

4.  **Servidor Web:** Configure seu servidor web (Apache/Nginx) para apontar o `Document Root` para a pasta `public/` do projeto PHP.

### Frontend (React)

1.  **Navegue para o diretório frontend:**
    `cd frontend`

2.  **Instale as dependências:**
    `npm install`

3.  **Configure a URL da API:**
    Crie um arquivo `.env.local` dentro da pasta `frontend/` e adicione a URL base da sua API PHP. Por exemplo:
    `VITE_API_BASE_URL=http://localhost/crm-terreiro/public`
    (Substitua `http://localhost/crm-terreiro/public` pela URL real onde sua API PHP está rodando.)

4.  **Inicie o servidor de desenvolvimento React:**
    `npm run dev`
    O frontend estará acessível em `http://localhost:5173` (ou outra porta disponível).

## Guia de Implantação

A implantação desta aplicação agora envolve dois componentes: o backend PHP (API) e o frontend React.

### Implantação do Backend (PHP API)

Siga os passos de configuração do ambiente PHP (Passos 1 a 4 da seção anterior) para configurar o servidor web e o banco de dados. Certifique-se de que sua API esteja acessível na URL configurada em `VITE_API_BASE_URL` no frontend.

### Implantação do Frontend (React)

1.  **Build de Produção:** No diretório `frontend/`, execute:
    `npm run build`
    Isso criará uma pasta `dist/` contendo os arquivos estáticos otimizados do seu aplicativo React.
2.  **Servir os Arquivos Estáticos:** Os arquivos da pasta `dist/` devem ser servidos por um servidor web (Apache, Nginx, etc.) ou por um serviço de hospedagem de sites estáticos. Opcionalmente, eles podem ser colocados na pasta `public/` do backend PHP, se o servidor web for configurado para servir o `index.html` da aplicação React como ponto de entrada para todas as rotas (SPA).

---

## Auditoria e Recomendações (Backend PHP)

### 1. Melhorias de Segurança

-   **Prevenção de XSS (Cross-Site Scripting):** É crucial aplicar `htmlspecialchars()` ou equivalente a **todas** as saídas de dados fornecidas pelo usuário antes de exibi-las nas views (se houver views legadas). Para a API, garanta que os dados retornados sejam corretamente codificados em JSON para evitar injeções no cliente.
-   **Controle de Acesso Baseado em Papéis (RBAC):** O sistema agora utiliza um `BaseController` centralizado para gerenciar a autenticação e autorização por roles (`admin`/`staff`) de forma mais consistente em todos os controladores, redirecionando usuários sem permissão.
-   **Logging Avançado:** O uso de `App\Helpers\ForgeLogger` é positivo. Garanta que eventos de segurança críticos, como tentativas de login falhas, alterações de permissão e acessos a dados sensíveis, sejam logados de forma robusta e monitorados.

### 2. Melhorias de Performance

-   **N+1 Query Problem:** Em endpoints de API que retornam listas de itens com dados relacionados, inspecione manualmente se há múltiplas consultas ao banco de dados para cada item (o problema "N+1"). Isso pode ser otimizado com "eager loading" dos dados relacionados em uma única consulta.
-   **Gerenciamento de Conexão com Banco de Dados:** Atualmente, uma nova conexão PDO pode ser aberta a cada instanciação de um modelo. Para aplicações de alta carga, considere implementar um padrão Singleton ou injeção de dependência para a conexão com o banco de dados, garantindo que uma única conexão seja reutilizada durante o ciclo de vida da requisição.

### 3. Qualidade e Arquitetura do Código

-   **Template Mestre e Padronização de Páginas:** A arquitetura agora utiliza um template mestre (`layout.php`) e um `BaseController` para padronizar a renderização das views, garantindo consistência visual e a correta inclusão de CSS/JS em todas as páginas internas.
-   **Sistema de Rotas Aprimorado:** O roteador (`app/router.php`) foi aprimorado para registrar erros de roteamento, facilitando o diagnóstico de páginas 404 e exceções.
-   **Consistência da API:** Garanta que todos os endpoints da API sigam um padrão consistente de nomenclatura, estrutura de resposta JSON e tratamento de erros.
-   **Limpeza de Código:** Mantenha o código limpo, bem comentado e seguindo padrões de codificação PHP para facilitar a manutenção e futuras expansões.

Direitos Autorais: Andre Silva