# CLAUDE.md — Sistema PNET v2

Este arquivo orienta o Claude Code (e outros agentes) a trabalhar no repositório **`sistema-pnet-v2`**.
**Responda sempre em português do Brasil (pt-BR).**

> Todos os comandos rodam na raiz de `sistema-pnet-v2`, sempre via Sail (`vendor/bin/sail …`).

## SDD (`specs/`) — leia SEMPRE antes de planejar ou mudar código

No início de toda sessão e antes de qualquer tarefa, leia na ordem:

1. [specs/constitution.md](specs/constitution.md): princípios inegociáveis, nomes com sufixo de tipo, tenancy, dados e Definition of Done.
2. [specs/spec.md](specs/spec.md): o que o sistema faz. Módulos, RF, RN, CA, as 4 mudanças do PRD v2 e as decisões registradas.
3. [specs/plan.md](specs/plan.md): como construir. Arquitetura, recursos-espelho, mapeamento v1→v2, design das mudanças e fases.
4. [specs/tasks.md](specs/tasks.md): o backlog por fase, com dependências, skill responsável e status (☐/◐/☑).
5. [specs/implementation.md](specs/implementation.md): o fluxo por tarefa, o mapa de skills, os gates e os comandos.

A hierarquia é `constitution` > `spec` > `plan` > `tasks` > `implementation`. Se o código divergir do SDD, o
código é o estado real: aponte a divergência e pergunte se o documento deve ser atualizado. Os arquivos de
`specs/` só são alterados mediante pedido explícito, e sempre em pt-BR.

## Regras do PRD v2 (resumo)

- **CRM:** só frontend (dados mock). **Não** implemente backend de CRM até ele ser especificado no spec.
- **Sufixo de tipo em toda classe:** `ClientService`, `UserTypeEnum`, `StoreClientRequest`, `ClientPolicy`, `SendEmailDispatchJob`, `AutoBlockClientsCommand`, `CpfRule`, `ClientServiceTest`. Os controllers mantêm o prefixo `Tenant` (`TenantClientController`). Models ficam sem sufixo.
- **Estilo das telas:** é o mesmo da v1. **Não mude nada.**
- **Mudanças de regra** (detalhes no spec §3):
  - MUD-01: proposta do CRM → proposta de documentações. Contrato pendente, com interino de pré-preenchimento.
  - MUD-02: a proposta distribui emolumentos, taxas, ITBI, lançamentos manuais e serviços em contas a pagar e a receber.
  - MUD-03: o orçamento da calculadora entra no funil do CRM (`QuoteCreatedEvent`). O listener fica pendente.
  - MUD-04: "Billable Service" removido. Orçamento e proposta usam o catálogo `services`.
- **Na dúvida, pare e pergunte.**

## Mapa de skills

As skills ficam em `.claude/skills/<nome>/SKILL.md`. Este mapa é **idêntico** ao do `specs/implementation.md` §2.

| Situação / tipo de tarefa | Skill principal | Skills de apoio | Revisão | Testes |
| --- | --- | --- | --- | --- |
| Construir backend novo: migration, model, service, action, request, controller, rota, permissão, job, mail, command | `laravel-development-senior` | `laravel-best-practices`, `laravel-permission-development`, `pest-testing`, `fortify-development` (auth) | `laravel-code-review-senior` | O próprio BE escreve; o `qa-senior` faz o aceite |
| Construir ou ajustar frontend: páginas, componentes, tipos TS, sidebar, CRM | `inertia-vue-frontend-senior` | `inertia-vue-development`, `tailwindcss-development` | `laravel-code-review-senior` (contrato e permissões) + checagem visual | `qa-senior` (type-check e checagem visual) |
| Corrigir bug, refatorar, otimizar ou limpar código já feito na v2 | `laravel-refactoring-senior` | `laravel-best-practices`, `pest-testing` | `laravel-code-review-senior` (**gate obrigatório** dentro da skill) | Teste que reproduz o bug, escrito pela própria skill |
| Revisar, auditar ou analisar código, sem mudar nada | `laravel-code-review-senior` | `laravel-best-practices` | — | — |
| Plano de testes, regressão da v1, isolamento entre tenants, aceite de fase, relatório de QA | `qa-senior` | `pest-testing` | `laravel-code-review-senior` (qualidade dos testes) | — |

**Regras do mapa:**

- Na dúvida entre construir e corrigir: se o código **ainda não existe** na v2, use `laravel-development-senior`. Se **já existe** e precisa mudar, use `laravel-refactoring-senior`.
- Quem quer **saber** o que está errado usa a revisão. Quem quer o problema **corrigido** usa a refatoração.
- O backend **não edita** `resources/js/`. Ele entrega o **contrato** (seção 4 do `implementation.md`) e o frontend implementa.
- O frontend **não edita** PHP. Se faltar uma prop ou rota, ele devolve a tarefa ao backend.
- Toda tarefa BE ou FE passa pelo `laravel-code-review-senior` antes de ser fechada.

## Stack

PHP 8.5 · Laravel 13 · stancl/tenancy v3 (database-per-tenant) · spatie/laravel-permission · Fortify v1 ·
Inertia v2 + Vue 3 (TS) · Ziggy v2 · Tailwind v4 + shadcn-vue · Pest 4 · Sail (MySQL 8.4, Redis, MinIO, Mailpit) ·
Laravel Boost (MCP).

## Comandos essenciais

```bash
vendor/bin/sail up -d
vendor/bin/sail artisan migrate --path=database/migrations/central   # banco central
vendor/bin/sail artisan tenants:migrate                              # tenants (nunca "migrate" puro p/ tenant)
vendor/bin/sail artisan tenants:sync-permissions
vendor/bin/sail artisan test --compact tests/Feature/<Arquivo>Test.php
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail npm run type-check
vendor/bin/sail npm run build    # ou npm run dev / composer run dev
```

## Laravel Boost (MCP)

- Use as ferramentas do Boost em vez de supor:
  - `search-docs`: a doc da versão instalada, **antes** de usar uma API;
  - `database-schema` e `database-query`: o banco real;
  - `last-error`, `read-log-entries` e `browser-logs`: erros;
  - `get-absolute-url`: URLs.
- Gere arquivos com `vendor/bin/sail artisan make:… --no-interaction` e renomeie para o sufixo de tipo.
- Não crie scripts de verificação nem use o tinker quando um teste cobre o caso.
- O `boost:install` acrescenta abaixo deste arquivo as diretrizes geradas pelo Boost e instala as skills de apoio.

## RTK (Rust Token Killer)

Prefixe os comandos de shell com `rtk` quando ele estiver instalado (ex.: `rtk git status`, `rtk vendor/bin/sail artisan test`).
O prefixo é seguro: sem filtro dedicado, o comando passa sem alteração. `rtk gain` mostra a economia.
