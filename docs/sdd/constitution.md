# Constitution — Sistema PNET v2

> **O que é:** os princípios que não se negociam na v2 do Sistema PNET. Todo artefato do SDD
> ([spec.md](spec.md), [plan.md](plan.md), [tasks.md](tasks.md), [implementation.md](implementation.md))
> e todo agente ou desenvolvedor obedece a este documento.
> Em caso de conflito, esta constitution vence.
> Para mudar uma regra daqui, é preciso uma decisão registrada na seção 16.
>
> **Onde os comandos rodam:** todos os comandos citados no SDD rodam na raiz do projeto
> **`sistema-pnet-v2`**, sempre via Sail (`vendor/bin/sail …`).

---

## 1. Propósito e escopo

A v2 reescreve do zero o Sistema PNET, um SaaS modular, multi-tenant e com um banco por tenant.

- **Base:** o comportamento da v1, documentado no [spec.md](spec.md).
- **Mudanças de regra:** as 4 alterações do PRD v2.
- **Defeitos:** os da v1 que já estão mapeados são corrigidos.

**O que fica fora do backend da v2:** o módulo **CRM**. Ele é entregue **só no frontend**, como na v1, até ser especificado. Ver P-14.

---

## 2. Stack fechada

Nenhuma dependência nova (composer ou npm) entra sem aprovação registrada na seção 16.

| Camada | Tecnologia (versão) |
| --- | --- |
| Linguagem / framework | PHP 8.5, Laravel 13 |
| Multi-tenancy | stancl/tenancy v3 (database-per-tenant, identificação por domínio) |
| Autorização | spatie/laravel-permission (no banco de cada tenant) |
| Autenticação | Fortify v1 (base), com fluxo de login e senha do tenant próprio |
| Frontend | Vue 3 (`<script setup lang="ts">`), Inertia v2, Ziggy v2 |
| UI | Tailwind CSS v4, shadcn-vue (new-york, slate), reka-ui, lucide, vue-sonner, @tanstack/vue-table, @unovis |
| PDF | barryvdh/laravel-dompdf v3 |
| Storage | S3/MinIO (league/flysystem-aws-s3-v3) |
| Testes | Pest 4 (PHPUnit 12) |
| Ambiente | Laravel Sail: MySQL 8.4, Redis, MinIO, Mailpit |
| Qualidade | Laravel Pint, `vue-tsc`, Prettier (+ plugin Tailwind) |
| Ferramentas de IA | Laravel Boost (MCP), skills dos agentes ([implementation.md](implementation.md)) |

---

## 3. Princípios inegociáveis

| # | Princípio | Por quê |
| --- | --- | --- |
| P-01 | **Isolamento de tenant.** Todo acesso a dado de tenant passa por `$tenant->run(...)`. Quando há mais de uma escrita, a ordem é sempre `$tenant->run(` → `DB::transaction(`. | LGPD e segurança: nenhum dado vaza entre empresas. |
| P-02 | **Copiar o padrão, não inventar.** Toda feature segue o recurso-espelho descrito no [plan.md](plan.md). Nada de método, rota, coluna, permissão, componente ou prop inventados. | Consistência e previsibilidade. |
| P-03 | **Sufixo de tipo em toda classe** (seção 6). | Regra do PRD v2. |
| P-04 | **Dinheiro em centavos inteiros**, percentuais em `decimal(7,4)` (seção 7). | Sem erro de ponto flutuante. |
| P-05 | **Regra de negócio no Service ou na Action.** O controller só orquestra. Validação fica no FormRequest. Sem camada Repository. | Testabilidade e controllers finos. |
| P-06 | **Autorização em duas camadas:** middleware `permission:` em toda rota e Policy quando a regra depende do registro. | O plano contratado vale no backend. |
| P-07 | **Efeitos externos depois do commit e em fila:** e-mail, PDF, arquivo, evento de integração. | Um rollback nunca deixa efeito órfão. |
| P-08 | **Arquivos em disco privado**, baixados só por rota autenticada e autorizada. | Os documentos são dados pessoais (LGPD). |
| P-09 | **Dados de referência e permissões entram por migration idempotente**, nunca por seeder. | Chegam a qualquer ambiente só com o `migrate`. |
| P-10 | **Estilo das telas congelado.** A v2 reproduz a v1 pixel a pixel: mesmos componentes, tokens, layout e dark mode (seção 9). | Regra do PRD v2: "NÃO mude nada". |
| P-11 | **Toda mudança tem teste Pest**, e o teste é executado antes de a tarefa ser dada como pronta. | Qualidade. |
| P-12 | **pt-BR** em textos de UI, mensagens, PDFs, e-mails, comentários e documentação. **Inglês** em identificadores de código, tabelas, colunas e rotas. | Padrão do repositório. |
| P-13 | **Nenhum GET com efeito colateral.** Escritas usam POST, PUT, PATCH ou DELETE. Ações críticas são idempotentes, com trava e checagem de estado. | Segurança e consistência. |
| P-14 | **CRM só no frontend.** Nenhuma migration, model, service ou rota de escrita do CRM até o módulo ser especificado. As integrações com o CRM ficam como contrato pendente no [spec.md](spec.md). | Regra do PRD v2. |
| P-15 | **Na dúvida, pare e pergunte.** Nenhum agente decide sozinho algo que muda o resultado. | Critério de parada do PRD v2. |

---

## 4. Arquitetura e camadas

```text
Request → Middleware (tenancy + auth + permission) → Tenant<X>Controller
        → Store/Update/Index<X>Request (validação)
        → <X>Service | <Verbo><X>Action (regra; $tenant->run → DB::transaction)
        → Model (Eloquent; scopes e regras do próprio agregado)
        → Inertia::render('tenant/<modulo>/<recurso>/<acao>/<Acao>') | redirect + flash
Efeitos pós-commit → <X>Job / <X>Mail (ShouldQueue) / <X>Event → <X>Listener
```

- **Service** é o padrão. **Action** só para um fluxo de várias etapas com um ponto de entrada (`handle()`), como a calculadora. Uma Action nunca duplica regra de um Service: chama o Service.
- **Exceções de domínio** (`<Motivo>Exception`, em `app/Exceptions/`) bloqueiam regras de negócio. O controller as captura **antes** do `\Throwable` e as transforma em flash `warning`, ou em HTTP 422 nos endpoints JSON.
- **Flash keys:** `success`, `error` e `warning`. Nenhuma outra.
- **Rotas:**
  - Declaradas uma a uma em `routes/tenant.php` (tenant) ou `routes/web.php` (central). Sem `Route::resource`.
  - Nome no formato `tenant.<modulo>.<recurso>.<acao>`.
  - Ações: `list`, `create`, `store`, `edit`, `update`, `destroy`, `show`, `restore`, `toggle-active`.
- **Sem lógica no controller:** nenhuma consulta Eloquent direto no controller. A v1 faz isso em `Contact::where`, `BankAccount::...` e não deve ser copiada.

---

## 5. Multi-tenancy

1. **Bancos:**
   - O banco central guarda apenas o SaaS: tenants, domínios, planos, módulos e catálogo de permissões. A cobrança fica fora da v2 (D-11).
   - Todo dado operacional fica no banco do tenant.
2. **Migrations:**
   - `database/migrations/central/` roda com `vendor/bin/sail artisan migrate --path=database/migrations/central`.
   - `database/migrations/tenant/` roda com `vendor/bin/sail artisan tenants:migrate`.
   - Nunca rodar `migrate` puro para tabela de tenant.
3. **Service:** o `Tenant` é parâmetro explícito, normalmente o último. Nos controllers, ele é obtido com o helper `tenant()`.
4. **Recursos compartilhados:**
   - Fila: o `QueueTenancyBootstrapper` restaura o tenant de cada job.
   - Disco: prefixo `tenant<id>/`.
   - Cache: tag por tenant.
   - Sessão: prefixo Redis por tenant.
   - **Exceção:** um cache global, como os municípios do IBGE, precisa ser declarado como tal.
5. **Rate limit:** a chave combina o domínio do tenant com o usuário, porque os ids de usuário se repetem entre tenants.
6. **Provisionamento:** roda em fila, com a sequência `CreateDatabase` → `MigrateDatabase` → `SeedTenantDatabaseJob`. Uma falha apaga o tenant. O domínio precisa ser um host válido (com ponto).

---

## 6. Nomenclatura — sufixo de tipo obrigatório

**Regra:** todo nome de classe termina com o seu tipo. Os controllers de tenant **mantêm o prefixo `Tenant`**.

| Tipo | Padrão | Exemplo | Pasta |
| --- | --- | --- | --- |
| Controller (tenant) | `Tenant<Recurso>Controller` | `TenantClientController` | `app/Http/Controllers/` |
| Controller de ação única | `Tenant<Verbo><Recurso>Controller` (`__invoke`) | `TenantConvertQuoteToProposalController` | `app/Http/Controllers/` |
| Controller (central/auth) | `<Contexto><Recurso>Controller` | `TenantRegistrationController`, `AuthTenantController` | `app/Http/Controllers/`, `Auth/` |
| Form Request | `<Ação><Recurso>Request` | `StoreClientRequest`, `IndexProposalRequest` | `app/Http/Requests/` |
| Service | `<Recurso>Service` (singular) | `ClientService`, `SupplierService` | `app/Services/` |
| Action | `<Verbo><Recurso>Action` | `CreateQuoteAction`, `ConvertQuoteToProposalAction` | `app/Actions/<Modulo>/` |
| Policy | `<Model>Policy` | `ClientPolicy`, `ProposalPolicy` | `app/Policies/` |
| Enum | `<Nome>Enum` | `UserTypeEnum`, `ProposalStatusEnum`, `CostItemTypeEnum` | `app/Enums/` |
| Job | `<Ação>Job` | `SendEmailDispatchJob`, `AttachQuotePdfToProposalJob`, `SeedTenantDatabaseJob` | `app/Jobs/` |
| Command | `<Ação>Command` | `AutoBlockClientsCommand`, `AlertOverdueProposalStagesCommand`, `EnsureBucketCommand` | `app/Console/Commands/` |
| Rule | `<Nome>Rule` | `CpfRule`, `CnpjRule`, `NonOverlappingBracketsRule`, `UniqueCatalogNameRule` | `app/Rules/` |
| Exception | `<Motivo>Exception` | `InstallmentAlreadyPaidException` | `app/Exceptions/` |
| Mail | `<Evento>Mail` | `ProposalCreatedMail` | `app/Mail/` |
| Event / Listener | `<Fato>Event` / `<Reação>Listener` | `QuoteCreatedEvent` / `CreateCrmDealFromQuoteListener` | `app/Events/`, `app/Listeners/` |
| Middleware | `<Nome>Middleware` | `RedirectProposalRestrictedUsersMiddleware` | `app/Http/Middleware/` |
| Cliente HTTP externo | `<Sistema>Client` | `RegistryFeeApiClient`, `IbgeLocalityClient` | `app/Services/<Sistema>/` |
| DTO | `<Nome>Dto` | `RegistryFeeRequestDto`, `ItbiInputDto` | junto do service que o usa |
| Query object | `<Nome>Query` | `FinishedProposalsReportQuery` | `app/Queries/` |
| Builder / Calculator | `<Nome>Builder`, `<Nome>Calculator` | `CostItemsBuilder`, `ItbiCalculator` | `app/Services/<Modulo>/` |
| Utilitário puro | `<Nome>Helper` | `MoneyHelper`, `DocumentMaskHelper` | `app/Support/` |
| Teste | `<Classe>Test` | `ClientServiceTest`, `StoreProposalRequestTest` | `tests/Feature/`, `tests/Unit/` |
| Factory / Seeder | `<Model>Factory` / `<Nome>Seeder` | `ProposalFactory`, `TenantContactSeeder` | `database/` |
| Model | `<Entidade>` (singular, **sem sufixo**) | `Client`, `Proposal` | `app/Models/` (sem subpastas) |

**Renomeações obrigatórias em relação à v1** (exemplos; a lista completa fica no [plan.md](plan.md)):

- `SuppliersService` → `SupplierService`.
- `EmployeesService` → `EmployeeService`.
- `ServicesService` → `ServiceCatalogService`.
- `CoastService` e `PaidMarketService` são removidos (estão vazios).
- `ProposalStatus` → `ProposalStatusEnum`, e o mesmo vale para todos os enums sem sufixo.
- `CreateQuote` → `CreateQuoteAction`.
- `AttachQuotePdfToProposal` → `AttachQuotePdfToProposalJob`.
- `EnsureBucket` → `EnsureBucketCommand`.
- `UserHasRole` → `UserHasRoleRule`.

**Outros nomes:**

- **Tabelas e colunas:** inglês e `snake_case`. Tabelas no plural; pivots no singular, em ordem alfabética (`applicant_proposal`).
- **Rotas, permissões, query strings e valores de enum:** sempre em **inglês**. A v1 usa `periodo`, `conta_id`, `cliente`, `despesa`, `somente_proprietario`; na v2 esses valores passam para o inglês e os rótulos em pt-BR vêm do `label()`.
- **Vue:** a pasta é `pages/tenant/<modulo>/<recurso>/{list/List.vue, create/Create.vue, edit/Edit.vue, show/Show.vue, components/<Recurso>Form.vue}`. Tipos ficam em `resources/js/types/<area>/<Entidade>.ts`, reexportados em `types/index.ts`.

---

## 7. Dados e banco

| Regra | Detalhe |
| --- | --- |
| Dinheiro | `unsignedBigInteger` em **centavos** em **todas** as colunas monetárias. A v1 mistura `int`/`bigint`/`ubigint`; a v2 unifica (D-06). O nome da coluna não leva sufixo. Exibição: `MoneyHelper::format()` no PHP e `formatMoney()` no front. |
| Percentual | `decimal(7,4)`, cast `decimal:4`. O valor é arredondado só no final: `(int) round($cents * $rate / 100)`. |
| Datas | ISO no transporte, formato pt-BR na tela. Data vazia é `null`, nunca `0000-00-00`. |
| Chaves | `$table->id()`. A exceção é `fee_calculations`, que usa UUID. FK com `constrained()` e `onDelete` explícito: `restrictOnDelete` para catálogos, `cascadeOnDelete` para filhos do agregado, `nullOnDelete` para vínculos opcionais. |
| Soft delete | Cadastros e entidades de negócio usam `softDeletes()`. Relações para catálogos usam `withTrashed()`, para que o registro excluído continue aparecendo onde está em uso. |
| Unicidade | Toda regra de unicidade de negócio tem índice `unique` no banco ou validação com `withoutTrashed()`. A decisão de cada caso fica registrada no spec. |
| Models | `$fillable` explícito, sem `id`. Casts pelo método `casts()`, com o PHPDoc `@return array<string, string>`. Relações com tipo de retorno e nome em camelCase. |
| Migrations | Classe anônima, com `up()` e `down()` reversíveis. Ao alterar uma coluna, todos os atributos dela são repetidos. |
| Dados de referência | Migration idempotente usando `DB::table`: insere só o que falta e conta também os registros com soft delete. O `down()` só remove o que é seguro remover. |
| Seeders | Só dados fictícios de desenvolvimento. Nunca chegam à produção. |

---

## 8. Segurança e autorização

1. **Formato das permissões:** `<modulo>.<recurso>.<view|create|edit|delete>`.
   - O catálogo central é criado por migration, com o rótulo em pt-BR.
   - As permissões chegam ao tenant pelo `TenantPermissionService`: `forModules`, `forTenant`, `apply`, `sync`.
   - **O plano filtra as permissões:** nenhuma migration de tenant cria permissão "para todos".
2. **Duas migrations por permissão nova:** uma central (o catálogo) e uma de tenant (a sincronização respeitando o plano).
3. **Cargos:**
   - São definidos no `RoleEnum` e gravados pelo rótulo em pt-BR.
   - As permissões padrão de cada cargo são aplicadas só no provisionamento.
4. **Policies:** só existem quando a regra depende do registro (`ProposalPolicy`, `DrivePolicy`, `FeeCalculationPolicy`). Usam `checkPermissionTo()`.
5. **Form Requests:**
   - O `authorize()` retorna `true`, porque a autorização acontece na rota e na Policy.
   - O controller usa só `$request->validated()`; nunca `all()`.
6. **Segredos:** chaves e tokens ficam só no `.env`, e cada chave é declarada vazia no `.env.example`. Toda chamada externa usa TLS.
7. **HTML de terceiros** é sanitizado no servidor antes de ser exibido com `v-html`.
8. **Senha:** nunca é enviada por e-mail. Usuário novo recebe um link de definição de senha.

---

## 9. Frontend — estilo congelado

A v2 **não muda** o visual da v1. Os itens abaixo são reproduzidos 1:1:

- **Tema:** `resources/css/app.css`, com os tokens OKLCH light/dark, `--radius: 0.625rem`, scrollbars e o `@custom-variant dark`.
- **Componentes:** a pasta `components/ui/*` do shadcn-vue, com as customizações da v1: `Button` com `loading`, `DataTable`, `ServerDataTable`, `ComboboxRemote`, `ComboboxSelect` e `Sonner`.
- **Layouts:** `TenantLayout`, `TenantSidebar` e `AuthLayout`.
- **Componentes de domínio:** `MoneyInput`, `@/lib/masks`, `@/lib/validators` e `@/lib/constants`.
- **Composables:** `usePermission`, `useTenant`, `useCepLookup` e `useContactLookup`.
- **Padrões de página** (conforme o mapa de telas do [spec.md](spec.md)):
  - cabeçalho `h2.text-3xl font-bold`;
  - card de formulário;
  - `ActionDropdown`;
  - `AlertDialog` de exclusão;
  - abas sublinhadas;
  - controles segmentados;
  - cards de KPI;
  - toasts em `bottom-right`.

**Regras de frontend:**

- Página sempre com `<script setup lang="ts">`, `defineOptions({ layout: TenantLayout })` e `<Head title>` em pt-BR.
- Formulários com `useForm`. O `<Recurso>Form.vue` recebe `form` por prop e emite `submit`.
- URLs sempre por `route('tenant.…')` (Ziggy, D-08). Nenhuma URL literal; a v1 tem algumas na sidebar e nas buscas.
- Estilo só com utilitários Tailwind e tokens do tema. Antes de criar um componente, verificar se ele já existe.
- **Correções permitidas** (não mudam o visual):
  - carregar a fonte Instrument Sans, já declarada no tema;
  - remover arquivos mortos;
  - ligar o botão "Exportar lista" ou removê-lo — decidir no spec.

---

## 10. Efeitos colaterais, fila e integrações

- E-mails são `<Evento>Mail implements ShouldQueue`, disparados com `afterCommit`.
- Jobs são `<Ação>Job`, também com `afterCommit` quando nascem dentro de uma transação.
- **Integrações entre módulos:** usam `<Fato>Event` → `<Reação>Listener`, ou um Service chamado na mesma transação quando a consistência precisa ser atômica. A escolha de cada caso fica no [plan.md](plan.md).
- **Clientes HTTP:**
  - timeout explícito;
  - retry só em falha de conexão;
  - exceções em `app/Exceptions/<Sistema>/`;
  - testes com `Http::fake()` e `Http::preventStrayRequests()`.
- **Agendamentos:** ficam em `routes/console.php`. Comandos que enviam e-mail recorrente precisam evitar reenvio duplicado (marcação de envio).

---

## 11. Testes e qualidade

| Regra | Detalhe |
| --- | --- |
| Framework | Pest 4. `vendor/bin/sail artisan make:test --pest <Classe>Test --no-interaction` |
| Nível | **Testes de Service e de regra** (`tests/Feature/<Classe>ServiceTest.php`), testes de Form Request (helper `formRequest()`) e testes unitários de classes puras (calculadoras, builders, enums). Testes HTTP só para middleware e roteamento (`DocumentsRoutesTest`), conforme D-09. |
| Tenant | O helper `sharedTenant()` é o padrão. Toda asserção sobre dado de tenant fica dentro de `$this->tenant->run(fn () => …)`. |
| Helpers globais | `sharedTenant()`, `createTenant()`, `makeTenant()`, `createFinancialEntry()`, `formRequest()`, `userWithRole()` e `createProposal()`, em `tests/Pest.php`. |
| Cobertura mínima | Cada regra de negócio (RN) do spec tem um teste. Cada permissão e Policy tem teste de acesso negado. Cada fluxo com dinheiro tem asserção em centavos. |
| Execução | Só os arquivos afetados: `vendor/bin/sail artisan test --compact tests/Feature/<Arquivo>.php`. |
| Estilo | `vendor/bin/sail bin pint --dirty --format agent` depois de mexer em PHP. `vendor/bin/sail npm run type-check` depois de mexer em TS ou Vue. |
| Review | Toda tarefa passa pelo agente de code review antes de ser fechada ([implementation.md](implementation.md)). |

---

## 12. Idioma e documentação

- Os artefatos do SDD e a documentação de `docs/` são escritos em **pt-BR**.
- Documentos só são criados ou alterados mediante pedido explícito.
- Se a documentação e o código divergirem, **o código é o estado real**: aponte a divergência e pergunte se o documento deve ser atualizado.
- Comentários: PHPDoc em pt-BR explicando o **porquê** da regra não óbvia. Sem comentário inline trivial e sem código comentado.

---

## 13. Uso das ferramentas

- Antes de usar uma API do Laravel, Inertia, Pest ou Tailwind, consultar a doc da versão instalada (`search-docs` do Boost ou Context7).
- O estado real vem das ferramentas do Boost: `database-schema`, `database-query`, `last-error`, `read-log-entries` e `browser-logs`. Não supor.
- Arquivos são gerados com `vendor/bin/sail artisan make:… --no-interaction`.
- Se uma mudança de front "não aparecer", pergunte se o `npm run dev` ou o `npm run build` está rodando.

---

## 14. Definition of Done global

Uma tarefa só está **pronta** quando todos os itens abaixo estão cumpridos:

- [ ] Segue o padrão do recurso-espelho e as seções 4 a 9 desta constitution.
- [ ] Os nomes têm sufixo de tipo (seção 6).
- [ ] Rotas têm `permission:`, e há Policy quando a regra é por registro.
- [ ] Dinheiro em centavos e efeitos externos depois do commit.
- [ ] Os testes Pest dos arquivos afetados foram escritos **e executados**, todos verdes.
- [ ] Pint aplicado; `type-check` sem erro quando houve mudança no front.
- [ ] Code review sem achados 🔴 ou 🟠 pendentes.
- [ ] O critério de aceite (BDD) da tarefa no [spec.md](spec.md) foi atendido.
- [ ] Nenhuma tela teve o visual alterado em relação à v1.

---

## 15. Hierarquia dos documentos

`constitution.md` > `spec.md` (o quê) > `plan.md` (como) > `tasks.md` (ordem) > `implementation.md` (execução).

Um documento de nível inferior nunca contradiz um superior. Se surgir uma contradição, pare e pergunte (P-15).

---

## 16. Registro de decisões

| ID | Data | Decisão |
| --- | --- | --- |
| D-01 | 2026-10-26 | Os controllers mantêm o prefixo `Tenant` e todas as classes ganham sufixo de tipo. |
| D-02 | 2026-10-26 | O CRM fica só no frontend. As integrações com ele (orçamento → funil e proposta do CRM → proposta de documentações) ficam como contrato pendente. |
| D-03 | 2026-10-26 | O "Billable Service" é removido. Orçamento e proposta passam a usar o catálogo `services` como está: `generates_receipt` e a descrição obrigatória são descartados. |
| D-04 | 2026-10-26 | (Q-C1) DTOs usam o sufixo `Dto` (`RegistryFeeRequestDto`, `ItbiInputDto`). Utilitários puros usam `Helper` (`MoneyHelper`, `DocumentMaskHelper`). O `ServicesService` vira `ServiceCatalogService`. |
| D-05 | 2026-10-26 | (Q-C2) Models ficam **sem sufixo** (`Client`, `Proposal`), no padrão do Laravel. |
| D-06 | 2026-10-26 | (Q-C3) **Todo** dinheiro é `unsignedBigInteger` em centavos, incluindo catálogo, salário, financeiro e planos. |
| D-07 | 2026-10-26 | (Q-C8) Valores de enum, query strings e parâmetros ficam em **inglês**: `client`, `expense`, `owner_only`, `period`, `bank_account_id`, `start`, `end`. Os rótulos em pt-BR vêm de `label()`. |
| D-08 | 2026-10-26 | (Q-C4) Mantém o **Ziggy** (`route()`). Não haverá Wayfinder. |
| D-09 | 2026-10-26 | (Q-C5) Os testes ficam no nível de **Service, Form Request e classes puras**. Testes HTTP só para middleware e roteamento. Não haverá teste HTTP por rota. |
| D-10 | 2026-10-26 | (Q-C6) Todo PHP novo leva `declare(strict_types=1);`. Classes `final` em Actions, DTOs, Calculators, Builders, Clients e Services sem herança. |
| D-11 | 2026-10-26 | (Q-C7) A cobrança do SaaS (Mercado Pago, assinaturas, pagamentos, cupons) fica **fora da v2**: nem as tabelas são criadas. Planos e módulos continuam funcionando como na v1. |
