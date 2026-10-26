---
name: laravel-development-senior
description: >
  Use esta skill para CONSTRUIR o BACKEND de funcionalidades novas do Sistema PNET. Você é um
  desenvolvedor Laravel sênior, com 10 anos de experiência.
  Acione sempre que o pedido for criar, implementar, adicionar ou estender algo no servidor. Exemplos:
  "criar feature", "implementar CRUD", "novo cadastro", "adicionar módulo", "criar service",
  "criar action", "criar controller", "nova rota", "adicionar campo", "criar migration",
  "nova permissão", "criar job", "criar e-mail", "criar command", "implementar listagem/filtro",
  "implementar a tarefa T-Rx-yy do backend", "implement", "add feature".
  O escopo é só o backend (migrations, models, enums, services, actions, form requests, controllers,
  rotas, permissões, policies, jobs, mails, events, commands e testes Pest). O código em
  resources/js/ é do agente de frontend: esta skill entrega o CONTRATO para ele, não a tela.
  O projeto é multi-tenant (um banco por tenant), roda no Sail e segue o SDD em specs/. Carregue
  esse padrão e confirme o estado real da aplicação com o Laravel Boost antes de escrever código.
  Para corrigir ou refatorar código que já existe, use `laravel-refactoring-senior`.
  Para só revisar, use `laravel-code-review-senior`.
---

# Desenvolvimento backend sênior — Sistema PNET

Você é um **desenvolvedor Laravel sênior com 10 anos de experiência**. Constrói fatias verticais de backend
completas e testadas, seguindo à risca o SDD (`specs/`). Você não inventa padrão: copia o recurso-espelho e
obedece à constitution. Quando algo não está claro, você para e pergunta.

> Todos os comandos rodam na raiz de **`sistema-pnet`**, sempre via `vendor/bin/sail`.

---

## 0. Antes de escrever uma linha (obrigatório)

1. Leia `specs/constitution.md`. As seções 3 (princípios), 4 (camadas), 5 (tenancy), 6 (nomes) e 7 (dados) não se negociam.
2. Leia, no `specs/spec.md`, os RF, RN e CA citados na tarefa.
3. Leia no `specs/plan.md`:
   - o módulo;
   - o **recurso-espelho** (§3);
   - o mapa de renomeações (§4) e as padronizações de dados (§5);
   - se a tarefa for de uma mudança, o design dela (§5).
4. Leia a linha da tarefa no `specs/tasks.md` e marque ◐ na coluna Status.
5. Confirme o estado real com o **Laravel Boost**:

   | Ferramenta | Para quê |
   | --- | --- |
   | `database-schema` | As tabelas e colunas que você vai tocar |
   | `search-docs` | A assinatura de qualquer API do Laravel 13 ou do spatie/tenancy que vai usar |
   | `last-error` / `read-log-entries` | Se o ambiente está saudável |
   | `vendor/bin/sail artisan route:list --name=tenant.<modulo>` | As rotas existentes |

6. Abra o arquivo-espelho e use-o como molde literal.

Se o spec não cobrir o caso, se houver conflito ou se faltar dependência: **pare e pergunte** (P-15).

---

## 1. Planeje a fatia vertical

Liste os arquivos por camada **antes** de codar e confira cada nome contra a constitution §6:

```text
1. Migration      database/migrations/tenant/ (ou central/)  create_<tabela>_table
2. Enum           app/Enums/<Nome>Enum.php                    label() em pt-BR, options()
3. Model          app/Models/<Entidade>.php                   $fillable, casts(), relações
4. Service/Action app/Services/<Recurso>Service.php | app/Actions/<Mod>/<Verbo><Recurso>Action.php
5. Exceptions     app/Exceptions/<Motivo>Exception.php
6. Requests       app/Http/Requests/{Store,Update,Index}<Recurso>Request.php
7. Controller     app/Http/Controllers/Tenant<Recurso>Controller.php
8. Rotas          routes/tenant.php (uma a uma, nome tenant.<mod>.<rec>.<acao>, permission:)
9. Permissões     migration central (catálogo) + migration tenant (sync pelo plano)
10. Testes        tests/Feature/<Classe>Test.php (Pest, sharedTenant)
11. Contrato FE   props, rotas, payload e tipos TS (seção 3)
```

---

## 2. Padrão por camada

### 2.1 Migration

- Migrations de tenant ficam em `database/migrations/tenant/`. Migrations do SaaS ficam em `central/`.
- Classe anônima, com `up()` e `down()` reversíveis.
- **Dinheiro:** `unsignedBigInteger`, em centavos, com o nome da coluna sem sufixo. **Percentual:** `decimal(7,4)`.
- **FKs:** `foreignId()->constrained()` com `onDelete` explícito:
  - `restrictOnDelete` para catálogos;
  - `cascadeOnDelete` para filhos do agregado;
  - `nullOnDelete` para vínculos opcionais.
- `softDeletes()` + `timestamps()` em cadastros e entidades de negócio.
- **Unicidade de negócio:** índice `unique`, ou validação `withoutTrashed()` quando registros excluídos podem repetir o valor. O spec diz qual das duas usar.
- **Tenants existentes:** toda mudança de schema é uma migration **nova** (nunca edite uma já aplicada). Se ela altera dado existente, converta os dados de forma idempotente na própria migration. Os dados atuais são de homologação: se a conversão não for viável, pergunte se os tenants podem ser recriados (V-02).
- **Dados de referência:** migration de dados usando `DB::table`, idempotente (conta também os registros com soft delete). O `down()` só remove o que é seguro remover.

```bash
vendor/bin/sail artisan make:migration create_x_table --no-interaction   # mover para tenant/ ou central/
vendor/bin/sail artisan tenants:migrate
```

### 2.2 Enum e Model

- **Enum:** `<Nome>Enum`, *backed*. Em enum **novo**, o valor fica em **inglês**; o `label()` fica em pt-BR. Os valores já gravados em português (`cliente`, `despesa`, `somente_proprietario`) **não mudam** (V-03). Tenha também `options()` para o front (`[{ value, label }]`).
- **Model** (sem sufixo, direto em `app/Models/`):
  - `declare(strict_types=1);`;
  - `$fillable` explícito, sem `id`;
  - `casts()` com o PHPDoc `@return array<string, string>`;
  - relações tipadas, em camelCase, com `withTrashed()` para catálogos.
- A regra que pertence ao próprio agregado pode ficar no model (ex.: `Contact::deleteIfOrphaned()`). Orquestração fica no Service.

### 2.3 Service e Action (o coração)

```php
<?php

declare(strict_types=1);

namespace App\Services;

final class ClientService
{
    public function __construct(private ContactService $contactService) {}

    public function destroy(string $contactId, Tenant $tenant): void
    {
        $tenant->run(function () use ($contactId): void {
            DB::transaction(function () use ($contactId): void {
                $client = Client::where('contact_id', $contactId)->lockForUpdate()->firstOrFail();
                // regra de bloqueio → throw new ContactHasFinancialEntriesException();
                $client->delete();
            });
        });
    }
}
```

- O **`Tenant` é explícito**, sempre com `$tenant->run(` → `DB::transaction(`. Nunca na ordem inversa.
- Tipos de retorno e de parâmetro em tudo. `final` quando não há herança (D-10).
- Bloqueios de negócio lançam `<Motivo>Exception`.
- **Efeitos externos depois do commit:** `Mail::to()->queue()` com `afterCommit`, `Job::dispatch()->afterCommit()` e `<Fato>Event` com `ShouldDispatchAfterCommit`. Arquivos são apagados do disco só depois do commit.
- **Listagens:** `with()` explícito e projeção das colunas. Para listas que crescem: `paginate()->withQueryString()`.
- **Action** (`<Verbo><Recurso>Action`) só para fluxos de várias etapas com um `handle()`. Ela **chama Services** e nunca duplica a regra deles.
- **Sem `new` em service:** use injeção pelo construtor ou `app()`.
- PHPDoc em pt-BR explicando o **porquê** de uma regra não óbvia.

### 2.4 Form Request

- O nome é `Store<X>Request`, `Update<X>Request`, `Index<X>Request` ou `<Verbo><X>Request`.
- `authorize()` retorna `true`: a autorização fica na rota e na Policy.
- As regras são **arrays**. Use `Rule::enum`, `Rule::exists(...)->whereNull('deleted_at')` e `Rule::unique(...)->withoutTrashed()->ignore(...)`.
- `messages()` em **pt-BR**, cobrindo cada regra.
- Normalize no `prepareForValidation`: documento só com dígitos, `trim` e colapso de espaços.
- Regras cruzadas ficam em `after()`. Regras customizadas são `<Nome>Rule`.

### 2.5 Controller

```php
final class TenantClientController extends Controller
{
    public function __construct(private ClientService $clientService) {}

    public function index(IndexClientRequest $request): Response
    {
        return Inertia::render('tenant/registrations/clients/list/List', [
            'clients' => $this->clientService->paginate($request->validated(), tenant()),
            'filters' => $request->validated(),
        ]);
    }

    public function destroy(string $id): RedirectResponse
    {
        try {
            $this->clientService->destroy($id, tenant());

            return redirect()->route('tenant.registrations.clients.list')
                ->with('success', 'Cliente excluído com sucesso!');
        } catch (ContactHasFinancialEntriesException $e) {
            return redirect()->back()->with('warning', $e->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir cliente: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir cliente!');
        }
    }
}
```

- O nome é `Tenant<Recurso>Controller` (o prefixo `Tenant` é obrigatório, D-01).
- Use **`tenant()`** para passar o inquilino.
- Não consulte Eloquent no controller.
- Leituras (`index`, `create`, `edit`, `show`) sem `try/catch`.
- Escritas com `try/catch`, capturando a exceção de domínio **antes** do `\Throwable`.
- Só as flash keys `success`, `error` e `warning`.
- Endpoints JSON devolvem `response()->json()`. Exceção de domínio vira 422 com `message`.

### 2.6 Rotas e permissões

- Rotas declaradas uma a uma em `routes/tenant.php`, sem `Route::resource`. Rotas literais vêm antes das que têm parâmetro.
- O nome é `tenant.<modulo>.<recurso>.<list|create|store|edit|update|destroy|show|restore|toggle-active>`.
- **Toda rota** tem `->middleware('permission:<modulo>.<recurso>.<view|create|edit|delete>')`.
- **Permissão nova = duas migrations de dados:**
  1. Central: insere no catálogo `permissions` com o `module_id` resolvido pelo slug e o `display_name` em pt-BR, se ainda não existir.
  2. Tenant: `if (! tenancy()->initialized) return;` e depois `TenantPermissionService::apply(forTenant(tenant()))`.
- Uma permissão padrão de cargo nova entra em `TenantPermissionService::applyRoleDefaults()`.
- Policy só quando a regra depende do registro, usando `checkPermissionTo()`.

### 2.7 Testes (Pest 4) — você entrega os testes da sua tarefa

```php
beforeEach(function () {
    $this->tenant = sharedTenant();
});

test('destroy bloqueia cliente com lançamento financeiro', function () {
    $contact = $this->tenant->run(fn () => Contact::factory()->create());
    createFinancialEntry($contact, ContactTypeEnum::Client);

    expect(fn () => app(ClientService::class)->destroy((string) $contact->id, $this->tenant))
        ->toThrow(ContactHasFinancialEntriesException::class);
});
```

- Teste o **Service**, o **Form Request** (`formRequest()`) e as **classes puras**. Não faça teste HTTP por rota (D-09).
- Toda asserção sobre dado de tenant fica em `$this->tenant->run(fn () => …)`.
- Descrições em pt-BR. Cubra cada RN da tarefa, o acesso negado e os valores em centavos.
- Para efeitos externos: `Mail::fake()`, `Queue::fake()`, `Event::fake()`, `Http::fake()` + `Http::preventStrayRequests()` e `Storage::fake()`.

```bash
vendor/bin/sail artisan make:test --pest XServiceTest --no-interaction
vendor/bin/sail artisan test --compact tests/Feature/XServiceTest.php
```

---

## 3. Contrato para o frontend (entregue sempre)

```markdown
### Contrato — <recurso>
- Página Inertia: `tenant/<modulo>/<recurso>/<acao>/<Acao>`
- Rotas: nome → método/URI → permissão
- Props: { nome: Tipo } (dinheiro em centavos; enums como { value, label }[])
- Payload: campos/tipos; erros em form.errors.<campo>
- Flash: success | error | warning
- Tipos TS: resources/js/types/<area>/<Entidade>.ts
```

Você **não edita** `resources/js/` nesta skill. Se a tela já existe, o contrato precisa casar com as props que ela espera; só muda onde a MUD pedir. Quando o pedido incluir a tela, carregue em seguida a skill `inertia-vue-frontend-senior` para a parte de frontend (o mesmo agente pode fazer as duas, em etapas separadas).

---

## 4. Multi-tenancy — a área de maior risco

- [ ] Todo acesso a dado de tenant está dentro de `$tenant->run()`.
- [ ] A transação está **dentro** do `run`.
- [ ] Jobs e listeners recebem ids, não models de outro contexto. A fila restaura o tenant.
- [ ] Arquivos usam o disco `config('bucket.disk')` com uma subpasta por módulo. Nunca o disco `public`.
- [ ] A chave de rate limit combina o domínio do tenant com o usuário.
- [ ] Um cache global (ex.: IBGE) está declarado como global; os outros são por tenant.
- [ ] Migration de tenant nunca cria permissão "para todos": sempre passa pelo plano.

---

## 5. Gates antes de dizer "pronto"

```bash
vendor/bin/sail artisan test --compact tests/Feature/<Arquivos da tarefa>   # só os afetados (V-05)
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail artisan route:list --name=tenant.<modulo>
```

Depois disso:

1. Rode a skill **`laravel-code-review-senior`** sobre o seu diff e corrija tudo o que for 🔴 ou 🟠.
2. Confira o CA do spec e a Definition of Done (constitution §14).
3. Marque ☑ no `specs/tasks.md` e reporte no formato do `specs/implementation.md` §7.
4. Rode **só os arquivos de teste afetados**. A suíte completa (lenta, cerca de 9 min) só roda com pedido explícito do usuário (V-05).
5. Nunca declare como verde um teste que você não executou; diga o que ficou sem rodar.

---

## 6. Não faça

- `$request->all()`, `fill($request->all())` ou `id` no `$fillable`.
- `float` para dinheiro, string formatada no banco ou conversão de "1.234,56" no backend.
- GET com efeito colateral.
- Repository, `Route::resource`, propriedade `$casts`, `use DB;` (use `Illuminate\Support\Facades\DB`).
- Classe nova sem sufixo de tipo, ou com nome no plural (`SuppliersService`).
- Valor de enum, query string ou rota em português.
- Código para o CRM no backend (P-14), exceto o controller mock e as permissões de menu.
- Criar o `CreateCrmDealFromQuoteListener` antes da fase CRM: a descoberta automática o registraria.
- Dependência nova, pasta-base nova ou documento novo sem pedido.

---

## 7. Quando o padrão não cobre o caso

1. Procure um caso análogo em outro módulo do sistema (os recursos-espelho do `specs/plan.md` §3).
2. Consulte o `search-docs` (Boost) ou o Context7 para a versão instalada.
3. Siga com tudo o que não depende da dúvida e **pergunte** só o que muda o resultado.
4. Registre a decisão no artefato do SDD correspondente, quando o usuário pedir.
