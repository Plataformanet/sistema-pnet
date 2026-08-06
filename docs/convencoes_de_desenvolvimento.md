# Convenções de Desenvolvimento — Guia Obrigatório para Modelos de IA

> **Público-alvo:** agentes de IA (Claude Code, Antigravity, Cursor, Copilot etc.) e desenvolvedores que vão criar ou alterar funcionalidades no **Sistema PNET**.
>
> **Regra número um:** este projeto já tem um padrão consolidado. **Nunca invente uma abordagem nova.** Antes de escrever qualquer linha, abra um recurso equivalente já existente (ex.: Produtos, Clientes, Contas a Pagar) e replique a estrutura dele.

---

## 0. Antes de começar (checklist de contexto)

Execute nesta ordem, sempre:

1. Ler [docs/arquitetura/project_overview.md](arquitetura/project_overview.md) e [docs/arquitetura/architecture_guide.md](arquitetura/architecture_guide.md).
2. Ler a documentação do módulo afetado em `docs/modulos/<modulo>/`.
3. Antes de mexer em banco ou rotas: [docs/arquitetura/database_dictionary.md](arquitetura/database_dictionary.md) e [docs/arquitetura/system_routes.md](arquitetura/system_routes.md).
4. Abrir o **recurso-espelho** mais parecido com o que será criado e usá-lo como molde literal.
5. Confirmar o estado real da aplicação com as ferramentas do **Laravel Boost** (schema, queries, logs, docs por versão) em vez de supor.

**A documentação não é a verdade final: o código é.** Se divergirem, aponte a divergência ao usuário e pergunte se o documento deve ser atualizado. Arquivos em `docs/` só são criados ou alterados **mediante pedido explícito**.

---

## 1. Princípios inegociáveis

| # | Regra | Motivo |
|:--|:--|:--|
| 1 | **Copie o padrão existente, não crie um novo.** | Consistência entre ~25 controllers e ~24 services. |
| 2 | **Não invente método, rota, coluna, permissão, componente ou prop.** Verifique se existe. | Alucinação quebra build e migrations. |
| 3 | **Não adicione dependências** (composer/npm) sem aprovação. | Stack fechada. |
| 4 | **Não crie pastas-base novas** em `app/`, `resources/js/` ou `docs/`. | Estrutura definida. |
| 5 | **Todo comando roda via Sail:** `vendor/bin/sail artisan …`, `vendor/bin/sail npm …`, `vendor/bin/sail composer …`. | Projeto Dockerizado. |
| 6 | **Toda mudança precisa de teste** (novo ou atualizado) e o teste precisa ser executado. | Regra do projeto. |
| 7 | **Rodar `vendor/bin/sail bin pint --dirty --format agent`** depois de mexer em PHP. | Estilo. |
| 8 | **Textos visíveis ao usuário e comentários/documentação em pt-BR.** Identificadores de código em inglês. | Padrão do repositório. |
| 9 | **Nunca vaze dados entre tenants:** todo acesso a dado de inquilino passa por `$tenant->run(...)`. | Isolamento database-per-tenant. |
| 10 | Se um módulo tem um padrão próprio (ex.: financeiro, drive), **siga o do módulo**, não o global. | Padrões locais prevalecem. |

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
7. Permissões         database/seeders/TenantPermissionSeeder.php
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
- **Valores monetários são armazenados como `integer` (centavos).**

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

## 5. Services — o coração da aplicação

**Toda a lógica de negócio e todo acesso a banco do tenant ficam em `app/Services/<Recurso>Service.php`.** Controller nunca consulta Eloquent diretamente (exceto lookups triviais já existentes no código legado).

### 5.1. Assinatura padrão

O `Tenant` é sempre parâmetro **explícito** (normalmente o último) e o corpo roda dentro de `$tenant->run(...)`:

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

- `authorize()` retorna `true` — **a autorização é feita pelo middleware `permission:` na rota**, não no request.
- Regras sempre em **formato de array** (`['required', 'string']`), nunca string com pipes.
- `messages()` **em pt-BR, cobrindo cada regra relevante** — é o que o usuário final vê.
- Regras customizadas vão para `app/Rules/` (ex.: `CpfRule`) e são referenciadas na lista.
- No controller, use sempre `$request->validated()` (ou `$request->validated('campo')`). Nunca `$request->all()`.

---

## 7. Controllers

Nome: `Tenant<Recurso>Controller` (recursos de inquilino). Autenticação central/tenant fica em `app/Http/Controllers/Auth/`.

[app/Http/Controllers/TenantProductController.php](../app/Http/Controllers/TenantProductController.php)

```php
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
            Log::error('Erro ao criar produto: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar produto!');
        }
    }
}
```

Regras obrigatórias:

- **Injeção por construtor com property promotion** (`protected XService $x`). Sem `__construct` vazio.
- **`tenant()`** é o helper usado para obter o inquilino atual e repassar ao service. Não use `tenancy()->tenant` nem resolva à mão.
- Ações de escrita (`store`, `update`, `destroy`, ações customizadas) ficam dentro de **`try / catch (\Throwable $th)`** com:
  - `Log::error('Erro ao <ação> <recurso>: '.$th->getMessage());`
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
- **Toda rota tem `->middleware('permission:<modulo>.<recurso>.<view|create|edit|delete>')`.**
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
- Toda permissão nova precisa ser registrada em [database/seeders/TenantPermissionSeeder.php](../database/seeders/TenantPermissionSeeder.php), no formato de arrays paralelos `name` / `display_name` (rótulo em pt-BR, ex.: `'Clientes (Visualizar)'`).
- Enums de apoio: `app/Enums/RolesEnum.php`, `PermissionsEnum.php`, `PermissionTypeDriveEnum.php`.
- Autorização por **Policy** só existe quando a regra é dinâmica por registro (hoje: `DrivePolicy`). Cadastros simples são protegidos apenas pelo middleware de permissão.
- Módulos habilitáveis por plano são controlados pelas tabelas centrais (`modules`, `plan_modules`, `tenant_modules`) e chegam ao front em `tenant.hasModules`.

---

## 10. Frontend (Vue 3 + Inertia + Tailwind v4)

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
- Máscaras e dinheiro: `@/lib/masks` (`maskCurrency`, `parseCurrencyToCents`, `maskCPF`, `maskCNPJ`, `maskCEP`, `maskPhone`). Valores vão ao backend **em centavos**.
- Composables disponíveis: `usePermission`, `useTenant`, `useCepLookup`, `useContactLookup`.
- Estilização só com utilitários Tailwind e tokens do tema (`text-foreground`, `border-border`, `bg-card`). Sem CSS solto.

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
vendor/bin/sail artisan migrate            # central
vendor/bin/sail artisan tenants:migrate    # tenants
vendor/bin/sail artisan db:seed --class=TenantPermissionSeeder

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
