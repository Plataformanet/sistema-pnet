# Tasks — Sistema PNET v2

> **O que é:** o backlog executável da v2, na ordem das fases do [plan.md](plan.md#9-fases-e-marcos).
> Cada tarefa referencia as regras do [spec.md](spec.md) e é feita pela **skill responsável**.
> O fluxo de cada tarefa está no [implementation.md](implementation.md).
>
> **Todos os comandos rodam na raiz de `sistema-pnet-v2`.**

## Legenda

| Sigla | Skill |
| --- | --- |
| **BE** | `laravel-development-senior` (backend novo) |
| **FE** | `inertia-vue-frontend-senior` (Vue/Inertia/Tailwind) |
| **RF** | `laravel-refactoring-senior` (correções e ajustes em código já feito) |
| **CR** | `laravel-code-review-senior` (revisão, só relatório) |
| **QA** | `qa-senior` (plano de testes, regressão, aceite) |

**Pronto quando:** atende à Definition of Done da [constitution.md §14](constitution.md#14-definition-of-done-global).

**Toda tarefa BE ou FE termina com CR.** Toda fase termina com uma tarefa QA de aceite.

**Status:** ☐ pendente · ◐ em andamento · ☑ concluída. Quem conclui a tarefa marca ☑ aqui (Q-I-02).

### Comandos recorrentes

```bash
vendor/bin/sail up -d
vendor/bin/sail artisan migrate --path=database/migrations/central
vendor/bin/sail artisan tenants:migrate
vendor/bin/sail artisan tenants:sync-permissions
vendor/bin/sail artisan test --compact tests/Feature/<Arquivo>Test.php
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail npm run type-check
vendor/bin/sail npm run build
```

---

## F0 — Bootstrap

| Status | ID | Tarefa | Camadas / arquivos | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F0-01 | Criar o projeto Laravel 13 em `sistema-pnet-v2` sem apagar `specs/` e `.claude/skills/`; instalar o Sail com mysql, redis, minio e mailpit (`compose.yaml` igual ao da v1) | raiz | — | BE |
| ☐ | T-F0-02 | Instalar os pacotes nas versões da v1: stancl/tenancy, spatie/permission, inertia-laravel, fortify, ziggy, dompdf, flysystem-s3; dev: pest, boost, pint, pail | `composer.json` | 01 | BE |
| ☐ | T-F0-03 | Configurar a tenancy: `config/tenancy.php`, `TenancyServiceProvider` (pipeline), bootstrappers (incluindo `TenantSessionBootstrapper`), `bootstrap/app.php` (rotas central/tenant, exceções) e `routes/tenant.php` com o grupo base | config, providers, routes | 02 | BE |
| ☐ | T-F0-04 | Configurar `config/bucket.php`, `filesystems` (disco minio com `retain_visibility=false`), `proposals.php`, `reports.php`, `services.php` (registry_fee, ibge) e o `.env.example` completo | config | 02 | BE |
| ☐ | T-F0-05 | Criar a infraestrutura de testes: `tests/Pest.php` (helpers), `TestCase.php`, `Support/TenantRegistry.php`, `phpunit.xml` (banco `testing`) | tests | 03 | QA |
| ☐ | T-F0-06 | Copiar o front da v1: `package.json`, `vite.config.js`, `tsconfig.json`, `components.json`, `.prettierrc`, `resources/css/app.css`, `resources/views/app.blade.php`, `resources/js/{app.ts,bootstrap.ts,components,layouts,lib,composables,types}`. Ainda sem páginas. | resources | 01 | FE |
| ☐ | T-F0-07 | Limpar o front copiado: remover `login-04`, `SearchForm.vue`, `PermissionGroupCard.vue` e o botão "Exportar lista"; carregar a fonte Instrument Sans | resources/js | 06 | FE |
| ☐ | T-F0-08 | Instalar o Boost (`boost:install`), copiar as skills para `.claude/skills/` e o `CLAUDE.md` da v2 | raiz | 02 | BE |
| ☐ | T-F0-09 | `npm install`, `npm run build`, `type-check` sem erro; Pint configurado | — | 07 | FE |
| ☐ | T-F0-10 | Aceite F0: o ambiente sobe, a build passa e a suíte vazia fica verde | — | todos | QA |

## F1 — CORE

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F1-01 | Migrations centrais: tenants, domains, plans, modules, plan_modules, tenant_modules, permissions (`name` único), cache/jobs/sessions. **Sem tabelas de cobrança.** | RN-CORE-05, D-11 | F0 | BE |
| ☐ | T-F1-02 | Migrations de dados centrais: `seed_core_modules`, `seed_core_permissions` (catálogo da [spec §4](spec.md#4-catálogo-de-permissões-da-v2), incluindo `crm.deals.view` e `crm.pipelines.view`), `seed_plans` | RN-CORE-05 | 01 | BE |
| ☐ | T-F1-03 | Models `Tenant`, `Domain`, `Plan`, `Module`, `PlanModule`, `TenantModule`, `Permission` (central); `TenantProvisioningStatusEnum` | — | 01 | BE |
| ☐ | T-F1-04 | Migrations de tenant base: users (+2FA, softDeletes), password_reset_tokens, password_set_tokens, tabelas do spatie, tenant_settings (`key` único), sessions, cache | RN-CORE-08, Q-S-01 | F0 | BE |
| ☐ | T-F1-05 | Migrations de dados de tenant: cargos (`RoleEnum`), 19 configurações padrão + chaves `documents.finance.*` vazias | RN-CORE-13, RN-MUD02-09 | 04 | BE |
| ☐ | T-F1-06 | `TenantPermissionService` (forModules, forTenant, apply, applyRoleDefaults, sync) + `SyncTenantPermissionsCommand` | RN-CORE-06/07 | 02, 04 | BE |
| ☐ | T-F1-07 | Cadastro do SaaS: `StoreTenantRegistrationRequest`, `TenantService`, `TenantRegistrationController` (store, status com throttle), `SeedTenantDatabaseJob` | RF-CORE-01/02, RN-CORE-01..04 | 06 | BE |
| ☐ | T-F1-08 | Telas centrais `Register.vue` e `Provisioning.vue` (1:1 com a v1) | RF-CORE-01/02 | 07 | FE |
| ☐ | T-F1-09 | Auth do tenant: `AuthTenantController`, `TenantPasswordService` (brokers users/welcome), rate limit `password-reset`, `RedirectProposalRestrictedUsersMiddleware`, `HandleInertiaRequests` (props compartilhadas) | RF-CORE-04..06, RN-CORE-08..10 | 04 | BE |
| ☐ | T-F1-10 | Páginas de auth (`Login`, `ForgotPassword`, `ResetPassword`) e `AuthLayout` | RF-CORE-04..06 | 09 | FE |
| ☐ | T-F1-11 | `TenantLayout` e `TenantSidebar`, com a navegação da v2: sem "Vendas" e sem "Serviços Cobráveis"; CRM com `crm.deals.view` | RF-CORE-03 | 09 | FE |
| ☐ | T-F1-12 | Usuários: `UserService` (inativar + soft delete), requests, `TenantUserController`, rotas e permissões | RF-CORE-07, RN-CORE-11 | 06 | BE |
| ☐ | T-F1-13 | Cargos: `RoleService`, requests, `TenantRoleController` | RF-CORE-08 | 06 | BE |
| ☐ | T-F1-14 | Telas de usuários e cargos (`PermissionsGrid`) | RF-CORE-07/08 | 12, 13 | FE |
| ☐ | T-F1-15 | Empresa: `CompanySettingService` (+ logo, `pdfBranding`), `TenantSettingsService`, `TenantCompanySettingController` (edit, update, logo) | RF-CORE-09, RN-CORE-12 | 05 | BE |
| ☐ | T-F1-16 | Perfil: `UserProfileService`, `TenantProfileController` (avatar, senha) | RF-CORE-10 | 04 | BE |
| ☐ | T-F1-17 | Telas Empresa e Perfil | RF-CORE-09/10 | 15, 16 | FE |
| ☐ | T-F1-18 | `bucket:ensure` (`EnsureBucketCommand`) | — | F0 | BE |
| ☐ | T-F1-19 | Portar os testes da v1: TenantTest, TenantPermissionServiceTest, TenantPasswordResetTest, AuthTenantLoginRedirectTest, UserServiceTest, UserProfileTest, CompanySettingsTest, StoreTenantRegistrationRequestTest, RedirectProposalRestrictedUsersTest | — | 07..16 | QA |
| ☐ | T-F1-20 | Aceite F1: provisionamento real (`createTenant`), login por cargo, isolamento entre 2 tenants | CA-CORE-01/02 | todos | QA |

## F2 — CAD

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F2-01 | Migrations: contacts (`cpf_cnpj` só dígitos + unique), addresses, clients, suppliers, employees (salário em ubigint), financial_contacts | RN-CAD-01/02/06 | F1 | BE |
| ☐ | T-F2-02 | Enums `PersonTypeEnum`, `ContactTypeEnum`; Rules `CpfRule`, `CnpjRule`; Models com relações e `deleteIfOrphaned` | RN-CAD-03/05 | 01 | BE |
| ☐ | T-F2-03 | `ContactService`, `ClientService`, `SupplierService`, `EmployeeService` (persist idempotente, toggleActive, destroy com bloqueio); exceções | RN-CAD-02..05 | 02 | BE |
| ☐ | T-F2-04 | Requests (normalização do documento no `prepareForValidation`), controllers, rotas (incluindo `get-contact-by-cpf-cnpj`, `toggle-active`) e permissões | RF-CAD-01..03 | 03 | BE |
| ☐ | T-F2-05 | Telas de clientes, fornecedores e funcionários (copiadas), com `useContactLookup` sobre a rota nomeada | RF-CAD-01..03 | 04 | FE |
| ☐ | T-F2-06 | Portar ClientServiceTest, SuppliersServiceTest→SupplierServiceTest, EmployeesServiceTest→EmployeeServiceTest e StoreContactRequestTest; teste novo de unicidade de documento | CA-CAD-01/02 | 03 | QA |

## F3 — CAT

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F3-01 | Migrations e models: product_categories, products, service_categories, services (ubigint; `services.sku` único) | RN-CAT-01, Q-S-03 | F1 | BE |
| ☐ | T-F3-02 | `ProductService`, `ProductCategoryService`, `ServiceCatalogService` (+ `resolveSelection`, `scopeActive`), `ServiceCategoryService`; requests (`min:0`), controllers e rotas | RF-CAT-01..03, RN-CAT-02/03 | 01 | BE |
| ☐ | T-F3-03 | Telas de produtos, serviços e categorias (copiadas) | RF-CAT-01..03 | 02 | FE |
| ☐ | T-F3-04 | Testes novos (o catálogo não tinha testes): CRUD, validação de negativo, `resolveSelection` | RN-CAT-01/02 | 02 | QA |

## F4 — FIN

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F4-01 | Migrations: bank_accounts, financial_categories, financial_subcategories, cost_types (compartilhado), account_payables/receivables (`cost_type_id`, sem `bank_account_out`, já com `proposal_id` e `proposal_cost_item_id` nulos + unique), installments | RN-FIN-01..04, RN-FIN-13 | F2 | BE |
| ☐ | T-F4-02 | Enums `FinancialCategoryTypeEnum`, `InstallmentStatusEnum`, `PaymentMethodEnum`; Models | — | 01 | BE |
| ☐ | T-F4-03 | `BankAccountService` (conta principal única, bloqueio de exclusão com lançamentos) | RN-FIN-01/02 | 02 | BE |
| ☐ | T-F4-04 | `FinancialCategoryService` e `FinancialSubcategoryService` | RN-FIN-03 | 02 | BE |
| ☐ | T-F4-05 | `AccountService` abstrato + `AccountPayableService`/`AccountReceivableService`: criar, editar, baixar, excluir, saldo atômico, divisão de parcelas sem duplicação create/update; exceções de domínio | RN-FIN-05..12 | 03 | BE |
| ☐ | T-F4-06 | Requests (`Index…` com query string em inglês), controllers (lista com KPIs, contacts, show, installments/update) e rotas | RF-FIN-03..05 | 05 | BE |
| ☐ | T-F4-07 | `CashFlowService`, `SpendingFlowService` (+ PDF), `BillingFlowService` (SQL portável) + controllers | RF-FIN-06..08 | 05 | BE |
| ☐ | T-F4-08 | Telas do financeiro (copiadas; parâmetros de filtro em inglês; os rótulos não mudam) | RF-FIN-01..08 | 06, 07 | FE |
| ☐ | T-F4-09 | `DashboardService` (KPIs reais + próximos vencimentos) + `TenantController@dashboard` + widgets ligados às props | RN-CORE-14 | 05 | BE |
| ☐ | T-F4-10 | Dashboard: ligar os 4 KPIs e "Próximos vencimentos" às props, sem mudar o visual | RN-CORE-14 | 09 | FE |
| ☐ | T-F4-11 | Portar AccountBalanceServiceTest, AccountPayableTest, AccountReceivableTest, os Index*RequestTest, FinancialCategoryTest, BankAccountServiceTest (com bloqueio), Cash/Spending/BillingFlowServiceTest; teste novo do dashboard | CA-FIN-01..03 | 05..09 | QA |

## F5 — DRV

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F5-01 | Migrations: drive_folders, drives, drive_permissions (`DrivePermissionTypeEnum` em inglês), drive_logs estruturado | RN-DRV-01..05 | F1 | BE |
| ☐ | T-F5-02 | `DriveService` (recebe array/DTO, não FormRequest), `DriveFolderService` (disco do bucket), `DriveLogService`, `DrivePolicy`; mover/restaurar/excluir depois do commit | RN-DRV-01..06 | 01 | BE |
| ☐ | T-F5-03 | Requests, controllers (drive, search, logs, folders, trash) e rotas | RF-DRV-01/02 | 02 | BE |
| ☐ | T-F5-04 | Telas do Drive (lista, lixeira, logs, modais, composables), sem mudança visual | RF-DRV-01/02 | 03 | FE |
| ☐ | T-F5-05 | Portar DriveServiceTest, DriveFolderServiceTest, StoreDriveRequestTest, DriveLogServiceTest, TenantDriveControllerTest | — | 02 | QA |

## F6 — DOC catálogos e etapas

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F6-01 | Migrations: banks, notaries, contract_types, property_types, developments, stages (`bank_id`, idx `(bank_id, order)`); `seed_documents_catalogs` (sem serviços cobráveis) | 2.7, RN-DOC-STG-01 | F1 | BE |
| ☐ | T-F6-02 | `BrazilianStateEnum`, `ReceiptTypeEnum`; `UniqueCatalogNameRule`; Models (`scopeOrdered`, `withTrashed` nas relações) | RN-DOC-CAT-01..03 | 01 | BE |
| ☐ | T-F6-03 | `CatalogService` + Bank/Notary/ContractType/CostType/PropertyType/Development services; requests, controllers, rotas (com restore) e permissões | 2.7 | 02 | BE |
| ☐ | T-F6-04 | `StageService` (timeline por banco, move, moveTo, restore, timelineOptions, lock da ordem) + requests (`Index`/`Store`/`Update`/`MoveStageRequest`) + controller | RN-DOC-STG-01..06 | 03 | BE |
| ☐ | T-F6-05 | Telas dos 6 catálogos e das etapas (copiadas; remover `billable-services`) | 2.7, 2.8 | 03, 04 | FE |
| ☐ | T-F6-06 | Portar CatalogServiceTest, CatalogRequestTest (sem billable), StageServiceTest, ReferenceDataMigrationsTest | CA-DOC-STG-01 | 04 | QA |

## F7 — DOC propostas

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F7-01 | Migrations: applicants, sellers, holder_bank_accounts, proposals, proposal_partner, applicant_proposal, proposal_seller, proposal_properties, proposal_stages (+`overdue_alerted_at`), proposal_documents (+ FK circular), proposal_cost_items (+`service_id`), receipts, proposal_service | 2.9, MUD-04 | F2, F3, F6 | BE |
| ☐ | T-F7-02 | Enums da proposta (`ProposalStatusEnum`, `PropertyConditionEnum`, `AmortizationTableEnum`, `MaritalStatusEnum`, `BankAccountTypeEnum`, `DocumentOwnerEnum`, `DocumentTypeEnum`, `CostItemTypeEnum` com `generatesReceivable`/`generatesPayable`) + `RoleEnum` helpers; Models + scopes (`visibleTo`, `withoutFeeEstimate`, `finalStage`) | RN-DOC-PRO-04/08/09 | 01 | BE |
| ☐ | T-F7-03 | `ApplicantService` (`findOrCreateByCpf`), `SellerService` (`findOrCreateByDocument`), `ProposalService` (create, update com troca de banco, delete, status), `ProposalQueryService` (lista, contadores), `ProposalPolicy` | RN-DOC-PRO-01..08 | 02 | BE |
| ☐ | T-F7-04 | `ProposalTimelineService` (instantiate por banco, start, complete, restore, overdueStages) + `AlertOverdueProposalStagesCommand` com `overdue_alerted_at` | RN-DOC-PRO-10..15 | 03 | BE |
| ☐ | T-F7-05 | `ProposalDocumentService` (privado, checklist, storeGenerated, remoção depois do commit) | RN-DOC-PRO-17/18 | 03 | BE |
| ☐ | T-F7-06 | `ProposalCostItemService` (manual, valor, comprovante, boleto, destroy) + `ReceiptService` (sem recibo para `service`) | RN-DOC-PRO-19..23 | 03 | BE |
| ☐ | T-F7-07 | `ProposalPdfService` (info com máscara, tracking, list, seleção, recibo) + `DocumentMaskHelper`, `MoneyHelper` | RF-DOC-PRO-06 | 03 | BE |
| ☐ | T-F7-08 | Mails (8, com `ShouldQueue` + `afterCommit`) e templates | RN-DOC-PRO-03/10..12 | 03 | BE |
| ☐ | T-F7-09 | Requests, controllers (proposal, applicant/seller editors, lookup, timeline, documents, cost-items, receipts, pdf) e rotas | RF-DOC-PRO-01..06 | 03..08 | BE |
| ☐ | T-F7-10 | Telas de propostas (lista, criar, editar com 6 abas, ver), copiadas; prefill de `source=crm` (MUD-01) | RF-DOC-PRO-01..05, RN-MUD01-03 | 09 | FE |
| ☐ | T-F7-11 | Portar ProposalServiceTest, ProposalRequestTest, ProposalTimelineServiceTest (+ alerta único), ProposalCostItemServiceTest, ProposalPolicyTest, ProposalDocumentServiceTest, ProposalInfoPdfTest, DocumentsRoutesTest | — | 03..09 | QA |

## F8 — DOC calculadora, ITBI e orçamentos (MUD-04, MUD-03)

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F8-01 | Migrations: itbi_municipalities, itbi_rates, itbi_brackets, fee_calculations (UUID), quotes, quote_service, fee_estimates | 2.10, 2.11, MUD-04 | F7 | BE |
| ☐ | T-F8-02 | Enums (`CalculationTypeEnum`, `SupportedStateEnum`, `FinancingSystemEnum`, `FeeDiscountEnum`, `ItbiModuleEnum`, `QuoteStatusEnum`); Models | — | 01 | BE |
| ☐ | T-F8-03 | `ItbiCalculator` + `ItbiInputDto` (classe pura) | RN-DOC-CALC-02 | 02 | BE |
| ☐ | T-F8-04 | `RegistryFeeApiClient` + `RegistryFeeRequestDto`/`RegistryFeeResultDto` + exceções; `IbgeLocalityClient` (cache global) | RN-DOC-CALC-03/08 | 02 | BE |
| ☐ | T-F8-05 | `ItbiMunicipalityService` + requests (`NonOverlappingBracketsRule`) + controllers (municípios, alíquotas, faixas) | RN-DOC-CALC-07 | 03 | BE |
| ☐ | T-F8-06 | `CostItemsBuilder` (linha `service` sem recibo, com `service_id`), `FeeBreakdownBuilder`, `FeeCalculationService`, `FeeCalculationPolicy` | RN-DOC-CALC-04/05, RN-MUD04-04 | 03, 04 | BE |
| ☐ | T-F8-07 | Actions `CalculateFeesAction`, `AttachFeeEstimateToProposalAction`, `CreateQuoteAction` (+ `QuoteCreatedEvent`), `UpdateQuoteAction`, `ConvertQuoteToProposalAction` (+ diálogo de contrato, `QuoteConvertedEvent`); `QuoteDeletedEvent` | RN-DOC-CALC-06, RN-DOC-QUO-01..05, MUD-03 | 06 | BE |
| ☐ | T-F8-08 | `QuoteService`, `QuotePdfService`, `QuoteSummaryMail`, `AttachQuotePdfToProposalJob`; controllers da calculadora e dos orçamentos; rotas (throttle `fee-calculator`, `documents-lookup`) | RF-DOC-CALC-*, RF-DOC-QUO-* | 07 | BE |
| ☐ | T-F8-09 | Telas da calculadora, do ITBI e dos orçamentos (copiadas); `ServicePicker` sobre `services` | RF-DOC-CALC-*, RF-DOC-QUO-*, RN-MUD04-03 | 08 | FE |
| ☐ | T-F8-10 | `lang/pt_BR/fee_calculator.php` (textos legais) + `FeeCalculationsPruneCommand` (`PruneExpiredFeeCalculationsCommand`) | — | 07 | BE |
| ☐ | T-F8-11 | Portar ItbiCalculatorTest, FeeCalculatorTest, QuoteFlowTest (sem billable); testes novos: `resolveSelection`, linha `service` sem recibo, sincronização do pivot, evento depois do commit | CA-DOC-CALC-01, CA-DOC-QUO-01/02, CA-MUD04-01 | 03..08 | QA |

## F9 — DOC protocolos e relatórios

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F9-01 | Migration registry_protocols + `RegistryProtocolPositionEnum` + model + `RegistryProtocolService` (partiesFor, panelFor) + requests, controllers e rotas | 2.12 | F7 | BE |
| ☐ | T-F9-02 | `FinishedProposalsReportQuery`, `ReportZipBuilder`, `BuildFinishedProposalsZipJob`, `FinishedProposalsZipReadyMail`, `PruneExpiredReportZipsCommand`, controllers (+ link assinado) | 2.13 | F7 | BE |
| ☐ | T-F9-03 | Telas de protocolos (+ painel na timeline) e do relatório | RF-DOC-REG-*, RF-DOC-REP-01 | 01, 02 | FE |
| ☐ | T-F9-04 | Portar RegistryProtocolServiceTest, RegistryProtocolRequestTest, ReportServiceTest, ReportZipBuilderTest | — | 01, 02 | QA |

## F10 — MUD-02: distribuição financeira

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F10-01 | `ProposalFinanceSettingsService` + `UpdateFinanceIntegrationSettingsRequest` + rota `tenant.settings.company.finance-integration.update` | RN-MUD02-09 | F4, F8 | BE |
| ☐ | T-F10-02 | Card "Integração Documentações → Financeiro" na tela Empresa (mesmos componentes) | RN-MUD02-09 | 01 | FE |
| ☐ | T-F10-03 | `ProposalFinancialDistributionService` (distribute, distributeAll, syncAmount, removeFor, removeOpenForProposal) + `LinkedEntryAlreadyPaidException` | RN-MUD02-01..07 | 01 | BE |
| ☐ | T-F10-04 | Ligar a distribuição em `ProposalCostItemService` (store, update, destroy), `CostItemsBuilder::persist`, nas Actions de vínculo e conversão e em `ProposalService::delete`; aviso `warning` quando faltar configuração | RN-MUD02-01/05/06 | 03 | BE |
| ☐ | T-F10-05 | Rota e controller `cost-items/distribute` (reprocessar) | RN-MUD02-06 | 03 | BE |
| ☐ | T-F10-06 | Aba Pagamentos: colunas "A receber" e "A pagar" + botão "Distribuir no financeiro"; badge "Proposta Nº" nas listas do financeiro | RN-MUD02-08 | 04, 05 | FE |
| ☐ | T-F10-07 | Testes: mapeamento por tipo, idempotência, sincronização do valor, bloqueio com parcela paga, exclusão da linha e da proposta, configuração ausente, conversão de orçamento ponta a ponta | CA-MUD02-01/02 | 04 | QA |

## F11 — CRM front + interino MUD-01

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-F11-01 | `TenantCrmController` (mock, como na v1) + rotas com `permission:crm.deals.view` + middleware de usuário externo | RN-CRM-01/02 | F1 | BE |
| ☐ | T-F11-02 | Telas do CRM (Kanban, Lista, modal, gaveta, tipos), copiadas 1:1 | RF-CRM-01..04 | 01 | FE |
| ☐ | T-F11-03 | Botão "Gerar proposta de documentações" na gaveta (só para `won` + permissão) → create com prefill | RN-MUD01-01/03 | 02, T-F7-10 | FE |
| ☐ | T-F11-04 | Registrar no backlog do CRM os contratos pendentes (MUD-01 RN-MUD01-02, MUD-03 listener e eventos) | 3.1, 3.3 | — | QA |

## F12 — Endurecimento

| Status | ID | Tarefa | Dep. | Skill |
| --- | --- | --- | --- | --- |
| ☐ | T-F12-01 | Code review geral por módulo (tenancy, N+1, segurança, nomes com sufixo) | F1..F11 | CR |
| ☐ | T-F12-02 | Corrigir os achados 🔴/🟠 do review | 01 | RF |
| ☐ | T-F12-03 | Regressão completa (`vendor/bin/sail artisan test --compact`) + testes de isolamento entre tenants por módulo | 02 | QA |
| ☐ | T-F12-04 | Checagem visual lado a lado v1 × v2 (light/dark, desktop/mobile) de todas as telas do mapa | F11 | FE + QA |
| ☐ | T-F12-05 | `.env.example`, `README` de setup e atualização dos documentos de `specs/` com o estado final | 03 | BE |
| ☐ | T-F12-06 | Relatório final de QA (aceite de cada CA do spec) | 03, 04 | QA |

---

## Decisões registradas (perguntas do tasks)

Respondidas em **2026-10-26**; em todas foi adotada a recomendação. Na prática:

- **Toda tarefa BE já sai com os próprios testes Pest.**
- As tarefas QA portam a regressão da v1, testam o isolamento e fazem o aceite da fase.

| ID | Pergunta | Decisão (= recomendação) |
| --- | --- | --- |
| Q-T-01 | A granularidade está boa (1 tarefa ≈ 1 entrega revisável), ou prefere quebrar mais, por exemplo uma tarefa por CRUD de catálogo? | Como está |
| Q-T-02 | Backend e frontend da mesma feature: tarefas separadas (como está) ou uma tarefa só, com as duas skills? | Separadas: o FE consome o contrato entregue pelo BE |
| Q-T-03 | O QA escreve os testes portados (como está) ou o BE escreve os testes da própria tarefa e o QA só valida? | BE escreve os testes da tarefa; QA porta a regressão da v1 e faz o aceite |
