---
name: laravel-development-senior
description: >
  Constrói o BACKEND Laravel do Sistema PNET a partir de uma task já aprovada. Acione para escrever
  código PHP novo: "implementar", "implementar a task", "criar controller", "criar service",
  "criar action", "criar migration", "criar model", "criar form request", "nova rota",
  "nova permissão", "criar job", "criar mail", "criar command", "adicionar campo";
  "implement", "build the endpoint", "create controller", "create migration", "add route".
  Escopo: migrations, models, enums, services, actions, requests, controllers, rotas, permissões,
  policies, jobs, mails, commands e os testes Pest da task. Não edita resources/js/: entrega o
  contrato para a `frontend-senior`. Para mudar código que já existe, use `laravel-code-refactoring`;
  para só avaliar código, use `laravel-code-review`.
---

# Backend Laravel — Sistema PNET

## Papel no fluxo EGM

**CONSTRUTORA.** Roda na sessão Elephant, a que tem o contexto da feature.

| Etapa | Uso |
| --- | --- |
| `egm-brainstorm`, `egm-draft` | Só quando o usuário pedir: fornece conhecimento do backend (recurso-espelho, camadas, tabelas, permissões) para a seção em curso. Não gera código, não cria arquivo, não pula a pausa da seção. Quem escreve o `design_draft.md` é o `egm-draft`. |
| `egm-review`, `egm-review-loop` | Não atua. |
| `egm-resolve` | Propõe a correção técnica de um achado; o `design.md` só muda com a aprovação do usuário, dentro do `egm-resolve`. |
| Implementação | Uso principal: implementa os itens do `tasks.md` de uma feature Aprovada. |

> Se você estiver atuando como revisor Goldfish do EGM (subagente de egm-review ou
> egm-review-loop), NÃO aplique esta skill. Avalie apenas o documento e as fontes
> referenciadas por ele.

Nunca escreva em `concepts_brief.md`, `design_draft.md` ou `*_report.md`. Os derivados
`spec.md`, `plan.md` e `tasks.md` não se editam à mão; a única exceção é marcar `- [x]` no `tasks.md`.

## 1. A spec aprovada é a fonte da verdade

1. Em `specs/README.md`, a feature precisa estar **Aprovada** ou **Em implementação**. Se estiver em
   Rascunho ou Em revisão, não escreva código (D-05). Se a mudança não tem pasta, confira se cai numa
   exceção da D-05; se não cair, pare e diga ao usuário que a feature precisa passar pelo EGM.
2. Leia `specs/NNN-nome/design.md` (a fonte) e o `tasks.md`. Identifique os itens `- [ ]` da tarefa.
   Implemente **só** esses itens: nada de escopo extra, nem "aproveitando".
3. A seção "Afastamentos de convenção" do `design.md` vale só para o código desta feature.
4. **Conflito** entre esta skill, as convenções ou o código e a spec aprovada: PARE. Explique o
   conflito (item da spec × regra) e sugira corrigi-lo via `egm-resolve`; se a feature já está
   Aprovada, a mudança no `design.md` a volta para Em revisão e passa por `egm-review-loop`
   (convenções §15.5). Nunca desvie da spec em silêncio. Se a spec contraria a constituição, a
   constituição vence (D-01) e a spec precisa ser corrigida.

## 2. Antes de codar

- Siga o checklist das convenções §0 e a anatomia da §2. Abra o recurso-espelho e copie o padrão.
- Antes de usar um arquivo como molde, confira `specs/violacoes-conhecidas.md` e a §13: aquele código
  pode ser o exemplo do que **não** copiar (ex.: `Log::` com `getMessage()`, V8).
- Confirme o estado real pelo Laravel Boost: `database-schema` antes de migration ou model,
  `search-docs` antes de usar API do Laravel 13 ou do stancl/tenancy, `route:list` antes de criar rota.

## 3. Regras do projeto que mais quebram

Cada linha aponta a fonte; leia lá o texto completo.

| Ponto | Regra | Fonte |
| --- | --- | --- |
| Tenant | Service que escreve recebe o `Tenant`: `$tenant->run(fn () => DB::transaction(...))`, nessa ordem. Controller passa `tenant()`. | P-01, conv. §5 |
| Efeitos | Apagar/mover arquivo, mail, job: só depois do commit, com `afterCommit()`. Falha de disco pós-commit: `warning` com ID do tenant e caminho, sem desfazer o sucesso. | P-03 |
| Dinheiro | Inteiro em centavos em todas as camadas; percentual não é dinheiro. | P-04 |
| Permissão | Migration central (catálogo) + migration tenant via `TenantPermissionService`, filtrada pelo plano. Nunca seeder, nunca "para todos". Rota com `permission:<modulo>.<recurso>.<acao>` compatível com a operação. | P-05, P-07, conv. §8–§9 |
| Migration | Pasta `central/` ou `tenant/`. Migration já aplicada não se edita. | P-06, conv. §3 |
| Log | Registra ação, ID do tenant e ID do registro. Nunca payload, `getMessage()` de `QueryException` ou a exceção inteira. | P-08, conv. §7 |
| Nomes | Sufixo de tipo da §4a; controller de tenant com prefixo `Tenant`; model sem sufixo; identificador em inglês, texto ao usuário em pt-BR. | P-11, P-12 |
| Stack | Sem dependência nova nem pasta-base nova sem aprovação. Tudo via Sail. | P-10 |

## 4. Testes da task

- Padrão da §11: teste do **Service** (e do Form Request via `formRequest()`), não da rota HTTP, com
  `sharedTenant()` e asserções dentro de `$this->tenant->run()`. Use os helpers de `tests/Pest.php`.
- Cada regra de negócio da task tem teste, incluindo o caminho negado e os valores em centavos.
- Se o `qa-runner` já escreveu os testes da task (TDD), use-os: faça-os passar sem alterar as
  asserções. Se um teste contrariar a spec, pare e reporte; não ajuste o teste.
- Rode **só os arquivos de teste afetados**, em background (o ambiente é lento). A suíte completa só
  com pedido do usuário. Nunca declare verde um teste que não rodou.

## 5. Contrato para o frontend

Ao terminar uma task que alimenta uma tela, entregue no reporte:

```markdown
### Contrato — <recurso>
- Página Inertia: `tenant/<modulo>/<recurso>/<acao>/<Acao>`
- Rotas: nome → método/URI → permissão
- Props: { nome: Tipo } (dinheiro em centavos; enums como { value, label }[])
- Payload e erros: campos/tipos; erros em form.errors.<campo>
- Flash: success | error | warning
- Tipos TS: resources/js/types/<area>/<Entidade>.ts
```

O contrato tem de bater com o que o `design.md` descreve. Se a spec não define algo de que a tela
precisa, é conflito (§1.4).

## 6. Gates antes de dizer "pronto"

1. Testes afetados verdes; `vendor/bin/sail bin pint --dirty --format agent`; `route:list` filtrado.
2. **Revisão em contexto zerado:** chame o subagente `laravel-code-reviewer` passando só a pasta da
   feature e o diff (nunca o histórico da conversa). Corrija todo Blocker e Major e rode de novo os
   testes afetados.
3. Marque `- [x]` nos itens concluídos do `tasks.md`.
4. Commit só se o usuário pedir, com a mensagem começando por `specs/NNN-nome: ` (conv. §15.7).
5. Reporte: itens da task feitos, testes rodados e resultado, o que não rodou, contrato, achados da
   revisão.

## 7. Não faça

- Editar `resources/js/`, `specs/` ou `docs/` (D-02).
- Implementar o que não está no `tasks.md`, ou "corrigir" a spec no código.
- Copiar um padrão listado em `specs/violacoes-conhecidas.md`.
- Escrever código durante `egm-brainstorm`, `egm-draft`, `egm-review` ou `egm-review-loop`.
