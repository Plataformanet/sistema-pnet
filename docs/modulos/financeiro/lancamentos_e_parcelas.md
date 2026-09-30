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
3.  **Integridade de Valores:** A soma de todas as parcelas geradas (`installments`) associadas a um lançamento deve obrigatoriamente ser igual ao valor total (`total`) declarado no cabeçalho do lançamento. A regra é validada no cadastro (`StoreAccount*Request::after()`) e na edição (pelo service, que conhece as parcelas já pagas). Quando o sistema divide um valor entre parcelas, a diferença do arredondamento fica na última parcela, sem perda de centavos (ex.: R$ 1.000,00 em 3x = 333,33 + 333,33 + 333,34).
4.  **Validação de Entrada:** `total` e o valor de cada parcela são obrigatórios e maiores que zero (em centavos). A condição de pagamento aceita apenas `a-vista` ou de `1` a `12` parcelas. O status inicial aceita apenas `open` (Em Aberto) ou `paid` (Pago). Conta bancária excluída não pode receber lançamentos.
5.  **Vinculação de Comprovantes (Receipts):** O sistema permite salvar o arquivo físico do comprovante de pagamento no armazenamento local do Tenant (Drive de Arquivos) e salva o caminho do arquivo no campo `receipt` do título principal correspondente.

### 2.1. Movimentação do Saldo Bancário e Estornos

Toda operação que envolve dinheiro já movimentado roda em uma única transação (`$tenant->run` → `DB::transaction`): se qualquer etapa falhar, nada é gravado, nem o saldo nem as parcelas. O saldo (`bank_accounts.current_balance`) é alterado por um `UPDATE` atômico no banco, e as parcelas envolvidas são travadas (`lockForUpdate`) durante a operação, evitando perda de movimentações concorrentes.

| Operação | Efeito no saldo |
| :--- | :--- |
| **Baixa de parcela** (`installments/update`) | Debita (a pagar) ou credita (a receber) o valor da parcela; grava `payment_date` com a data da baixa. Parcela já paga é recusada, e a rota de um tipo não aceita parcela do outro. |
| **Criação já paga** (status `paid`) | Movimenta a soma de **todas** as parcelas; `payment_date` de cada parcela = seu vencimento. |
| **Edição do total ou da quantidade de parcelas** | As parcelas pagas são mantidas; apenas as abertas são recriadas com o valor restante (total − pago), ocupando os números de parcela livres. Novo total menor que o já pago é recusado. |
| **Edição individual das parcelas** (total inalterado) | Vencimentos e valores das parcelas abertas podem mudar; o **valor de parcela paga não pode ser alterado**, e a soma final precisa continuar igual ao total. |
| **Troca de conta bancária** | O valor já pago é estornado da conta antiga e lançado na nova. |
| **Exclusão do lançamento** | O valor pago é **estornado** da conta e as parcelas são excluídas junto com o lançamento. |

Parcelas em aberto nascem com `payment_date` nulo. As regras de bloqueio lançam exceções de domínio (base `FinancialEntryException`), exibidas ao usuário como aviso (`warning`) nos formulários ou como HTTP 422 na baixa.

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
    *   `total`: BigInt (Valor total em centavos)
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
    *   `value`: BigInt (Valor da parcela em centavos)
    *   `description`: Text (nullable)
    *   `due_date`: Date (Data de vencimento)
    *   `payment_date`: Date (Data de pagamento/recebimento real; nula enquanto a parcela está em aberto)
    *   `status`: String — enum `AccountsEnum`: `open` (Em Aberto), `paid` (Pago); `overdue` e `received` existem no enum, mas não são gravados pelos fluxos atuais

### 3.2. Estrutura de Código
*   **Controllers:** `TenantAccountPayableController`, `TenantAccountReceivableController` (na pasta `App\Http\Controllers`)
*   **Services:** `AccountService` (base abstrata com criação, edição, exclusão, baixa e movimentação de saldo), estendida por `AccountPayableService` (saída, fornecedor) e `AccountReceivableService` (entrada, cliente)
*   **Exceções de domínio:** `InstallmentAlreadyPaidException`, `PaidInstallmentLockedException`, `AccountTotalBelowPaidException`, `InstallmentsTotalMismatchException` (todas estendem `FinancialEntryException`)
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

### Cenário 2: Baixa de parcela
*   **Dado que** uma parcela polimórfica está com status "Em Aberto"
*   **Quando** o operador do financeiro dá baixa na parcela
*   **Então** o sistema deve alterar o status da parcela para "Pago" e gravar a data da baixa em `payment_date`
*   **E** aplicar a entrada/saída no saldo atualizado da conta bancária (`current_balance`) associada ao título.

### Cenário 3: Baixa duplicada
*   **Dado que** uma parcela de R$ 300,00 já foi paga
*   **Quando** o operador tenta dar baixa nela novamente (ex.: duplo clique)
*   **Então** o sistema deve recusar a operação com a mensagem "Esta parcela já foi paga."
*   **E** o saldo da conta deve ter sido debitado uma única vez.

### Cenário 4: Estorno na exclusão
*   **Dado que** um lançamento a pagar tem uma parcela de R$ 300,00 paga
*   **Quando** o operador exclui o lançamento
*   **Então** o sistema deve devolver R$ 300,00 ao saldo da conta bancária
*   **E** excluir as parcelas do lançamento.

### Cenário 5: Edição do total com parcela paga
*   **Dado que** um lançamento de R$ 900,00 em 3x tem a 1ª parcela (R$ 300,00) paga
*   **Quando** o operador altera o total para R$ 1.000,00
*   **Então** o sistema deve manter a parcela paga e recriar as duas abertas com R$ 350,00 cada
*   **E** não alterar o saldo da conta bancária.
*   **Mas se** o novo total for menor que o valor já pago, a edição deve ser recusada.
