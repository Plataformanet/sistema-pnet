# Implementation — Sistema PNET

> **O que é:** o guia de execução para os agentes e desenvolvedores que vão evoluir o Sistema PNET. Define:
>
> - o fluxo de cada tarefa do [tasks.md](tasks.md);
> - **qual skill usar em cada caso**;
> - os gates de qualidade;
> - os comandos.
>
> Obedece à [constitution.md](constitution.md). **Todos os comandos rodam na raiz de `sistema-pnet`.**

---

## 1. Ordem de leitura antes de qualquer tarefa

1. [constitution.md](constitution.md): os princípios e a nomenclatura.
2. As seções do [spec.md](spec.md) citadas na tarefa (RF, RN, CA).
3. As seções do [plan.md](plan.md) do módulo e o recurso-espelho ([plan §3](plan.md#3-recursos-espelho-o-molde-de-cada-tipo-de-feature)).
4. A linha da tarefa no [tasks.md](tasks.md): dependências e skill.
5. A **skill** da tarefa (seção 2), carregada **antes** de escrever código.

---

## 2. Mapa de skills

As skills ficam em `.claude/skills/<nome>/SKILL.md`. O mesmo mapa está no `CLAUDE.md` e no `AGENTS.md`. O `.agents/skills/` é cópia idêntica do `.claude/skills/`.

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

**Mapa por fase (resumo do [tasks.md](tasks.md)):**

| Fase | Skill principal | Apoio |
| --- | --- | --- |
| R1 Renomeações | `laravel-refactoring-senior` | CR, QA (aceite com a suíte completa) |
| R2 Padronizações de dados | `laravel-refactoring-senior` | BE (permissões), FE (sidebar), QA |
| R3 MUD-04 | `laravel-development-senior` (migração) + `laravel-refactoring-senior` (código atual) | FE (`ServicePicker`), QA |
| R4 MUD-02 | `laravel-development-senior` | RF (fornecedor opcional em pagar, ligar nos services atuais), FE (formulário de pagar, colunas), QA |
| R5 MUD-03 / MUD-01 | `laravel-development-senior` | FE (botão do CRM, prefill), QA |
| R6 Correções | `laravel-refactoring-senior` | BE (dashboard, diálogo de contrato, exportação de listas em PDF), FE (limpeza, widgets, diálogo de contrato, botão "Exportar lista"), QA |
| R7 Fechamento | `laravel-code-review-senior` + `qa-senior` | RF (correções), BE (atualizar `docs/`) |

---

## 3. Fluxo de uma tarefa

```text
1. Ler (seção 1) → carregar a skill da tarefa
2. Conferir o estado real com o Boost: database-schema, route:list, last-error
3. Planejar a fatia (arquivos por camada, seguindo o recurso-espelho)
4. Implementar (BE: migration → model → service/action → request → controller → rota → permissão)
5. Testes Pest da tarefa → rodar só os arquivos afetados (nunca a suíte completa sem pedido)
6. vendor/bin/sail bin pint --dirty --format agent   (PHP)
   vendor/bin/sail npm run type-check               (TS/Vue)
7. Code review (laravel-code-review-senior) → corrigir 🔴/🟠
8. Conferir o CA do spec e a Definition of Done (constitution §14)
9. Reportar (seção 7) e marcar a tarefa como ☑ na coluna Status do tasks.md
   (◐ quando começar)
```

**Paradas obrigatórias (P-15):**

- quando o spec não cobre um caso;
- quando duas regras conflitam;
- quando a tarefa exigiria uma dependência nova;
- quando seria preciso mudar o visual de uma tela;
- quando o código atual e o spec divergem.

Nesses casos, pare e pergunte ao usuário. Não decida sozinho.

---

## 4. Contrato backend → frontend (entregue ao fim de cada tarefa BE)

```markdown
### Contrato — <recurso>
- Página Inertia: `tenant/<modulo>/<recurso>/<acao>/<Acao>`
- Rotas (nome → método/URI → permissão):
  - `tenant.<modulo>.<recurso>.list` → GET /… → `<modulo>.<recurso>.view`
- Props: `{ <nome>: <Tipo TS> }` (dinheiro em centavos, enums como `{ value, label }[]`)
- Payload dos formulários: campos e tipos (centavos inteiros, datas ISO)
- Erros de validação: chaves de `form.errors`
- Flash: success | error | warning (mensagens em pt-BR)
- Tipos TS a criar ou atualizar em `resources/js/types/<area>/<Entidade>.ts`
```

---

## 5. Gates de qualidade

| Gate | Comando ou verificação | Quando |
| --- | --- | --- |
| Testes da tarefa (V-05) | `vendor/bin/sail artisan test --compact tests/Feature/<Arquivo>Test.php`, **só os arquivos afetados** | Toda tarefa BE e RF |
| Estilo PHP | `vendor/bin/sail bin pint --dirty --format agent` | Toda mudança em PHP |
| Tipos do front | `vendor/bin/sail npm run type-check` | Toda mudança em TS ou Vue |
| Build | `vendor/bin/sail npm run build` | Fim de toda tarefa FE |
| Rotas | `vendor/bin/sail artisan route:list --name=tenant.<modulo>` | Tarefa com rota nova |
| Nomes | Nenhuma classe nova sem sufixo de tipo (constitution §6) | Review |
| Tenancy | Todo acesso a dado de tenant dentro de `$tenant->run()`; transação dentro do `run` | Review |
| Dinheiro | Inteiros em centavos de ponta a ponta | Review e QA |
| Visual | Telas tocadas sem mudança visual, em light/dark e desktop/mobile | Tarefa FE e R7 |
| Code review | Sem achados 🔴/🟠 pendentes | Toda tarefa |
| Regressão da fase | Suíte completa verde (cerca de 9 min), **só com o pedido do usuário** | Aceite QA da fase |
| Migrations | `migrate --path=database/migrations/central` + `tenants:migrate` aplicados sem erro em todos os tenants | Tarefa com migration |

---

## 6. Comandos de referência

```bash
# Ambiente
vendor/bin/sail up -d
vendor/bin/sail npm run dev            # ou: vendor/bin/sail composer run dev

# Geração (sempre --no-interaction; mover a migration para central/ ou tenant/)
vendor/bin/sail artisan make:migration create_x_table --no-interaction
vendor/bin/sail artisan make:model X --factory --no-interaction
vendor/bin/sail artisan make:class Services/XService --no-interaction
vendor/bin/sail artisan make:request StoreXRequest --no-interaction
vendor/bin/sail artisan make:controller TenantXController --no-interaction
vendor/bin/sail artisan make:enum XEnum --no-interaction
vendor/bin/sail artisan make:job XJob --no-interaction
vendor/bin/sail artisan make:mail XMail --no-interaction
vendor/bin/sail artisan make:command XCommand --no-interaction
vendor/bin/sail artisan make:rule XRule --no-interaction
vendor/bin/sail artisan make:policy XPolicy --model=X --no-interaction
vendor/bin/sail artisan make:event XEvent --no-interaction
vendor/bin/sail artisan make:test --pest XServiceTest --no-interaction

# Banco
vendor/bin/sail artisan migrate --path=database/migrations/central
vendor/bin/sail artisan tenants:migrate
vendor/bin/sail artisan tenants:sync-permissions [--tenants=<id>]

# Qualidade
vendor/bin/sail artisan test --compact [--filter=X | tests/Feature/XTest.php]
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail npm run type-check
vendor/bin/sail npm run build

# Diagnóstico
vendor/bin/sail artisan route:list --name=tenant.documents
vendor/bin/sail artisan config:clear      # depois de mudar config/ em ambiente com cache
```

**Ferramentas do Laravel Boost (MCP):**

| Ferramenta | Uso |
| --- | --- |
| `search-docs` | Doc da versão instalada, antes de usar uma API |
| `database-schema` | Schema real |
| `database-query` | Consultas de leitura |
| `last-error` / `read-log-entries` | Erros de runtime |
| `browser-logs` | Erros do front |
| `get-absolute-url` | URL correta do projeto |

---

## 7. Formato do reporte de cada tarefa (pt-BR)

```markdown
## T-Fx-yy — <título>
**Skill:** <skill> · **Spec:** RN-…, CA-…
### O que foi feito
- <arquivos por camada>
### Contrato para o frontend (se BE)
<seção 4>
### Testes
- `<arquivo>`: N testes, todos verdes (comando executado)
### Review
- Resultado do laravel-code-review-senior (achados e correções)
### Pendências / perguntas
- <ou "nenhuma">
```

Nunca relatar um resultado de teste que não foi executado. Se algo foi pulado, diga isso.

---

## 8. Divergências e decisões

- Se o código divergir do spec, o código é o estado real: aponte a divergência e pergunte se o spec deve ser atualizado.
- Toda decisão nova do usuário é registrada no artefato correspondente:
  - na seção "Decisões registradas" desse artefato;
  - ou na seção 16 da constitution, quando for um princípio.
- Os documentos de `specs/` só são alterados mediante pedido explícito.

---

## Decisões registradas (perguntas do implementation)

Respondidas em **2026-10-26**; em todas foi adotada a recomendação. Na prática:

- O code review vale para **toda** tarefa.
- O status é marcado no `tasks.md`: ☐, depois ◐, depois ☑.
- As skills de apoio do Boost já estão instaladas no projeto e continuam como apoio.

| ID | Pergunta | Decisão (= recomendação) |
| --- | --- | --- |
| Q-I-01 | O code review é obrigatório em **toda** tarefa, ou só no fim de cada fase? | Em toda tarefa (é rápido e evita retrabalho) |
| Q-I-02 | Ao concluir uma tarefa, o agente marca `[x]` no `tasks.md` ou o status fica só no reporte? | Marcar no `tasks.md` (coluna de status) |
| Q-I-03 | As skills de apoio do Laravel Boost (`pest-testing`, `inertia-vue-development`, `tailwindcss-development`, `laravel-best-practices`, `laravel-permission-development`, `fortify-development`) continuam junto com as 5 skills? | Sim (já instaladas) |
