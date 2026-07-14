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
| **GET** | `/reset-password` | `tenant.reset-password` | *Nenhum* | Redefinição de senha |
| **GET** | `/dashboard` | `tenant.dashboard` | `Authenticate` | Página inicial após o login |

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
| **PATCH** | `/finance/accounts-payable/installments/value` | `tenant.finance.accounts-payable.installments.value`| `permission:finance.accounts_payable.edit` |

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
| **PATCH** | `/finance/accounts-receivable/installments/value` | `tenant.finance.accounts-receivable.installments.value`| `permission:finance.accounts_receivable.edit` |

#### Fluxos e Relatórios Financeiros
| Método | Rota | Nome da Rota | Middleware de Permissão | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/finance/cash-flow` | `tenant.finance.cash-flow.index` | `permission:finance.cash_flow.view` | Fluxo de Caixa Geral |
| **GET** | `/finance/spending-flow` | `tenant.finance.spending-flow.index` | `permission:finance.spending_flow.view` | Fluxo de Gastos consolidado |
| **GET** | `/finance/spending-flow/pdf` | `tenant.finance.spending-flow.pdf` | `permission:finance.spending_flow.view` | Exporta relatório em PDF |
| **GET** | `/finance/billing` | `tenant.finance.billing.index` | `permission:finance.billing.view` | Relatório de Faturamentos |

---

### 2.5. Outras Configurações e Segurança
*   **Controller:** `TenantRoleController`

| Método | Rota | Nome da Rota | Middleware de Permissão | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/settings/roles/list` | `tenant.settings.roles.list` | `permission:settings.roles.view` | Listagem de cargos (Roles) |
| **GET** | `/settings/roles/create` | `tenant.settings.roles.create` | `permission:settings.roles.create` | Formulário de criação de cargo |
| **POST** | `/settings/roles/store` | `tenant.settings.roles.store` | `permission:settings.roles.create` | Salva novo cargo |
| **GET** | `/settings/roles/{id}/edit`| `tenant.settings.roles.edit` | `permission:settings.roles.edit` | Edição de cargos |
| **PUT** | `/settings/roles/{id}` | `tenant.settings.roles.update` | `permission:settings.roles.edit` | Atualização de cargos |
| **DELETE**| `/settings/roles/{id}` | `tenant.settings.roles.destroy`| `permission:settings.roles.delete` | Exclusão de cargo |

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
