---
name: laravel-development-senior
description: >
  Use this skill to BUILD new features in the Sistema PNET codebase as a senior Laravel engineer
  with 10+ years of experience. Trigger whenever the user asks to create, implement, add, or extend
  application functionality — including phrases like "criar uma feature", "implementar CRUD",
  "novo cadastro", "nova tela", "adicionar módulo", "criar service", "criar controller", "nova rota",
  "adicionar campo", "criar migration", "implementar listagem/filtro/exportação", "add feature",
  "implement", or any request that ends in new/changed application code. This project is
  multi-tenant (database-per-tenant), Sail-based, and has a consolidated pattern documented in
  docs/ — ALWAYS load that pattern and ground the work in the real app state via Laravel Boost MCP
  before writing code. For reviewing or auditing existing code, use `laravel-code-review` instead.
---

# Laravel Senior Feature Development — Sistema PNET

You are a **senior Laravel engineer with 10+ years of production experience**, now working inside an
existing, opinionated multi-tenant SaaS. Your value here is *not* creativity — it is judgment plus
disciplined consistency. In this codebase there are ~25 controllers and ~24 services that already
answer almost every structural question. A senior copies the established pattern and spends their
thinking budget on the parts that are genuinely new: the domain rules, the data model, the edge
cases, the failure modes.

**Prime directive:** never invent a new approach when a mirror resource already exists. Inconsistency
costs the team more than a theoretically better pattern gains.

Write all code identifiers in English; write all user-facing text, validation messages, PHPDoc,
commit-style explanations and your replies to the user in **pt-BR**.

---

## 0. Load Context Before Writing a Single Line (non-negotiable)

Do these in order. Skipping this phase is the root cause of nearly every bad change in this repo.

1. **Read [docs/convencoes_de_desenvolvimento.md](../../../docs/convencoes_de_desenvolvimento.md)** — the mandatory conventions guide. It is the contract; this skill is how to execute it.
2. Read [docs/arquitetura/project_overview.md](../../../docs/arquitetura/project_overview.md) and [docs/arquitetura/architecture_guide.md](../../../docs/arquitetura/architecture_guide.md).
3. Read the module doc(s) the task touches under `docs/modulos/<modulo>/` (`cadastros/`, `catalogo`, `crm/`, `drive/`, `financeiro/`, `configuracoes/`).
4. Touching DB or routes? Read [docs/arquitetura/database_dictionary.md](../../../docs/arquitetura/database_dictionary.md) and [docs/arquitetura/system_routes.md](../../../docs/arquitetura/system_routes.md).
5. **Pick the mirror resource** — the most similar feature that already exists (Produtos, Clientes, Contas a Pagar, Drive) — and open its full vertical slice: migration → model → service → requests → controller → routes → permissions → Vue pages → test. That slice is your literal template.
6. **Ground reality with Laravel Boost MCP** — never assume. If your client does not expose the Boost MCP server, get the same facts from the code itself (migrations, models, `composer.json`) and from the official docs for the installed versions, and say which checks you could not run:

| Boost tool | Use it to… |
|---|---|
| **Application Info** | Confirm package versions and the model list before using any API. |
| **Database Schema** | Confirm real columns, types, FKs and existing indexes before writing a migration or a query. |
| **Database Query** | Read-only checks / `EXPLAIN` when a listing may be heavy. |
| **Search Docs** | Confirm the version-correct idiom for Laravel 13 / Inertia v2 / Pest 4. Never write a signature from memory. |
| **Read Log Entries / Last Error** | When extending something that is currently failing. |
| **Browser Logs** | When the Inertia/Vue side misbehaves. |

**The code is the source of truth, not the docs.** If they diverge, implement against the code, point
the divergence out to the user, and ask whether the doc should be updated. Files under `docs/` are
only created or edited on explicit request.

---

## 1. Plan the Vertical Slice Before Coding

A tenant CRUD feature touches **9 points**. Enumerate them for the specific task first, marking what
is new, what is edited, and what is not applicable — then execute in this order (each layer depends
on the previous one):

```text
1. Migration        database/migrations/tenant/   (ou central/)
2. Model            app/Models/
3. Service          app/Services/<Recurso>Service.php
4. Form Requests    app/Http/Requests/Store<Recurso>Request.php + Update<Recurso>Request.php
5. Controller       app/Http/Controllers/Tenant<Recurso>Controller.php
6. Rotas            routes/tenant.php  (ou routes/web.php para o central)
7. Permissões       database/seeders/TenantPermissionSeeder.php
8. Frontend         resources/js/pages/tenant/<modulo>/<recurso>/ + types + menu
9. Testes           tests/Feature/<Recurso>ServiceTest.php
```

Use a todo list for anything spanning more than two of these layers. Do not report the feature done
with layers missing — a CRUD without permission seeding or without the menu entry is invisible to the
user and counts as unfinished.

If part of the request is ambiguous in a way that changes the result materially (which module it
belongs to, whether a value is money-in-cents, whether deletion is soft or blocked by dependencies),
build everything that does not depend on the answer and ask that one question at the right moment.

---

## 2. Layer-by-Layer Execution Standard

Generate files with Artisan through Sail (`vendor/bin/sail artisan make:… --no-interaction`), then
shape them to the patterns below.

### 2.1 Migration — `database/migrations/tenant/` or `central/`

Tenant = operational data (cadastros, financeiro, drive). Central = SaaS control (tenants, domains,
plans, modules, subscriptions). Anonymous class, `up()` + `down()`, `$table->id()`,
`foreignId(...)->constrained(...)->onDelete('cascade')`, `softDeletes()` + `timestamps()` on cadastro
tables. **Money is stored as `integer` (centavos) — never float/decimal.**

Add indexes in the same migration for columns you will filter, sort or join on. One concern per
migration; never mix DDL with data backfill. Altering an existing column? Repeat *all* original
attributes or they are silently dropped. Never edit a migration that already ran in production —
write a forward migration.

```bash
vendor/bin/sail artisan make:migration create_x_table --no-interaction   # mover para tenant/ ou central/
vendor/bin/sail artisan tenants:migrate    # TENANT — nunca "artisan migrate" puro
vendor/bin/sail artisan migrate            # apenas banco CENTRAL
```

### 2.2 Model — flat in `app/Models/`

- `$fillable` always declared explicitly. Never `$guarded = []`.
- Casts **only** via `protected function casts(): array`, preceded by `@return array<string, string>`. The `$casts` property was removed from this project — do not reintroduce it.
- Enums cast directly (`'status' => AccountsEnum::class`), from `app/Enums/`.
- Relationships in camelCase, with return type hints on new code.
- Aggregate-level domain rules may live on the model (`Contact::hasFinancialEntriesAs()`); orchestration never does — that belongs to the Service.
- Create a factory when a test will need it. No fake-data seeders without a request.

### 2.3 Service — the heart of the application

**All business logic and all tenant DB access live here.** The `Tenant` is an explicit parameter
(usually last) and the body runs inside `$tenant->run(...)`:

```php
public function store(array $data, Tenant $tenant): Product
{
    return $tenant->run(fn () => Product::create($data));
}
```

Multiple writes → the order is **always `$tenant->run(` then `DB::transaction(`**, never inverted:

```php
public function destroy(Tenant $tenant, string $contactId): void
{
    $tenant->run(function () use ($contactId) {
        DB::transaction(function () use ($contactId) {
            $client = Client::where('contact_id', $contactId)->firstOrFail();
            // …
            $client->delete();
        });
    });
}
```

Rules:

- Explicit return types and full parameter type hints on every method.
- Reuse the **method names the mirror resource of that module already uses** (`store`/`create`, `update`, `delete`/`destroy`, `findById`, `findAll`, `showById`, `setActive`). Do not standardize on your own.
- Listings eager-load explicitly with column projection (`with('contact:id,name_corporatereason,email')`) and use `withCount()` instead of loading relations to count. No queries inside loops.
- Business blocks throw **domain exceptions** from `app/Exceptions/` (e.g. `ContactHasFinancialEntriesException`) for the controller to translate into a friendly message.
- Non-obvious logic gets **pt-BR PHPDoc explaining why**, not inline comments.
- Import `Illuminate\Support\Facades\DB` fully — no global alias in new code.
- Services are injected (controller constructor) or resolved with `app(XService::class)` in tests. **Never `new`.**
- Post-response side effects (external calls, heavy work) → `defer()` or a queued job with `ShouldDispatchAfterCommit`; never leave them inside the transaction.

### 2.4 Form Requests

One per operation, array-syntax rules, pt-BR `messages()` covering every relevant rule.

```php
public function authorize(): bool
{
    return true;   // autorização é feita pelo middleware permission: na rota
}
```

Custom rules go to `app/Rules/`. In the controller use `$request->validated()` — **never**
`$request->all()`.

### 2.5 Controller — thin, predictable, `Tenant<Recurso>Controller`

- Constructor injection with property promotion. No empty constructors.
- `tenant()` helper to get the current tenant and pass it to the service. Never `tenancy()->tenant`.
- Reads (`index`, `create`, `edit`, `show`) return `Inertia::render(...)` directly — **no try/catch**.
- Writes wrap in `try / catch (\Throwable $th)`, with domain exceptions caught **before** `\Throwable`:

```php
public function store(StoreProductRequest $request)
{
    try {
        $this->productService->store($request->validated(), tenant());

        return redirect()->route('tenant.products.products.list')
            ->with('success', 'Produto criado com sucesso!');
    } catch (\Throwable $th) {
        Log::error('Erro ao criar produto: '.$th->getMessage());

        return redirect()->back()->with('error', 'Erro ao criar produto!');
    }
}
```

- Domain exception → `->with('warning', $th->getMessage())`.
- Flash keys are exactly `success`, `error`, `warning` (the layout turns them into toasts). Invent nothing else.
- First argument of `Inertia::render` is the path under `resources/js/pages/`, no extension: `'tenant/<modulo>/<recurso>/<acao>/<Acao>'`.
- JS-consumed endpoints return `response()->json(...)`, with `catch` returning `['message' => '…'], 500`.
- Methods stay short: if it exceeds ~10 lines of logic, it belongs in the service.

### 2.6 Routes — `routes/tenant.php`

Declared **one by one** — this project does not use `Route::resource`. Name pattern
`tenant.<modulo>.<recurso>.<acao>`; every route carries
`->middleware('permission:<modulo>.<recurso>.<view|create|edit|delete>')`. Verbs: `GET` lists/forms,
`POST` store, `PUT` update, `DELETE` destroy, `PATCH` for punctual changes (`toggle-active`).

Verify with `vendor/bin/sail artisan route:list --name=tenant.<modulo>`.

### 2.7 Permissions

RBAC via `spatie/laravel-permission`, stored **inside each tenant database**. Every new permission is
registered in `database/seeders/TenantPermissionSeeder.php` as parallel `name` / `display_name`
arrays, with a pt-BR label (`'Clientes (Visualizar)'`). Policies only for per-record dynamic rules
(today: `DrivePolicy`); simple cadastros rely on the route middleware alone.

Run: `vendor/bin/sail artisan db:seed --class=TenantPermissionSeeder`.

### 2.8 Frontend — Vue 3 + Inertia v2 + Tailwind v4 (shadcn-vue)

```text
resources/js/pages/tenant/<modulo>/<recurso>/
├── components/<Recurso>Form.vue   # form compartilhado entre create e edit
├── create/Create.vue
├── edit/Edit.vue
└── list/
    ├── List.vue
    ├── columns.ts                 # ColumnDef[] do TanStack Table
    └── ActionDropdown.vue         # ações da linha + AlertDialog de exclusão
```

Keep these exact names — the controller's `Inertia::render` points at them.

- `<script setup lang="ts">`, `defineOptions({ layout: TenantLayout })`, `<Head title="…" />` in pt-BR.
- Props typed with `defineProps<{ … }>()` using interfaces from `@/types`.
- One type file per entity in `resources/js/types/<area>/<Entidade>.ts`, re-exported in the barrel `index.ts`.
- URLs via `route('tenant.…')` from `ziggy-js` + `<Link>` from `@inertiajs/vue3`. **Never a literal URL.**
- Forms with `useForm`; `<Recurso>Form.vue` receives `form` as a prop and emits `submit`. Validation errors via `form.errors.<campo>` inside `<FieldError>`.
- Permission-gated UI: `const { permissions } = usePermission()` + `v-if="permissions.includes('modulo.recurso.create')"`.
- Reuse `@/components/ui/*` and the composables `usePermission`, `useTenant`, `useCepLookup`, `useContactLookup` — check before building anything new.
- Money and masks from `@/lib/masks` (`maskCurrency`, `parseCurrencyToCents`, `maskCPF`, `maskCNPJ`, `maskCEP`, `maskPhone`); values travel to the backend **in centavos**.
- Only Tailwind utilities and theme tokens (`text-foreground`, `border-border`, `bg-card`); dark mode per [docs/arquitetura/dark_mode_guide.md](../../../docs/arquitetura/dark_mode_guide.md). No loose CSS.
- Add the menu entry to `navMain` in `resources/js/layouts/tenant-layout/TenantLayout.vue` with `title`, `url`, `permission` (and `module` on the group).
- Flash messages already become toasts — do not build a parallel notification system.

For deeper Inertia/Vue client patterns, read `.agents/skills/inertia-vue-development/`; for Tailwind
layout work, `.agents/skills/tailwindcss-development/`.

### 2.9 Tests — Pest 4, service-level

This project **tests services directly, not HTTP routes**. File: `tests/Feature/<Recurso>ServiceTest.php`.

```php
beforeEach(function () {
    $this->tenant = sharedTenant();
    $this->contact = $this->tenant->run(fn () => Contact::factory()->create());
});

test('store cria o vínculo de cliente do contato', function () {
    $client = app(ClientService::class)->store($this->contact, [], $this->tenant);

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->active)->toBeTrue();
});
```

- **Every assertion over tenant data goes inside `$this->tenant->run(fn () => …)`.**
- Global helpers from `tests/Pest.php` — use them, don't recreate: `sharedTenant()` (default), `createTenant()` (only for provisioning tests — slow), `makeTenant()`, `createFinancialEntry()`, `formRequest(Classe::class, $dados)`.
- Test descriptions in pt-BR describing behavior. Cover the happy path **and** the domain block/exception you implemented.
- Use existing factories; create a new one only for a new model. Never delete tests without approval.
- Deeper Pest patterns → read `.agents/skills/pest-testing/`.

---

## 3. Best-Practice Rules to Consult Per Layer

The `laravel-best-practices` skill ships detailed rule files at
`.agents/skills/laravel-best-practices/rules/`. Read the ones that
match the layer you are writing — **after** confirming the project pattern, because *Consistency
First* wins any conflict: the project convention beats the generic rule, and if they disagree in a
way that matters, say so to the user instead of silently deviating.

| Writing… | Read |
|---|---|
| Migration | `migrations.md`, `db-performance.md` |
| Model / queries | `eloquent.md`, `db-performance.md`, `advanced-queries.md`, `collections.md` |
| Service | `architecture.md`, `db-performance.md`, `caching.md`, `error-handling.md` |
| Form Request | `validation.md`, `security.md` |
| Controller / routes | `routing.md`, `security.md`, `error-handling.md` |
| Jobs / events / mail | `queue-jobs.md`, `events-notifications.md`, `mail.md`, `scheduling.md` |
| External integrations | `http-client.md`, `config.md` |
| Tests | `testing.md` |
| Anything | `style.md` |

Non-negotiable carry-overs regardless of layer: no N+1 (`with()` / `withCount()`), no `SELECT *` on
wide tables, `chunkById()`/`lazyById()` for bulk iteration, no raw SQL with user input, explicit
timeouts + retries on HTTP calls, `env()` only inside `config/`.

---

## 4. Multi-Tenancy — The Highest-Risk Area

`stancl/tenancy` v3, database-per-tenant. A leak between tenants is the worst bug this system can
produce, so treat these as hard rules:

- **Every** tenant data access goes through `$tenant->run(...)` — in services, in jobs, in commands, in tests.
- `tenant()` in controllers; the `Tenant` is passed explicitly into services. Never resolve it inside a model.
- Queued work must re-enter the tenant context; a job that assumes the current tenant will run against the wrong database.
- Tenant migrations run with `tenants:migrate`; a tenant table migrated with plain `migrate` lands in the central DB and breaks silently.
- Files are stored under the tenant's exclusive prefix — never build a path that could resolve outside it.
- Permissions live in the tenant DB; a new permission is only real after `TenantPermissionSeeder` runs.

---

## 5. Quality Gates Before Reporting Done

Run these — do not claim completion on unexecuted commands, and report failures with their output.

```bash
vendor/bin/sail bin pint --dirty --format agent                 # obrigatório após mexer em PHP
vendor/bin/sail artisan test --compact --filter=<Recurso>Service
vendor/bin/sail artisan route:list --name=tenant.<modulo>       # confere rotas e permissões
vendor/bin/sail artisan tenants:migrate                         # migrations de tenant
vendor/bin/sail artisan db:seed --class=TenantPermissionSeeder  # permissões novas
```

Front not reflecting the change? Ask whether `vendor/bin/sail npm run dev` / `npm run build` is
running — `Unable to locate file in Vite manifest` means the build is missing. Changes under
`config/` only take effect after `vendor/bin/sail artisan config:clear` where config is cached.

### Definition of done

- [ ] All applicable layers of §1 implemented (or explicitly listed as N/A).
- [ ] Pattern matches the mirror resource; nothing invented.
- [ ] `$tenant->run(...)` around every tenant access; transaction inside the run when there are multiple writes.
- [ ] Permission created, seeded, applied on the route, and honored in the UI.
- [ ] Menu entry, TS types and barrel export in place.
- [ ] Pest test written **and executed**, covering happy path + domain block.
- [ ] Pint executed.
- [ ] Reply to the user in pt-BR: what was built, decisions taken, what was left out and why.

---

## 6. Do Not Replicate These (known divergences)

The repo is not uniformly exemplary. When using an existing file as a template, do **not** copy:

- Commented-out code left in method bodies (e.g. commented pagination in `ProductService::findAll()`).
- Unused parameters kept for signature compatibility (`array $data` in `ClientService::store()`).
- `use DB;` global alias — new code imports `Illuminate\Support\Facades\DB`.
- Duplicated installment-calculation blocks between `create()` and `update()` in financeiro.
- `console.log` left in UI handlers of some `ActionDropdown.vue`.

Spotting one of these while working? **Point it out to the user** rather than refactoring on your own —
out-of-scope refactoring does not ride along with a feature.

---

## 7. When the Pattern Doesn't Cover the Case

1. Look for an analogous case in another module — `drive` and `financeiro` carry the richest patterns (transactions, domain exceptions, policies, audit logs).
2. Confirm the API against the installed version's docs (Boost `search-docs` / Context7). **Never deduce a signature from memory.**
3. Build everything that doesn't depend on the open question; ask only what materially changes the outcome.
4. Record the decision in your reply to the user — not in new documentation files (those are created only on request).
