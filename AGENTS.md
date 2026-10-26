# AGENTS.md

Este arquivo orienta agentes (Codex, Antigravity e outros) neste repositório. Responda sempre em português do Brasil.

## SDD (`specs/`) e documentação (`docs/`) — LEIA SEMPRE PRIMEIRO

**No início de toda sessão, antes de planejar ou mudar qualquer código, leia o SDD em [specs/](specs/).** O sistema evolui de forma incremental até o padrão definido ali (não há v2 — decisão V-01). Ordem de leitura:

1. [specs/constitution.md](specs/constitution.md) — princípios inegociáveis, **sufixo de tipo em toda classe**, tenancy, dados, testes e Definition of Done.
2. [specs/spec.md](specs/spec.md) — o que o sistema faz. Os itens **[alvo]** ainda não estão implementados.
3. [specs/plan.md](specs/plan.md) — como evoluir: recursos-espelho, mapa de renomeações, padronizações e design das 4 mudanças (MUD-01 a MUD-04).
4. [specs/tasks.md](specs/tasks.md) — backlog por fase (R0 a R7), com skill responsável e status (☐/◐/☑).
5. [specs/implementation.md](specs/implementation.md) — fluxo de cada tarefa, mapa de skills, gates e comandos.

**Hierarquia:** `constitution` > `spec` > `plan` > `tasks` > `implementation` > `docs/`.

- **`specs/` manda** no que fazer e como fazer.
- **[docs/](docs/)** descreve o **estado atual** do sistema e é atualizado ao fim de cada fase:
  - [docs/convencoes_de_desenvolvimento.md](docs/convencoes_de_desenvolvimento.md) — convenções atuais;
  - [docs/arquitetura/](docs/arquitetura/) — visão geral, arquitetura, dicionário de dados, rotas, dark mode;
  - [docs/modulos/](docs/modulos/) — documentação funcional por módulo.
- [docs/prd/](docs/prd/) e [docs/prd-sistema-pnet-v2.md](docs/prd-sistema-pnet-v2.md) são histórico de requisitos; as decisões deles já estão consolidadas no `specs/spec.md`.

Se o código divergir do SDD ou de `docs/`, o código é o estado real: aponte a divergência e pergunte se o documento deve ser atualizado. Arquivos de `specs/` e `docs/` só são criados ou alterados mediante pedido explícito.

**Todo arquivo em `specs/` ou `docs/` é escrito em português do Brasil (pt-BR)**: títulos, texto, tabelas e comentários. Só identificadores de código, comandos e caminhos ficam como estão.

### Regras do PRD de evolução (resumo)

- **CRM:** só frontend (dados mock). **Não** implemente backend de CRM até ele ser especificado no spec.
- **Sufixo de tipo em toda classe:** `ClientService`, `UserTypeEnum`, `StoreClientRequest`, `ClientPolicy`, `SendEmailDispatchJob`, `AutoBlockClientsCommand`, `CpfRule`, `ClientServiceTest`. Os controllers mantêm o prefixo `Tenant` (`TenantClientController`). Models ficam sem sufixo.
- **Estilo das telas:** não muda.
- **Mudanças de regra:**
  - **MUD-01:** CRM → proposta de documentações.
  - **MUD-02:** a proposta distribui os valores no financeiro.
  - **MUD-03:** o orçamento entra no funil do CRM.
  - **MUD-04:** "Billable Service" removido; o orçamento usa o catálogo `services`.
- **Testes:** cada tarefa roda **só os arquivos de teste afetados**. A suíte completa (lenta, cerca de 9 min) só roda com pedido explícito.
- **Na dúvida, pare e pergunte.**

<laravel-boost-guidelines>
=== .ai/rtk rules ===

<!-- rtk-instructions v2 -->

# RTK (Rust Token Killer) - Token-Optimized Commands

## Golden Rule

**Always prefix commands with `rtk`**. If RTK has a dedicated filter, it uses it. If not, it passes through unchanged. This means RTK is always safe to use.

**Important**: Even in command chains with `&&`, use `rtk`:
```bash

# ❌ Wrong

git add . && git commit -m "msg" && git push

# ✅ Correct

rtk git add . && rtk git commit -m "msg" && rtk git push
```

## RTK Commands by Workflow

### Build & Compile (80-90% savings)

```bash
rtk cargo build         # Cargo build output

rtk cargo check         # Cargo check output

rtk cargo clippy        # Clippy warnings grouped by file (80%)

rtk tsc                 # TypeScript errors grouped by file/code (83%)

rtk lint                # ESLint/Biome violations grouped (84%)

rtk prettier --check    # Files needing format only (70%)

rtk next build          # Next.js build with route metrics (87%)

```

### Test (60-99% savings)

```bash
rtk cargo test          # Cargo test failures only (90%)

rtk go test             # Go test failures only (90%)

rtk jest                # Jest failures only (99.5%)

rtk vitest              # Vitest failures only (99.5%)

rtk playwright test     # Playwright failures only (94%)

rtk pytest              # Python test failures only (90%)

rtk rake test           # Ruby test failures only (90%)

rtk rspec               # RSpec test failures only (60%)

rtk test <cmd>          # Generic test wrapper - failures only

```

### Git (59-80% savings)

```bash
rtk git status          # Compact status

rtk git log             # Compact log (works with all git flags)

rtk git diff            # Compact diff (80%)

rtk git show            # Compact show (80%)

rtk git add             # Ultra-compact confirmations (59%)

rtk git commit          # Ultra-compact confirmations (59%)

rtk git push            # Ultra-compact confirmations

rtk git pull            # Ultra-compact confirmations

rtk git branch          # Compact branch list

rtk git fetch           # Compact fetch

rtk git stash           # Compact stash

rtk git worktree        # Compact worktree

```

Note: Git passthrough works for ALL subcommands, even those not explicitly listed.

### GitHub (26-87% savings)

```bash
rtk gh pr view <num>    # Compact PR view (87%)

rtk gh pr checks        # Compact PR checks (79%)

rtk gh run list         # Compact workflow runs (82%)

rtk gh issue list       # Compact issue list (80%)

rtk gh api              # Compact API responses (26%)

```

### JavaScript/TypeScript Tooling (70-90% savings)

```bash
rtk pnpm list           # Compact dependency tree (70%)

rtk pnpm outdated       # Compact outdated packages (80%)

rtk pnpm install        # Compact install output (90%)

rtk npm run <script>    # Compact npm script output

rtk npx <cmd>           # Compact npx command output

rtk prisma              # Prisma without ASCII art (88%)

```

### Files & Search (60-75% savings)

```bash
rtk ls <path>           # Tree format, compact (65%)

rtk read <file>         # Code reading with filtering (60%)

rtk grep <pattern>      # Search grouped by file (75%). Format flags (-c, -l, -L, -o, -Z) run raw.

rtk find <pattern>      # Find grouped by directory (70%)

```

### Analysis & Debug (70-90% savings)

```bash
rtk err <cmd>           # Filter errors only from any command

rtk log <file>          # Deduplicated logs with counts

rtk json <file>         # JSON structure without values

rtk deps                # Dependency overview

rtk env                 # Environment variables compact

rtk summary <cmd>       # Smart summary of command output

rtk diff                # Ultra-compact diffs

```

### Infrastructure (85% savings)

```bash
rtk docker ps           # Compact container list

rtk docker images       # Compact image list

rtk docker logs <c>     # Deduplicated logs

rtk kubectl get         # Compact resource list

rtk kubectl logs        # Deduplicated pod logs

```

### Network (65-70% savings)

```bash
rtk curl <url>          # Compact HTTP responses (70%)

rtk wget <url>          # Compact download output (65%)

```

### Meta Commands

```bash
rtk gain                # View token savings statistics

rtk gain --history      # View command history with savings

rtk discover            # Analyze Claude Code sessions for missed RTK usage

rtk proxy <cmd>         # Run command without filtering (for debugging)

rtk init                # Add RTK instructions to CLAUDE.md

rtk init --global       # Add RTK to ~/.claude/CLAUDE.md

```

## Token Savings Overview

| Category | Commands | Typical Savings |
|----------|----------|-----------------|
| Tests | vitest, playwright, cargo test | 90-99% |
| Build | next, tsc, lint, prettier | 70-87% |
| Git | status, log, diff, add, commit | 59-80% |
| GitHub | gh pr, gh run, gh issue | 26-87% |
| Package Managers | pnpm, npm, npx | 70-90% |
| Files | ls, read, grep, find | 60-75% |
| Infrastructure | docker, kubectl | 85% |
| Network | curl, wget | 65-70% |

Overall average: **60-90% token reduction** on common development operations.
<!-- /rtk-instructions -->

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- tightenco/ziggy (ZIGGY) - v2
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA_VUE) - v2
- vue (VUE) - v3
- prettier (PRETTIER) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `vendor/bin/sail npm run build`, `vendor/bin/sail npm run dev`, or `vendor/bin/sail composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `vendor/bin/sail artisan route:list`). Use `vendor/bin/sail artisan list` to discover available commands and `vendor/bin/sail artisan [command] --help` to check parameters.
- Inspect routes with `vendor/bin/sail artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `vendor/bin/sail artisan config:show app.name`, `vendor/bin/sail artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `vendor/bin/sail artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `vendor/bin/sail artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== sail rules ===

# Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `vendor/bin/sail artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `vendor/bin/sail artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

=== laravel/v13 rules ===

# Laravel 13

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 13 Structure

- In Laravel 13, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 13 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test --format agent`, simply run `vendor/bin/sail bin pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `vendor/bin/sail artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `vendor/bin/sail artisan make:test --pest SomeFeatureTest` instead of `vendor/bin/sail artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `vendor/bin/sail artisan test --compact` or filter: `vendor/bin/sail artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>


## Mapa de skills

As skills ficam em `.claude/skills/<nome>/SKILL.md`. O `.agents/skills/` é uma cópia idêntica, para outros agentes. Este mapa é **idêntico** ao do `specs/implementation.md` §2. Carregue a skill **antes** de escrever código ou de revisar.

| Situação / tipo de tarefa | Skill principal | Skills de apoio | Revisão | Testes |
| --- | --- | --- | --- | --- |
| Construir backend novo: migration, model, service, action, request, controller, rota, permissão, job, mail, command | `laravel-development-senior` | `laravel-best-practices`, `laravel-permission-development`, `pest-testing`, `fortify-development` (auth) | `laravel-code-review-senior` | O próprio BE escreve; o `qa-senior` faz o aceite |
| Construir ou ajustar frontend: páginas, componentes, tipos TS, sidebar, CRM | `inertia-vue-frontend-senior` | `inertia-vue-development`, `tailwindcss-development` | `laravel-code-review-senior` (contrato e permissões) + checagem visual | `qa-senior` (type-check e checagem visual) |
| Corrigir bug, refatorar, otimizar, renomear, padronizar ou limpar código que já existe | `laravel-refactoring-senior` | `laravel-best-practices`, `pest-testing` | `laravel-code-review-senior` (**gate obrigatório** dentro da skill) | Teste que reproduz o bug, escrito pela própria skill |
| Revisar, auditar ou analisar código, sem mudar nada | `laravel-code-review-senior` | `laravel-best-practices` | — | — |
| Plano de testes, regressão, isolamento entre tenants, aceite de fase, relatório de QA | `qa-senior` | `pest-testing` | `laravel-code-review-senior` (qualidade dos testes) | — |

**Regras do mapa:**

- Na dúvida entre construir e corrigir: se o código **ainda não existe**, use `laravel-development-senior`. Se **já existe** e precisa mudar, use `laravel-refactoring-senior`.
- Quem quer **saber** o que está errado usa a revisão. Quem quer o problema **corrigido** usa a refatoração.
- O backend **não edita** `resources/js/`. Ele entrega o **contrato** (seção 4) e o frontend implementa.
- O frontend **não edita** PHP. Se faltar uma prop ou rota, ele devolve a tarefa ao backend.
- Toda tarefa BE ou FE passa pelo `laravel-code-review-senior` antes de ser fechada.

## Code Review

Para qualquer pedido de revisão, auditoria ou análise de qualidade ou performance em que o usuário quer **feedback**, e não mudança de código, **use a skill `laravel-code-review-senior`**. Isso vale mesmo para pedidos curtos, como "revise este código", "quais melhorias podem ser feitas aqui?", "existe algum n+1 aqui?" ou "isto está limpo?".

Pedidos para de fato refatorar, corrigir ou otimizar vão para a `laravel-refactoring-senior`, que roda essa revisão como gate final.

Antes de opinar, **fundamente a revisão no estado real da aplicação com as ferramentas MCP do Laravel Boost**, nunca suponha:

- Confirme as versões de PHP/Laravel, os pacotes e os models (Application Info).
- Verifique o schema e quais índices existem de fato antes de afirmar que uma query é lenta ou que falta um índice (Database Schema).
- Rode `EXPLAIN` ou queries para confirmar gargalos reais (Database Query).
- Consulte os logs e o último erro para ancorar achados de runtime (Read Log Entries / Last Error).
- Confirme na documentação oficial o idioma da versão instalada (Search Docs), em vez de inventar assinaturas de método.

Nenhuma afirmação de performance entra na revisão sem verificação. A revisão é entregue em pt-BR, ordenada por severidade, com `arquivo:linha`, a correção concreta em código (antes → depois) e os próximos passos priorizados.

## Instructions

- Always ask for permission before running git commands.
- Always ask for permission before running any command.
- Never make any changes without permission.
- Please always reply in Brazilian Portuguese.
