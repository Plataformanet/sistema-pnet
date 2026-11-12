# Convenções de Desenvolvimento — Guia Obrigatório para Modelos de IA

> **Este documento detalha a [constituição](../specs/constitution.md) e está abaixo dela (D-01).**
>
> **Público-alvo:** agentes de IA (Claude Code, Antigravity, Cursor, Copilot etc.) e desenvolvedores que vão criar ou alterar funcionalidades no **Sistema PNET**.
>
> **Regra número um:** este projeto já tem um padrão consolidado. **Nunca invente uma abordagem nova.** Antes de escrever qualquer linha, abra um recurso equivalente já existente (ex.: Produtos, Clientes, Contas a Pagar) e replique a estrutura dele. **Exceção:** se o recurso-espelho viola a constituição (os casos conhecidos estão em [`specs/violacoes-conhecidas.md`](../specs/violacoes-conhecidas.md)), escreva o código novo conforme a constituição.

---

## 0. Antes de começar (checklist de contexto)

Execute nesta ordem, sempre:

1. Ler [specs/constitution.md](../specs/constitution.md) e, se a tarefa muda comportamento, [specs/README.md](../specs/README.md), a pasta da feature e a §15 (D-05); depois [docs/arquitetura/project_overview.md](arquitetura/project_overview.md) e [docs/arquitetura/architecture_guide.md](arquitetura/architecture_guide.md).
2. Ler a documentação do módulo afetado em `docs/modulos/<modulo>/`.
3. Antes de mexer em banco ou rotas: [docs/arquitetura/database_dictionary.md](arquitetura/database_dictionary.md) e [docs/arquitetura/system_routes.md](arquitetura/system_routes.md).
4. Abrir o **recurso-espelho** mais parecido com o que será criado e usá-lo como molde, com a exceção da Regra número um.
5. Confirmar o estado real da aplicação com as ferramentas do **Laravel Boost** (schema, queries, logs, docs por versão) em vez de supor.

Divergências entre código e documentos seguem a D-01: o código vale sobre os documentos descritivos (`docs/arquitetura/`, `docs/modulos/`), e o código que diverge da constituição ou destas convenções é violação. Arquivos em `docs/` só são criados ou alterados com pedido explícito (D-02).

---

## 1. Regras operacionais

| # | Regra | Motivo |
|:--|:--|:--|
| 1 | **Copie o padrão existente, não crie um novo**, com a exceção da Regra número um. | Consistência entre os controllers e services. |
| 2 | **Não invente método, rota, coluna, permissão, componente ou prop.** Verifique se existe. | Alucinação quebra build e migrations. |
| 3 | Dependências novas: ver **P-10**. | Constituição. |
| 4 | Pastas-base novas em `app/` e `resources/js/`: ver **P-10**. **Não crie pastas-base novas em `docs/`.** | Estrutura definida. |
| 5 | Comandos via Sail: ver **P-10**. | Constituição. |
| 6 | Testes: ver **P-09**; seleção de testes na §11. | Constituição. |
| 7 | **Rodar `vendor/bin/sail bin pint --dirty --format agent`** depois de mexer em PHP. | Estilo. |
| 8 | Texto ao usuário e identificadores: ver **P-11**; documentação: ver **D-02**. **Comentários e PHPDoc em pt-BR.** | Padrão do repositório. |
| 9 | Contexto de tenant: ver **P-01**. | Constituição. |
| 10 | Se um módulo tiver uma seção própria nestas convenções, ela vence a seção geral, mas nunca vence a constituição. Hoje nenhum módulo tem seção própria; padrão que só existe no código do módulo não é regra. | Precedência (D-01). |
| 11 | Nomes de classe: ver **P-12** e a §4a. | Constituição. |
| 12 | Base visual: ver **P-13** e a §10. | Constituição. |

---

## 2. Anatomia de uma feature (o caminho completo)

Uma funcionalidade CRUD de tenant toca **9 pontos**. Nenhum é opcional:

```text
1. Migration          database/migrations/tenant/     (ou central/)
2. Model              app/Models/
3. Service            app/Services/
4. Form Requests      app/Http/Requests/              (Store… e Update…)
5. Controller         app/Http/Controllers/           (Tenant…Controller)
6. Rotas              routes/tenant.php               (ou routes/web.php)
7. Permissões         migration de dados em database/migrations/central/ (catálogo)
                      + migration de tenant via TenantPermissionService (tenants existentes)
8. Frontend           resources/js/pages/tenant/<modulo>/<recurso>/
                      + resources/js/types/<area>/<Recurso>.ts (+ barrel index.ts)
                      + item de menu em resources/js/layouts/tenant-layout/TenantLayout.vue
9. Testes             tests/Feature/<Recurso>ServiceTest.php
```

O restante deste documento detalha cada camada com o padrão **real** extraído do código.

---

## 3. Migrations

- **Tenant** (dados operacionais: cadastros, financeiro, drive) → `database/migrations/tenant/`.
- **Central** (SaaS: tenants, domínios, planos, módulos, assinaturas) → `database/migrations/central/`.
- Classe anônima (`return new class extends Migration`), com `up()` e `down()`.
- IDs auto-incremento (`$table->id()`), FKs com `foreignId(...)->constrained(...)->onDelete('cascade')`.
- `softDeletes()` + `timestamps()` na maioria das tabelas de cadastro.
- Valores monetários: ver **P-04**; coluna `integer` (centavos).
- Dados de referência: ver **P-05**. Regras dessas migrations de dados:
  - usam o query builder (`DB::table(...)`), não models, para não depender do estado futuro do model;
  - são **idempotentes**: inserem só o que falta, contando também registros com soft delete, para não ressuscitar o que o tenant apagou e não sobrescrever o que ele ajustou;
  - `down()` só remove o que é seguro remover. Planos e configurações, por exemplo, têm `down()` vazio.
  - Modelos: [2026_10_01_100009_seed_documents_catalogs.php](../database/migrations/tenant/2026_10_01_100009_seed_documents_catalogs.php) (tenant) e [2026_10_06_185110_seed_plans.php](../database/migrations/central/2026_10_06_185110_seed_plans.php) (central).
- Seeders (`database/seeders/`) ficam só para **dados fictícios de desenvolvimento** (`TenantUserSeeder`, `TenantContactSeeder`), que nunca devem chegar à produção.

Referência: [database/migrations/tenant/2026_05_11_130945_create_products_table.php](../database/migrations/tenant/2026_05_11_130945_create_products_table.php)

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_category_id')->constrained('product_categories')->onDelete('cascade');
    $table->string('name');
    $table->integer('cost_value');
    $table->boolean('status')->default(true);
    $table->softDeletes();
    $table->timestamps();
});
```

Comandos:

```bash
vendor/bin/sail artisan make:migration create_x_table --no-interaction   # mover para tenant/ ou central/
vendor/bin/sail artisan tenants:migrate      # migrations de TENANT — nunca "artisan migrate" puro
vendor/bin/sail artisan migrate              # apenas para o banco CENTRAL
```

> ⚠️ Alterando coluna existente: repita **todos** os atributos originais, senão eles são perdidos.

---

## 4. Models

Ficam **direto em `app/Models/`** (sem subpastas), tanto os centrais quanto os de tenant.

Padrão real ([app/Models/Product.php](../app/Models/Product.php)):

```php
class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_category_id', 'name', 'sku', /* … */];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_value' => 'integer',
            'manage_stock' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }
}
```

Regras:

- `$fillable` sempre declarado (nunca `$guarded = []`).
- **Casts sempre pelo método `protected function casts(): array`**, precedido do PHPDoc `@return array<string, string>`. A propriedade `$casts` foi eliminada do projeto — **não a reintroduza**.
- Enums são usados como cast diretamente (`'status' => AccountsEnum::class`) — veja `app/Enums/`.
- Relacionamentos com nome em camelCase (`productCategory`, `financialContact`).
- Regras de negócio de domínio podem morar no model quando são do próprio agregado (ex.: `Contact::hasFinancialEntriesAs()`, `Contact::deleteIfOrphaned()`), mas **orquestração fica no Service**.
- Ao criar model novo, crie também factory quando houver teste que precise (`database/factories/`). Não crie seeder de dados fictícios sem pedido.

---

## 4a. Nomes de classe

Regra: **P-12**. Toda classe criada para o projeto segue esta tabela.

| Tipo | Padrão | Pasta | Exemplo |
| --- | --- | --- | --- |
| Controller de tenant | `Tenant<Recurso>Controller` | `app/Http/Controllers/` | `TenantClientController` |
| Controller de ação única | `Tenant<Verbo><Recurso>Controller` | `app/Http/Controllers/` | `TenantConvertQuoteToProposalController` |
| Controller central ou de autenticação | `<Contexto><Recurso>Controller` | `app/Http/Controllers/`, `Auth/` | `AuthTenantController`, `TenantController` |
| Form Request | `<Ação><Recurso>Request` | `app/Http/Requests/` | `StoreClientRequest` |
| Service | `<Recurso>Service` | `app/Services/` | `ClientService` |
| Action | `<Verbo><Recurso>Action` | `app/Actions/<Modulo>/` | `CreateQuoteAction` |
| Policy | `<Model>Policy` | `app/Policies/` | `ProposalPolicy` |
| Enum | `<Nome>Enum` | `app/Enums/` | `RolesEnum` |
| Job | `<Ação>Job` | `app/Jobs/` | `SeedTenantDatabaseJob` |
| Command | `<Ação>Command` | `app/Console/Commands/` | — |
| Rule | `<Nome>Rule` | `app/Rules/` | `CpfRule` |
| Exception | `<Motivo>Exception` | `app/Exceptions/` | `ContactHasFinancialEntriesException` |
| Mail | `<Evento>Mail` | `app/Mail/` | — |
| Middleware | `<Nome>Middleware` | `app/Http/Middleware/` | `RedirectProposalRestrictedUsersMiddleware` |
| Bootstrapper de tenancy | `<Nome>Bootstrapper` | `app/Tenancy/Bootstrappers/` | `TenantSessionBootstrapper` |
| Service provider | `<Nome>ServiceProvider` | `app/Providers/` | `TenancyServiceProvider` |
| Cliente HTTP externo | `<Sistema>Client` | `app/Services/<Sistema>/` | `IbgeLocalityClient` |
| DTO | `<Nome>Dto` | junto do service | `ItbiInputDto` |
| Query | `<Nome>Query` | `app/Queries/` | `FinishedProposalsReportQuery` |
| Builder / Calculator | `<Nome>Builder`, `<Nome>Calculator` | `app/Services/<Modulo>/` | `CostItemsBuilder`, `ItbiCalculator` |
| Utilitário puro | `<Nome>Helper` | `app/Support/` | `MoneyHelper` |
| Model | `<Entidade>`, sem sufixo | `app/Models/` | `Proposal` |
| Factory | `<Model>Factory` | `database/factories/` | — |
| Seeder | `<Nome>Seeder` | `database/seeders/` | `TenantContactSeeder` |
| Teste | `<Classe>Test` | `tests/Feature/`, `tests/Unit/` | `ClientServiceTest` |

Exceção (P-12): classes geradas pelo framework ou pelos pacotes mantêm o nome original: as Actions do Fortify em `app/Actions/Fortify/`, `Authenticate`, `HandleInertiaRequests`, `VerifyCsrfToken`, `Controller`, `AppServiceProvider`, `FortifyServiceProvider` e `tests/TestCase.php`.

---

## 5. Services — o coração da aplicação

**Toda a lógica de negócio e todo acesso a banco do tenant ficam em `app/Services/<Recurso>Service.php`.** Controller nunca consulta Eloquent diretamente (exceto lookups triviais já existentes no código legado).

### 5.1. Assinatura padrão

O `Tenant` é parâmetro **explícito** (normalmente o último) e o corpo roda dentro de `$tenant->run(...)`. A outra forma aceita pela **P-01** (service que declara no PHPDoc que exige contexto já inicializado) é exceção, usada só por services chamados de dentro de outro `run`:

[app/Services/ProductService.php](../app/Services/ProductService.php)

```php
class ProductService
{
    public function store(array $data, Tenant $tenant): Product
    {
        return $tenant->run(fn () => Product::create($data));
    }

    public function update(array $data, string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => Product::findOrFail($id)->update($data));
    }

    public function delete(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => Product::findOrFail($id)->delete());
    }

    public function findById(string $id, Tenant $tenant): Product
    {
        return $tenant->run(fn () => Product::findOrFail($id));
    }

    public function findAll(Tenant $tenant): Collection
    {
        return $tenant->run(fn () => Product::all());
    }
}
```

Nomes de métodos usados no projeto: `store` / `create`, `update`, `delete` / `destroy`, `findById`, `findByContactId`, `findAll`, `showById`, `setActive`. **Use o mesmo nome que o recurso-espelho do módulo usa** — não padronize por conta própria.

### 5.2. Escritas múltiplas → transação dentro do `run`

A ordem é sempre **`$tenant->run(` → `DB::transaction(`**, nunca o contrário:

[app/Services/ClientService.php](../app/Services/ClientService.php)

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

### 5.3. Outras regras de Service

- **Tipo de retorno explícito** em todos os métodos; type hints em todos os parâmetros.
- Consultas de listagem carregam relações explicitamente (`with('contact:id,name_corporatereason,email')`) para evitar N+1, e podem projetar o array final que o front consome.
- Regras de bloqueio de negócio lançam **exceções de domínio** de `app/Exceptions/` (ex.: `ContactHasFinancialEntriesException`, `UpdateInstallmentException`), que o controller captura para virar mensagem amigável.
- Lógica não óbvia recebe **PHPDoc em pt-BR** explicando o porquê (ver `ClientService::persist()`), não comentário inline.
- Herança só quando já existe base (`AccountService` é abstrata e obriga `getModel(): string`; `AccountPayableService` e `AccountReceivableService` a estendem). Não crie hierarquias novas sem necessidade real.
- Services são resolvidos por injeção de dependência (construtor do controller) ou `app(XService::class)` nos testes. **Não instancie com `new`.**

---

## 6. Form Requests

Um por operação: `Store<Recurso>Request` e `Update<Recurso>Request` em `app/Http/Requests/`. Também existem requests específicos de ação (`MoveDriveRequest`, `IndexAccountPayableRequest`, `RestoreDriveRequest`).

[app/Http/Requests/StoreProductRequest.php](../app/Http/Requests/StoreProductRequest.php)

```php
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'sku' => ['required', Rule::unique('products', 'sku')],
            'current_stock' => ['required_if:manage_stock,true', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'sku.unique' => 'O SKU já está cadastrado.',
        ];
    }
}
```

Regras:

- `authorize()` retorna `true`: a autorização é feita pelo middleware `permission:` na rota e, quando o acesso depende do registro, por Policy (**P-07**).
- Regras sempre em **formato de array** (`['required', 'string']`), nunca string com pipes.
- `messages()` **em pt-BR, cobrindo cada regra relevante** — é o que o usuário final vê.
- Regras customizadas vão para `app/Rules/` (ex.: `CpfRule`) e são referenciadas na lista.
- No controller, use sempre `$request->validated()` (ou `$request->validated('campo')`). Nunca `$request->all()`.

---

## 7. Controllers

Nome: `Tenant<Recurso>Controller` (recursos de inquilino). Autenticação central/tenant fica em `app/Http/Controllers/Auth/`.

**Padrão exigido:**

[app/Http/Controllers/TenantProductController.php](../app/Http/Controllers/TenantProductController.php)

```php
use Illuminate\Database\QueryException;

class TenantProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected ProductCategoryService $productCategoryService
    ) {}

    public function index()
    {
        $products = $this->productService->findAll(tenant());

        return Inertia::render('tenant/products/products/list/List', compact('products'));
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $this->productService->store($request->validated(), tenant());

            return redirect()->route('tenant.products.products.list')
                ->with('success', 'Produto criado com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao criar produto', $th instanceof QueryException
                ? ['tenant' => tenant('id'), 'exception' => QueryException::class, 'sqlstate' => $th->getCode()]
                : ['tenant' => tenant('id'), 'message' => $th->getMessage()]);

            return redirect()->back()->with('error', 'Erro ao criar produto!');
        }
    }
}
```

O `TenantProductController` real ainda usa o padrão antigo e está em `specs/violacoes-conhecidas.md`. Não altere o controller nesta mudança. (`getCode()` de uma `QueryException` devolve o SQLSTATE.)

Regras obrigatórias:

- **Injeção por construtor com property promotion** (`protected XService $x`). Sem `__construct` vazio.
- **`tenant()`** é o helper usado para obter o inquilino atual e repassar ao service. Não use `tenancy()->tenant` nem resolva à mão.
- Ações de escrita (`store`, `update`, `destroy`, ações customizadas) ficam dentro de **`try / catch (\Throwable $th)`** com:
  - registro do erro conforme a **P-08**: para `QueryException`, só a classe e o SQLSTATE; para as demais, a mensagem; sempre com o ID do tenant, nunca com o payload nem com o objeto de exceção;
  - `redirect()->route(<rota da lista>)->with('success', '<Recurso> <ação> com sucesso!')` no caminho feliz;
  - `redirect()->back()->with('error', 'Erro ao <ação> <recurso>!')` no erro.
- **Exceções de domínio são capturadas ANTES do `\Throwable`** e viram `with('warning', $th->getMessage())` (ver `TenantClientController::destroy`).
- Métodos de leitura (`index`, `create`, `edit`, `show`) **não** usam try/catch — retornam `Inertia::render(...)` direto.
- O primeiro argumento do `Inertia::render` é o **caminho do arquivo Vue a partir de `resources/js/pages/`**, sem extensão: `'tenant/<modulo>/<recurso>/<acao>/<Acao>'`.
- Endpoints consumidos por JS (autocomplete, lookup) retornam `response()->json(...)`, com `catch` devolvendo `['message' => '…'], 500`.
- Chaves de flash existentes: **`success`, `error`, `warning`** (o layout as converte em toast). Não invente outras.

---

## 8. Rotas

- Rotas de inquilino: [routes/tenant.php](../routes/tenant.php), dentro do grupo com `web`, `InitializeTenancyByDomain`, `PreventAccessFromCentralDomains` e, para telas internas, `Authenticate::class`.
- Rotas centrais (cadastro do SaaS, webhooks): [routes/web.php](../routes/web.php).
- **Rotas declaradas uma a uma — o projeto não usa `Route::resource`.**
- Nome: `tenant.<modulo>.<recurso>.<acao>`, com ações `list`, `create`, `store`, `edit`, `update`, `destroy`, `show`, `toggle-active`.
- **Toda rota autenticada que opera recurso de módulo tem `->middleware('permission:<modulo>.<recurso>.<acao>')`** (**P-07**; as exceções estão lá). Para listar as rotas autenticadas sem permissão (só devem aparecer as exceções da P-07):

```bash
vendor/bin/sail php -r 'foreach (json_decode(shell_exec("php artisan route:list --json"), true) as $r) { $m = implode(" ", $r["middleware"] ?? []); if (str_starts_with($r["name"] ?? "", "tenant.") && str_contains($m, "Authenticate") && ! str_contains($m, "PermissionMiddleware")) echo $r["name"], PHP_EOL; }'
```
- Verbos: `GET` lista/formulários, `POST` store, `PUT` update, `DELETE` destroy, `PATCH` alterações pontuais (ex.: `toggle-active`).

```php
Route::get('/products/products/list', [TenantProductController::class, 'index'])
    ->name('tenant.products.products.list')
    ->middleware('permission:products.products.view');
Route::post('/products/products/store', [TenantProductController::class, 'store'])
    ->name('tenant.products.products.store')
    ->middleware('permission:products.products.create');
Route::put('/products/products/{id}', [TenantProductController::class, 'update'])
    ->name('tenant.products.products.update')
    ->middleware('permission:products.products.edit');
```

Confira o resultado com `vendor/bin/sail artisan route:list --name=tenant.<modulo>`.

---

## 9. Permissões, papéis e módulos

- RBAC via `spatie/laravel-permission`, com permissões **dentro do banco de cada tenant**.
- Nomenclatura: **`<modulo>.<recurso>.<acao>`** — ações `view`, `create`, `edit`, `delete`.
- **Permissões e módulos:** ver **P-05**.
- **O plano limita as permissões.** Cada tenant recebe só as permissões dos seus módulos ativos (`tenant_modules`). Como as rotas são protegidas apenas pelo middleware `permission:`, é esse filtro que faz o plano valer no backend. Por isso **nenhuma migration de tenant cria permissão "para todos"**: toda gravação passa pelo [TenantPermissionService](../app/Services/TenantPermissionService.php):
  - `forModules($modules)` / `forTenant($tenant)`: lista (deduplicada) das permissões do catálogo central liberadas pelos módulos. `forTenant()` usa a conexão central do `Tenant`, então funciona também dentro do contexto do tenant.
  - `apply($permissions)`: dentro do contexto do tenant, faz upsert dos cargos (`RolesEnum`) e das permissões e concede todas ao Administrador. Só adiciona, nunca remove.
  - `sync($tenant)`: `forTenant()` + `apply()` no banco do tenant, numa transação.
- **Quem usa o service:**
  - **Tenant novo:** o `TenantService` monta o payload com `forModules()` e o job `SeedTenantDatabaseJob` o grava com `apply()`. O payload existe porque, com fila síncrona, o pipeline de provisionamento roda antes de os módulos serem vinculados ao tenant.
  - **Tenants existentes:** `vendor/bin/sail artisan tenants:sync-permissions [--tenants=<id>]`, idempotente.
- **Toda permissão nova precisa de duas migrations de dados**, com o rótulo em pt-BR (ex.: `'Clientes (Visualizar)'`):
  1. **Central** (`database/migrations/central/`): insere a permissão no catálogo central (`permissions`, com `module_id` resolvido pelo `slug` do módulo) só se ela ainda não existir. Modelo: [2026_10_06_173656_seed_core_permissions.php](../database/migrations/central/2026_10_06_173656_seed_core_permissions.php).
  2. **Tenant** (`database/migrations/tenant/`): leva a permissão aos tenants já provisionados, respeitando o plano de cada um:

     ```php
     public function up(): void
     {
         if (! tenancy()->initialized) {
             return;
         }

         $tenantPermissionService = app(TenantPermissionService::class);
         $tenantPermissionService->apply($tenantPermissionService->forTenant(tenant()));
     }

     public function down(): void
     {
         Permission::whereIn('name', ['<modulo>.<recurso>.view', /* ... */])->delete();
         app(PermissionRegistrar::class)->forgetCachedPermissions();
     }
     ```

     A migration central roda antes da de tenant no deploy (`migrate` → `tenants:migrate`), então o catálogo já tem a permissão nova quando o tenant sincroniza.
- As migrations de dados precisam ser **idempotentes**: inserem só o que ainda não existe (módulo por `slug`, permissão por `name`), porque podem rodar em ambientes que já têm parte dos registros.
- O catálogo base (módulos core e todas as permissões existentes) vem de [2026_10_06_173654_seed_core_modules.php](../database/migrations/central/2026_10_06_173654_seed_core_modules.php) e [2026_10_06_173656_seed_core_permissions.php](../database/migrations/central/2026_10_06_173656_seed_core_permissions.php). Não edite essas migrations depois de aplicadas: permissão nova entra em uma migration nova.
- **Permissões padrão dos cargos** (Analista com todo o grupo `documents.*`; Cliente, Vendedor do imóvel e Parceiro com o acesso básico às propostas) ficam em `TenantPermissionService::applyRoleDefaults()`. Elas são aplicadas **só no provisionamento** (`SeedTenantDatabaseJob`), nunca no `sync`, para não desfazer o que o administrador ajustar na tela de cargos. Cargo novo com permissões padrão entra nesse método.
- Enums de apoio: `app/Enums/RolesEnum.php`, `PermissionsEnum.php`, `PermissionTypeDriveEnum.php`.
- Policy: quando o acesso depende do registro (**P-07**). Hoje existem `DrivePolicy`, `ProposalPolicy` e `FeeCalculationPolicy`.
- Módulos habilitáveis por plano são controlados pelas tabelas centrais (`modules`, `plan_modules`, `tenant_modules`) e chegam ao front em `tenant.hasModules`.

---

## 10. Frontend (Vue 3 + Inertia + Tailwind v4)

**Base visual (P-13):** tokens de `resources/css/app.css`; componentes `@/components/ui/*`; layouts `TenantLayout`, `TenantSidebar` e `AuthLayout`; `MoneyInput`; `@/lib/masks`; composables `usePermission`, `useTenant`, `useCepLookup` e `useContactLookup`.

### 10.1. Estrutura de pastas por recurso

```text
resources/js/pages/tenant/<modulo>/<recurso>/
├── components/<Recurso>Form.vue     # formulário compartilhado entre create e edit
├── create/Create.vue
├── edit/Edit.vue
└── list/
    ├── List.vue
    ├── columns.ts                   # ColumnDef[] do TanStack Table
    └── ActionDropdown.vue           # ações da linha + AlertDialog de exclusão
```

Alguns recursos também têm `show/Show.vue`. **Mantenha exatamente esses nomes**, pois o `Inertia::render` do controller aponta para eles.

### 10.2. Regras de página

- `<script setup lang="ts">` sempre.
- `defineOptions({ layout: TenantLayout })` em toda página de tenant (import de `@/layouts/tenant-layout/TenantLayout.vue`).
- `<Head title="…" />` em pt-BR.
- Props tipadas com `defineProps<{ … }>()`, usando as interfaces de `@/types`.
- Navegação e URLs: `route('tenant.…')` importado de `ziggy-js` + `<Link>` do `@inertiajs/vue3`. **Nunca escreva URL literal.**
- Formulários: `useForm` do Inertia; o componente `<Recurso>Form.vue` recebe `form` por prop e emite `submit`.
- Erros de validação: `form.errors.<campo>` dentro de `<FieldError>`.
- Exibição condicional por permissão: `const { permissions } = usePermission()` + `v-if="permissions.includes('modulo.recurso.create')"`.
- Componentes de UI vêm de `@/components/ui/*` (shadcn-vue). **Antes de criar um componente, verifique se já existe.**
- Máscaras e dinheiro: `@/lib/masks` (`maskCurrency`, `parseCurrencyToCents`, `formatMoney`, `centsToMask`, `maskCPF`, `maskCNPJ`, `maskCEP`, `maskPhone`). Valores vão ao backend **em centavos**.
- Campo de valor em dinheiro: use o componente `MoneyInput` (`@/components/MoneyInput.vue`). O `v-model` é sempre em **centavos inteiros** (R$ 1.234,56 → `123456`), então o valor vai ao backend sem conversão. Use a prop `nullable` quando o campo for opcional. Para **exibir** um valor em centavos, use `formatMoney(cents)`.
- Listagens: escolha a tabela pelo volume esperado.
    - **`DataTable`:** paginação no cliente. Serve para listas pequenas, carregadas inteiras.
    - **`ServerDataTable`:** paginação, busca e filtro de excluídos no servidor, com a mesma aparência. Use em listas que crescem sem limite (cadastros com soft delete, propostas, orçamentos). Ambos são exportados de `@/components/ui/data-table`.
- Contrato do `ServerDataTable`:
    - **Backend:** o service devolve um `LengthAwarePaginator` com `->paginate(...)->withQueryString()`. O controller valida os filtros com um Form Request (ex.: `IndexCatalogRequest`) e envia à página o paginator e `filters` (`$request->validated()`).
    - **Frontend:** `<ServerDataTable :columns :paginator :filters :url="route('tenant.…list')" />`. `extraParams` leva filtros adicionais da página (ex.: status), `with-trashed-filter` liga ou desliga o filtro "Mostrar excluídos", e o slot `toolbar` recebe controles extras. Os tipos são `Paginated<T>` e `CatalogFilters`, de `@/types`. Exemplo: [banks/list/List.vue](../resources/js/pages/tenant/documents/banks/list/List.vue).
- Composables disponíveis: `usePermission`, `useTenant`, `useCepLookup`, `useContactLookup`.
- Estilização só com utilitários Tailwind e tokens do tema (`text-foreground`, `border-border`, `bg-card`). Sem CSS solto. Consulte o guia de arquitetura do modo escuro em [docs/arquitetura/dark_mode_guide.md](arquitetura/dark_mode_guide.md).

### 10.3. Tipos TypeScript

Um arquivo por entidade em `resources/js/types/<area>/<Entidade>.ts`, sempre reexportado no barrel [resources/js/types/index.ts](../resources/js/types/index.ts). Consuma via `import { Product } from "@/types";`.

### 10.4. Menu lateral

Item novo entra no array `navMain` de [resources/js/layouts/tenant-layout/TenantLayout.vue](../resources/js/layouts/tenant-layout/TenantLayout.vue), com `title`, `url`, `permission` (e `module` no grupo). A `TenantSidebar` filtra automaticamente por módulo contratado e permissão do usuário.

### 10.5. Feedback ao usuário

Flash do backend (`success` / `error` / `warning`) já é convertido em toast pelo `TenantLayout`. Não crie sistema de notificação paralelo.

---

## 11. Testes (Pest 4)

Padrão do projeto: **testar o Service diretamente**, não a rota HTTP. Arquivos em `tests/Feature/<Recurso>ServiceTest.php`.

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

Helpers globais definidos em [tests/Pest.php](../tests/Pest.php) — use-os, não recrie:

| Helper | Quando usar |
|:--|:--|
| `sharedTenant()` | Padrão. Tenant migrado uma vez por suíte, teste isolado em transação. |
| `createTenant()` | Só para testar **provisionamento** (cria e migra banco de verdade — lento). |
| `makeTenant()` | Só tabelas centrais, sem criar banco do tenant. |
| `createFinancialEntry()` | Criar lançamento que bloqueia exclusão de papel. |
| `formRequest(Classe::class, $dados)` | Validar Form Request sem rota HTTP. |

**Seleção de testes:** cada tarefa roda só os arquivos de teste afetados. A suíte completa (cerca de 9 min) é obrigatória antes de integrar na `main` (**P-09**) e roda também quando o usuário pedir.

Regras:

- **Toda asserção sobre dado do tenant vai dentro de `$this->tenant->run(fn () => …)`.**
- Descrições dos testes em **pt-BR**, descrevendo o comportamento.
- Use factories existentes (`Contact`, `Address`, `Client`, `User`); crie factory nova só se o model novo precisar.
- Não apague testes sem aprovação.

Execução:

```bash
vendor/bin/sail artisan test --compact --filter=ClientService
vendor/bin/sail artisan test --compact tests/Feature/ClientServiceTest.php
```

---

## 12. Comandos de referência

```bash
# Geração de arquivos (sempre --no-interaction)
vendor/bin/sail artisan make:controller TenantXController --no-interaction
vendor/bin/sail artisan make:request StoreXRequest --no-interaction
vendor/bin/sail artisan make:model X --no-interaction
vendor/bin/sail artisan make:class Services/XService --no-interaction
vendor/bin/sail artisan make:test --pest XServiceTest --no-interaction

# Banco
vendor/bin/sail artisan migrate --path=database/migrations/central   # central (inclui módulos, permissões e planos)
vendor/bin/sail artisan tenants:migrate                             # tenants
vendor/bin/sail artisan tenants:sync-permissions                    # tenants: cargos e permissões conforme o plano
composer tenants-migrate-fresh-seed                                 # dev: recria os tenants locais com dados fictícios

# Qualidade
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail artisan test --compact

# Front
vendor/bin/sail npm run dev
vendor/bin/sail npm run build
```

> Se o usuário relatar que a mudança de front “não apareceu”, pergunte se `npm run dev` / `npm run build` está rodando. Erro `Unable to locate file in Vite manifest` = build ausente.
>
> Mudanças em `config/` só valem após `vendor/bin/sail artisan config:clear` em ambientes com cache de configuração.

---

## 13. Divergências conhecidas (não replique cegamente)

As violações dos princípios da constituição estão em [`specs/violacoes-conhecidas.md`](../specs/violacoes-conhecidas.md). Esta seção lista só desvios de estilo.

Ao usar arquivos existentes como molde, esteja ciente de que **nem tudo no repositório é exemplar**. Não copie estes pontos:

- Código comentado deixado no corpo do método (ex.: paginação comentada em `ProductService::findAll()`).
- Parâmetros recebidos e não usados (ex.: `array $data` em `ClientService::store()`), mantidos por compatibilidade de assinatura.
- `use DB;` importando o alias global em vez de `Illuminate\Support\Facades\DB` — **use o namespace completo em código novo**.
- Blocos longos de cálculo de parcelas duplicados entre `create()` e `update()` no financeiro.
- `console.log` em handlers de UI (ex.: ação "Visualizar" em alguns `ActionDropdown.vue`).

Se identificar um desses padrões atrapalhando a tarefa, **aponte ao usuário** em vez de refatorar por conta própria — refatoração fora de escopo não entra junto com a feature.

---

## 14. Quando o padrão não cobre o caso

1. Procure um caso análogo em outro módulo (drive e financeiro têm padrões mais ricos: transações, exceções de domínio, policies, logs de auditoria).
2. Consulte a documentação oficial da versão instalada (`search-docs` do Boost / Context7). **Não deduza assinatura de método de memória.**
3. Continue tudo o que não depende da dúvida e **pergunte ao usuário** apenas o que muda materialmente o resultado.
4. Registre a decisão na resposta ao usuário — não em novos arquivos de documentação (que só são criados sob pedido).

---

## 15. SDD por feature

Regra: **D-05**. Procedimento para criar, revisar, implementar e concluir uma feature.

1. **Quando:** toda mudança que a D-05 cobre cujo primeiro commit tem data igual ou posterior à data "Ratificada em" do cabeçalho da constituição.
2. **Pasta:** `specs/NNN-nome/`. `NNN` é o maior número existente em `specs/` mais 1, começando em `001`, atribuído ao criar a pasta, no início do `egm-draft`. `nome` é kebab-case, em pt-BR, com até 4 palavras. Se duas branches pegarem o mesmo número, quem integra por último renumera a própria pasta e a linha do `specs/README.md` antes do merge; as mensagens de commit antigas ficam como estão.
3. **Conteúdo:** `design.md` (a fonte), `reviews/` e os derivados `spec.md`, `plan.md` e `tasks.md`. Como os skills gravam em outros lugares, o agente:
   - ao fim do `egm-brainstorm`, move o `concepts_brief.md` para `specs/NNN-nome/`;
   - no `egm-draft`, na pergunta "Where should the design be saved?", informa o diretório `specs/NNN-nome/` e, ao final, renomeia o arquivo gerado para `design.md`;
   - ao fim do `egm-draft`, apaga o `design_draft.md` (cópia de trabalho) antes do commit `specs/NNN-nome: Rascunho`;
   - ao fim de cada `egm-review` ou `egm-review-loop`, move os relatórios que o skill grava no diretório de trabalho (`zoom_out_report.md`, `comprehension_report.md`, `critic_report.md`, `intermediate_zoom_out_report.md`, `implementation_readiness_report.md`, `consolidated_review_report.md`, `goldfish_review_report.md`) para `specs/NNN-nome/reviews/`, com o prefixo da rodada (`r1-`, `r2-`…) para não apagar os anteriores.

   Os skills do EGM têm `disable-model-invocation`: o agente pede ao usuário que rode o comando (no Claude Code, `/egm:egm-draft` etc.) e segue o skill quando ele for chamado.
4. **Derivação**, feita pelo agente que conduz o `egm-review`, ao fim do estágio 9, no commit que marca a feature como Aprovada:
   - `spec.md` ← Visão geral, O problema, Metas, Não-metas, Jornadas críticas e Questões em aberto;
   - `plan.md` ← Plano técnico, Alternativas, Riscos, Requisitos não-funcionais e a seção "Afastamentos de convenção" do design. O `design.md` DEVE ter essa seção, dentro do Plano técnico, escrita no `egm-draft` e revisada no `egm-review` (com "Nenhum" quando não houver);
   - `tasks.md` ← Plano de implementação detalhado, com cada arquivo como um item `- [ ]` e a verificação no fim.

   Os derivados não são editados à mão, com uma exceção: marcar `- [x]` no `tasks.md`. Para mudar conteúdo, muda-se o `design.md` e regeram-se os três; ao regerar o `tasks.md`, o agente mantém marcados só os itens cujo texto não mudou. Em divergência entre o `design.md` e um derivado, vale o `design.md`.
5. **Status em `specs/README.md`**, mudado pelo agente no commit que muda o estado: Rascunho (durante o `egm-draft`), Em revisão (durante o `egm-review`), Aprovada, Em implementação, Concluída ou Abandonada. A mensagem do commit que muda o status é `specs/NNN-nome: <Status>`.
   - **Aprovada** exige o `egm-review` com todos os estágios (5 a 9), no modo interativo ou no consolidado, sem Blocker ou Major aberto. "Aberto" é o achado que não foi corrigido nem contestado com aceite do usuário; um achado aceito no limite de rodadas só deixa de ser aberto se entrar na seção de Riscos do `design.md` como risco aceito.
   - Qualquer mudança no `design.md` depois de Aprovada volta o status para Em revisão. As seções alteradas passam por um `egm-review-loop` antes de a feature voltar a Aprovada. Enquanto estiver Em revisão, NÃO DEVE haver commit de código da feature.
   - **Abandonada** é marcada pelo responsável técnico. É histórico, como Concluída, e os afastamentos dela perdem efeito.
6. **Afastamentos:** só na seção "Afastamentos de convenção" do `design.md` (copiada para o `plan.md`), com a seção destas convenções e o motivo. Valem se a feature chegou a Aprovada sem achado aberto sobre eles. Nunca afastam regra que só aponta para um princípio da constituição (ex.: regras 3 a 6, 8, 9, 11 e 12 da §1).
7. **Implementação:** a mensagem de cada commit de código começa com `specs/NNN-nome: ` (ex.: `specs/004-webhook-banco: valida assinatura`), e o commit marca os itens do `tasks.md` concluídos.
8. **Conclusão:** rodar o `egm-drift-report` sobre `specs/NNN-nome/` e sobre cada `docs/modulos/<modulo>/` que a feature tocou; corrigir, no commit `specs/NNN-nome: Concluída`, as divergências (no código ou em `docs/`); levar para estas convenções ou para `docs/` o que deve continuar valendo; marcar Concluída. A pasta passa a ser histórico.
