# Planejamento e Divisão de Sprints (Sprints 1 a 5)

Este documento registra o backlog técnico e funcional das primeiras 5 sprints do **Sistema PNET**, estruturado para a nossa divisão de trabalho:
*   **Backend Developer:** Responsável por banco de dados, migrations, lógica de controladores, segurança, APIs e regras de negócio no Laravel 13.
*   **Frontend Developer:** Responsável pelas views em Vue 3, estilização com Tailwind CSS v4, comportamento de interface e integração de formulários via Inertia.js v2.

---

## Sprint 1: Fundação Técnica e Identidade

### 📖 Histórias de Usuário (User Stories)
*   **História 1:** Como um Administrador do SaaS, quero provisionar um novo Tenant via `/cadastro` para automatizar a criação do banco de dados e execução de migrations.
*   **História 2:** Como um Usuário do Tenant, quero me autenticar no subdomínio específico para acessar o ambiente isolado da minha organização.
*   **História 3:** Como um Usuário logado, quero acessar o Dashboard para visualizar métricas operacionais consolidadas.

### ✅ Critérios de Aceite (Acceptance Criteria)
*   O sistema deve resolver a conexão com o banco de dados do Tenant via subdomínio antes de qualquer validação de credenciais de login.
*   O processo de provisionamento do Tenant deve gerar um usuário administrador inicial padrão e criar as configurações iniciais da empresa.

### 🛠️ Backlog de Backend (Laravel 13 / Multi-Tenancy)
1.  **Configuração de Infraestrutura Multi-Tenant:**
    *   Instalar e configurar o pacote `stancl/tenancy`.
    *   Criar migrations centrais (`tenants`, `domains`) para mapear inquilinos e subdomínios.
    *   Criar migrations básicas do tenant (`users` com suporte a 2FA, `tenant_settings` para personalização).
2.  **Lógica de Provisionamento:**
    *   Desenvolver o `TenantRegistrationController` para automatizar a criação do banco de dados isolado e execução das migrations do tenant no momento do cadastro.
3.  **Fluxo de Autenticação Multi-Tenant:**
    *   Criar o `AuthTenantController` tratando login, logout e recuperação de senhas isolados por subdomínio.
    *   Registrar middlewares para resolver o banco de dados dinamicamente antes de qualquer validação.

### 🎨 Backlog de Frontend (Vue 3 / Inertia / Tailwind v4)
1.  **Interface de Cadastro do SaaS:**
    *   Criar página pública de `/cadastro` no domínio central para novas empresas se registrarem.
2.  **Telas de Acesso e Segurança:**
    *   Criar tela de `/login` e `/forgot-password` personalizada no subdomínio do inquilino.
3.  **Layout Base da Aplicação (Dashboard):**
    *   Desenvolver a casca administrativa do painel (Sidebar de navegação principal, Topbar com perfil do usuário e estrutura responsiva).
    *   Criar a página inicial do `/dashboard` com grids e placeholders para métricas de negócio.

---

## Sprint 2: Governança, Permissões e Estrutura Base

### 📖 Histórias de Usuário (User Stories)
*   **História 1:** Como um Administrador do Tenant, quero definir Papéis (Roles) para organizar a hierarquia de funções da empresa.
*   **História 2:** Como um Administrador do Tenant, quero atribuir permissões granulares (CRUD) a papéis para garantir o princípio do menor privilégio.
*   **História 3:** Como um Administrador do Tenant, quero vincular papéis a usuários para habilitar o acesso modular.

### ✅ Critérios de Aceite (Acceptance Criteria)
*   As permissões devem ser validadas via middleware no backend antes da execução de qualquer controller.
*   O sistema deve realizar o bloqueio e ocultação de menus e botões no frontend com base no payload de permissões do usuário logado.

### 🛠️ Backlog de Backend (Laravel 13 / Segurança)
1.  **Configuração de Permissões (RBAC):**
    *   Instalar o pacote `spatie/laravel-permission` no escopo do Tenant.
    *   Configurar os middlewares de rota baseados em permissões no arquivo `routes/tenant.php` (ex: `permission:registrations.clients.view`).
2.  **APIs de Administração de Usuários:**
    *   Criar o `TenantRoleController` para CRUD de Cargos (Roles) e associação de permissões.
    *   Criar o `TenantUserController` para gerenciar colaboradores vinculados ao Tenant (incluindo status ativo/inativo).

### 🎨 Backlog de Frontend (Vue 3 / Inertia / Tailwind v4)
1.  **Telas de Gerenciamento de Acessos:**
    *   Criar a listagem e formulário de criação/edição de Usuários (`/settings/users/*`).
    *   Criar a tela de configuração de Cargos (`/settings/roles/*`) contendo grid com checkboxes de permissões modulares.
2.  **Controle de Visibilidade Dinâmico:**
    *   Criar helper global no Vue 3 para verificar permissões do usuário logado baseado nas propriedades compartilhadas (Share Props) do Inertia.
    *   Ocultar ou desabilitar botões de edição, exclusão e abas de menu no frontend caso o usuário logado não possua a permissão necessária.

---

## Sprint 3: Cadastro Base e Catálogo

> 🔗 **Especificações e Critérios de Aceite Detalhados:**
> *   [Épico: Contatos Unificados (Clientes, Fornecedores e Funcionários)](/docs/modulos/cadastros/contatos_unificados.md)
> *   [Épico: Catálogo de Produtos e Serviços](/docs/modulos/cadastros/catalogo.md)

### 🛠️ Backlog de Backend (Laravel 13 / Banco de Dados)
1.  **Estrutura de Contatos Unificados:**
    *   Criar migrations e models do cadastro base: `contacts`, `clients`, `suppliers` e `employees`, e `addresses`.
    *   Desenvolver endpoint `/get-contact-by-cpf-cnpj/{cpf_cnpj}` para validação de duplicidade.
2.  **Estrutura de Catálogo de Itens:**
    *   Criar migrations e models de categorias e produtos (`products`, `product_categories`).
    *   Criar migrations e models de categorias e serviços (`services`, `service_categories`).
    *   Implementar regras de validação (preço de venda não negativo, alerta de estoque mínimo).

### 🎨 Backlog de Frontend (Vue 3 / Inertia / Tailwind v4)
1.  **Formulário Dinâmico de Contatos:**
    *   Criar formulário inteligente de Contatos (com exibição de campos dinâmicos por tipo).
    *   Implementar máscaras de inputs (CPF, CNPJ, Celular, CEP) e busca de CEP automática.
2.  **Telas do Catálogo:**
    *   Criar listagem e formulários de cadastro de Produtos (incluindo controle de estoque mínimo e SKU).
    *   Criar listagem e formulários de cadastro de Serviços.

---

## Sprint 4: Financeiro Integrado e Fluxos Transacionais

> 🔗 **Especificações e Critérios de Aceite Detalhados:**
> *   [Épico: Contas Bancárias e Fluxos](/docs/modulos/financeiro/contas_e_fluxo.md)
> *   [Épico: Lançamentos e Parcelamento Polimórfico](/docs/modulos/financeiro/lancamentos_e_parcelas.md)

### 🛠️ Backlog de Backend (Laravel 13 / Lógica Financeira)
1.  **Banco de Dados do Core Financeiro:**
    *   Criar migrations de `bank_accounts`, `financial_categories`, `financial_subcategories`, `account_payables`, `account_receivables` e `costs`.
2.  **Mecanismo Polimórfico de Parcelas:**
    *   Criar tabela `installments` com suporte a polimorfismo (`installmentable`).
3.  **Lógica de Fluxo de Caixa e Relatórios:**
    *   Desenvolver lógica do `TenantCashFlowController` computando saldos baseando-se nas datas de vencimento/pagamento das parcelas.
    *   Criar endpoint de exportação do Fluxo de Gastos em PDF.

### 🎨 Backlog de Frontend (Vue 3 / Inertia / Tailwind v4)
1.  **Parametrização Financeira:**
    *   Criar telas de gerenciamento de Contas Bancárias e Plano de Contas.
2.  **Lançamentos Financeiros e Parcelamento:**
    *   Criar formulários de Contas a Pagar e Receber.
    *   Desenvolver componente dinâmico no Vue para geração de parcelas (cálculo de datas e valores com possibilidade de ajuste manual).
3.  **Relatórios Financeiros:**
    *   Criar painel visual do Fluxo de Caixa (gráfico/tabela comparativo de Entradas vs. Saídas).
    *   Listagem de parcelas a pagar/receber com botão rápido para dar baixa.

---

## Sprint 5: Drive (Gestão de Documentos e Anexos)

> 🔗 **Especificações e Critérios de Aceite Detalhados:**
> *   [Épico: Drive de Arquivos, Pastas e Permissões](/docs/modulos/drive/gestao_de_documentos.md)

### 🛠️ Backlog de Backend (Laravel 13 / File Storage)
1.  **Estrutura de Arquivos e Pastas:**
    *   Criar migrations e models do Drive (`drives`, `drive_folders`, `drive_permissions`, `drive_logs`).
    *   Implementar a lógica de isolamento físico de diretório por ID do Tenant.
2.  **Segurança e Auditoria:**
    *   Desenvolver lógica de download seguro validando se o usuário logado possui permissão na ACL.
    *   Implementar exclusão lógica (Soft Delete) de arquivos/pastas e gerenciamento de lixeira.
    *   Gravar logs de auditoria de arquivos em `drive_logs`.

### 🎨 Backlog de Frontend (Vue 3 / Inertia / Tailwind v4)
1.  **Interface Visual do Drive:**
    *   Desenvolver painel gerenciador de arquivos (estilo Google Drive) com navegação de pastas e upload drag and drop.
2.  **Controle de Acesso e Lixeira:**
    *   Criar modal de compartilhamento para gerenciar acessos de outros usuários.
    *   Desenvolver tela de Lixeira com opção de restaurar ou excluir permanentemente.
3.  **Integração com Comprovantes Financeiros:**
    *   Adicionar campo de anexo nos formulários do financeiro, integrando com o storage do Drive.
