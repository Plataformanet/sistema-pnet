# Épico: Catálogo (Produtos e Serviços)

---

## 1. Visão Geral do Recurso
*   **Nome do Épico:** Catálogo Unificado de Produtos e Serviços
*   **Status:** Aprovado / Homologado (Em Produção)
*   **Módulo Associado:** Catálogo (Espinha Dorsal)

### 1.1. Contexto de Negócio
O sistema precisa gerenciar os itens comercializados pelas empresas operacionais. Isso engloba bens materiais (Produtos) com necessidades de controle de estoque e custos de aquisição, bem como entregas intelectuais ou operacionais (Serviços) cobradas com base em horas de duração ou taxas.

### 1.2. Atores Envolvidos
*   **Colaborador do Almoxarifado / Operador:** Pode gerenciar estoque de produtos e cadastrar novos itens.
*   **Gestor Operacional:** Cadastra e altera valores de venda e parâmetros de serviços.

---

## 2. Regras de Negócio e Requisitos Funcionais

1.  **Unicidade do SKU/Código de Barras:** O SKU (Stock Keeping Unit) e o Código de Barras devem ser únicos na tabela de produtos para garantir a integridade dos dados e identificação física.
2.  **Valores Financeiros Inteiros:** Todos os valores monetários (`cost_value`, `sell_value`, `fees`) são armazenados em formato inteiro correspondente a centavos (ex: R$ 15,50 é salvo no banco como `1550`). Valores negativos são bloqueados.
3.  **Controle Opcional de Estoque:** No cadastro do produto, o campo `manage_stock` determina se o sistema deve controlar o fluxo de entrada/saída de unidades físicas. Se ativo, o sistema valida e impede transações quando o estoque atual (`current_stock`) atinge limites críticos em relação ao estoque mínimo (`min_stock`).
4.  **Duração de Serviços:** Serviços possuem indicação estimada de tempo de execução (`duration`) expressa em minutos, utilizada para organização de agendamentos e cálculo de capacidade produtiva.

---

## 3. Especificação Técnica e Modelagem

### 3.1. Dicionário de Dados (Tenant)
*   **Tabela:** `product_categories`
    *   `id`: BigInt (PK, Auto-increment)
    *   `name`: String
    *   `status`: Boolean (default true - ativo/inativo)
*   **Tabela:** `products`
    *   `id`: BigInt (PK, Auto-increment)
    *   `product_category_id`: BigInt (FK para `product_categories`, onDelete Cascade)
    *   `name`: String
    *   `sku`: String (Código único de controle)
    *   `barcode`: String (Código de barras único)
    *   `cost_value`: Integer (Preço de custo em centavos)
    *   `sell_value`: Integer (Preço de venda em centavos)
    *   `manage_stock`: Boolean (default false)
    *   `current_stock`: Integer (nullable)
    *   `min_stock`: Integer (nullable)
    *   `unit_of_measure`: String (ex: UN, KG, PC)
    *   `description`: Text (nullable)
    *   `status`: Boolean (default true)
*   **Tabela:** `service_categories`
    *   `id`: BigInt (PK, Auto-increment)
    *   `name`: String
    *   `status`: Boolean (default true)
*   **Tabela:** `services`
    *   `id`: BigInt (PK, Auto-increment)
    *   `service_category_id`: BigInt (FK para `service_categories`, onDelete Cascade)
    *   `name`: String
    *   `sku`: String (Código único de serviço)
    *   `cost_value`: Integer (Custo de operação em centavos)
    *   `sell_value`: Integer (Preço cobrado ao cliente em centavos)
    *   `fees`: Integer (Impostos ou taxas associadas)
    *   `duration`: Integer (Duração estimada em minutos, nullable)
    *   `description`: Text (nullable)
    *   `status`: Boolean (default true)

### 3.2. Estrutura de Código
*   **Controllers:** `TenantProductController`, `TenantProductCategoryController`, `TenantServiceController`, `TenantServiceCategoryController` (na pasta `App\Http\Controllers`)
*   **Models:** `Product`, `ProductCategory`, `Service`, `ServiceCategory` (na pasta `App\Models`)
*   **Rotas Chave:**
    *   `GET /products/products/list` -> `tenant.products.products.list` (Middleware: `permission:products.products.view`)
    *   `GET /services/services/list` -> `tenant.services.services.list` (Middleware: `permission:services.services.view`)

---

## 4. Referência de API (Payload de Persistência)

### 4.1. Criar Produto `POST /products/products/store`
*   **Request Payload (JSON):**
```json
{
  "product_category_id": 2,
  "name": "Papel A4 Resma 500fls",
  "sku": "PAPEL-A4-RESMA",
  "barcode": "7891234567890",
  "cost_value": 1850,
  "sell_value": 3200,
  "manage_stock": true,
  "current_stock": 100,
  "min_stock": 10,
  "unit_of_measure": "UN",
  "description": "Papel sulfite branco formato A4 para impressoras."
}
```
*   **Response (200 OK):**
```json
{
  "id": 8,
  "name": "Papel A4 Resma 500fls",
  "sku": "PAPEL-A4-RESMA",
  "current_stock": 100,
  "status": true
}
```

---

## 5. Critérios de Aceite (Cenários de Teste)

### Cenário 1: Impedir preço de venda negativo
*   **Dado que** o gestor está cadastrando um novo produto ou serviço
*   **Quando** ele preenche o campo de preço de venda (`sell_value`) com um valor menor que zero (ex: -R$ 5,00)
*   **Então** o sistema deve rejeitar o salvamento
*   **E** exibir a mensagem de validação: "O valor de venda não pode ser inferior a zero."

### Cenário 2: Validação de alertas de estoque mínimo
*   **Dado que** o produto "Papel A4 Resma" possui `manage_stock = true` e `min_stock = 10`
*   **E** o estoque atual (`current_stock`) está em 12 unidades
*   **Quando** ocorre uma saída de estoque de 3 unidades (ficando com 9)
*   **Então** o sistema deve registrar a saída com sucesso
*   **E** marcar o produto internamente com flag ou aviso de "Estoque Baixo" no dashboard ou relatórios.
