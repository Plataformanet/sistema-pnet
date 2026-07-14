# Épico: Contas Bancárias, Categorias e Fluxos

---

## 1. Visão Geral do Recurso
*   **Nome do Épico:** Gestão de Contas Bancárias, Plano de Contas e Relatórios de Fluxo
*   **Status:** Aprovado / Homologado (Em Produção)
*   **Módulo Associado:** Financeiro (Espinha Dorsal)

### 1.1. Contexto de Negócio
O sistema precisa conciliar os saldos financeiros e prover visibilidade gerencial sobre a saúde financeira do inquilino. Este recurso engloba o controle das carteiras financeiras do tenant (contas bancárias e caixas internos), a classificação contábil (Categorias e Subcategorias) e a consolidação de dados em relatórios (Fluxo de Caixa diário/mensal e exportação de gastos).

### 1.2. Atores Envolvidos
*   **Operador Financeiro / Assistente:** Gerencia as contas bancárias, cadastra subcategorias e analisa o fluxo de caixa.
*   **Gestor Financeiro / Diretor:** Possui acesso a relatórios de faturamento consolidado e exportação de PDF de fluxo de gastos para contabilidade externa.

---

## 2. Regras de Negócio e Requisitos Funcionais

1.  **Chave Única de Conta Bancária:** Para evitar cadastros duplicados de contas correntes, a combinação física de `[bank, agency, account_number]` deve ser única dentro do mesmo tenant.
2.  **Conta Principal Padrão (`main_account`):** O inquilino deve indicar uma de suas contas como a conta principal padrão. Ao criar lançamentos financeiros (pagar/receber), o sistema seleciona automaticamente esta conta principal, permitindo alteração manual pelo operador.
3.  **Tipagem do Plano de Contas:** Categorias financeiras exigem a definição de `type` (Receita ou Despesa). Subcategorias devem ser vinculadas obrigatoriamente a uma categoria e herdam seu tipo.
4.  **Cálculo Real do Fluxo de Caixa por Parcelas:** O relatório de fluxo de caixa calcula entradas e saídas analisando a data de vencimento (`due_date`) e data de pagamento real (`payment_date`) das parcelas (`installments`), e **não** os valores globais dos cabeçalhos de contas a pagar/receber, garantindo precisão diária/mensal.

---

## 3. Especificação Técnica e Modelagem

### 3.1. Dicionário de Dados (Tenant)
*   **Tabela:** `bank_accounts`
    *   `id`: BigInt (PK, Auto-increment)
    *   `name`: String (Nome identificador - ex: "Banco do Brasil - Conta Corrente")
    *   `bank`: String (Nome/Código do banco)
    *   `agency`: String (Número da agência)
    *   `account_number`: String (Número da conta)
    *   `account_type`: String (ex: Corrente, Poupança, Caixa Físico)
    *   `initial_balance`: Integer (Saldo inicial no setup, em centavos)
    *   `current_balance`: Integer (Saldo atualizado conciliado, em centavos)
    *   `active`: Boolean (default true)
    *   `main_account`: Boolean (default false)
*   **Tabela:** `financial_categories`
    *   `id`: BigInt (PK, Auto-increment)
    *   `name`: String (ex: "Receita de Serviços", "Despesa com Pessoal")
    *   `type`: String ("receita" ou "despesa")
    *   `observations`: Text (nullable)
    *   `active`: Boolean (default true)
*   **Tabela:** `financial_subcategories`
    *   `id`: BigInt (PK, Auto-increment)
    *   `financial_category_id`: BigInt (FK para `financial_categories`, onDelete Cascade)
    *   `name`: String (ex: "Salários", "Vale Transporte")
    *   `observations`: Text (nullable)
    *   `active`: Boolean (default true)

### 3.2. Estrutura de Código
*   **Controllers:** `TenantBankAccountController`, `TenantFinancialCategoryController`, `TenantFinancialSubcategoryController`, `TenantCashFlowController`, `TenantSpendingFlowController` (na pasta `App\Http\Controllers`)
*   **Models:** `BankAccount`, `FinancialCategory`, `FinancialSubcategory` (na pasta `App\Models`)
*   **Rotas Chave:**
    *   `GET /finance/cash-flow` -> `tenant.finance.cash-flow.index` (Middleware: `permission:finance.cash_flow.view`)
    *   `GET /finance/spending-flow/pdf` -> `tenant.finance.spending-flow.pdf` (Middleware: `permission:finance.spending_flow.view`)

---

## 4. Referência de API (Relatório de Fluxo de Caixa)

### 4.1. Consultar Resumo do Fluxo de Caixa `GET /finance/cash-flow`
*   **Response (200 OK):**
```json
{
  "period": "2026-07",
  "summary": {
    "total_inflows": 1500000,
    "total_outflows": 850000,
    "net_period": 650000
  },
  "daily_entries": [
    {
      "date": "2026-07-14",
      "inflow": 450000,
      "outflow": 120000,
      "balance": 330000
    }
  ]
}
```

---

## 5. Critérios de Aceite (Cenários de Teste)

### Cenário 1: Impedir duplicidade de conta física bancária
*   **Dado que** o tenant já tem cadastrada a conta Banco Itaú (Banco: "341", Agência: "1234", Conta: "56789-0")
*   **Quando** o operador tenta cadastrar uma nova conta com os mesmos dados de Banco ("341"), Agência ("1234") e Conta ("56789-0")
*   **Então** o sistema deve invalidar a requisição
*   **E** retornar a mensagem: "Esta conta bancária já está cadastrada para a empresa."

### Cenário 2: Conciliação automática de saldo no Fluxo de Caixa
*   **Dado que** existem duas parcelas (`installments`) de despesas vencendo hoje no valor de R$ 100,00 cada
*   **E** uma parcela foi paga hoje e a outra permanece em aberto
*   **Quando** o operador visualiza o relatório de Fluxo de Caixa consolidado
*   **Então** o sistema deve computar apenas a parcela Paga no cálculo do saldo realizado de hoje (reduzindo R$ 100,00)
*   **E** listar a parcela pendente apenas na aba de projeções de vencimento.
