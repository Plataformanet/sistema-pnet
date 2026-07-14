# Épico: Cadastro Unificado (Clientes, Fornecedores e Equipe)

---

## 1. Visão Geral do Recurso
*   **Nome do Épico:** Cadastro Unificado de Contatos (Clientes, Fornecedores e Funcionários)
*   **Status:** Aprovado / Homologado (Em Produção)
*   **Módulo Associado:** Cadastros Base (Espinha Dorsal)

### 1.1. Contexto de Negócio
O sistema exige o cadastro e manutenção de contatos chave para operações e faturamento. Para evitar que o mesmo indivíduo/empresa seja duplicado caso assuma papéis diferentes (ex: um fornecedor que também é cliente), o sistema adota uma base de contatos centralizada e unificada com especializações funcionais.

### 1.2. Atores Envolvidos
*   **Colaborador / Operador do Tenant:** Pode visualizar, criar e editar contatos, clientes, fornecedores e funcionários com base em suas permissões.
*   **Administrador do Tenant:** Possui controle total de exclusão de cadastros.

---

## 2. Regras de Negócio e Requisitos Funcionais

1.  **Unicidade de CPF/CNPJ:** O sistema impede que mais de um contato compartilhe o mesmo número de documento (CPF ou CNPJ) no banco de dados de um inquilino.
2.  **Validação Prévia de Duplicidade:** O sistema implementa uma validação assíncrona ao digitar o documento no frontend, consultando o banco para resgatar dados de contatos já existentes.
3.  **Endereço Único Opcional:** Cada contato possui um relacionamento opcional de um para um (1:1) com a tabela de endereços.
4.  **Especializações Relacionais:**
    *   Um **Cliente** é apenas uma flag relacional apontando para o Contato base.
    *   Um **Fornecedor** exige informações adicionais de pessoa responsável, categoria de insumos e descrição técnica.
    *   Um **Funcionário** exige dados trabalhistas como data de nascimento, RG, cargo, salário e data de admissão.

---

## 3. Especificação Técnica e Modelagem

### 3.1. Dicionário de Dados do Banco de Dados (Tenant)
*   **Tabela:** `contacts`
    *   `id`: BigInt (PK, Auto-increment)
    *   `type`: String ("Física" ou "Jurídica")
    *   `name_corporatereason`: String (Nome Completo / Razão Social)
    *   `fantasy_name`: String (Nome Fantasia, nullable)
    *   `cpf_cnpj`: String (Documento único por tenant)
    *   `email`: String
    *   `phone`: String (Telefone Fixo)
    *   `cell_phone`: String (Celular)
*   **Tabela:** `addresses`
    *   `id`: BigInt (PK)
    *   `contact_id`: BigInt (FK para `contacts`, unique, onDelete Cascade)
    *   `zip_code`, `street`, `number`, `neighborhood`, `city`, `state`: String
    *   `complement`: String (nullable)
*   **Tabela:** `clients`
    *   `id`: BigInt (PK)
    *   `contact_id`: BigInt (FK para `contacts`, unique, onDelete Cascade)
*   **Tabela:** `suppliers`
    *   `id`: BigInt (PK)
    *   `contact_id`: BigInt (FK para `contacts`, unique, onDelete Cascade)
    *   `responsible_person`: String (nullable)
    *   `description`: Text (Descrição do serviço prestado)
    *   `supply_category`: String (Categoria de suprimentos)
*   **Tabela:** `employees`
    *   `id`: BigInt (PK)
    *   `contact_id`: BigInt (FK para `contacts`, unique, onDelete Cascade)
    *   `rg`: String
    *   `birth_date`: Date
    *   `position`: String (Cargo)
    *   `salary`: Integer (Salário em centavos)
    *   `hire_date`: Date (Data de admissão)

### 3.2. Estrutura de Código
*   **Controllers:** `TenantClientController`, `TenantSupplierController`, `TenantEmployeeController` (na pasta `App\Http\Controllers`)
*   **Models:** `Contact`, `Address`, `Client`, `Supplier`, `Employee` (na pasta `App\Models`)
*   **Rotas Chave:**
    *   `GET /registrations/clients/list` -> `tenant.registrations.clients.list` (Middleware: `permission:registrations.clients.view`)
    *   `GET /registrations/clients/get-contact-by-cpf-cnpj/{cpf_cnpj}` -> `tenant.registrations.clients.get-contact-by-cpf-cnpj`

---

## 4. Referência de API (Payload de Validação)

### 4.1. Buscar Contato por CPF/CNPJ `GET /registrations/clients/get-contact-by-cpf-cnpj/{cpf_cnpj}`
*   **Response (200 OK - Contato Existente):**
```json
{
  "id": 15,
  "type": "Física",
  "name_corporatereason": "Alan Victor Fernandes",
  "fantasy_name": null,
  "cpf_cnpj": "12345678909",
  "email": "alan@empresa.com",
  "phone": "1133334444",
  "cell_phone": "11999998888"
}
```
*   **Response (404 Not Found - Contato Inexistente):**
```json
{
  "message": "Contato não encontrado."
}
```

---

## 5. Critérios de Aceite (Cenários de Teste)

### Cenário 1: Impedir duplicidade de CPF no cadastro
*   **Dado que** já existe um contato cadastrado com o CPF "123.456.789-09"
*   **Quando** o operador do sistema tenta cadastrar um novo cliente com o mesmo CPF "123.456.789-09"
*   **Então** o sistema deve invalidar a requisição
*   **E** retornar a mensagem de validação: "O CPF/CNPJ informado já está cadastrado."

### Cenário 2: Validação de Especialização (Fornecedor)
*   **Dado que** um contato já existe como "Cliente" no sistema
*   **Quando** o operador acessa a tela de cadastro de fornecedores e informa o CPF/CNPJ daquele contato
*   **Então** o sistema deve resgatar os dados do contato existente
*   **E** permitir que o operador salve o registro de fornecedor preenchendo apenas os campos extras (`responsible_person`, `supply_category`, `description`) sem duplicar os dados base de contatos.
