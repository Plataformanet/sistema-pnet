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
3. **O visual não muda (P-10).** O frontend só é tocado onde o contrato muda:
   - as MUDs e a remoção de serviços cobráveis;
   - o fornecedor opcional em contas a pagar (RN-FIN-14);
   - o grupo CRM da sidebar com permissão (RN-CRM-02);
   - o diálogo de contrato na conversão de orçamento (RN-DOC-QUO-06);
   - o dashboard real e o botão "Exportar lista" (RF-CORE-12);
   - a limpeza de arquivos mortos, a fonte Instrument Sans e as URLs literais (spec §8).
   - Elementos novos usam os componentes e as classes já existentes na própria tela.
4. **Rotas e nomes de rota atuais ficam** (`tenant.<modulo>.<recurso>.<acao>`, com as ações extras e os sub-recursos da V-13). Só saem as rotas de serviços cobráveis. Entram só as das MUDs e as de exportação de listas em PDF (RF-CORE-12).
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
app/Listeners/               # NÃO criar até a fase CRM (ver 6.3)
```

Os Services novos (`ProposalFinancialDistributionService`, `ListPdfService`, `DashboardService`) ficam direto em `app/Services/`, como manda a constitution §6.

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

**Fora da R1** (os nomes já estão no padrão ou são exceções da constitution): Models, `Tenant*Controller`, `*Request`, `*Policy`, `*Mail`, `*Exception`, `*Service` no singular, os enums já com `Enum` (`RolesEnum`, `AccountsEnum`…). Esses enums mantêm o nome atual: o enum de cargos continua `RolesEnum` (V-16).

---

## 5. Fase R2 — Padronizações de dados

Cada item é uma migration **nova**, de tenant ou central conforme o caso, mais o ajuste de código e de testes.

| Item | Migration | Código |
| --- | --- | --- |
| Dinheiro em `unsignedBigInteger` (D-06, V-10) | `products.cost_value/sell_value`, `services.cost_value/sell_value/fees`, `employees.salary`, `account_payables.total`, `account_receivables.total`, `installments.value` e central `plans.price`: `->unsignedBigInteger()->change()` repetindo todos os atributos (ex.: o `nullable` dos totais). **Ficam como estão:** `bank_accounts.initial_balance/current_balance` (`bigInteger` com sinal, V-10) e as tabelas de cobrança sem uso (V-08) | Casts `integer`; validação `integer|min:0` |
| Documento único | Normaliza `contacts.cpf_cnpj` para só dígitos; se houver duplicados, a migration **falha** com a lista (homologação: limpar ou recriar o tenant, V-02); depois cria `unique(cpf_cnpj)` | `prepareForValidation` normaliza; `getContactByCpfCnpj` busca só por dígitos |
| Unicidades | `tenant_settings.key` unique (deduplica antes); central `permissions.name` unique | `TenantSetting` com `SoftDeletes` |
| Usuários | `users.deleted_at` | `UserService::delete` = `status = false` + soft delete |
| Alerta de etapa | `proposal_stages.overdue_alerted_at` | `AlertOverdueProposalStagesCommand` marca e não reenvia |
| Drive | `drive_logs` estruturado (`user_id`, `drive_id`, `action`, `ip_address`, `meta`), migrando o JSON atual para `meta` | `DriveLogService`; `DriveFolderService` passa a usar o disco do bucket |
| Financeiro | Remove `bank_account_out` | `BankAccountService::delete` bloqueia quando houver lançamentos ativos |
| Catálogo | `services.sku` unique (considerando soft delete) | Validação |
| Menu e permissões | Remove do catálogo `documents.contracts.*`; cria `crm.deals.view` e `crm.pipelines.view` (par central + tenant). A migration de tenant sincroniza respeitando o plano e **atribui as duas ao cargo Administrador** quando o plano tem o módulo (V-12, V-15). `sales.*` **fica**, para o módulo Vendas futuro | CRM com permissão; o grupo "Vendas" da sidebar não muda |

---

## 6. Design técnico das 4 mudanças

### 6.1 MUD-04 — Serviços do catálogo no orçamento e na proposta (fase R3)

- **Três migrations de tenant, em sequência.** No MySQL, o DDL (`CREATE`/`ALTER`/`DROP TABLE`) faz commit implícito, então uma transação não protege estrutura e dados juntos. Por isso, só a migration de dados usa `DB::transaction`, e cada passo é idempotente (V-02):
  1. **Estrutura (aditiva):** cria `quote_service` e `proposal_service` (`<owner>_id` cascade, `service_id` restrict, `amount` ubigint, unique no par) e `proposal_cost_items.service_id` (nullable FK). Cada passo tem guarda `Schema::hasTable`/`hasColumn`.
  2. **Dados (numa transação):**
     - cria a `service_categories` "Documentação", se não existir (contando as excluídas);
     - para cada `billable_services` (incluindo as excluídas), cria um `services` com `name`, `description`, `sell_value = price`, `cost_value = 0`, `fees = 0`, `sku = DOC-<id>`, `status = true` e `deleted_at` copiado, **só se ainda não existir** um `services` com esse `sku` (guarda de idempotência, contando soft delete). Se o `sku` já existir com outro nome, a migration falha com a lista (V-02);
     - copia os dois pivots antigos para os novos com o id mapeado, usando `insertOrIgnore` (o unique do par evita duplicar);
     - preenche `proposal_cost_items.service_id` das linhas `service` ainda nulas, casando pelo nome e pela proposta. As linhas sem correspondência ficam nulas e são listadas no log.
  3. **Remoção:** confere que todo `billable_services` tem o `services` correspondente (senão falha com a lista) e só então remove `quote_billable_service`, `proposal_billable_service` e `billable_services`.
  - Rodar de novo não duplica nada. O `down()` recria o estado anterior só em estrutura; a conversão de dados é de mão única (V-02).
  - A R3 roda depois da T-R2-01 (tipos de `services`) e da T-R2-08 (`unique(sku)`); ver seção 9.
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

**Pré-requisito — fornecedor opcional em contas a pagar (RN-FIN-14, V-11):**

- **Migration** (tenant): `account_payables.financial_contact_id` → `->nullable()->change()`, mantendo a FK `constrained('financial_contacts')`.
- **Código:**
  - `StoreAccountPayableRequest` (e o request de edição): `financial_contact_id` passa a `nullable`; o contas a receber não muda;
  - `AccountPayableService`: só resolve o `FinancialContact` (e checa o papel inativo) quando o contato vem preenchido;
  - listas, detalhe, PDFs e relatórios do financeiro: tratam o contato nulo (`—`), sem N+1.
- **Front:** o `ComboboxRemote` de fornecedor no formulário de pagar fica opcional (sem asterisco), e as listas e o detalhe mostram "—". O visual não muda.

**Service novo** `app/Services/ProposalFinancialDistributionService.php` (`final`):

| Método | O que faz |
| --- | --- |
| `distribute(ProposalCostItem $item, Tenant $tenant): array` | Idempotente por `proposal_cost_item_id`. Cria a conta a receber (se o papel de cliente não estiver inativo) e, quando o tipo pede, a conta a pagar **sem contato**, reaproveitando `AccountReceivableService` / `AccountPayableService` (à vista, `open`). Devolve o que foi criado e o que foi pulado, com o motivo. |
| `distributeAll(Proposal $proposal, Tenant $tenant): array` | Botão "Distribuir no financeiro". Devolve o resumo dos lançamentos criados e pulados, com o motivo (categoria fixa excluída ou inativa, sem conta principal, cliente inativo). |
| `syncAmount(ProposalCostItem $item, Tenant $tenant): void` | Atualiza o total e a parcela enquanto estão `open`; senão lança `LinkedEntryAlreadyPaidException`. |
| `removeFor(ProposalCostItem $item, Tenant $tenant): void` | Exclui os lançamentos vinculados, com o estorno do `AccountService`. |
| `removeOpenForProposal(Proposal $proposal, Tenant $tenant): void` | Exclui os abertos e mantém os pagos. |

- **Categorias fixas** (RN-MUD02-09, Q-S-21), **sem tela de configuração**:
  - **Migration de dados de referência** (tenant, idempotente, no padrão de `2026_10_01_100009_seed_documents_catalogs.php`):
    - cria, se não existirem (contando as excluídas), as categorias "Documentações" (`receita`) e "Documentações — Repasses" (`despesa`) e as subcategorias de cada uma;
    - grava os ids em `tenant_settings`: `documents.finance.receivable_category_id`, `documents.finance.payable_category_id`, `documents.finance.<tipo>.receivable_subcategory_id` (5 tipos) e `documents.finance.<tipo>.payable_subcategory_id` (só `emolument`, `extra_fee`, `itbi` e `manual`);
    - roda também no provisionamento (`MigrateDatabase`), então os tenants novos já nascem com as categorias.
  - O `ProposalFinancialDistributionService` lê esses ids pelo service de `tenant_settings` atual (com cache). Se a categoria ou a subcategoria estiver excluída ou inativa, a distribuição da linha é pulada com aviso.
  - **Não há chave de fornecedor** (V-11) nem de prazo.
  - **Forma de pagamento:** `FinancialPaymentMethodEnum::PIX`. **Vencimento:** a `date` da linha ou, sem data, hoje.
  - **Evolução futura (adendo da Q-S-21):** se algum tenant precisar escolher as categorias, basta uma tela que grave nas mesmas chaves `documents.finance.*`; o service de distribuição não muda.
- **Mapeamento** em `CostItemTypeEnum`: `generatesReceivable()` é sempre `true`; `generatesPayable()` é `false` só para `service`.
- **Contatos:**
  - **Receber:** `FinancialContact` do 1º proponente com o tipo `cliente` (valor atual do `ContactTypeEnum`). Se o papel de cliente não existir, ele é criado via `ClientService::persist`. Se estiver **inativo**, o lançamento a receber da linha é pulado e o motivo volta no resumo, para virar `warning` (Q-S-19). A checagem acontece **antes** de chamar o `AccountReceivableService`, para que a `InactiveContactException` não derrube a transação.
  - **Pagar:** sem contato (`financial_contact_id = null`, RN-FIN-14).
- **Ponto de chamada:** na **mesma transação** das operações que criam, editam ou removem linhas:
  - `ProposalCostItemService` (`storeManual`, `updateAmount`, `destroy`);
  - `CostItemsBuilder`, chamado pelo `AttachFeeEstimateToProposalAction` (vínculo) e pelo `ConvertQuoteToProposalAction` (conversão);
  - `ProposalService::delete`.
  - A criação manual da proposta (`ProposalService` de criação) **não** é ponto de chamada: ela não gera linhas (RN-DOC-PRO-02).
  - Sem eventos, para manter a atomicidade.
- **Categoria fixa excluída ou inativa, sem conta principal ou com cliente inativo:** a distribuição (ou só o a receber, no caso do cliente) é pulada, com um aviso (`warning`). A operação é salva normalmente.
- **Sem o módulo `finance` no tenant** (Q-S-22): o `distribute()` e o `distributeAll()` retornam logo no início, sem criar lançamento e sem aviso. A checagem usa os módulos ativos do tenant (`tenant_modules`, o mesmo dado do `tenant.hasModules` do front). O `syncAmount()` e o `removeFor()` continuam rodando, porque só mexem em lançamentos que já existem.
- **Migration:** em `account_payables` e `account_receivables`, `proposal_id` e `proposal_cost_item_id` nullable, `nullOnDelete`, mais `unique(proposal_cost_item_id)`.
- **Rota nova** (nome conforme a V-13):
  - `POST /documents/proposals/{id}/cost-items/distribute` (`tenant.documents.proposals.cost-items.distribute`, `permission:documents.proposal_financial.create`). Método `distribute()` no `TenantProposalCostItemController` atual, que autoriza `ProposalPolicy::manageFinancial` na proposta antes de chamar o service (P-06).
- **Telas** (com os componentes já usados em cada tela):
  - **Aba Pagamentos e Taxas:** colunas "A receber" e "A pagar", com o status da parcela e um link para o lançamento, + o botão "Distribuir no financeiro". As colunas e o botão só aparecem com `tenant.hasModules` incluindo `finance` (Q-S-22).
  - **Listas de pagar e receber:** badge "Proposta Nº 00042", com link para a proposta.

### 6.3 MUD-03 — Orçamento → funil do CRM (fase R5)

- **Agora:**
  - `QuoteCreatedEvent`, `QuoteConvertedEvent` e `QuoteDeletedEvent` (`quoteId`), com `ShouldDispatchAfterCommit` (RN-MUD03-01 e RN-MUD03-05).
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
- **Diálogo de contrato na conversão de orçamento** (RN-DOC-QUO-06, Q-S-09):
  - **Back:**
    - `TenantQuoteController@show` envia `contractTypes` (ativos, `{ value, label }[]`) e `defaultContractTypeId` (`config('proposals.default_contract_type_id')` quando ativo; senão `null`);
    - `ConvertQuoteToProposalRequest` (novo) valida `contract_type_id`: `required`, existente e sem soft delete;
    - o `TenantConvertQuoteToProposalController` passa o id ao `ConvertQuoteToProposalAction`, que o repassa ao `ProposalService::createFromQuote`; o `defaultContractTypeId()` deixa de ser usado na conversão.
  - **Front:** o `AlertDialog` de confirmação de "Gerar proposta" (tela Ver) ganha o select "Tipo de contrato", pré-selecionado, e envia `contract_type_id` no POST. O visual não muda.
- **Exportação de listas em PDF** (RF-CORE-12, RN-CORE-15, Q-S-17), nas 12 listas com `DataTable`:
  - **`ListPdfService`** (novo, em `app/Services/`):
    - método `render(string $title, array $columns, Collection $rows, int $total, User $issuer, Tenant $tenant)`, que devolve o PDF do dompdf (o `$issuer` é o usuário logado, exibido como emissor);
    - a constante `PRINT_LIMIT = 500`;
    - a view `resources/views/pdf/list.blade.php`, que reaproveita `pdf/_header`, `pdf/_styles` e `CompanySettingService::pdfBranding`;
    - papel A4: paisagem quando a lista tem mais de 5 colunas, retrato nas demais.
  - **O service de cada recurso:**
    - ganha `forPrint(array $filters): array{rows: Collection, total: int}`, com busca, filtro de status, ordem padrão e `limit(ListPdfService::PRINT_LIMIT)`;
    - ganha `printColumns(): array<string, string>`, com o rótulo de cada coluna e a sua chave;
    - formata as linhas com `MoneyHelper` e `DocumentMaskHelper`.
  - **Request:** `ExportListPdfRequest`, com:
    - `search`: `nullable|string|max:100`;
    - `status`: `nullable|in:all,active,inactive`, usado só em Clientes, Fornecedores e Funcionários (as outras 9 listas só têm busca).
  - **Controller:** método `pdf()` no `Tenant<Recurso>Controller` que já existe. Ele chama `forPrint` e `render` e devolve `->stream('lista-de-<recurso>.pdf')`.
  - **Rota:**
    - `GET /<modulo>/<recurso>/pdf`, com o nome `tenant.<modulo>.<recurso>.pdf` e a mesma permissão `.view` da lista;
    - declarada antes das rotas com `{id}`;
    - o PDF é gerado de forma síncrona na requisição GET, como o PDF da lista de Propostas (permitido pela V-14).
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
- **Testes novos** (constitution §11: toda RN com teste; toda permissão e Policy com teste de acesso negado):
  - R2:
    - unicidade do documento, bloqueio de exclusão de conta, alerta único, usuário inativado, unique de settings;
    - dinheiro: validação `integer|min:0` com mensagem nos catálogos e salário; saldo bancário negativo continua aceito (V-10);
    - `services.sku` único (RN-CAT-01);
    - `drive_logs` estruturado e pastas no disco do bucket (RN-DRV-05/06);
    - permissões `crm.*`: atribuídas ao Administrador só quando o plano tem o módulo (V-12) e acesso negado ao CRM sem `crm.deals.view`.
  - R3: migração dos serviços cobráveis (incluindo rodar duas vezes sem duplicar), `resolveSelection`, linha `service` sem recibo, sincronização do pivot.
  - R4:
    - os CA-MUD02 (mapeamento, idempotência, sincronização, bloqueio com parcela paga, exclusões);
    - categorias fixas: a migration roda duas vezes sem duplicar; os lançamentos usam a categoria e a subcategoria do tipo, `pix` e o vencimento certo; categoria excluída ou inativa → linha pulada com aviso;
    - conta a pagar sem fornecedor (RN-FIN-14) e receber ainda obrigatório;
    - cliente inativo: só o a receber é pulado, com aviso (Q-S-19); cliente sem papel: o papel é criado;
    - tenant sem o módulo `finance`: nenhum lançamento e nenhum aviso; depois de ativar o módulo, o "Distribuir" cria os que faltam (Q-S-22);
    - acesso negado na rota nova (permissão e `ProposalPolicy::manageFinancial`).
  - R5: os 3 eventos depois do commit (e nenhum em rollback); prefill do create.
  - R6:
    - dashboard;
    - diálogo de contrato: CA-DOC-QUO-03;
    - exportação de listas em PDF: CA-CORE-03..05 (filtros, limite e permissão) e isolamento entre tenants.

---

## 9. Fases e marcos

| Fase | Entrega | Depende de | Marco de saída |
| --- | --- | --- | --- |
| R0 | `specs/`, `CLAUDE.md`, `AGENTS.md`, skills (`.claude` e `.agents`) | — | Documentação e skills alinhadas (concluída na adoção do SDD) |
| R1 | Renomeações com sufixo de tipo (seção 4) | R0 | `grep` sem nomes antigos; testes dos grupos verdes; aceite com a suíte completa |
| R2 | Padronizações de dados (seção 5) | R1 | Migrations aplicadas em todos os tenants; testes novos verdes |
| R3 | MUD-04 (6.1) | R1, T-R2-01, T-R2-08 | Sem `billable` no código; dados migrados; testes verdes |
| R4 | MUD-02 (6.2) | R2, R3 | CA-MUD02-01/02 verdes |
| R5 | MUD-03 + MUD-01 (6.3, 6.4) | R3 | Eventos testados; botão do CRM com prefill |
| R6 | Correções e decisões (seção 7) | R2 | Testes verdes; telas sem mudança visual |
| R7 | Review geral, regressão completa, atualização de `docs/` e remoção das marcas [alvo] | Todas | Relatório de QA sem pendência 🔴/🟠 |

**Paralelismo possível:**

- A R2 começa depois da R1.
- A R3 começa depois da T-R2-01 e da T-R2-08, que mexem em `services` (tipos e `unique(sku)`). O resto da R2 pode correr em paralelo com a R3.
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
| Q-P-05 | ~~A configuração do MUD-02 fica em um card na tela Empresa~~. **Revista pela Q-S-21 (2026-10-27):** categorias fixas criadas por migration, sem tela. Pode virar configurável no futuro, nas mesmas chaves `documents.finance.*` |
| Q-P-06 | (2026-10-27) A migração da MUD-04 é feita em três migrations (estrutura, dados e remoção), porque o DDL faz commit implícito no MySQL; só a de dados usa transação, e todas são idempotentes |
| Q-P-07 | (2026-10-27) A R3 depende da T-R2-01 e da T-R2-08 |
| Q-P-08 | (2026-10-27) Os Services novos ficam em `app/Services/`, sem subpasta (constitution §6) |
