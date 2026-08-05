# Épico: Gestão de Documentos (Drive do Tenant)

---

## 1. Visão Geral do Recurso
*   **Nome do Épico:** Drive de Arquivos, Pastas, Arraste e Solte (Drag & Drop) e Controle de Acessos
*   **Status:** Aprovado / Homologado (Em Produção)
*   **Módulo Associado:** Drive (Módulo Horizontal)

### 1.1. Contexto de Negócio
A gestão operacional de empresas de serviços lida intensamente com fluxo de arquivos sensíveis (comprovantes de pagamento, contratos, certidões imobiliárias). O sistema provê uma solução nativa de gerenciamento de arquivos em nuvem por Tenant, com isolamento absoluto de diretórios, hierarquização de pastas, seleção múltipla por teclado/touch, movimentação por drag & drop, controle de permissão por usuário com herança hierárquica e auditoria de ações para segurança contra vazamentos.

### 1.2. Atores Envolvidos
*   **Usuário Comum do Tenant:** Pode visualizar, fazer upload, download, criar pastas e reorganizar arquivos/pastas por arraste ou atalhos nas áreas permitidas.
*   **Gestor do Tenant / Proprietário:** Pode conceder ou revogar permissões de visualização e edição de pastas/arquivos específicos para a equipe, visualizar logs e gerenciar a lixeira.

---

## 2. Regras de Negócio e Requisitos Funcionais

1.  **Isolamento Físico de Storage:** Os arquivos de upload de cada Tenant são armazenados fisicamente em subpastas exclusivas no storage baseadas no ID/UUID do inquilino corrente (evitando vazamento de diretórios entre empresas).
2.  **Estrutura de Pastas Dinâmica:** Arquivos (`drives`) podem estar contidos dentro de pastas (`drive_folders`). Pastas excluídas deletam logicamente todos os arquivos e subpastas aninhados dentro delas.
3.  **Restrição de Arquivos na Raiz:** Arquivos físicos não podem existir soltos na raiz do Drive (`folder_id` não pode ser nulo para arquivos). Apenas pastas podem residir na raiz.
4.  **Controle de Permissões Fino & Herança Hierárquica:** Um arquivo/pasta possui permissões de acesso específicas por usuário (`drive_permissions`). A validação de acesso (`userCanAccess`) avalia recursivamente a árvore de pastas pai: se qualquer pasta ancestral possuir a restrição `SOMENTE_PROPRIETARIO`, todo o conteúdo aninhado herda a restrição automaticamente.
5.  **Movimentação por Drag & Drop e Seleção Múltipla:**
    *   Suporte a seleção múltipla via `Ctrl` / `Cmd` (intercalada) e `Shift` (intervalo/range).
    *   Arraste interno de itens (únicos ou seleção em lote) para pastas da tabela ou para a migalha de pão (*Breadcrumb*).
    *   Arraste externo do sistema operacional com overlay responsivo em tela cheia (`fixed inset-6`).
    *   Painel flutuante de ações em lote fixado no rodapé (*Floating Action Bar*), 100% responsivo para mobile e desktop.
6.  **Navegação Adaptativa (Touch vs. Desktop):**
    *   **Desktop (Mouse):** Clique simples seleciona a linha; duplo clique (`@dblclick`) abre a pasta.
    *   **Mobile / Touch:** Toque simples na linha da pasta navega diretamente para dentro do diretório; seleção para ações em lote feita via Checkbox.
7.  **Fluxo de Lixeira e Exclusão Segura:** Ao deletar um arquivo ou pasta, ele é enviado para a Lixeira do Drive (`drive_trash`) via exclusão lógica (soft delete). O usuário pode restaurar o item ou realizar a limpeza definitiva da lixeira.
8.  **Logs de Auditoria de Acesso:** Toda operação de criação, visualização, download, movimentação ou exclusão de documentos registra automaticamente uma entrada na tabela de `drive_logs`.

---

## 3. Especificação Técnica e Modelagem

### 3.1. Dicionário de Dados (Tenant)
*   **Tabela:** `drive_folders` (Pastas)
    *   `id`: BigInt (PK, Auto-increment)
    *   `parent_id`: BigInt (FK autorelacionada para `drive_folders` - subpastas, nullable)
    *   `name`: String
    *   `created_by`: BigInt (FK para `users`)
*   **Tabela:** `drives` (Arquivos e Registros Representantes de Pastas)
    *   `id`: BigInt (PK, Auto-increment)
    *   `drive_folder_id`: BigInt (FK para `drive_folders`, nullable)
    *   `name`: String (Nome amigável exibido)
    *   `document_path`: String (Caminho lógico real no storage de arquivos)
    *   `document_type`: String (Extensão ou `folder` para representantes de pasta)
    *   `document_size`: BigInt (Tamanho do arquivo em bytes)
    *   `user_id`: BigInt (FK para `users`)
*   **Tabela:** `drive_permissions` (Permissões de Acesso)
    *   `id`: BigInt (PK, Auto-increment)
    *   `drive_id`: BigInt (FK para `drives`, onDelete Cascade)
    *   `user_id`: BigInt (FK para `users`, onDelete Cascade)
    *   `permission_type`: String (`SOMENTE_PROPRIETARIO` ou `ACESSO_TOTAL`)
*   **Tabela:** `drive_logs` (Auditoria de Arquivos)
    *   `id`: BigInt (PK, Auto-increment)
    *   `user_id`: BigInt (FK para `users`)
    *   `drive_id`: BigInt (FK para `drives`, nullable)
    *   `action`: String (ex: "upload", "download", "moved", "deleted", "restored")
    *   `ip_address`: String (IP do usuário)

### 3.2. Estrutura de Código
*   **Composables (Frontend - Vue 3):**
    *   `useDriveSelection.ts`: Gerencia o estado de seleção, `Ctrl`/`Cmd` + Clique, `Shift` + Clique e prevenção de toggle no 2º clique de `@dblclick`.
    *   `useDriveDragDrop.ts`: Gerencia os eventos de arraste interno, realce visual de alvos (Drop Target) e bloqueio de soltura inválida (🚫).
*   **Componentes & Views (Vue 3):**
    *   `List.vue`: View principal com tabela responsiva, navegação por duplo clique / touch, barra flutuante de ações em lote e overlay de upload externo.
    *   `MoveModal.vue`: Modal de movimentação assistida via árvore de pastas.
*   **Services & Controllers (Backend - PHP 8.5 / Laravel 13):**
    *   `DriveService.php`: Contém a regra de negócio de movimentação em lote (`moveSelected`), atualização recursiva de caminhos (`collectPhysicalFolderMoves`), checagem hierárquica de permissões (`userCanAccess`) e fallback gracioso de movimentação física no MinIO/S3.
    *   `TenantDriveController.php`: Recebe as chamadas da API retornando respostas tratadas em JSON (HTTP 422 para erros de validação/autorização).

---

## 4. Referência de API (Payload de Movimentação)

### 4.1. Mover Itens em Lote `POST /drive/move`
*   **Request Payload (JSON):**
```json
{
  "items": [
    { "id": 14, "type": "folder" },
    { "id": 105, "type": "file" }
  ],
  "destination_folder_id": 32
}
```
*   **Response Sucesso (200 OK):**
```json
{
  "message": "Itens movidos com sucesso!"
}
```
*   **Response Erro (422 Unprocessable Entity / Authorization):**
```json
{
  "message": "Você não tem permissão para mover esta pasta."
}
```

---

## 5. Critérios de Aceite (Cenários de Teste)

### Cenário 1: Herança Hierárquica de Permissões (ACL)
*   **Dado que** a "Pasta Financeiro" possui a restrição `SOMENTE_PROPRIETARIO` para o Usuário A
*   **E** a "Subpasta Recibos" está aninhada dentro da "Pasta Financeiro" sem registros diretos na tabela `drive_permissions`
*   **Quando** o Usuário B (não proprietário) tenta acessar a "Subpasta Recibos" ou mover itens para ela
*   **Então** o método `userCanAccess` deve consultar a árvore pai recursivamente
*   **E** identificar a restrição na "Pasta Financeiro" ancestral
*   **E** negar o acesso retornando `false` e disparando HTTP `403/422 Forbidden`.

### Cenário 2: Movimentação no Storage (MinIO/S3) sem Exceção em Pastas Vazias
*   **Dado que** o operador move a pasta "Projetos 2026" contendo apenas subpastas vazias no storage MinIO/S3
*   **Quando** a movimentação é processada
*   **Então** o banco de dados deve atualizar a coluna `document_path` dos registros no MySQL em transação
*   **E** o backend deve mover o diretório pai no storage uma única vez via `$disk->move()`, utilizando fallback gracioso caso não existam objetos físicos a copiar
*   **E** a requisição deve concluir sem disparar erros visuais de Flysystem para o usuário.

### Cenário 3: Navegação Responsiva (Mobile vs Desktop)
*   **Dado que** um usuário acessa a aplicação via celular/dispositivo touch
*   **Quando** ele toca na linha de uma pasta
*   **Então** o sistema deve detectar `pointer: coarse` e navegar diretamente para dentro da pasta com 1 toque
*   **E** caso deseje selecionar a pasta para ações em lote, o usuário pode utilizar o Checkbox da linha.
