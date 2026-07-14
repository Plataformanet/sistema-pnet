# Épico: Contas a Pagar/Receber e Parcelamento Polimórfico

---

## 1. Visão Geral do Recurso
*   **Nome do Épico:** Gestão de Lançamentos de Contas a Pagar/Receber e Motor de Parcelamento Polimórfico
*   **Status:** Aprovado / Homologado (Em Produção)
*   **Módulo Associado:** Financeiro (Espinha Dorsal)

### 1.1. Contexto de Negócio
Para operar de forma sustentável, as empresas precisam controlar seus títulos de despesas (Contas a Pagar) e receitas comerciais (Contas a Receber). O sistema implementa uma estrutura unificada onde os títulos principais representam o fato gerador do valor financeiro, enquanto as parcelas controlam os prazos, vencimentos e conciliações efetivas de pagamento.

### 1.2. Atores Envolvidos
*   **Operador Financeiro:** Cria, edita e remove lançamentos de contas a pagar e receber, além de realizar a baixa das parcelas.
*   **Gestor Operacional / Faturamento:** Acompanha o histórico de parcelas pendentes dos clientes para cobrança.

---

## 2. Regras de Negócio e Requisitos Funcionais

1.  **Estrutura de Lançamento Unificada:** As tabelas `account_payables` e `account_receivables` possuem a mesma estrutura física, mudando apenas a direção do fluxo financeiro. Elas guardam informações do cabeçalho da transação (valor total, método de pagamento, plano de contas e fornecedor/cliente).
2.  **Motor de Parcelamento Polimórfico:** As parcelas físicas são armazenadas na tabela única `installments`. Ela utiliza relacionamentos polimórficos (`installmentable_type` e `installmentable_id`) para se conectar de forma genérica a um registro de conta a pagar ou conta a receber.
3.  **Integridade de Valores:** A soma de todas as parcelas geradas (`installments`) associadas a um lançamento deve obrigatoriamente ser igual ao valor total (`total`) declarado no cabeçalho do lançamento.
4.  **Vinculação de Comprovantes (Receipts):** O sistema permite salvar o arquivo físico do comprovante de pagamento no armazenamento local do Tenant (Drive de Arquivos) e salva o caminho do arquivo no campo `receipt` do título principal correspondente.

---

## 3. Especificação Técnica e Modelagem

### 3.1. Dicionário de Dados (Tenant)
*   **Tabela:** `account_payables` (Contas a Pagar) e `account_receivables` (Contas a Receber)
    *   `id`: BigInt (PK, Auto-increment)
    *   `financial_category_id`: BigInt (FK para `financial_categories`)
    *   `financial_subcategory_id`: BigInt (FK para `financial_subcategories`, nullable)
    *   `cost_id`: BigInt (FK para `costs` - classificação de custos, nullable)
    *   `bank_account_id`: BigInt (FK para `bank_accounts`)
    *   `financial_contact_id`: BigInt (FK para `financial_contacts`)
    *   `description`: Text
    *   `total`: Integer (Valor total em centavos)
    *   `payment_method`: String (ex: Boleto, Cartão, PIX, Dinheiro)
    *   `payment_condition`: String ("À Vista" ou "Parcelado")
    *   `total_installments`: Integer (Quantidade total de parcelas geradas)
    *   `bank_account_out`: Integer (Identificador de banco de saída)
    *   `observations`: Text (nullable)
    *   `receipt`: String (Caminho físico do arquivo de comprovante, nullable)
*   **Tabela:** `installments` (Parcelas)
    *   `id`: BigInt (PK, Auto-increment)
    *   `installmentable_type`: String (ex: `App\Models\AccountPayable` ou `App\Models\AccountReceivable`)
    *   `installmentable_id`: BigInt (ID do registro pai correspondente)
    *   `installment_number`: Integer (Número identificador da parcela, ex: 1, 2, 3...)
    *   `value`: Integer (Valor da parcela em centavos)
    *   `description`: Text (nullable)
    *   `due_date`: Date (Data de vencimento)
    *   `payment_date`: Date (Data de pagamento/recebimento real, nullable)
    *   `status`: String (ex: "Pendente", "Pago", "Atrasado", "Cancelado")

### 3.2. Estrutura de Código
*   **Controllers:** `TenantAccountPayableController`, `TenantAccountReceivableController` (na pasta `App\Http\Controllers`)
*   **Models:** `AccountPayable`, `AccountReceivable`, `Installment`, `Cost`, `FinancialContact` (na pasta `App\Models`)
*   **Rotas Chave:**
    *   `POST /finance/accounts-payable/store` -> `tenant.finance.accounts-payable.store`
    *   `PATCH /finance/accounts-payable/installments/update` -> `tenant.finance.accounts-payable.installments.update`

---

## 4. Referência de API (Payload de Criação de Lançamento)

### 4.1. Lançamento Parcelado de Conta a Receber `POST /finance/accounts-receivable/store`
*   **Request Payload (JSON):**
```json
{
  "financial_category_id": 1,
  "financial_subcategory_id": 2,
  "bank_account_id": 1,
  "financial_contact_id": 4,
  "description": "Faturamento do Contrato de Assessoria Imobiliária",
  "total": 300000,
  "payment_method": "Boleto",
  "payment_condition": "Parcelado",
  "total_installments": 3,
  "installments": [
    {
      "installment_number": 1,
      "value": 100000,
      "due_date": "2026-08-14"
    },
    {
      "installment_number": 2,
      "value": 100000,
      "due_date": "2026-09-14"
    },
    {
      "installment_number": 3,
      "value": 100000,
      "due_date": "2026-10-14"
    }
  ]
}
```

---

## 5. Critérios de Aceite (Cenários de Teste)

### Cenário 1: Diferença matemática na soma das parcelas
*   **Dado que** o operador está cadastrando uma conta a pagar de valor total R$ 150,00
*   **Quando** ele informa a divisão de duas parcelas com valores de R$ 70,00 cada (soma de R$ 140,00)
*   **Então** o sistema deve rejeitar o cadastro do título
*   **E** retornar a mensagem: "A soma das parcelas (R$ 140,00) deve ser exatamente igual ao valor total do título (R$ 150,00)."

### Cenário 2: Baixa automática de parcela
*   **Dado que** uma parcela polimórfica está com status "Pendente"
*   **Quando** o operador do financeiro informa a data de pagamento (`payment_date`)
*   **Então** o sistema deve alterar o status da parcela para "Pago"
*   **E** aplicar a entrada/saída no saldo atualizado da conta bancária (`current_balance`) associada ao título.
