# Sprint 1: Fundação Técnica e Identidade

---

## 1. Visão Geral da Sprint
*   **Status:** Concluída (Em Produção)
*   **Foco Principal:** Setup inicial do ambiente multi-tenant e autenticação base por subdomínio.

---

## 2. Histórias de Usuário & Critérios de Aceite

### 📖 Histórias de Usuário (User Stories)
*   **História 1:** Como um Administrador do SaaS, quero provisionar um novo Tenant via `/cadastro` para automatizar a criação do banco de dados e execução de migrations.
*   **História 2:** Como um Usuário do Tenant, quero me autenticar no subdomínio específico para acessar o ambiente isolado da minha organização.
*   **História 3:** Como um Usuário logado, quero acessar o Dashboard para visualizar métricas operacionais consolidadas.

### ✅ Critérios de Aceite (Acceptance Criteria)
*   O sistema deve resolver a conexão com o banco de dados do Tenant via subdomínio antes de qualquer validação de credenciais de login.
*   O processo de provisionamento do Tenant deve gerar um usuário administrador inicial padrão e criar as configurações iniciais da empresa.

---

## 3. Divisão de Backlog Técnico

### 🛠️ Tarefas de Backend (Laravel 13 / Multi-Tenancy)
1.  **Configuração de Infraestrutura Multi-Tenant:**
    *   Instalar e configurar o pacote `stancl/tenancy`.
    *   Criar migrations centrais (`tenants`, `domains`) para mapear inquilinos e subdomínios.
    *   Criar migrations básicas do tenant (`users` com suporte a 2FA, `tenant_settings` para personalização).
2.  **Lógica de Provisionamento:**
    *   Desenvolver o `TenantRegistrationController` para automatizar a criação do banco de dados isolado e execução das migrations do tenant no momento do cadastro.
3.  **Fluxo de Autenticação Multi-Tenant:**
    *   Criar o `AuthTenantController` tratando login, logout e recuperação de senhas isolados por subdomínio.
    *   Registrar middlewares para resolver o banco de dados dinamicamente antes de qualquer validação.

### 🎨 Tarefas de Frontend (Vue 3 / Inertia / Tailwind v4)
1.  **Interface de Cadastro do SaaS:**
    *   Criar página pública de `/cadastro` no domínio central para novas empresas se registrarem.
2.  **Telas de Acesso e Segurança:**
    *   Criar tela de `/login` e `/forgot-password` personalizada no subdomínio do inquilino.
3.  **Layout Base da Aplicação (Dashboard):**
    *   Desenvolver a casca administrativa do painel (Sidebar de navegação principal, Topbar com perfil do usuário e estrutura responsiva).
    *   Criar a página inicial do `/dashboard` com grids e placeholders para métricas de negócio.
