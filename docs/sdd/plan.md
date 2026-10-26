# Plan — Sistema PNET v2

> **O que é:** o plano técnico de **como** construir o que o [spec.md](spec.md) define, obedecendo à
> [constitution.md](constitution.md). A ordem executável fica no [tasks.md](tasks.md), e o modo de
> executar no [implementation.md](implementation.md).
>
> Todos os comandos rodam na raiz de **`sistema-pnet-v2`**, via `vendor/bin/sail`.

---

## 1. Estratégia geral

1. **Reescrever o backend do zero, no padrão da constitution.** O código da v1 (`sistema-pnet`) é **referência de comportamento**: copiar a regra, não o arquivo.
   - Os testes da v1 (487 casos) são portados como **suíte de regressão**, renomeando classes e valores de enum.
2. **Reaproveitar o frontend 1:1.**
   - Os arquivos visuais da v1 (tema, `components/ui`, layouts, lib, composables, páginas) são copiados para a v2 e só ajustados onde o contrato mudou: enums em inglês, remoção de serviços cobráveis, MUD-01/02/04.
   - É assim que se garante "não mudar nada" no visual (P-10).
3. **As mesmas rotas e nomes de rota da v1** (`tenant.<modulo>.<recurso>.<acao>`), para as páginas Vue continuarem funcionando com o `route()` do Ziggy.
   - As exceções são as rotas removidas (serviços cobráveis) e as novas (MUD-02 e as configurações da integração).
4. **Ordem por dependência:** core e tenancy → cadastros → catálogo → financeiro → drive → documentações → integrações (MUD) → CRM front.
   - Cada fase só começa com a anterior verde (testes e review).

---

## 2. Estrutura de pastas da v2

```text
sistema-pnet-v2/
├── specs/                      # constitution, spec, plan, tasks, implementation (este SDD)
├── .claude/skills/             # skills dos agentes (ver implementation.md)
├── CLAUDE.md
├── app/
│   ├── Actions/
│   │   ├── FeeCalculator/      # CalculateFeesAction, CreateQuoteAction, UpdateQuoteAction,
│   │   │                       # ConvertQuoteToProposalAction, AttachFeeEstimateToProposalAction
│   │   └── Crm/                # (pendente) ConvertCrmDealToProposalAction
│   ├── Console/Commands/       # *Command
│   ├── Enums/                  # *Enum
│   ├── Events/                 # QuoteCreatedEvent
│   ├── Exceptions/             # *Exception (+ RegistryFee/, Itbi/)
│   ├── Http/
│   │   ├── Controllers/        # Tenant*Controller, Auth/AuthTenantController, TenantRegistrationController
│   │   ├── Middleware/         # *Middleware
│   │   └── Requests/           # Store|Update|Index|<Verbo>*Request
│   ├── Jobs/                   # *Job
│   ├── Listeners/              # (vazio até o CRM — ver 5.3)
│   ├── Mail/                   # *Mail
│   ├── Models/                 # sem sufixo, sem subpastas
│   ├── Policies/               # *Policy
│   ├── Providers/              # AppServiceProvider, TenancyServiceProvider, FortifyServiceProvider
│   ├── Queries/                # FinishedProposalsReportQuery
│   ├── Rules/                  # *Rule
│   ├── Services/               # *Service (+ FeeCalculator/, Itbi/, RegistryFee/, Ibge/, Reports/, Finance/)
│   ├── Support/                # MoneyHelper, DocumentMaskHelper
│   └── Tenancy/Bootstrappers/  # TenantSessionBootstrapper
├── config/                     # bucket.php, proposals.php, reports.php, services.php, tenancy.php …
├── database/
│   ├── migrations/central/
│   ├── migrations/tenant/
│   ├── factories/
│   └── seeders/                # só dados fictícios
├── lang/pt_BR/                 # fee_calculator.php, validation
├── resources/
│   ├── css/app.css             # cópia 1:1 da v1
│   ├── js/                     # cópia da v1 (pages, components, layouts, lib, composables, types)
│   └── views/                  # app.blade.php, pdf/, mail/, errors/
├── routes/                     # web.php (central), tenant.php, console.php
└── tests/                      # Pest.php, TestCase.php, Support/TenantRegistry.php, Feature/, Unit/
```

---

## 3. Recursos-espelho (o molde de cada tipo de feature)

| Tipo de feature | Espelho na v2 (construído primeiro) | Referência de comportamento na v1 |
| --- | --- | --- |
| CRUD simples de tenant | Produtos (`TenantProductController`, `ProductService`) | `app/Services/ProductService.php` |
| Papel sobre contato | Clientes (`ClientService::persist`, `ContactService`) | `ClientService`, `ContactService` |
| Catálogo com soft delete, restauração e `ServerDataTable` | Bancos (`CatalogService` + `BankService`) | `CatalogService`, `BankService` |
| Lançamento com saldo e parcelas | `AccountService` (abstrato) + `AccountPayableService` | `AccountService` |
| Fluxo de várias etapas | `ConvertQuoteToProposalAction` | `ConvertQuoteToProposal` |
| Policy por registro | `ProposalPolicy` | `ProposalPolicy` |
| Cliente HTTP externo | `RegistryFeeApiClient` + DTOs | `RegistryFeeApiClient` |
| Job em fila pós-commit | `AttachQuotePdfToProposalJob` | `AttachQuotePdfToProposal` |
| Migration de dados de referência | `seed_documents_catalogs` | `2026_10_01_100009_seed_documents_catalogs.php` |
| Permissão nova | Par de migrations central + tenant | `2026_10_15_100000_*` / `2026_10_15_100002_*` |

---

## 4. Mapeamento v1 → v2

### 4.1 Classes renomeadas (sufixo de tipo, D-01/D-04)

| v1 | v2 |
| --- | --- |
| Enums sem sufixo: `AmortizationTable`, `BankAccountType`, `BrazilianState`, `CalculationType`, `CostItemType`, `DocumentOwner`, `DocumentType`, `FeeDiscount`, `FinancingSystem`, `ItbiModule`, `MaritalStatus`, `PersonType`, `PropertyCondition`, `ProposalStatus`, `QuoteStatus`, `ReceiptType`, `RegistryProtocolPosition`, `SupportedState`, `TenantProvisioningStatus` | O mesmo nome + `Enum` (ex.: `ProposalStatusEnum`) |
| `RolesEnum`, `AccountsEnum`, `FinancialCategoryEnum`, `PermissionTypeDriveEnum`, `DocumentTypeDriveEnum`, `FinancialPaymentMethodEnum` | `RoleEnum`, `InstallmentStatusEnum`, `FinancialCategoryTypeEnum`, `DrivePermissionTypeEnum`, `DriveDocumentTypeEnum`, `PaymentMethodEnum` |
| `PermissionsEnum`, `PaymentStatusEnum`, `SubscriptionStatusEnum`, `CouponTypeEnum`, `CouponDurationEnum` | Removidos (sem uso / D-11) |
| Actions `CalculateFees`, `CreateQuote`, `UpdateQuote`, `ConvertQuoteToProposal`, `AttachFeeEstimateToProposal` | O mesmo nome + `Action` |
| Jobs `AttachQuotePdfToProposal`, `SeedTenantDatabase` | O mesmo nome + `Job` |
| Command `EnsureBucket` | `EnsureBucketCommand` (assinatura `bucket:ensure`) |
| Rules `NonOverlappingBrackets`, `UniqueCatalogName`, `UserHasRole` | O mesmo nome + `Rule` |
| Middleware `RedirectProposalRestrictedUsers` | `RedirectProposalRestrictedUsersMiddleware` |
| Services `SuppliersService`, `EmployeesService`, `ServicesService` | `SupplierService`, `EmployeeService`, `ServiceCatalogService` |
| Services `CoastService`, `PaidMarketService`, `BillableServiceService` | Removidos |
| DTOs `ItbiInput`, `RegistryFeeRequestData`, `RegistryFeeResult` | `ItbiInputDto`, `RegistryFeeRequestDto`, `RegistryFeeResultDto` |
| `FeeBreakdown` | `FeeBreakdownBuilder` (monta o DTO `FeeBreakdownDto`) |
| `Support\Money`, `Support\DocumentMask` | `MoneyHelper`, `DocumentMaskHelper` |
| `ReportService::finishedProposals` | `FinishedProposalsReportQuery` (`app/Queries`) |
| `TenantBillableServiceController`, `Store/UpdateBillableServiceRequest` | Removidos (MUD-04) |
| `TenantSettingController` (sem rota), `central/Dashboard.vue`, `drive_folder/*` | Removidos |
| `app/Utils/Utils.php` | Removido; as funções úteis vão para `*Helper` |

### 4.2 Banco de dados

O que muda está em [spec.md §5](spec.md). Notas de implementação:

- Como a v2 nasce do zero, **as migrations já nascem no formato final**: sem `rename`, `convert` nem "add column" incremental. Um arquivo por tabela, na ordem de FKs.
- **Exceção:** as migrations de dados de referência (módulos, permissões, planos, catálogos, cargos, configurações), que continuam separadas e idempotentes.
- A FK circular `proposal_stages.proposal_document_id` ↔ `proposal_documents.proposal_stage_id` é criada numa migration posterior às duas tabelas.

### 4.3 Valores de enum em inglês (D-07)

| Enum | v1 → v2 |
| --- | --- |
| `ContactTypeEnum` | `cliente`/`fornecedor`/`funcionário` → `client`/`supplier`/`employee` |
| `FinancialCategoryTypeEnum` | `receita`/`despesa` → `income`/`expense` |
| `DrivePermissionTypeEnum` | `somente_proprietario`/`somente_leitura`/`acesso_total` → `owner_only`/`read_only`/`full_access` |
| `PersonTypeEnum` | `PF`/`PJ` (contatos) e `Individual`/`Company` → `individual`/`company` (um enum só) |
| Query strings do financeiro | `periodo`, `inicio`, `fim`, `conta_id`, `categoria_id`, `quantidade` → `period`, `start`, `end`, `bank_account_id`, `category_id`, `per_page` |

O frontend ajusta os tipos TS e os `value` dos selects; os **rótulos exibidos não mudam**.

---

## 5. Design técnico das 4 mudanças

### 5.1 MUD-04 — Serviços do catálogo no orçamento e na proposta

- **Migrations:** `quote_service` e `proposal_service`, cada uma com `id`, `<owner>_id` (cascade), `service_id` (`restrictOnDelete`), `amount` (ubigint) e `unique(<owner>_id, service_id)`.
- **Models:**
  - `Quote::services()` e `Proposal::services()`: BelongsToMany(`Service`) com `withPivot('amount')->withTrashed()`.
  - `Service::quotes()` e `Service::proposals()`.
  - `Service::scopeActive()` (`status = true`).
- **`ServiceCatalogService::resolveSelection(array $items, Tenant $tenant): array`:**
  - descarta ids inexistentes, inativos ou excluídos, e duplicados;
  - devolve `[service_id => amount efetivo]`, onde vazio = `sell_value`.
- **`CostItemsBuilder`:** a linha `service` usa `name`, `description` (nullable) e `generates_receipt = false`.
- **`ProposalCostItemService::updateAmount()`:** quando a linha é `service`, atualiza também `proposal_service.amount`. Para isso, a linha guarda `service_id` (nova coluna nullable em `proposal_cost_items`).
- **Frontend:**
  - Remover `pages/tenant/documents/billable-services/**` e o item de menu.
  - `ServicePicker` passa a receber `services: { id, name, sell_value }[]`.
  - Em `types/documents/*.ts`, `BillableServiceOption` vira `ServiceOption`.
  - **O visual do picker não muda.**

### 5.2 MUD-02 — Distribuição financeira

**Novo service** `app/Services/Finance/ProposalFinancialDistributionService.php` (`final`):

| Método | O que faz |
| --- | --- |
| `distribute(ProposalCostItem $item, Tenant $tenant): void` | Idempotente por `proposal_cost_item_id`. Resolve a configuração (`ProposalFinanceSettingsService`); cria a conta a receber e, se o mapeamento pedir, a conta a pagar, reaproveitando `AccountReceivableService::create` / `AccountPayableService::create` (à vista, `open`). |
| `distributeAll(Proposal $proposal, Tenant $tenant): array` | Percorre as linhas sem lançamento. É usado pelo botão "Distribuir no financeiro" e devolve um resumo (criados ou pulados, com o motivo). |
| `syncAmount(ProposalCostItem $item, Tenant $tenant): void` | Atualiza o total e a parcela se a parcela estiver `open`; senão lança `LinkedEntryAlreadyPaidException`. |
| `removeFor(ProposalCostItem $item, Tenant $tenant): void` | Exclui os lançamentos vinculados, com o estorno do `AccountService::delete`. |
| `removeOpenForProposal(Proposal $proposal, Tenant $tenant): void` | Exclui os lançamentos abertos da proposta e mantém os pagos. |

- **Configuração:** `ProposalFinanceSettingsService` lê e grava as chaves `documents.finance.<tipo>.*` e `documents.finance.due_in_days` em `tenant_settings`, e diz se o tipo está "configurado".
- **Mapeamento** em `CostItemTypeEnum`, com os métodos `generatesReceivable()` (sempre `true`) e `generatesPayable()` (`false` só para `service`).
- **Ponto de chamada:** dentro da **mesma transação**, nos services/actions que criam, editam ou removem linhas:
  - `ProposalCostItemService::storeManual`, `updateAmount` e `destroy`;
  - `CostItemsBuilder::persist`, chamado por `AttachFeeEstimateToProposalAction` e `ConvertQuoteToProposalAction`;
  - `ProposalService::delete`.
  - Não se usam eventos, para manter a atomicidade (constitution, seção 10).
- **Falta de configuração:** registra o motivo e não lança exceção. O service chamador devolve um aviso, que o controller transforma em flash `warning`.
- **Contato a receber:** `FinancialContact (contact_id do 1º proponente, client)`. Se o papel de cliente não existir, ele é criado via `ClientService::persist`.
- **Contato a pagar:** o `payable_supplier_contact_id` configurado (contato com papel `supplier`).
- **Migration:** em `account_payables` e `account_receivables`, `proposal_id` e `proposal_cost_item_id` nullable, com `nullOnDelete`, mais `unique(proposal_cost_item_id)`.
- **Telas:**
  - **Configurações → Empresa:** novo card "Integração Documentações → Financeiro", uma tabela com uma linha por tipo, cinco selects (categoria/subcategoria a receber, categoria/subcategoria a pagar e fornecedor) e o campo de prazo. Usa os mesmos componentes `Select`, `Field` e card da tela.
  - **Aba Pagamentos e Taxas:** colunas "A receber" e "A pagar" (badge de status + link) e o botão "Distribuir no financeiro".
  - **Listas do financeiro:** badge "Proposta Nº 00042" na descrição.
- **Rotas novas:**
  - `POST /documents/proposals/{id}/cost-items/distribute` (`tenant.documents.proposals.cost-items.distribute`, `permission:documents.proposal_financial.create`).
  - `PUT /settings/company/finance-integration` (`tenant.settings.company.finance-integration.update`, `permission:settings.company.edit`): o card da tela Empresa (Q-P-05) tem um formulário próprio.

### 5.3 MUD-03 — Orçamento → funil do CRM

- **Agora:**
  - `app/Events/QuoteCreatedEvent.php` (`quoteId`, `tenantId`), com `ShouldDispatchAfterCommit`.
  - `CreateQuoteAction` dispara o evento.
  - Teste: `Event::fake()` e `assertDispatched` depois do commit.
- **Pendente** (fase CRM backend):
  - `app/Listeners/CreateCrmDealFromQuoteListener.php`, `ShouldQueue`.
  - **Atenção:** o Laravel 13 descobre listeners automaticamente em `app/Listeners`. Por isso **o arquivo não é criado** até o backend do CRM existir.
  - Os efeitos de conversão e exclusão (RN-MUD03-03) virão de `QuoteConvertedEvent` e `QuoteDeletedEvent`, também já disparados agora, sem listener.

### 5.4 MUD-01 — CRM → proposta de documentações

- **Agora (interino):**
  - Na `DealDetailDrawer.vue`, o botão "Gerar proposta de documentações" aparece só com `status === 'won'` e com a permissão `documents.proposals.create`.
  - Ele usa o mesmo `Button` e os mesmos estilos de ação da gaveta.
  - Faz `router.visit(route('tenant.documents.proposals.create', { source: 'crm', applicant_name, purchase_value }))`.
  - O `TenantProposalController@create` lê esses parâmetros (validados por `CreateProposalRequest`) e os envia como `prefill` para o `ProposalForm`.
- **Pendente:** `ConvertCrmDealToProposalAction`, `ProposalService::createFromCrmDeal(CrmDealDto, User)` e a coluna `crm_deals.proposal_id`, como no contrato RN-MUD01-02.

---

## 6. Frontend — reaproveitamento 1:1

1. **Copiar da v1 para a v2:**
   - `resources/css/app.css`, `resources/views/app.blade.php`;
   - `resources/js/{app.ts, bootstrap.ts, components/**, layouts/**, lib/**, composables/**, types/**, pages/**}`;
   - `components.json`, `tsconfig.json`, `.prettierrc`, `vite.config.js`;
   - as versões de `package.json`.
2. **Remover:**
   - `pages/tenant/documents/billable-services/**`, `pages/central/Dashboard.vue`;
   - `components/login-04/**`, `layouts/tenant-layout/SearchForm.vue`, `settings/roles/components/PermissionGroupCard.vue`;
   - o grupo "Vendas" e o item "Serviços Cobráveis" da sidebar.
3. **Ajustar o contrato:**
   - valores de enum em inglês (4.3);
   - `ServicePicker` (5.1);
   - colunas e botão da aba Pagamentos (5.2);
   - card de integração (5.2);
   - botão do CRM (5.4);
   - prefill do `ProposalForm`;
   - URLs literais trocadas por `route()`;
   - permissão `crm.deals.view` no grupo CRM.
4. **Correções invisíveis:**
   - carregar a fonte Instrument Sans no `app.blade.php` (já declarada no tema);
   - remover o botão "Exportar lista" do `DataTable` (Q-S-17).
5. **Validação visual:** cada tela é comparada lado a lado com a v1 (mesmos dados de seed), em light e dark, em desktop e mobile, antes de fechar a fase.

---

## 7. Backend — notas por módulo

| Módulo | Pontos técnicos |
| --- | --- |
| CORE | `TenantService` (cadastro), `TenancyServiceProvider` (pipeline), `SeedTenantDatabaseJob`, `TenantPermissionService` (`forModules`/`forTenant`/`apply`/`applyRoleDefaults`/`sync`), `TenantPasswordService`, `HandleInertiaRequests` (props compartilhadas iguais à v1), `RateLimiter` no `AppServiceProvider`, `DashboardService` (KPIs reais, RN-CORE-14). |
| CAD | `ContactService` reaproveita pelo documento só com dígitos (normalizado no `prepareForValidation`). O controller não consulta `Contact`. `Contact::deleteIfOrphaned()`. |
| CAT | `ProductService`, `ServiceCatalogService` (+ `resolveSelection`), categorias. |
| FIN | `AccountService` abstrato (copiar a regra e eliminar a duplicação create/update apontada na v1), `BankAccountService` (bloqueio de exclusão), `CashFlowService`, `SpendingFlowService`, `BillingFlowService` (agregação portável). |
| DRV | `DriveService` sem receber FormRequest como parâmetro (recebe array/DTO); disco único `bucket.disk`; `DriveLogService` estruturado. |
| DOC | `CatalogService` + 6 catálogos; `StageService` (timeline por banco); Propostas (`ProposalService`, `ProposalQueryService`, `ProposalTimelineService`, `ProposalDocumentService`, `ProposalCostItemService`, `ReceiptService`, `ProposalPdfService`, `ApplicantService`, `SellerService`); Calculadora (Actions + `ItbiCalculator`, `CostItemsBuilder`, `FeeBreakdownBuilder`, `RegistryFeeApiClient`, `IbgeLocalityClient`, `FeeCalculationService`, `QuoteService`, `QuotePdfService`, `ItbiMunicipalityService`); `RegistryProtocolService`; `FinishedProposalsReportQuery`, `ReportZipBuilder`, `BuildFinishedProposalsZipJob`. |
| CRM | Só o `TenantCrmController` (mock), com `permission:crm.deals.view`. |

---

## 8. Estratégia de testes

- **Infraestrutura copiada da v1:**
  - `tests/Pest.php` (helpers), `tests/TestCase.php` (migra central + tenant uma vez) e `tests/Support/TenantRegistry.php`;
  - `phpunit.xml`: MySQL `testing`, `queue=sync`, `mail=array`, `cache=array`.
- **Portar os testes da v1 por módulo**, junto com a fase do módulo:
  - renomear classes e enums;
  - remover os de serviços cobráveis;
  - adaptar o que foi decidido diferente (RN-CAD-01, RN-FIN-02, RN-DOC-PRO-15, Q-S-01).
- **Testes novos:**
  - MUD-02: distribuição por tipo, idempotência, sincronização de valor, bloqueio com parcela paga, exclusão da proposta e configuração ausente.
  - MUD-03: evento disparado depois do commit.
  - MUD-04: resolução de serviços, linha `service` sem recibo e sincronização do pivot.
  - MUD-01: prefill do create.
  - Dashboard real; unicidade de documento; bloqueio de exclusão de conta.
- **Cobertura mínima por fase:** todas as RN do módulo têm teste.
- **Front:** `vue-tsc --noEmit` sem erro e checagem visual manual (seção 6).

---

## 9. Fases e marcos

| Fase | Entrega | Depende de | Marco de saída |
| --- | --- | --- | --- |
| F0 | Bootstrap do projeto: Laravel 13, Sail, pacotes, tenancy, spatie, Fortify, Inertia/Vue, frontend copiado, Pint, Pest, Boost e skills | — | `sail up`, `npm run build`, `test` vazio verde, tela de login renderizando |
| F1 | CORE: central (planos, módulos, permissões), provisionamento, auth, usuários, cargos, empresa, perfil, layout e sidebar | F0 | Tenant novo provisionado e login funcionando; testes do CORE portados e verdes |
| F2 | CAD: contatos, clientes, fornecedores, funcionários | F1 | Testes CAD verdes |
| F3 | CAT: produtos, serviços e categorias | F1 | Testes CAT verdes |
| F4 | FIN: contas, categorias, pagar e receber, parcelas, fluxos e faturamento; dashboard real | F2 | Testes FIN verdes |
| F5 | DRV | F1 | Testes DRV verdes |
| F6 | DOC catálogos e etapas (timeline por banco) | F1 | Testes verdes |
| F7 | DOC propostas: CRUD, pessoas, timeline, documentos, linhas de custo, recibos, PDFs, e-mails e alerta | F2, F6 | Testes verdes |
| F8 | DOC calculadora, ITBI e orçamentos com **serviços do catálogo (MUD-04)** + `QuoteCreatedEvent` (MUD-03) | F3, F7 | Testes verdes |
| F9 | DOC protocolos e relatórios | F7 | Testes verdes |
| F10 | **MUD-02**: distribuição financeira + configuração + telas | F4, F8 | CA-MUD02-01/02 verdes |
| F11 | CRM front (mock) + interino MUD-01 + permissões de menu | F7 | Telas 1:1 com a v1 |
| F12 | Endurecimento: review geral, QA de regressão, checagem visual completa, `.env.example`, documentação | Todas | Relatório de QA sem pendência 🔴/🟠 |

**Paralelismo possível:**

- F2, F3, F5 e F6 depois de F1.
- F9 e F11 depois de F7.

---

## 10. Riscos e mitigação

| Risco | Mitigação |
| --- | --- |
| Diferença visual ao reescrever telas | Copiar o front da v1 em vez de reescrever; checagem lado a lado (seção 6) |
| Regressão de regra ao reescrever o backend | Portar os 487 testes da v1 como regressão antes de fechar cada fase |
| Vazamento entre tenants | P-01; testes de isolamento pelo QA; review com checklist de tenancy |
| MUD-02 deixar o saldo inconsistente | Reaproveitar o `AccountService` (que já trava e estorna); uma transação só; idempotência por `unique` |
| CRM pendente bloquear MUD-01/MUD-03 | Contratos explícitos, eventos já disparados e interino de prefill |
| Listener descoberto automaticamente antes da hora | Não criar o arquivo do listener até a fase CRM (5.3) |
| Token da API de emolumentos | Token novo no `.env`; `Http::preventStrayRequests()` nos testes |

---

## 11. Decisões registradas (perguntas do plan)

Todas as perguntas abaixo foram respondidas em **2026-10-26**, e em todas foi adotada a recomendação.

| ID | Pergunta | Decisão (= recomendação) |
| --- | --- | --- |
| Q-P-01 | Bootstrap da F0: criar o projeto com `laravel new` + starter kit Vue e depois copiar o front da v1, ou copiar o esqueleto da v1 (composer/package/config) e limpar `app/`? | `laravel new` limpo + copiar `resources/`, os configs de front e os `config/*.php` específicos |
| Q-P-02 | Haverá **migração de dados** da v1 para a v2 (tenants existentes)? | Não no escopo inicial. Se houver, fica uma fase F13 com script dedicado, porque os valores de enum mudaram |
| Q-P-03 | A ordem e o paralelismo das fases (seção 9) estão bons? | Como na tabela |
| Q-P-04 | A v2 terá um **repositório git** próprio e um ambiente de homologação próprio (domínio e bucket)? | Repositório novo; homologação separada da v1 |
| Q-P-05 | Configuração do MUD-02: card na tela Empresa (recomendado) ou tela nova "Configurações → Financeiro"? | Card na tela Empresa (sem tela nova) |
