# Mapa de Rotas do Sistema (Sistema PNET)

Este documento descreve todas as rotas expostas no **Sistema PNET**, divididas entre a administração central do SaaS e as operações internas dos inquilinos (Tenants).

---

## 1. Rotas Centrais (Administração / Cadastro do SaaS)
Estas rotas rodam no domínio principal e gerenciam o fluxo de adesão de novos clientes.
*   **Arquivo de origem:** `routes/web.php`
*   **Controller:** `App\Http\Controllers\TenantRegistrationController`

| Método | Rota | Nome da Rota | Descrição / Ação |
| :--- | :--- | :--- | :--- |
| **GET** | `/cadastro` | `cadastro` | Exibe o formulário de cadastro de novo Tenant |
| **POST** | `/cadastro` | `cadastro.store` | Processa e cria o banco de dados do Tenant |
| **GET** | `/cadastro/status/{tenant}` | `cadastro.status` | Consulta o status de provisionamento (com rate limit: 60/min) |

---

## 2. Rotas do Tenant (Inquilinos)
Estas rotas rodam no subdomínio de cada inquilino e requerem inicialização de tenant via domínio.
*   **Arquivo de origem:** `routes/tenant.php`
*   **Middlewares padrão:** `web`, `InitializeTenancyByDomain`, `PreventAccessFromCentralDomains`

### 2.1. Autenticação e Acesso Básico
*   **Controller:** `App\Http\Controllers\Auth\AuthTenantController` e `TenantController`

| Método | Rota | Nome da Rota | Middlewares | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/login` | `tenant.login` | *Nenhum* | Exibe a página de login |
| **POST** | `/login` | `tenant.login.submit` | *Nenhum* | Processa a autenticação |
| **GET** | `/logout` | `tenant.logout` | *Nenhum* | Realiza a saída do sistema |
| **GET** | `/forgot-password`| `tenant.forgot-password`| *Nenhum* | Solicitação de nova senha |
| **GET** | `/reset-password` | `tenant.reset-password` | *Nenhum* | Tela de definição/redefinição de senha (recebe `token` e `email` na query) |
| **POST** | `/password/email` | `tenant.password.email` | `throttle:password-reset` | Envia o link de redefinição de senha |
| **POST** | `/password/reset` | `tenant.password.update` | `throttle:password-reset` | Grava a nova senha a partir do link |
| **GET** | `/dashboard` | `tenant.dashboard` | `Authenticate`, `RedirectProposalRestrictedUsers` | Página inicial após o login (equipe interna) |

> **Usuários externos às propostas** (Parceiro, Vendedor do imóvel e Cliente — `User::hasOnlyProposalRestrictedRoles()`): após o login vão direto para a lista de propostas (`tenant.documents.proposals.list`), respeitando a URL pretendida. O middleware `RedirectProposalRestrictedUsers` os redireciona para essa lista se tentarem abrir o Dashboard ou o CRM (`/crm/kanban`, `/crm/list`), e o menu lateral oculta esses itens.

> **Senha do tenant (`TenantPasswordService`):** o fluxo roda sempre no banco do tenant.
> *   **Dois tipos de link:** o de "esqueci a senha" vale 60 minutos (broker `users`). O do e-mail de boas-vindas de proponentes vale 72 horas (broker `welcome`, configurável por `AUTH_WELCOME_TOKEN_EXPIRE`). Os dois abrem a mesma tela `tenant.reset-password`.
> *   **Invalidação:** ao definir a senha, os links pendentes dos dois tipos deixam de valer.
> *   **Sem revelar cadastros:** e-mail sem cadastro recebe a mesma resposta, para não revelar quem tem conta.
> *   **Limite:** `password-reset` permite 5 requisições por minuto, com chave domínio do tenant + IP.
> *   **Rotas do Fortify:** `POST /forgot-password` e `POST /reset-password` (`password.email` / `password.update`) são registradas sem tenancy e operam no banco central. As telas do tenant não as usam.

---

### 2.2. Módulo de Cadastros Básicos (Registrations)
Controla o gerenciamento de clientes, fornecedores e funcionários com restrições por permissões individuais.

#### Clientes (`TenantClientController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/registrations/clients/list` | `tenant.registrations.clients.list` | `permission:registrations.clients.view` |
| **GET** | `/registrations/clients/create` | `tenant.registrations.clients.create` | `permission:registrations.clients.create` |
| **POST** | `/registrations/clients/store` | `tenant.registrations.clients.store` | `permission:registrations.clients.create` |
| **GET** | `/registrations/clients/{id}/edit`| `tenant.registrations.clients.edit` | `permission:registrations.clients.edit` |
| **PUT** | `/registrations/clients/{id}` | `tenant.registrations.clients.update` | `permission:registrations.clients.edit` |
| **DELETE**| `/registrations/clients/{id}` | `tenant.registrations.clients.destroy`| `permission:registrations.clients.delete` |
| **GET** | `/registrations/clients/get-contact-by-cpf-cnpj/{cpf_cnpj}` | `tenant.registrations.clients.get-contact-by-cpf-cnpj` | `permission:registrations.clients.view` |

#### Fornecedores (`TenantSupplierController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/registrations/suppliers/list` | `tenant.registrations.suppliers.list` | `permission:registrations.suppliers.view` |
| **GET** | `/registrations/suppliers/create` | `tenant.registrations.suppliers.create` | `permission:registrations.suppliers.create` |
| **POST** | `/registrations/suppliers/store` | `tenant.registrations.suppliers.store` | `permission:registrations.suppliers.create` |
| **GET** | `/registrations/suppliers/{id}/edit`| `tenant.registrations.suppliers.edit` | `permission:registrations.suppliers.edit` |
| **PUT** | `/registrations/suppliers/{id}` | `tenant.registrations.suppliers.update` | `permission:registrations.suppliers.edit` |
| **DELETE**| `/registrations/suppliers/{id}` | `tenant.registrations.suppliers.destroy`| `permission:registrations.suppliers.delete` |
| **GET** | `/registrations/suppliers/get-contact-by-cpf-cnpj/{cpf_cnpj}` | `tenant.registrations.suppliers.get-contact-by-cpf-cnpj` | `permission:registrations.suppliers.view` |

#### Funcionários (`TenantEmployeeController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/registrations/employees/list` | `tenant.registrations.employees.list` | `permission:registrations.employees.view` |
| **GET** | `/registrations/employees/create` | `tenant.registrations.employees.create` | `permission:registrations.employees.create` |
| **POST** | `/registrations/employees/store` | `tenant.registrations.employees.store` | `permission:registrations.employees.create` |
| **GET** | `/registrations/employees/{id}/edit`| `tenant.registrations.employees.edit` | `permission:registrations.employees.edit` |
| **PUT** | `/registrations/employees/{id}` | `tenant.registrations.employees.update` | `permission:registrations.employees.edit` |
| **DELETE**| `/registrations/employees/{id}` | `tenant.registrations.employees.destroy`| `permission:registrations.employees.delete` |
| **GET** | `/registrations/employees/get-contact-by-cpf-cnpj/{cpf_cnpj}` | `tenant.registrations.employees.get-contact-by-cpf-cnpj` | `permission:registrations.employees.view` |

#### Usuários do Tenant (`TenantUserController`)
*   *Nota: Acesso padrão para administradores do Tenant.*

| Método | Rota | Nome da Rota |
| :--- | :--- | :--- |
| **GET** | `/settings/users/list` | `tenant.settings.users.list` |
| **GET** | `/settings/users/create` | `tenant.settings.users.create` |
| **POST** | `/settings/users/store` | `tenant.settings.users.store` |
| **GET** | `/settings/users/{id}/edit` | `tenant.settings.users.edit` |
| **PUT** | `/settings/users/{id}` | `tenant.settings.users.update` |

---

### 2.3. Módulo de Produtos e Serviços (Catalog)

#### Produtos (`TenantProductController` & `TenantProductCategoryController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/products/products/list` | `tenant.products.products.list` | `permission:products.products.view` |
| **GET** | `/products/products/create` | `tenant.products.products.create` | `permission:products.products.create` |
| **POST** | `/products/products/store` | `tenant.products.products.store` | `permission:products.products.create` |
| **GET** | `/products/products/{id}/edit` | `tenant.products.products.edit` | `permission:products.products.edit` |
| **PUT** | `/products/products/{id}` | `tenant.products.products.update` | `permission:products.products.edit` |
| **DELETE**| `/products/products/{id}` | `tenant.products.products.delete` | `permission:products.products.delete` |
| **GET** | `/products/categories/list` | `tenant.products.categories.list`| `permission:products.categories.view` |
| **GET** | `/products/categories/create` | `tenant.products.categories.create`| `permission:products.categories.create` |
| **POST** | `/products/categories/store` | `tenant.products.categories.store` | `permission:products.categories.create` |
| **GET** | `/products/categories/{id}/edit`| `tenant.products.categories.edit` | `permission:products.categories.edit` |
| **PUT** | `/products/categories/{id}` | `tenant.products.categories.update` | `permission:products.categories.edit` |
| **DELETE**| `/products/categories/{id}` | `tenant.products.categories.destroy`| `permission:products.categories.delete` |

#### Serviços (`TenantServiceController` & `TenantServiceCategoryController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/services/services/list` | `tenant.services.services.list` | `permission:services.services.view` |
| **GET** | `/services/services/create` | `tenant.services.services.create` | `permission:services.services.create` |
| **POST** | `/services/services/store` | `tenant.services.services.store` | `permission:services.services.create` |
| **GET** | `/services/services/{id}/edit` | `tenant.services.services.edit` | `permission:services.services.edit` |
| **PUT** | `/services/services/{id}` | `tenant.services.services.update` | `permission:services.services.edit` |
| **DELETE**| `/services/services/{id}` | `tenant.services.services.destroy` | `permission:services.services.delete` |
| **GET** | `/services/categories/list` | `tenant.services.categories.list`| `permission:services.categories.view` |
| **GET** | `/services/categories/create` | `tenant.services.categories.create`| `permission:services.categories.create` |
| **POST** | `/services/categories/store` | `tenant.services.categories.store` | `permission:services.categories.create` |
| **GET** | `/services/categories/{id}/edit`| `tenant.services.categories.edit` | `permission:services.categories.edit` |
| **PUT** | `/services/categories/{id}` | `tenant.services.categories.update` | `permission:services.categories.edit` |
| **DELETE**| `/services/categories/{id}` | `tenant.services.categories.destroy`| `permission:services.categories.delete` |

---

### 2.4. Módulo Financeiro (Finance)

#### Configurações Bancárias e Categorias
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/finance/bank-accounts/list` | `tenant.finance.bank-accounts.list` | `permission:finance.accounts.view` |
| **GET** | `/finance/bank-accounts/create` | `tenant.finance.bank-accounts.create` | `permission:finance.accounts.create` |
| **POST** | `/finance/bank-accounts/store` | `tenant.finance.bank-accounts.store` | `permission:finance.accounts.create` |
| **GET** | `/finance/bank-accounts/{id}/edit`| `tenant.finance.bank-accounts.edit` | `permission:finance.accounts.edit` |
| **PUT** | `/finance/bank-accounts/{id}` | `tenant.finance.bank-accounts.update` | `permission:finance.accounts.edit` |
| **DELETE**| `/finance/bank-accounts/{id}` | `tenant.finance.bank-accounts.destroy`| `permission:finance.accounts.delete` |
| **GET** | `/finance/categories/list` | `tenant.finance.categories.list` | `permission:finance.categories.view` |
| **GET** | `/finance/categories/create` | `tenant.finance.categories.create` | `permission:finance.categories.create` |
| **POST** | `/finance/categories/store` | `tenant.finance.categories.store` | `permission:finance.categories.create` |
| **GET** | `/finance/categories/{id}/edit` | `tenant.finance.categories.edit` | `permission:finance.categories.edit` |
| **PUT** | `/finance/categories/{id}` | `tenant.finance.categories.update` | `permission:finance.categories.edit` |
| **DELETE**| `/finance/categories/{id}` | `tenant.finance.categories.destroy` | `permission:finance.categories.delete` |
| **GET** | `/finance/subcategories/list` | `tenant.finance.subcategories.list`| `permission:finance.subcategories.view` |
| **GET** | `/finance/subcategories/create` | `tenant.finance.subcategories.create`| `permission:finance.subcategories.create` |
| **POST** | `/finance/subcategories/store` | `tenant.finance.subcategories.store` | `permission:finance.subcategories.create` |
| **GET** | `/finance/subcategories/{id}/edit`| `tenant.finance.subcategories.edit` | `permission:finance.subcategories.edit` |
| **PUT** | `/finance/subcategories/{id}` | `tenant.finance.subcategories.update` | `permission:finance.subcategories.edit` |
| **DELETE**| `/finance/subcategories/{id}` | `tenant.finance.subcategories.destroy`| `permission:finance.subcategories.delete` |

#### Contas a Pagar (`TenantAccountPayableController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/finance/accounts-payable/list` | `tenant.finance.accounts-payable.list` | `permission:finance.accounts_payable.view` |
| **GET** | `/finance/accounts-payable/create` | `tenant.finance.accounts-payable.create` | `permission:finance.accounts_payable.create` |
| **POST** | `/finance/accounts-payable/store` | `tenant.finance.accounts-payable.store` | `permission:finance.accounts_payable.create` |
| **GET** | `/finance/accounts-payable/contacts` | `tenant.finance.accounts-payable.search-contact`| `permission:finance.accounts_payable.view` |
| **GET** | `/finance/accounts-payable/{id}` | `tenant.finance.accounts-payable.show` | `permission:finance.accounts_payable.view` |
| **GET** | `/finance/accounts-payable/{id}/edit` | `tenant.finance.accounts-payable.edit` | `permission:finance.accounts_payable.edit` |
| **PUT** | `/finance/accounts-payable/{id}` | `tenant.finance.accounts-payable.update` | `permission:finance.accounts_payable.edit` |
| **DELETE**| `/finance/accounts-payable/{id}` | `tenant.finance.accounts-payable.destroy` | `permission:finance.accounts_payable.delete` |
| **PATCH** | `/finance/accounts-payable/installments/update`| `tenant.finance.accounts-payable.installments.update`| `permission:finance.accounts_payable.edit` |

> `installments/update` dá baixa na parcela (payload `{ id }`) e debita o saldo da conta bancária. Parcela já paga ou de outro tipo de lançamento é recusada (HTTP 422 com `message`). A antiga rota `installments/value` foi removida: o valor das parcelas só é alterado pela edição do lançamento.

#### Contas a Receber (`TenantAccountReceivableController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/finance/accounts-receivable/list` | `tenant.finance.accounts-receivable.list` | `permission:finance.accounts_receivable.view` |
| **GET** | `/finance/accounts-receivable/create` | `tenant.finance.accounts-receivable.create` | `permission:finance.accounts_receivable.create` |
| **POST** | `/finance/accounts-receivable/store` | `tenant.finance.accounts-receivable.store` | `permission:finance.accounts_receivable.create` |
| **GET** | `/finance/accounts-receivable/contacts` | `tenant.finance.accounts-receivable.search-contact`| `permission:finance.accounts_receivable.view` |
| **GET** | `/finance/accounts-receivable/{id}` | `tenant.finance.accounts-receivable.show` | `permission:finance.accounts_receivable.view` |
| **GET** | `/finance/accounts-receivable/{id}/edit` | `tenant.finance.accounts-receivable.edit` | `permission:finance.accounts_receivable.edit` |
| **PUT** | `/finance/accounts-receivable/{id}` | `tenant.finance.accounts-receivable.update` | `permission:finance.accounts_receivable.edit` |
| **DELETE**| `/finance/accounts-receivable/{id}` | `tenant.finance.accounts-receivable.destroy` | `permission:finance.accounts_receivable.delete` |
| **PATCH** | `/finance/accounts-receivable/installments/update`| `tenant.finance.accounts-receivable.installments.update`| `permission:finance.accounts_receivable.edit` |

> `installments/update` dá baixa na parcela (payload `{ id }`) e credita o saldo da conta bancária, com as mesmas regras de Contas a Pagar.

#### Fluxos e Relatórios Financeiros
| Método | Rota | Nome da Rota | Middleware de Permissão | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/finance/cash-flow` | `tenant.finance.cash-flow.index` | `permission:finance.cash_flow.view` | Fluxo de Caixa Geral |
| **GET** | `/finance/spending-flow` | `tenant.finance.spending-flow.index` | `permission:finance.spending_flow.view` | Fluxo de Gastos consolidado |
| **GET** | `/finance/spending-flow/pdf` | `tenant.finance.spending-flow.pdf` | `permission:finance.spending_flow.view` | Exporta relatório em PDF |
| **GET** | `/finance/billing` | `tenant.finance.billing.index` | `permission:finance.billing.view` | Relatório de Faturamentos |

---

### 2.5. Outras Configurações, Perfil e Segurança
*   **Controllers:** `TenantRoleController`, `TenantCompanySettingController`, `TenantProfileController`

#### Cargos e Permissões (`TenantRoleController`)
| Método | Rota | Nome da Rota | Middleware de Permissão | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/settings/roles/list` | `tenant.settings.roles.list` | `permission:settings.roles.view` | Listagem de cargos (Roles) |
| **GET** | `/settings/roles/create` | `tenant.settings.roles.create` | `permission:settings.roles.create` | Formulário de criação de cargo |
| **POST** | `/settings/roles/store` | `tenant.settings.roles.store` | `permission:settings.roles.create` | Salva novo cargo |
| **GET** | `/settings/roles/{id}/edit`| `tenant.settings.roles.edit` | `permission:settings.roles.edit` | Edição de cargos |
| **PUT** | `/settings/roles/{id}` | `tenant.settings.roles.update` | `permission:settings.roles.edit` | Atualização de cargos |
| **DELETE**| `/settings/roles/{id}` | `tenant.settings.roles.destroy`| `permission:settings.roles.delete` | Exclusão de cargo |

#### Configurações da Empresa (`TenantCompanySettingController`)
| Método | Rota | Nome da Rota | Middleware de Permissão | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/settings/company` | `tenant.settings.company.edit` | `permission:settings.company.view` | Formulário de configurações da empresa |
| **POST** | `/settings/company` | `tenant.settings.company.update` | `permission:settings.company.edit` | Salva dados e logotipo da empresa |
| **GET** | `/settings/company/logo` | `tenant.settings.company.logo` | *Pública (Tenant)* | Transmite a imagem do logotipo do MinIO |

#### Perfil do Usuário (`TenantProfileController`)
| Método | Rota | Nome da Rota | Middleware de Permissão | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/profile` | `tenant.profile.edit` | *Autenticado* | Formulário de perfil do usuário |
| **POST** | `/profile` | `tenant.profile.update` | *Autenticado* | Atualiza nome e foto de avatar no MinIO |
| **GET** | `/profile/avatar` | `tenant.profile.avatar` | *Autenticado* | Transmite a foto de avatar do MinIO |
| **PUT** | `/profile/password` | `tenant.profile.password.update` | *Autenticado* | Atualiza a senha do usuário |

---

### 2.6. Módulo do Drive de Arquivos (Drive)
Gerenciamento de arquivos e pastas dos inquilinos com controle de permissão por usuário e lixeira.

#### Arquivos e Permissões (`TenantDriveController` & `TenantDriveSearchController` & `TenantDriveLogController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/drive` | `tenant.drive.index` | `permission:drive.drives.view` |
| **GET** | `/drive/search` | `tenant.drive.search` | `permission:drive.drives.view` |
| **GET** | `/drive/logs` | `tenant.drive.logs` | `permission:drive.logs.view` |
| **GET** | `/drive/{id}/download` | `tenant.drive.download` | `permission:drive.drives.view` |
| **POST** | `/drive` | `tenant.drive.store` | `permission:drive.drives.create` |
| **PUT** | `/drive` | `tenant.drive.update` | `permission:drive.drives.edit` |
| **DELETE**| `/drive/selected` | `tenant.drive.delete-selected`| `permission:drive.drives.delete` |
| **DELETE**| `/drive/{id}` | `tenant.drive.destroy` | `permission:drive.drives.delete` |
| **POST** | `/drive/permissions` | `tenant.drive.permissions.store` | `permission:drive.drives.create` |
| **GET** | `/drive/{id}/permissions`| `tenant.drive.permissions.users`| `permission:drive.drives.view` |
| **DELETE**| `/drive/{drive_id}/permissions/{user_id}`| `tenant.drive.permissions.remove`| `permission:drive.drives.delete` |

#### Pastas (`TenantDriveFolderController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/folders` | `tenant.drive.folders.index` | `permission:drive.folders.view` |
| **GET** | `/folders/create` | `tenant.drive.folders.create`| `permission:drive.folders.create` |
| **POST** | `/folders` | `tenant.drive.folders.store` | `permission:drive.folders.create` |
| **DELETE**| `/folders/{id}` | `tenant.drive.folders.destroy`| `permission:drive.folders.delete` |

#### Lixeira (`TenantDriveTrashController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/trash` | `tenant.drive.trash.index` | `permission:drive.trash.view` |
| **POST** | `/trash/restore` | `tenant.drive.trash.restore` | `permission:drive.trash.edit` |
| **DELETE**| `/trash` | `tenant.drive.trash.force-delete`| `permission:drive.trash.delete` |
| **POST** | `/trash/clear` | `tenant.drive.trash.clear` | `permission:drive.trash.delete` |

---

### 2.7. Módulo Documentações (Cadastros auxiliares, Propostas e Calculadora)
Agrupado no menu em "Documentações" (módulo `documents`). Todas as rotas ficam sob `/documents/...`, com nomes `tenant.documents.<recurso>.<ação>` e o middleware de permissão `documents.<recurso>.<view|create|edit|delete>`.

*   **Regras por registro (`ProposalPolicy`):** além da permissão da rota, as ações em uma proposta passam pela policy. Parceiro, Vendedor do imóvel e Cliente só veem as propostas a que estão vinculados (`Proposal::scopeVisibleTo`). Os documentos visíveis também dependem do cargo (`DocumentOwner::visibleTo`): o Parceiro vê os das pessoas e do imóvel, o Cliente não vê os do vendedor e o Vendedor do imóvel não vê os do comprador. O cálculo da calculadora só é visível para quem o fez ou para o administrador (`FeeCalculationPolicy`).
*   **Máscara no PDF de informações (`proposals.pdf.info`):** os dados de cada lado da negociação seguem a mesma regra dos documentos (`DocumentOwner::visibleTo`). Quem não pode ver os documentos de um lado recebe o CPF/CNPJ desse lado mascarado (`DocumentMask`: `***.982.247-**` ou `**.222.333/****-**`) e o e-mail e o telefone como "Oculto"; o nome continua visível. Na prática, o Cliente vê os dados do vendedor mascarados e o Vendedor do imóvel vê os do comprador mascarados. O Parceiro, que já pode ver os documentos dos dois lados, e a equipe veem tudo.
*   **Cadastros:** a exclusão é lógica e o `PATCH .../restore` desfaz. Bancos, cartórios, tipos e etapas usam páginas de listagem, criação e edição, com paginação no servidor.
*   **Endpoints JSON (consumidos pelo frontend):** `applicants.lookup` (busca do proponente pelo CPF; quem só tem cargos externos recebe apenas nome, CPF e se o proponente já existe), `fee-calculator.municipalities` (municípios do IBGE por UF) e `fee-calculator.proposals.search` (busca de propostas sem emolumento, no formato do `ComboboxRemote`).
*   **Limites de requisição:** `documents-lookup` (30 por minuto) e `fee-calculator` (20 por minuto), definidos no `AppServiceProvider`. A chave combina o domínio do tenant e o usuário, porque o store do limitador é compartilhado entre os tenants e os ids de usuário se repetem.

#### Bancos (`TenantBankController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/banks/create` | `tenant.documents.banks.create` | `permission:documents.banks.create` |
| **GET** | `/documents/banks/list` | `tenant.documents.banks.list` | `permission:documents.banks.view` |
| **POST** | `/documents/banks/store` | `tenant.documents.banks.store` | `permission:documents.banks.create` |
| **PUT** | `/documents/banks/{id}` | `tenant.documents.banks.update` | `permission:documents.banks.edit` |
| **DELETE** | `/documents/banks/{id}` | `tenant.documents.banks.destroy` | `permission:documents.banks.delete` |
| **GET** | `/documents/banks/{id}/edit` | `tenant.documents.banks.edit` | `permission:documents.banks.edit` |
| **PATCH** | `/documents/banks/{id}/restore` | `tenant.documents.banks.restore` | `permission:documents.banks.delete` |

#### Cartórios (`TenantNotaryController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/notaries/create` | `tenant.documents.notaries.create` | `permission:documents.notaries.create` |
| **GET** | `/documents/notaries/list` | `tenant.documents.notaries.list` | `permission:documents.notaries.view` |
| **POST** | `/documents/notaries/store` | `tenant.documents.notaries.store` | `permission:documents.notaries.create` |
| **PUT** | `/documents/notaries/{id}` | `tenant.documents.notaries.update` | `permission:documents.notaries.edit` |
| **DELETE** | `/documents/notaries/{id}` | `tenant.documents.notaries.destroy` | `permission:documents.notaries.delete` |
| **GET** | `/documents/notaries/{id}/edit` | `tenant.documents.notaries.edit` | `permission:documents.notaries.edit` |
| **PATCH** | `/documents/notaries/{id}/restore` | `tenant.documents.notaries.restore` | `permission:documents.notaries.delete` |

#### Tipos de Contrato (`TenantContractTypeController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/contract-types/create` | `tenant.documents.contract-types.create` | `permission:documents.contract_types.create` |
| **GET** | `/documents/contract-types/list` | `tenant.documents.contract-types.list` | `permission:documents.contract_types.view` |
| **POST** | `/documents/contract-types/store` | `tenant.documents.contract-types.store` | `permission:documents.contract_types.create` |
| **PUT** | `/documents/contract-types/{id}` | `tenant.documents.contract-types.update` | `permission:documents.contract_types.edit` |
| **DELETE** | `/documents/contract-types/{id}` | `tenant.documents.contract-types.destroy` | `permission:documents.contract_types.delete` |
| **GET** | `/documents/contract-types/{id}/edit` | `tenant.documents.contract-types.edit` | `permission:documents.contract_types.edit` |
| **PATCH** | `/documents/contract-types/{id}/restore` | `tenant.documents.contract-types.restore` | `permission:documents.contract_types.delete` |

#### Tipos de Custo (`TenantCostTypeController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/cost-types/create` | `tenant.documents.cost-types.create` | `permission:documents.cost_types.create` |
| **GET** | `/documents/cost-types/list` | `tenant.documents.cost-types.list` | `permission:documents.cost_types.view` |
| **POST** | `/documents/cost-types/store` | `tenant.documents.cost-types.store` | `permission:documents.cost_types.create` |
| **PUT** | `/documents/cost-types/{id}` | `tenant.documents.cost-types.update` | `permission:documents.cost_types.edit` |
| **DELETE** | `/documents/cost-types/{id}` | `tenant.documents.cost-types.destroy` | `permission:documents.cost_types.delete` |
| **GET** | `/documents/cost-types/{id}/edit` | `tenant.documents.cost-types.edit` | `permission:documents.cost_types.edit` |
| **PATCH** | `/documents/cost-types/{id}/restore` | `tenant.documents.cost-types.restore` | `permission:documents.cost_types.delete` |

#### Tipos de Imóvel (`TenantPropertyTypeController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/property-types/create` | `tenant.documents.property-types.create` | `permission:documents.property_types.create` |
| **GET** | `/documents/property-types/list` | `tenant.documents.property-types.list` | `permission:documents.property_types.view` |
| **POST** | `/documents/property-types/store` | `tenant.documents.property-types.store` | `permission:documents.property_types.create` |
| **PUT** | `/documents/property-types/{id}` | `tenant.documents.property-types.update` | `permission:documents.property_types.edit` |
| **DELETE** | `/documents/property-types/{id}` | `tenant.documents.property-types.destroy` | `permission:documents.property_types.delete` |
| **GET** | `/documents/property-types/{id}/edit` | `tenant.documents.property-types.edit` | `permission:documents.property_types.edit` |
| **PATCH** | `/documents/property-types/{id}/restore` | `tenant.documents.property-types.restore` | `permission:documents.property_types.delete` |

#### Empreendimentos (`TenantDevelopmentController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/developments/create` | `tenant.documents.developments.create` | `permission:documents.developments.create` |
| **GET** | `/documents/developments/list` | `tenant.documents.developments.list` | `permission:documents.developments.view` |
| **POST** | `/documents/developments/store` | `tenant.documents.developments.store` | `permission:documents.developments.create` |
| **PUT** | `/documents/developments/{id}` | `tenant.documents.developments.update` | `permission:documents.developments.edit` |
| **DELETE** | `/documents/developments/{id}` | `tenant.documents.developments.destroy` | `permission:documents.developments.delete` |
| **GET** | `/documents/developments/{id}/edit` | `tenant.documents.developments.edit` | `permission:documents.developments.edit` |
| **PATCH** | `/documents/developments/{id}/restore` | `tenant.documents.developments.restore` | `permission:documents.developments.delete` |

#### Etapas da Timeline (`TenantStageController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/stages/create` | `tenant.documents.stages.create` | `permission:documents.stages.create` |
| **GET** | `/documents/stages/list` | `tenant.documents.stages.list` | `permission:documents.stages.view` |
| **POST** | `/documents/stages/store` | `tenant.documents.stages.store` | `permission:documents.stages.create` |
| **PUT** | `/documents/stages/{id}` | `tenant.documents.stages.update` | `permission:documents.stages.edit` |
| **DELETE** | `/documents/stages/{id}` | `tenant.documents.stages.destroy` | `permission:documents.stages.delete` |
| **GET** | `/documents/stages/{id}/edit` | `tenant.documents.stages.edit` | `permission:documents.stages.edit` |
| **PATCH** | `/documents/stages/{id}/move` | `tenant.documents.stages.move` | `permission:documents.stages.edit` |
| **PATCH** | `/documents/stages/{id}/restore` | `tenant.documents.stages.restore` | `permission:documents.stages.delete` |

> **Reordenação (`move`):** aceita um de dois parâmetros e volta para a mesma página/filtro da listagem.
> *   **`direction`** (`up` / `down`): troca a etapa com a vizinha ativa. Usado pelas ações "Mover para cima/baixo" do menu.
> *   **`target_id`**: leva a etapa para a posição da etapa alvo (ativa), deslocando as que ficam entre as duas. Usado ao arrastar a linha na listagem. As ordens são redistribuídas entre os mesmos valores que as etapas ativas já usam, sem colidir com etapas excluídas.
> *   A nova ordem só vale para timelines instanciadas depois: `proposal_stages.position` é uma cópia feita na criação da timeline.

#### Serviços Cobráveis (`TenantBillableServiceController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/billable-services/create` | `tenant.documents.billable-services.create` | `permission:documents.billable_services.create` |
| **GET** | `/documents/billable-services/list` | `tenant.documents.billable-services.list` | `permission:documents.billable_services.view` |
| **POST** | `/documents/billable-services/store` | `tenant.documents.billable-services.store` | `permission:documents.billable_services.create` |
| **PUT** | `/documents/billable-services/{id}` | `tenant.documents.billable-services.update` | `permission:documents.billable_services.edit` |
| **DELETE** | `/documents/billable-services/{id}` | `tenant.documents.billable-services.destroy` | `permission:documents.billable_services.delete` |
| **GET** | `/documents/billable-services/{id}/edit` | `tenant.documents.billable-services.edit` | `permission:documents.billable_services.edit` |
| **PATCH** | `/documents/billable-services/{id}/restore` | `tenant.documents.billable-services.restore` | `permission:documents.billable_services.delete` |

#### Propostas (`TenantProposalController`, `TenantApplicantLookupController` e `TenantProposalPdfController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/applicants/lookup` | `tenant.documents.applicants.lookup` | `permission:documents.proposals.create` + `throttle:documents-lookup` |
| **GET** | `/documents/proposals/create` | `tenant.documents.proposals.create` | `permission:documents.proposals.create` |
| **GET** | `/documents/proposals/list` | `tenant.documents.proposals.list` | `permission:documents.proposals.view` |
| **GET** | `/documents/proposals/pdf/list` | `tenant.documents.proposals.pdf.list` | `permission:documents.proposals.view` |
| **POST** | `/documents/proposals/store` | `tenant.documents.proposals.store` | `permission:documents.proposals.create` |
| **GET** | `/documents/proposals/{id}` | `tenant.documents.proposals.show` | `permission:documents.proposals.view` |
| **PUT** | `/documents/proposals/{id}` | `tenant.documents.proposals.update` | `permission:documents.proposals.edit` |
| **DELETE** | `/documents/proposals/{id}` | `tenant.documents.proposals.destroy` | `permission:documents.proposals.delete` |
| **GET** | `/documents/proposals/{id}/edit` | `tenant.documents.proposals.edit` | `permission:documents.proposals.view` |
| **PATCH** | `/documents/proposals/{id}/particularities` | `tenant.documents.proposals.particularities.update` | `permission:documents.proposals.edit` |
| **GET** | `/documents/proposals/{id}/pdf/info` | `tenant.documents.proposals.pdf.info` | `permission:documents.proposals.view` |
| **GET** | `/documents/proposals/{id}/pdf/tracking` | `tenant.documents.proposals.pdf.tracking` | `permission:documents.proposals.view` |

#### Acompanhamento (`TenantProposalTimelineController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **POST** | `/documents/proposals/{id}/timeline/restore` | `tenant.documents.proposals.timeline.restore` | `permission:documents.proposal_timeline.delete` |
| **POST** | `/documents/proposals/{id}/timeline/start` | `tenant.documents.proposals.timeline.start` | `permission:documents.proposal_timeline.edit` |
| **POST** | `/documents/proposals/{id}/timeline/{proposalStageId}/complete` | `tenant.documents.proposals.timeline.complete` | `permission:documents.proposal_timeline.edit` |

#### Documentos da Proposta (`TenantProposalDocumentController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **POST** | `/documents/proposals/{id}/documents` | `tenant.documents.proposals.documents.store` | `permission:documents.proposal_documents.create` |
| **DELETE** | `/documents/proposals/{id}/documents/{documentId}` | `tenant.documents.proposals.documents.destroy` | `permission:documents.proposal_documents.delete` |
| **GET** | `/documents/proposals/{id}/documents/{documentId}/download` | `tenant.documents.proposals.documents.download` | `permission:documents.proposal_documents.view` |

#### Pagamentos, Taxas e Recibos (`TenantProposalCostItemController` e `TenantReceiptController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **POST** | `/documents/proposals/{id}/cost-items` | `tenant.documents.proposals.cost-items.store` | `permission:documents.proposal_financial.create` |
| **PATCH** | `/documents/proposals/{id}/cost-items/{costItemId}` | `tenant.documents.proposals.cost-items.update` | `permission:documents.proposal_financial.edit` |
| **DELETE** | `/documents/proposals/{id}/cost-items/{costItemId}` | `tenant.documents.proposals.cost-items.destroy` | `permission:documents.proposal_financial.delete` |
| **GET** | `/documents/proposals/{id}/cost-items/{costItemId}/bill` | `tenant.documents.proposals.cost-items.bill` | `permission:documents.proposals.view` |
| **POST** | `/documents/proposals/{id}/cost-items/{costItemId}/proof` | `tenant.documents.proposals.cost-items.proof.store` | `permission:documents.proposals.view` |
| **GET** | `/documents/proposals/{id}/cost-items/{costItemId}/proof` | `tenant.documents.proposals.cost-items.proof` | `permission:documents.proposals.view` |
| **POST** | `/documents/proposals/{id}/receipts` | `tenant.documents.proposals.receipts.store` | `permission:documents.proposal_financial.create` |
| **DELETE** | `/documents/proposals/{id}/receipts/{receiptId}` | `tenant.documents.proposals.receipts.destroy` | `permission:documents.proposal_financial.delete` |
| **GET** | `/documents/proposals/{id}/receipts/{receiptId}/pdf` | `tenant.documents.proposals.receipts.pdf` | `permission:documents.proposal_financial.view` |

#### Calculadora de Emolumentos (`TenantFeeCalculatorController`, `TenantFeeCalculationController`, `TenantMunicipalityLookupController`, `TenantProposalSearchController` e `TenantProposalFeeEstimateController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/fee-calculator` | `tenant.documents.fee-calculator.index` | `permission:documents.itbi_calculator.view` |
| **POST** | `/documents/fee-calculator/calculate` | `tenant.documents.fee-calculator.store` | `permission:documents.itbi_calculator.create` + `throttle:fee-calculator` |
| **GET** | `/documents/fee-calculator/calculate/{type}` | `tenant.documents.fee-calculator.create` | `permission:documents.itbi_calculator.create` |
| **GET** | `/documents/fee-calculator/proposals/search` | `tenant.documents.fee-calculator.proposals.search` | `permission:documents.itbi_calculator.create` |
| **GET** | `/documents/fee-calculator/results/{id}` | `tenant.documents.fee-calculator.results.show` | `permission:documents.itbi_calculator.view` |
| **GET** | `/documents/fee-calculator/results/{id}/attach` | `tenant.documents.fee-calculator.attach.create` | `permission:documents.itbi_calculator.create` |
| **POST** | `/documents/fee-calculator/results/{id}/attach` | `tenant.documents.fee-calculator.attach.store` | `permission:documents.itbi_calculator.create` |
| **GET** | `/documents/fee-calculator/states/{state}/municipalities` | `tenant.documents.fee-calculator.municipalities` | `permission:documents.itbi_calculator.view` |

#### Orçamentos (`TenantQuoteController`, `TenantQuotePdfController`, `TenantSendQuoteEmailController` e `TenantConvertQuoteToProposalController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/quotes/create/{feeCalculationId}` | `tenant.documents.quotes.create` | `permission:documents.quotes.create` |
| **GET** | `/documents/quotes/list` | `tenant.documents.quotes.list` | `permission:documents.quotes.view` |
| **POST** | `/documents/quotes/store/{feeCalculationId}` | `tenant.documents.quotes.store` | `permission:documents.quotes.create` |
| **GET** | `/documents/quotes/{id}` | `tenant.documents.quotes.show` | `permission:documents.quotes.view` |
| **PUT** | `/documents/quotes/{id}` | `tenant.documents.quotes.update` | `permission:documents.quotes.edit` |
| **DELETE** | `/documents/quotes/{id}` | `tenant.documents.quotes.destroy` | `permission:documents.quotes.delete` |
| **POST** | `/documents/quotes/{id}/convert` | `tenant.documents.quotes.convert` | `permission:documents.quotes.edit` |
| **GET** | `/documents/quotes/{id}/edit` | `tenant.documents.quotes.edit` | `permission:documents.quotes.edit` |
| **POST** | `/documents/quotes/{id}/email` | `tenant.documents.quotes.email` | `permission:documents.quotes.edit` |
| **GET** | `/documents/quotes/{id}/pdf` | `tenant.documents.quotes.pdf` | `permission:documents.quotes.view` |

#### ITBI - Municípios (`TenantItbiMunicipalityController`, `TenantItbiRateController` e `TenantItbiBracketController`)
| Método | Rota | Nome da Rota | Middleware de Permissão |
| :--- | :--- | :--- | :--- |
| **GET** | `/documents/itbi/municipalities/create` | `tenant.documents.itbi-municipalities.create` | `permission:documents.itbi_municipalities.create` |
| **GET** | `/documents/itbi/municipalities/list` | `tenant.documents.itbi-municipalities.list` | `permission:documents.itbi_municipalities.view` |
| **POST** | `/documents/itbi/municipalities/store` | `tenant.documents.itbi-municipalities.store` | `permission:documents.itbi_municipalities.create` |
| **PUT** | `/documents/itbi/municipalities/{id}` | `tenant.documents.itbi-municipalities.update` | `permission:documents.itbi_municipalities.edit` |
| **DELETE** | `/documents/itbi/municipalities/{id}` | `tenant.documents.itbi-municipalities.destroy` | `permission:documents.itbi_municipalities.delete` |
| **GET** | `/documents/itbi/municipalities/{id}/brackets` | `tenant.documents.itbi-municipalities.brackets.edit` | `permission:documents.itbi_municipalities.edit` |
| **PUT** | `/documents/itbi/municipalities/{id}/brackets` | `tenant.documents.itbi-municipalities.brackets.update` | `permission:documents.itbi_municipalities.edit` |
| **GET** | `/documents/itbi/municipalities/{id}/edit` | `tenant.documents.itbi-municipalities.edit` | `permission:documents.itbi_municipalities.edit` |
| **GET** | `/documents/itbi/municipalities/{id}/rate` | `tenant.documents.itbi-municipalities.rates.edit` | `permission:documents.itbi_municipalities.edit` |
| **PUT** | `/documents/itbi/municipalities/{id}/rate` | `tenant.documents.itbi-municipalities.rates.update` | `permission:documents.itbi_municipalities.edit` |

> `fee-calculator.store` calcula e grava o resultado em `fee_calculations`, redirecionando para `results.show` com o UUID. O navegador nunca carrega os valores calculados. `quotes.convert` cria a proposta a partir do orçamento e bloqueia uma segunda conversão. `quotes.email` envia o resumo com o PDF do orçamento anexado.
