# Épico: Gestão de Documentos (Drive do Tenant)

---

## 1. Visão Geral do Recurso
*   **Nome do Épico:** Drive de Arquivos, Pastas e Controle de Acessos
*   **Status:** Aprovado / Homologado (Em Produção)
*   **Módulo Associado:** Drive (Módulo Horizontal)

### 1.1. Contexto de Negócio
A gestão operacional de empresas de serviços lida intensamente com fluxo de arquivos sensíveis (comprovantes de pagamento, contratos, certidões imobiliárias). O sistema provê uma solução nativa de gerenciamento de arquivos em nuvem por Tenant, com isolamento absoluto de diretórios, hierarquização de pastas, controle de permissão por usuário e auditoria de ações para segurança contra vazamentos.

### 1.2. Atores Envolvidos
*   **Usuário Comum do Tenant:** Pode visualizar, fazer upload, download e criar pastas nas áreas permitidas.
*   **Gestor do Tenant / Proprietário:** Pode conceder ou revogar permissões de visualização e edição de pastas/arquivos específicos para a equipe, visualizar logs e gerenciar a lixeira.

---

## 2. Regras de Negócio e Requisitos Funcionais

1.  **Isolamento Físico de Storage:** Os arquivos de upload de cada Tenant são armazenados fisicamente em subpastas exclusivas no storage baseadas no ID/UUID do inquilino corrente (evitando vazamento de diretórios entre empresas).
2.  **Estrutura de Pastas Dinâmica:** Arquivos (`drives`) podem estar contidos dentro de pastas (`drive_folders`). Pastas excluídas deletam logicamente todos os arquivos e subpastas aninhados dentro delas.
3.  **Controle de Permissões Fino:** Um arquivo pode ter permissões de acesso específicas por usuário (`drive_permissions`). Se o usuário não for o criador do arquivo ou não tiver registro na tabela de permissões, o acesso ao arquivo é bloqueado.
4.  **Fluxo de Lixeira e Exclusão Segura:** Ao deletar um arquivo ou pasta, ele é enviado para a Lixeira do Drive (`drive_trash`) via exclusão lógica (soft delete). O usuário pode restaurar o item ou realizar a limpeza definitiva da lixeira para liberar espaço de armazenamento.
5.  **Logs de Auditoria de Acesso:** Toda operação de criação, visualização, download ou exclusão de documentos registra automaticamente uma entrada na tabela de `drive_logs`, contendo o ID do usuário e a ação realizada.

---

## 3. Especificação Técnica e Modelagem

### 3.1. Dicionário de Dados (Tenant)
*   **Tabela:** `drive_folders` (Pastas)
    *   `id`: BigInt (PK, Auto-increment)
    *   `parent_id`: BigInt (FK autorelacionada para `drive_folders` - subpastas, nullable)
    *   `name`: String
    *   `created_by`: BigInt (FK para `users`)
*   **Tabela:** `drives` (Arquivos)
    *   `id`: BigInt (PK, Auto-increment)
    *   `drive_folder_id`: BigInt (FK para `drive_folders`, nullable)
    *   `name`: String (Nome amigável exibido)
    *   `path`: String (Caminho lógico real no storage de arquivos)
    *   `extension`: String (Extensão - ex: pdf, png, docx)
    *   `size`: BigInt (Tamanho do arquivo em bytes)
    *   `created_by`: BigInt (FK para `users`)
*   **Tabela:** `drive_permissions` (Permissões de Acesso)
    *   `id`: BigInt (PK, Auto-increment)
    *   `drive_id`: BigInt (FK para `drives`, onDelete Cascade)
    *   `user_id`: BigInt (FK para `users`, onDelete Cascade)
    *   `can_edit`: Boolean (Permissão de escrita, default false)
*   **Tabela:** `drive_logs` (Auditoria de Arquivos)
    *   `id`: BigInt (PK, Auto-increment)
    *   `user_id`: BigInt (FK para `users`)
    *   `drive_id`: BigInt (FK para `drives`, nullable)
    *   `action`: String (ex: "upload", "download", "deleted", "restored")
    *   `ip_address`: String (IP do usuário)

### 3.2. Estrutura de Código
*   **Controllers:** `TenantDriveController`, `TenantDriveFolderController`, `TenantDriveTrashController`, `TenantDriveLogController` (na pasta `App\Http\Controllers`)
*   **Models:** `Drive`, `DriveFolder`, `DrivePermission`, `DriveLog` (na pasta `App\Models`)
*   **Rotas Chave:**
    *   `GET /drive` -> `tenant.drive.index` (Listagem geral)
    *   `GET /drive/{id}/download` -> `tenant.drive.download` (Download seguro com verificação de ACL)
    *   `DELETE /drive/selected` -> `tenant.drive.delete-selected` (Remoção lógica em lote)

---

## 4. Referência de API (Payload de Acesso)

### 4.1. Conceder Permissão a Usuário `POST /drive/permissions`
*   **Request Payload (JSON):**
```json
{
  "drive_id": 412,
  "user_id": 18,
  "can_edit": true
}
```
*   **Response (200 OK):**
```json
{
  "message": "Permissão concedida com sucesso ao usuário."
}
```

---

## 5. Critérios de Aceite (Cenários de Teste)

### Cenário 1: Restrição de Download sem Permissão (ACL)
*   **Dado que** o arquivo "Contrato_Social.pdf" (ID: 12) foi cadastrado pelo Usuário A
*   **E** o Usuário B tenta realizar o download do arquivo acessando a rota `/drive/12/download`
*   **E** o Usuário B não possui registro associado na tabela `drive_permissions` para este arquivo
*   **Quando** a requisição é processada
*   **Então** o sistema deve rejeitar o download do arquivo
*   **E** retornar código HTTP `403 Forbidden` com a mensagem: "Você não tem permissão para acessar este arquivo."

### Cenário 2: Exclusão Lógica e Lixeira
*   **Dado que** o operador seleciona o arquivo "Recibo_01.png" e clica em Excluir
*   **Quando** a ação é disparada no sistema
*   **Então** o sistema deve aplicar soft delete no registro da tabela `drives`
*   **E** o arquivo não deve mais aparecer na listagem principal do Drive
*   **E** deve passar a ser exibido na tela da Lixeira (`/trash`) possibilitando sua restauração.
