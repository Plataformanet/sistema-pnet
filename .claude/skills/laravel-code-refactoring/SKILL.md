---
name: laravel-code-refactoring
description: >
  Muda código Laravel que JÁ EXISTE no Sistema PNET: corrige bug, refatora, otimiza, renomeia,
  padroniza ou corrige violações conhecidas e achados de code review. Acione para: "refatorar este
  código", "corrigir este bug", "está dando erro", "não está funcionando", "otimizar esta query",
  "está lento", "remover N+1", "renomear a classe", "corrigir os achados do review", "corrigir a
  violação V3"; "refactor this", "fix this bug", "optimize this query", "fix review findings", ou
  quando um stack trace for colado. Para código novo, use `laravel-development-senior`; para só
  avaliar, `laravel-code-review`.
---

# Refatoração e correção — Sistema PNET

## Papel no fluxo EGM

**CONSTRUTORA.** Roda na sessão Elephant, depois de um `egm-bootstrap` ou `egm-drift-report`, de um
code review ou de um bug relatado.

| Etapa | Uso |
| --- | --- |
| `egm-brainstorm`, `egm-draft`, `egm-review`, `egm-review-loop` | Não atua e não gera código. |
| `egm-resolve` | Propõe a correção técnica de um achado; o `design.md` só muda com aprovação do usuário. |
| Implementação do refactor | Uso principal, com os pré-requisitos da §1. |

> Se você estiver atuando como revisor Goldfish do EGM (subagente de egm-review ou
> egm-review-loop), NÃO aplique esta skill. Avalie apenas o documento e as fontes
> referenciadas por ele.

Nunca escreva em `concepts_brief.md`, `design_draft.md` ou `*_report.md`; um relatório de drift é
entrada, não arquivo a editar.

## 1. Pré-requisito: o que autoriza mexer no código

| Tipo | Exige |
| --- | --- |
| Refatoração, otimização, renomeação, padronização | `specs/NNN-nome/` de refactor **Aprovada** em `specs/README.md`, com o `design.md` dizendo o que muda e o que fica igual. Sem isso, pare e peça ao usuário para rodar o fluxo EGM. |
| Bug que restaura comportamento já descrito em `docs/` ou num `spec.md` | Pode seguir sem pasta (D-05, exceção b). Cite no reporte o trecho que descreve o comportamento correto. |
| Bug cujo comportamento correto não está descrito | É mudança de comportamento: exige pasta Aprovada. Pare e explique. |
| Violação de `specs/violacoes-conhecidas.md` | Segue a regra do tipo (bug ou refactor). No mesmo commit, remova a entrada da lista (D-02, exceção a). |

Implemente **só** o que o `tasks.md` (ou a correção do bug) pede. Outro problema que você notar vira
"próximo passo"; não entra junto. Se a spec conflitar com a constituição ou com as convenções sem
afastamento declarado: PARE, explique e sugira `egm-resolve`.

## 2. Testes verdes antes e depois (obrigatório)

1. **Antes:** rode os testes que cobrem o código afetado e guarde a saída. Se algo não tem teste,
   escreva um teste de caracterização primeiro e rode-o verde. Bug: escreva o teste que reproduz e
   veja-o **falhar**.
2. **Depois:** os mesmos testes verdes, sem alterar asserções. Bug: o teste que falhava passa.
3. Performance: meça antes e depois (queries, `EXPLAIN`, tempo) e reporte os números.

Sem a rodada "antes", não mude o código. Nunca apague, pule ou enfraqueça um teste para passar.
Rode só os arquivos afetados, em background (ambiente lento); a suíte completa só com pedido.

## 3. Regras do projeto para refatorar

- **Contrato público congelado**, salvo se o `design.md` disser o contrário: assinaturas de Service e
  Action, caminho e props do `Inertia::render`, nomes de rota e de permissão, flash keys, schema.
- **Migration aplicada não se edita** (P-06): mudança de schema ou dado é migration nova, idempotente.
- **Tenant:** a refatoração não pode tirar acesso de dentro do contexto nem inverter
  `$tenant->run` → `DB::transaction` (P-01); mantenha o `lockForUpdate` onde ele existe.
- **Renomear classe (P-12, §4a):** rename de símbolo (Serena `rename_symbol`), renomear o arquivo e
  conferir com `grep -rw <NomeAntigo> app config routes database tests resources/views` que não sobrou
  referência, inclusive em string.
- Não use os pontos da §13 nem de `specs/violacoes-conhecidas.md` como molde.
- Backend apenas: se a mudança exigir ajuste em `resources/js/`, entregue o contrato à `frontend-senior`.

## 4. Gates antes de dizer "pronto"

1. Testes de antes e depois verdes; `vendor/bin/sail bin pint --dirty --format agent`.
2. **Revisão em contexto zerado:** chame o subagente `laravel-code-reviewer` com a pasta da feature
   (ou o trecho de `docs/` que descreve o bug) e o diff. Corrija Blocker e Major e rode os testes de novo.
3. Marque `- [x]` no `tasks.md`, quando houver. Commit só com pedido, prefixo `specs/NNN-nome: `.

Reporte nesta ordem: Resumo · Causa raiz (bug) · O que mudou · Prova (antes/depois) · Revisão ·
Próximos passos fora do escopo.

## 5. Não faça

- Mudar comportamento numa refatoração.
- Refatorar sem design aprovado, ou sem testes passando antes e depois.
- Refatoração oportunista junto com correção de bug.
- Editar `specs/` ou `docs/` (exceto a linha de `violacoes-conhecidas.md` da violação corrigida).
