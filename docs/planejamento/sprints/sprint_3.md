# Sprint 3: Cadastro Base e Catálogo

---

## 1. Visão Geral da Sprint
*   **Status:** Concluída (Em Produção)
*   **Foco Principal:** Implementação das tabelas de contatos (clientes, fornecedores, funcionários) e catálogo de produtos e serviços.

---

## 2. Links de Especificações (Regras de Negócio e Aceites)

> 🔗 **Especificações e Critérios de Aceite Detalhados:**
> *   [Épico: Contatos Unificados (Clientes, Fornecedores e Funcionários)](/docs/modulos/cadastros/contatos_unificados.md)
> *   [Épico: Catálogo de Produtos e Serviços](/docs/modulos/cadastros/catalogo.md)

---

## 3. Divisão de Backlog Técnico

### 🛠️ Tarefas de Backend (Laravel 13 / Banco de Dados)
1.  **Estrutura de Contatos Unificados:**
    *   Criar migrations e models do cadastro base: `contacts`, `clients`, `suppliers`, `employees` e `addresses`.
    *   Desenvolver endpoint `/get-contact-by-cpf-cnpj/{cpf_cnpj}` para validação de duplicidade.
2.  **Estrutura de Catálogo de Itens:**
    *   Criar migrations e models de categorias e produtos (`products`, `product_categories`).
    *   Criar migrations e models de categorias e serviços (`services`, `service_categories`).
    *   Implementar regras de validação (preço de venda não negativo, alerta de estoque mínimo).

### 🎨 Tarefas de Frontend (Vue 3 / Inertia / Tailwind v4)
1.  **Formulário Dinâmico de Contatos:**
    *   Criar formulário inteligente de Contatos (com exibição de campos dinâmicos por tipo).
    *   Implementar máscaras de inputs (CPF, CNPJ, Celular, CEP) e busca de CEP automática.
2.  **Telas do Catálogo:**
    *   Criar listagem e formulários de cadastro de Produtos (incluindo controle de estoque mínimo e SKU).
    *   Criar listagem e formulários de cadastro de Serviços.
