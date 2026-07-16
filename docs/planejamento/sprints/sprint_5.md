# Sprint 5: Drive (Gestão de Documentos e Anexos)

---

## 1. Visão Geral da Sprint
*   **Status:** Concluída (Em Produção)
*   **Foco Principal:** Sistema interno de gerenciamento de arquivos do inquilino (pastas, upload seguro, lixeira e controle de acessos).

---

## 2. Links de Especificações (Regras de Negócio e Aceites)

> 🔗 **Especificações e Critérios de Aceite Detalhados:**
> *   [Épico: Drive de Arquivos, Pastas e Permissões](/docs/modulos/drive/gestao_de_documentos.md)

---

## 3. Divisão de Backlog Técnico

### 🛠️ Tarefas de Backend (Laravel 13 / File Storage)
1.  **Estrutura de Arquivos e Pastas:**
    *   Criar migrations e models do Drive (`drives`, `drive_folders`, `drive_permissions`, `drive_logs`).
    *   Implementar a lógica de isolamento físico de diretório por ID do Tenant.
2.  **Segurança e Auditoria:**
    *   Desenvolver lógica de download seguro validando se o usuário logado possui permissão na ACL.
    *   Implementar exclusão lógica (Soft Delete) de arquivos/pastas e gerenciamento de lixeira.
    *   Gravar logs de auditoria de arquivos em `drive_logs`.

### 🎨 Tarefas de Frontend (Vue 3 / Inertia / Tailwind v4)
1.  **Interface Visual do Drive:**
    *   Desenvolver painel gerenciador de arquivos (estilo Google Drive) com navegação de pastas e upload drag and drop.
2.  **Controle de Acesso e Lixeira:**
    *   Criar modal de compartilhamento para gerenciar acessos de outros usuários.
    *   Desenvolver tela de Lixeira com opção de restaurar ou excluir permanentemente.
3.  **Integração com Comprovantes Financeiros:**
    *   Adicionar campo de anexo nos formulários do financeiro, integrando com o storage do Drive.
