# Detalhamento Técnico dos Módulos Principais (Sistema PNET)

Este documento detalha o funcionamento técnico, a modelagem de banco de dados e as regras de implementação dos módulos de **Cadastros**, **Catálogo**, **Financeiro** e **Drive (Documentos)** da aplicação atual.

---

## 1. Módulo de Cadastros Básicos (Registrations)

O sistema utiliza um modelo de dados de **Contatos Unificados** com especializações por tabelas relacionadas de um para um (1:1).

### 1.1. Modelagem do Banco de Dados
```mermaid
erDiagram
    CONTACTS ||--o| CLIENTS : "especializa (1:1)"
    CONTACTS ||--o| SUPPLIERS : "especializa (1:1)"
    CONTACTS ||--o| EMPLOYEES : "especializa (1:1)"
    CONTACTS ||--o| ADDRESSES : "possui (1:1)"
    
    CONTACTS {
        id bigint PK
        type string "Tipo (Física/Jurídica)"
        name_corporatereason string "Nome ou Razão Social"
        fantasy_name string "Nome Fantasia (opcional)"
        cpf_cnpj string "Documento único"
        email string
        phone string "Telefone Fixo"
        cell_phone string "Telefone Celular"
    }

    ADDRESSES {
        id bigint PK
        contact_id bigint FK
        zip_code string
        street string
        number string
        complement string "Opcional"
        neighborhood string
        city string
        state string
    }

    CLIENTS {
        id bigint PK
        contact_id bigint FK
    }

    SUPPLIERS {
        id bigint PK
        contact_id bigint FK
        responsible_person string "Pessoa de contato"
        description text "Descrição do fornecimento"
        supply_category string "Categoria de insumos"
    }

    EMPLOYEES {
        id bigint PK
        contact_id bigint FK
        rg string
        birth_date date
        position string "Cargo"
        salary integer "Salário em centavos"
        hire_date date "Data de Admissão"
    }
```

### 1.2. Regras e Endpoints de Cadastros
*   **Contatos Duplicados:** O sistema realiza a busca automática de contatos já cadastrados via endpoint `get-contact-by-cpf-cnpj/{cpf_cnpj}` para evitar duplicidade de registros entre os papéis (ex: um Funcionário ou Fornecedor que também é Cliente).
*   **Permissões de Acesso:** O acesso é controlado individualmente por ações via middlewares de permissão (ex: `permission:registrations.clients.create`, `permission:registrations.suppliers.edit`).

---

## 2. Módulo de Catálogo (Produtos e Serviços)

O catálogo é separado entre bens físicos (Produtos) com controle de estoque e serviços prestados com duração de tempo.

### 2.1. Categoria e Cadastro de Produtos (`products`)
*   **Categorias (`product_categories`):** Possui `name` e `status` (ativo/inativo).
*   **Produtos (`products`):**
    *   `product_category_id` (vínculo obrigatório).
    *   `name`, `sku` (código único de controle de estoque) e `barcode` (código de barras). O par `[sku, barcode]` é único no banco de dados.
    *   `cost_value` e `sell_value` (armazenados como inteiros para evitar problemas de ponto flutuante em centavos).
    *   `manage_stock` (booleano): Define se o sistema deve decrementar o estoque nas saídas.
    *   `current_stock` e `min_stock` (estoque atual e mínimo para alertas).
    *   `unit_of_measure` (Unidade de medida - ex: UN, KG, LT).
    *   `status` (ativo/inativo).

### 2.2. Categoria e Cadastro de Serviços (`services`)
*   **Categorias (`service_categories`):** Possui `name` e `status`.
*   **Serviços (`services`):**
    *   `service_category_id` (vínculo obrigatório).
    *   `name` e `sku` (código único do serviço).
    *   `cost_value` e `sell_value` (valor de custo interno e valor cobrado ao cliente final).
    *   `fees` (taxas ou tributação associada ao serviço).
    *   `duration` (duração estimada em minutos).
    *   `status` (ativo/inativo).

---

## 3. Módulo Financeiro (Finance)

Estrutura altamente integrada baseada em um plano de contas e parcelamentos polimórficos.

### 3.1. Contas Bancárias (`bank_accounts`)
Registra as contas correntes ou caixas internos do tenant para movimentações.
*   Campos: `name`, `bank`, `agency`, `account_number`, `account_type` (ex: Poupança, Corrente), `initial_balance` (saldo inicial de abertura) e `current_balance` (saldo conciliado atual).
*   As contas podem ser marcadas como `main_account` (conta padrão para transações) e devem ser ativas (`active = 1`).
*   A combinação `[bank, agency, account_number]` é única por tenant.

### 3.2. Plano de Contas (`financial_categories` & `financial_subcategories`)
*   **Categorias Financeiras:** Agrupadores que possuem `name`, `type` (Receita/Despesa) e `active`.
*   **Subcategorias Financeiras:** Nível secundário de classificação vinculado de forma obrigatória a uma categoria mãe.

### 3.3. Contas a Pagar (`account_payables`) e Contas a Receber (`account_receivables`)
Ambas as tabelas compartilham exatamente a mesma estrutura física, porém registram fluxos opostos (saídas e entradas).
*   **Campos de Relacionamento:**
    *   `financial_category_id` & `financial_subcategory_id` (Classificação no DRE/Fluxo de caixa).
    *   `bank_account_id` (Conta padrão associada).
    *   `financial_contact_id` (Vínculo com o Cliente/Fornecedor do financeiro).
    *   `cost_id` (Vínculo com a tabela de classificação de custos fixos ou variáveis).
*   **Campos de Controle:**
    *   `description` (texto descritivo da despesa/receita).
    *   `total` (valor total do título).
    *   `payment_method` (forma de pagamento - ex: PIX, Boleto, Cartão).
    *   `payment_condition` (condição - ex: À Vista, Parcelado).
    *   `total_installments` (quantidade total de parcelas geradas).
    *   `receipt` (caminho do anexo de comprovante ou nota fiscal).

### 3.4. Parcelamentos Polimórficos (`installments`)
Em vez de duplicar a lógica de parcelamento para Contas a Pagar e Contas a Receber, o sistema utiliza uma relação polimórfica (`morphs`).

```mermaid
erDiagram
    ACCOUNT_PAYABLES ||--o{ INSTALLMENTS : "installmentable"
    ACCOUNT_RECEIVABLES ||--o{ INSTALLMENTS : "installmentable"
    
    INSTALLMENTS {
        id bigint PK
        installmentable_type string "App\\Models\\AccountPayable ou AccountReceivable"
        installmentable_id bigint FK
        installment_number integer "Número da parcela (ex: 1, 2, 3...)"
        value integer "Valor da parcela em centavos"
        due_date date "Data de vencimento"
        payment_date date "Data do pagamento real (nulo se pendente)"
        status string "Status da parcela (Pendente, Pago, Atrasado, etc.)"
    }
```

*   **Fluxo de Caixa (`TenantCashFlowController`):** A leitura do fluxo de caixa diário/mensal é consolidada analisando diretamente as datas de vencimento (`due_date`) e pagamento (`payment_date`) da tabela `installments`, e não dos cabeçalhos das contas.
*   **Fluxo de Gastos (`TenantSpendingFlowController`):** Consolida saídas financeiras atreladas a `account_payables` e permite a exportação do relatório financeiro consolidado em PDF.

---

## 4. Módulo de Gestão de Documentos (Drive)

O sistema de arquivos gerencia a organização física e lógica de documentos, contendo pastas dinâmicas, controle de permissões por usuário (ACL) e auditoria de ações de download/upload.

### 4.1. Modelagem do Banco de Dados
```mermaid
erDiagram
    DRIVE_FOLDERS ||--o{ DRIVE_FOLDERS : "parent_id"
    DRIVE_FOLDERS ||--o{ DRIVES : "contém (1:N)"
    DRIVES ||--o{ DRIVE_PERMISSIONS : "restringe (1:N)"
    DRIVES ||--o{ DRIVE_LOGS : "audita (1:N)"
    
    DRIVE_FOLDERS {
        id bigint PK
        parent_id bigint FK "Auto-relacionamento"
        name string
        created_by bigint FK
    }

    DRIVES {
        id bigint PK
        drive_folder_id bigint FK "Opcional"
        name string
        path string "Caminho lógico real no storage"
        extension string
        size bigint "Tamanho do arquivo em bytes"
        created_by bigint FK
    }

    DRIVE_PERMISSIONS {
        id bigint PK
        drive_id bigint FK
        user_id bigint FK
        can_edit boolean
    }

    DRIVE_LOGS {
        id bigint PK
        user_id bigint FK
        drive_id bigint FK "Opcional"
        action string "upload/download/deleted/restored"
        ip_address string
    }
```

### 4.2. Regras e Endpoints de Documentos
*   **Isolamento Físico de Storage:** Arquivos são salvos em subdiretórios baseados no ID do Tenant para evitar o compartilhamento não autorizado de arquivos físicos.
*   **Controle de Acesso ACL:** Ao realizar download, a rota `/drive/{id}/download` executa um check de segurança na tabela `drive_permissions`. Se o usuário atual não for o criador do arquivo (`created_by`) e não possuir uma flag ativa na tabela de permissões, o download é abortado com erro HTTP `403`.
*   **Auditoria de Arquivos:** Todo upload, download ou deleção lógica insere um log na tabela `drive_logs` com fins de segurança e rastreamento.

