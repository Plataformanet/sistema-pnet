# Sprint 2: Governança, Permissões e Estrutura Base

---

## 1. Visão Geral da Sprint
*   **Status:** Concluída (Em Produção)
*   **Foco Principal:** Implementação de controle de acesso baseado em papéis (RBAC) e administração de usuários internos.

---

## 2. Histórias de Usuário & Critérios de Aceite

### 📖 Histórias de Usuário (User Stories)
*   **História 1:** Como um Administrador do Tenant, quero definir Papéis (Roles) para organizar a hierarquia de funções da empresa.
*   **História 2:** Como um Administrador do Tenant, quero atribuir permissões granulares (CRUD) a papéis para garantir o princípio do menor privilégio.
*   **História 3:** Como um Administrador do Tenant, quero vincular papéis a usuários para habilitar o acesso modular.

### ✅ Critérios de Aceite (Acceptance Criteria)
*   As permissões devem ser validadas via middleware no backend antes da execução de qualquer controller.
*   O sistema deve realizar o bloqueio e ocultação de menus e botões no frontend com base no payload de permissões do usuário logado.

---

## 3. Divisão de Backlog Técnico

### 🛠️ Tarefas de Backend (Laravel 13 / Segurança)
1.  **Configuração de Permissões (RBAC):**
    *   Instalar o pacote `spatie/laravel-permission` no escopo do Tenant.
    *   Configurar os middlewares de rota baseados em permissões no arquivo `routes/tenant.php` (ex: `permission:registrations.clients.view`).
2.  **APIs de Administração de Usuários:**
    *   Criar o `TenantRoleController` para CRUD de Cargos (Roles) e associação de permissões.
    *   Criar o `TenantUserController` para gerenciar colaboradores vinculados ao Tenant (incluindo status ativo/inativo).

### 🎨 Tarefas de Frontend (Vue 3 / Inertia / Tailwind v4)
1.  **Telas de Gerenciamento de Acessos:**
    *   Criar a listagem e formulário de criação/edição de Usuários (`/settings/users/*`).
    *   Criar a tela de configuração de Cargos (`/settings/roles/*`) contendo grid com checkboxes de permissões modulares.
2.  **Controle de Visibilidade Dinâmico:**
    *   Criar helper global no Vue 3 para verificar permissões do usuário logado baseado nas propriedades compartilhadas (Share Props) do Inertia.
    *   Ocultar ou desabilitar botões de edição, exclusão e abas de menu no frontend caso o usuário logado não possua a permissão necessária.
