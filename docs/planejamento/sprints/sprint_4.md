# Sprint 4: Financeiro Integrado e Fluxos Transacionais

---

## 1. Visão Geral da Sprint
*   **Status:** Concluída (Em Produção)
*   **Foco Principal:** Lógica de bancos, plano de contas, lançamentos financeiros e motor de parcelas polimórficas.

---

## 2. Links de Especificações (Regras de Negócio e Aceites)

> 🔗 **Especificações e Critérios de Aceite Detalhados:**
> *   [Épico: Contas Bancárias e Fluxos](/docs/modulos/financeiro/contas_e_fluxo.md)
> *   [Épico: Lançamentos e Parcelamento Polimórfico](/docs/modulos/financeiro/lancamentos_e_parcelas.md)

---

## 3. Divisão de Backlog Técnico

### 🛠️ Tarefas de Backend (Laravel 13 / Lógica Financeira)
1.  **Banco de Dados do Core Financeiro:**
    *   Criar migrations de `bank_accounts`, `financial_categories`, `financial_subcategories`, `account_payables`, `account_receivables` e `costs`.
2.  **Mecanismo Polimórfico de Parcelas:**
    *   Criar tabela `installments` com suporte a polimorfismo (`installmentable`).
3.  **Lógica de Fluxo de Caixa e Relatórios:**
    *   Desenvolver lógica do `TenantCashFlowController` computando saldos baseando-se nas datas de vencimento/pagamento das parcelas.
    *   Criar endpoint de exportação do Fluxo de Gastos em PDF.

### 🎨 Tarefas de Frontend (Vue 3 / Inertia / Tailwind v4)
1.  **Parametrização Financeira:**
    *   Criar telas de gerenciamento de Contas Bancárias e Plano de Contas.
2.  **Lançamentos Financeiros e Parcelamento:**
    *   Criar formulários de Contas a Pagar e Receber.
    *   Desenvolver componente dinâmico no Vue para geração de parcelas (cálculo de datas e valores com possibilidade de ajuste manual).
3.  **Relatórios Financeiros:**
    *   Criar painel visual do Fluxo de Caixa (gráfico/tabela comparativo de Entradas vs. Saídas).
    *   Listagem de parcelas a pagar/receber com botão rápido para dar baixa.
