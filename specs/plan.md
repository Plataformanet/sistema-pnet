# Plan — Sistema PNET (evolução incremental)

> **O que é:** o plano técnico de **como** levar o sistema atual ao que o [spec.md](spec.md) define (os itens **[alvo]**),
> obedecendo à [constitution.md](constitution.md). A ordem executável fica no [tasks.md](tasks.md), e o modo de
> executar no [implementation.md](implementation.md).
>
> Todos os comandos rodam na raiz de **`sistema-pnet`**, via `vendor/bin/sail`.

---

## 1. Estratégia geral

1. **Evoluir, não reescrever (V-01).** O código atual já cumpre a maior parte do spec e tem 487 testes. Cada fase entrega um **delta** pequeno e protegido por esses testes.
2. **Primeiro a forma, depois o comportamento:**
   - **R1:** renomeia as classes para o sufixo de tipo, sem mudar nada no comportamento.
   - **R2:** padroniza os dados.
   - **R3 a R5:** entram as mudanças de regra (MUDs).
3. **O visual não muda (P-10).** O frontend só é tocado onde o contrato muda: as MUDs, a remoção de serviços cobráveis, a limpeza de arquivos mortos e o dashboard real. Elementos novos usam os componentes e as classes já existentes na própria tela.
4. **Rotas e nomes de rota atuais ficam** (`tenant.<modulo>.<recurso>.<acao>`). Só saem as rotas de serviços cobráveis, e só entram as das MUDs.
5. **Ao fim de cada fase:**
   - o `docs/` (estado atual) é atualizado;
   - as marcas **[alvo]** concluídas são removidas do spec.

---

## 2. Estrutura de pastas

A estrutura é a atual (`docs/arquitetura/architecture_guide.md`). Entram só estas pastas novas:

```text
specs/                       # este SDD (constitution, spec, plan, tasks, implementation)
app/Events/                  # QuoteCreatedEvent, QuoteConvertedEvent, QuoteDeletedEvent (R5)
app/Queries/                 # FinishedProposalsReportQuery (R1)
app/Services/Finance/        # ProposalFinancialDistributionService, ProposalFinanceSettingsService (R4)
app/Listeners/               # NÃO criar até a fase CRM (ver 6.3)
```

---

## 3. Recursos-espelho (o molde de cada tipo de feature)

| Tipo de feature | Espelho no código atual |
| --- | --- |
| CRUD simples de tenant | `TenantProductController` + `ProductService` |
| Papel sobre contato | `ClientService::persist` + `ContactService` |
| Catálogo com soft delete, restauração e `ServerDataTable` | `CatalogService` + `BankService` + `TenantBankController` |
| Lançamento com saldo e parcelas | `AccountService` + `AccountPayableService` |
| Fluxo de várias etapas | `ConvertQuoteToProposal` (após a R1: `ConvertQuoteToProposalAction`) |
| Policy por registro | `ProposalPolicy` |
| Cliente HTTP externo | `RegistryFeeApiClient` + DTOs |
| Job em fila pós-commit | `AttachQuotePdfToProposal` (após a R1: `AttachQuotePdfToProposalJob`) |
| Migration de dados de referência | `database/migrations/tenant/2026_10_01_100009_seed_documents_catalogs.php` |
| Permissão nova | O par `central/2026_10_15_100000_*` + `tenant/2026_10_15_100002_*` |
| Tela de catálogo | `resources/js/pages/tenant/documents/banks/**` |

---

## 4. Fase R1 — Mapa de renomeações (sufixo de tipo)

**Técnica:**

1. Use o rename de símbolo (Serena `rename_symbol` ou IDE) **classe por classe**, atualizando `use`, docblocks, strings de classe e testes.
2. Renomeie o arquivo junto com a classe.
3. Depois de cada grupo:
   - `grep -rw <NomeAntigo> app tests database routes config resources/views` precisa voltar vazio;
   - rodar os testes que referenciam o grupo;
   - rodar o Pint.

**Atenção a classes guardadas como string no banco** (`installmentable_type`, `documentable_type`, `accountable_type`): elas são **Models**, que não mudam de nome, então nada muda.

| Grupo | Atual → novo | Arquivos (aprox.) |
| --- | --- | --- |
| Enums (19) | `AmortizationTable`, `BankAccountType`, `BrazilianState`, `CalculationType`, `CostItemType`, `DocumentOwner`, `DocumentType`, `FeeDiscount`, `FinancingSystem`, `ItbiModule`, `MaritalStatus`, `PersonType`, `PropertyCondition`, `ProposalStatus`, `QuoteStatus`, `ReceiptType`, `RegistryProtocolPosition`, `SupportedState`, `TenantProvisioningStatus` → mesmo nome + `Enum` | ~180 |
| Enums sem uso | `PermissionsEnum` → removido | 1 |
| Actions (5) | `CalculateFees`, `CreateQuote`, `UpdateQuote`, `ConvertQuoteToProposal`, `AttachFeeEstimateToProposal` → `+Action` | ~16 |
| Jobs (2) | `AttachQuotePdfToProposal`, `SeedTenantDatabase` → `+Job` | ~8 |
| Command | `EnsureBucket` → `EnsureBucketCommand` (a assinatura `bucket:ensure` não muda) | 1 |
| Rules (3) | `NonOverlappingBrackets`, `UniqueCatalogName`, `UserHasRole` → `+Rule` | ~10 |
| Middleware | `RedirectProposalRestrictedUsers` → `RedirectProposalRestrictedUsersMiddleware` (atualizar o alias e o registro em `bootstrap/app.php`/rotas) | ~3 |
| Services | `SuppliersService` → `SupplierService`; `EmployeesService` → `EmployeeService`; `ServicesService` → `ServiceCatalogService`; `CoastService` e `PaidMarketService` → removidos (vazios) | ~10 |
| DTOs | `ItbiInput` → `ItbiInputDto`; `RegistryFeeRequestData` → `RegistryFeeRequestDto`; `RegistryFeeResult` → `RegistryFeeResultDto` | ~15 |
| Builders | `FeeBreakdown` → `FeeBreakdownBuilder` (e o DTO de saída `FeeBreakdownDto`, se separado) | ~5 |
| Helpers | `Support\Money` → `MoneyHelper`; `Support\DocumentMask` → `DocumentMaskHelper` | ~11 |
| Query | `ReportService::finishedProposals` → `FinishedProposalsReportQuery` (`app/Queries`) | ~4 |
| Testes | Arquivos com nome de classe antigo: `SuppliersServiceTest` → `SupplierServiceTest`, `EmployeesServiceTest` → `EmployeeServiceTest` | 2 |

**Fora da R1** (os nomes já estão no padrão ou são exceções da constitution): Models, `Tenant*Controller`, `*Request`, `*Policy`, `*Mail`, `*Exception`, `*Service` no singular, os enums já com `Enum` (`RolesEnum`, `AccountsEnum`…). Esses enums mantêm o nome atual: renomear `RolesEnum` → `RoleEnum` é opcional e fica fora do escopo.

---

## 5. Fase R2 — Padronizações de dados

Cada item é uma migration **nova**, de tenant ou central conforme o caso, mais o ajuste de código e de testes.

| Item | Migration | Código |
| --- | --- | --- |
| Dinheiro em `unsignedBigInteger` | `products.cost_value/sell_value`, `services.cost_value/sell_value/fees`, `employees.salary`, central `plans.price`: `->unsignedBigInteger()->change()` repetindo todos os atributos | Casts `integer`; validação `integer|min:0` |
| Documento único | Normaliza `contacts.cpf_cnpj` para só dígitos; se houver duplicados, a migration **falha** com a lista (homologação: limpar ou recriar o tenant, V-02); depois cria `unique(cpf_cnpj)` | `prepareForValidation` normaliza; `getContactByCpfCnpj` busca só por dígitos |
| Unicidades | `tenant_settings.key` unique (deduplica antes); central `permissions.name` unique | `TenantSetting` com `SoftDeletes` |
| Usuários | `users.deleted_at` | `UserService::delete` = `status = false` + soft delete |
| Alerta de etapa | `proposal_stages.overdue_alerted_at` | `AlertOverdueProposalStagesCommand` marca e não reenvia |
| Drive | `drive_logs` estruturado (`user_id`, `drive_id`, `action`, `ip_address`, `meta`), migrando o JSON atual para `meta` | `DriveLogService`; `DriveFolderService` passa a usar o disco do bucket |
| Financeiro | Remove `bank_account_out` | `BankAccountService::delete` bloqueia quando houver lançamentos ativos |
| Catálogo | `services.sku` unique (considerando soft delete) | Validação |
| Menu e permissões | Remove do catálogo `documents.contracts.*`; cria `crm.deals.view` e `crm.pipelines.view` (par central + tenant). `sales.*` **fica**, para o módulo Vendas futuro | CRM com permissão; o grupo "Vendas" da sidebar não muda |

---

## 6. Design técnico das 4 mudanças

### 6.1 MUD-04 — Serviços do catálogo no orçamento e na proposta (fase R3)

- **Migration de dados** (tenant), numa transação:
  1. Cria a `service_categories` "Documentação", se não existir.
  2. Para cada `billable_services` (incluindo as excluídas), cria um `services` com `name`, `description`, `sell_value = price`, `cost_value = 0`, `fees = 0`, `sku` gerado (`DOC-<id>`), `status = true` e `deleted_at` copiado.
  3. Cria `quote_service` e `proposal_service` (`<owner>_id` cascade, `service_id` restrict, `amount` ubigint, unique no par) e copia os dois pivots antigos com o id mapeado.
  4. `proposal_cost_items.service_id` (nullable FK): preenchido para as linhas `service`, casando pelo nome e pela proposta. Linhas sem correspondência ficam nulas e são listadas no log.
  5. Remove `quote_billable_service`, `proposal_billable_service` e `billable_services`.
  - O `down()` recria o estado anterior só em estrutura. A conversão de dados é de mão única (V-02).
- **Permissões:** uma migration central remove `documents.billable_services.*` do catálogo; uma migration tenant remove as permissões e o vínculo com os cargos.
- **Código:**
  - Saem `BillableService`, `BillableServiceService`, `TenantBillableServiceController`, os requests, a factory e as rotas.
  - `Quote::services()` e `Proposal::services()` passam a ser BelongsToMany(`Service`) com `withPivot('amount')->withTrashed()`.
  - `ServiceCatalogService::resolveSelection()` ignora serviços inativos e excluídos; o valor vazio usa o `sell_value`.
  - `CostItemsBuilder`: a linha `service` recebe `service_id`, `service_description = description` e `generates_receipt = false`.
  - `ProposalCostItemService::updateAmount()` sincroniza o `proposal_service.amount`.
  - `ReceiptService` recusa recibo vinculado a linha `service`. Os recibos antigos ficam.
- **Frontend:**
  - Remover `pages/tenant/documents/billable-services/**` e o item de menu.
  - O `ServicePicker` passa a receber `services: { id, name, sell_value }[]`, **com o mesmo visual**.
  - `BillableServiceOption` → `ServiceOption` em `types/documents/*.ts`.

### 6.2 MUD-02 — Distribuição financeira (fase R4)

**Service novo** `app/Services/Finance/ProposalFinancialDistributionService.php` (`final`):

| Método | O que faz |
| --- | --- |
| `distribute(ProposalCostItem $item, Tenant $tenant): void` | Idempotente por `proposal_cost_item_id`. Cria a conta a receber e, quando o tipo pede, a conta a pagar, reaproveitando `AccountReceivableService` / `AccountPayableService` (à vista, `open`). |
| `distributeAll(Proposal $proposal, Tenant $tenant): array` | Botão "Distribuir no financeiro". Devolve o resumo dos lançamentos criados e pulados, com o motivo. |
| `syncAmount(ProposalCostItem $item, Tenant $tenant): void` | Atualiza o total e a parcela enquanto estão `open`; senão lança `LinkedEntryAlreadyPaidException`. |
| `removeFor(ProposalCostItem $item, Tenant $tenant): void` | Exclui os lançamentos vinculados, com o estorno do `AccountService`. |
| `removeOpenForProposal(Proposal $proposal, Tenant $tenant): void` | Exclui os abertos e mantém os pagos. |

- **Configuração:** `ProposalFinanceSettingsService` lê e grava em `tenant_settings`:
  - `documents.finance.<tipo>.{receivable_category_id, receivable_subcategory_id, payable_category_id, payable_subcategory_id, payable_supplier_contact_id}`;
  - `documents.finance.due_in_days`.
  - As chaves são criadas vazias por uma migration idempotente.
- **Mapeamento** em `CostItemTypeEnum`: `generatesReceivable()` é sempre `true`; `generatesPayable()` é `false` só para `service`.
- **Contatos:**
  - **Receber:** `FinancialContact` do 1º proponente com o tipo `cliente` (valor atual do `ContactTypeEnum`). O papel de cliente é criado via `ClientService::persist` se não existir.
  - **Pagar:** o contato fornecedor configurado.
- **Ponto de chamada:** na **mesma transação** das operações que criam, editam ou removem linhas:
  - `ProposalCostItemService` (`storeManual`, `updateAmount`, `destroy`);
  - `CostItemsBuilder` (vínculo e conversão);
  - `ProposalService::delete`.
  - Sem eventos, para manter a atomicidade.
- **Sem configuração:** a distribuição é pulada, com um aviso (`warning`). A proposta é salva normalmente.
- **Migration:** em `account_payables` e `account_receivables`, `proposal_id` e `proposal_cost_item_id` nullable, `nullOnDelete`, mais `unique(proposal_cost_item_id)`.
- **Rotas novas:**
  - `POST /documents/proposals/{id}/cost-items/distribute` (`tenant.documents.proposals.cost-items.distribute`, `permission:documents.proposal_financial.create`);
  - `PUT /settings/company/finance-integration` (`tenant.settings.company.finance-integration.update`, `permission:settings.company.edit`).
- **Telas** (com os componentes já usados em cada tela):
  - **Empresa:** card "Integração Documentações → Financeiro", uma linha por tipo, com os selects e o prazo.
  - **Aba Pagamentos e Taxas:** colunas "A receber" e "A pagar" + o botão "Distribuir no financeiro".
  - **Listas de pagar e receber:** badge "Proposta Nº 00042".

### 6.3 MUD-03 — Orçamento → funil do CRM (fase R5)

- **Agora:**
  - `QuoteCreatedEvent`, `QuoteConvertedEvent` e `QuoteDeletedEvent` (`quoteId`), com `ShouldDispatchAfterCommit`.
  - Disparados por `CreateQuoteAction`, `ConvertQuoteToProposalAction` e pela exclusão do orçamento.
  - Teste com `Event::fake()`.
- **Pendente:** `CreateCrmDealFromQuoteListener`. O Laravel 13 descobre listeners automaticamente em `app/Listeners`, por isso **o arquivo não é criado** até o backend do CRM existir.

### 6.4 MUD-01 — CRM → proposta de documentações (fase R5)

- **Agora (interino):**
  - Na `DealDetailDrawer.vue`, o botão "Gerar proposta de documentações" aparece só com `status === 'won'` e a permissão `documents.proposals.create`.
  - Ele faz `router.visit(route('tenant.documents.proposals.create', { source: 'crm', applicant_name, purchase_value }))`.
  - O `TenantProposalController@create` valida esses parâmetros (`CreateProposalRequest`) e envia `prefill` ao `ProposalForm`.
- **Pendente:** `ConvertCrmDealToProposalAction` + `ProposalService::createFromCrmDeal(CrmDealDto, User)` + `crm_deals.proposal_id`, quando o CRM tiver backend.

---

## 7. Fase R6 — Correções e decisões do spec

- Dashboard real: `DashboardService` com os 4 KPIs e os próximos vencimentos (RN-CORE-14), ligado aos widgets sem mudar o visual.
- Controllers sem consulta Eloquent: `Contact::where` nos controllers de cadastro e `BankAccount::...` no financeiro passam para os services.
- `DriveService` sem receber FormRequest (recebe array).
- Diálogo de contrato padrão na conversão de orçamento (Q-S-09).
- **Exportação de listas em PDF** (RF-CORE-12, RN-CORE-15, Q-S-17), nas 12 listas com `DataTable`:
  - **`ListPdfService`** (novo, em `app/Services/`):
    - método `render(string $title, array $columns, Collection $rows, int $total, Tenant $tenant)`, que devolve o PDF do dompdf;
    - a constante `PRINT_LIMIT = 500`;
    - a view `resources/views/pdf/list.blade.php`, que reaproveita `pdf/_header`, `pdf/_styles` e `CompanySettingService::pdfBranding`;
    - papel A4: paisagem quando a lista tem mais de 5 colunas, retrato nas demais.
  - **O service de cada recurso:**
    - ganha `forPrint(array $filters): array{rows: Collection, total: int}`, com busca, filtro de status, ordem padrão e `limit(ListPdfService::PRINT_LIMIT)`;
    - ganha `printColumns(): array<string, string>`, com o rótulo de cada coluna e a sua chave;
    - formata as linhas com `MoneyHelper` e `DocumentMaskHelper`.
  - **Request:** `ExportListPdfRequest`, com:
    - `search`: `nullable|string|max:100`;
    - `status`: `nullable|in:all,active,inactive`, usado só em Cadastros.
  - **Controller:** método `pdf()` no `Tenant<Recurso>Controller` que já existe. Ele chama `forPrint` e `render` e devolve `->stream('lista-de-<recurso>.pdf')`.
  - **Rota:**
    - `GET /<modulo>/<recurso>/pdf`, com o nome `tenant.<modulo>.<recurso>.pdf` e a mesma permissão `.view` da lista;
    - declarada antes das rotas com `{id}`;
    - o PDF é gerado de forma síncrona na requisição GET, como o PDF da lista de Propostas.
  - **Front:**
    - o `DataTable` emite `export` com o termo da busca (`globalFilter`);
    - cada `List.vue` trata o `@export` com `window.open(route('tenant.<modulo>.<recurso>.pdf', { search, status }), '_blank')`;
    - o visual do botão não muda.
- Front:
  - remover arquivos mortos (`login-04`, `SearchForm.vue`, `PermissionGroupCard.vue`, `central/Dashboard.vue`);
  - carregar a fonte Instrument Sans;
  - trocar as URLs literais por `route()`.
- Back: remover `TenantSettingController` (sem rota) e as renderizações de páginas inexistentes (`drive_folder/*`, `show` de cadastros).

---

## 8. Estratégia de testes

- A suíte atual (487 casos) é a **rede de segurança** de todas as fases.
- Em cada tarefa, rodar só os arquivos afetados (V-05).
- **R1:** como não muda comportamento, rodar os testes que citam as classes renomeadas (`grep -l`). A suíte completa roda no aceite da fase, com o pedido do usuário.
- **Testes novos:**
  - R2: unicidade do documento, bloqueio de exclusão de conta, alerta único, usuário inativado, unique de settings.
  - R3: migração dos serviços cobráveis, `resolveSelection`, linha `service` sem recibo, sincronização do pivot.
  - R4: os CA-MUD02 (mapeamento, idempotência, sincronização, bloqueio com parcela paga, exclusões, configuração ausente).
  - R5: eventos depois do commit; prefill do create.
  - R6:
    - dashboard;
    - exportação de listas em PDF: CA-CORE-03..05 (filtros, limite e permissão) e isolamento entre tenants.

---

## 9. Fases e marcos

| Fase | Entrega | Depende de | Marco de saída |
| --- | --- | --- | --- |
| R0 | `specs/`, `CLAUDE.md`, `AGENTS.md`, skills (`.claude` e `.agents`) | — | Documentação e skills alinhadas (concluída na adoção do SDD) |
| R1 | Renomeações com sufixo de tipo (seção 4) | R0 | `grep` sem nomes antigos; testes dos grupos verdes; aceite com a suíte completa |
| R2 | Padronizações de dados (seção 5) | R1 | Migrations aplicadas em todos os tenants; testes novos verdes |
| R3 | MUD-04 (6.1) | R1 | Sem `billable` no código; dados migrados; testes verdes |
| R4 | MUD-02 (6.2) | R2, R3 | CA-MUD02-01/02 verdes |
| R5 | MUD-03 + MUD-01 (6.3, 6.4) | R3 | Eventos testados; botão do CRM com prefill |
| R6 | Correções e decisões (seção 7) | R2 | Testes verdes; telas sem mudança visual |
| R7 | Review geral, regressão completa, atualização de `docs/` e remoção das marcas [alvo] | Todas | Relatório de QA sem pendência 🔴/🟠 |

**Paralelismo possível:**

- R2 e R3 depois da R1.
- R5 e R6 em paralelo com a R4.

---

## 10. Riscos e mitigação

| Risco | Mitigação |
| --- | --- |
| A renomeação em massa quebra uma referência por string (`::class` em config, morph map, rotas) | `grep` por nome antigo em `app`, `config`, `routes`, `database`, `tests` e `resources/views`; testes dos grupos |
| A migration de documento único falha por duplicados | Falha explícita com a lista; limpeza ou recriação do tenant de homologação (V-02) |
| A conversão de serviços cobráveis perde o vínculo de linhas antigas | Casa pelo nome e pela proposta; registra as linhas não casadas; dados de homologação |
| MUD-02 deixa o saldo inconsistente | Reaproveita o `AccountService` (trava e estorno); uma transação só; `unique` por linha |
| Mudança visual sem querer | P-10; checagem das telas tocadas em light/dark e desktop/mobile |
| Listener do CRM descoberto antes da hora | Não criar o arquivo até a fase CRM |

---

## 11. Decisões registradas

| ID | Decisão |
| --- | --- |
| Q-P-01 / Q-P-02 / Q-P-04 | Superadas pela V-01: não há projeto novo, nem migração entre sistemas, nem repositório novo |
| Q-P-03 | Ordem e paralelismo conforme a seção 9 (fases R) |
| Q-P-05 | A configuração do MUD-02 fica em um card na tela Empresa |
