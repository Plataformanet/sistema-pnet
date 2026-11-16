---
name: frontend-senior
description: >
  Constrói ou ajusta o FRONTEND do Sistema PNET (Vue 3 + Inertia v2 + Tailwind v4, e as views Blade
  de layout) a partir de uma task aprovada e do contrato do backend. Acione para escrever código de
  tela: "implementar a tela", "criar página Vue", "criar componente", "ajustar formulário",
  "nova coluna na lista", "ligar a página às props", "tipos TypeScript", "item no menu";
  "implement the page", "create Vue component", "wire the form", "add column".
  Escopo: resources/js/, resources/css/ e Blade de layout. Não edita PHP: consome o contrato da
  `laravel-development-senior`. Não muda o visual de tela existente sem pedido explícito (P-13).
---

# Frontend — Sistema PNET

## Papel no fluxo EGM

**CONSTRUTORA.** Roda na sessão Elephant.

| Etapa | Uso |
| --- | --- |
| `egm-draft` | Só quando o usuário pedir, e só na parte de interface (telas, componentes, tipos, menu) do Plano técnico e do Plano de implementação: fornece os arquivos Vue afetados e os componentes existentes a reusar. Não gera código, não cria arquivo, não pula a pausa da seção. |
| `egm-brainstorm`, `egm-review`, `egm-review-loop` | Não atua. |
| `egm-resolve` | Propõe a correção de um achado de interface; o `design.md` só muda com aprovação do usuário. |
| Implementação | Uso principal: implementa os itens de frontend do `tasks.md`. |

> Se você estiver atuando como revisor Goldfish do EGM (subagente de egm-review ou
> egm-review-loop), NÃO aplique esta skill. Avalie apenas o documento e as fontes
> referenciadas por ele.

Nunca escreva em `concepts_brief.md`, `design_draft.md` ou `*_report.md`; nos derivados da feature,
só marque `- [x]` no `tasks.md`.

## 1. A spec aprovada é a fonte da verdade

1. A feature precisa estar Aprovada ou Em implementação em `specs/README.md` (D-05).
2. Leia `specs/NNN-nome/design.md` e os itens de frontend do `tasks.md`. Implemente **só** o que a
   spec descreve: nenhuma tela, campo, coluna ou ajuste visual a mais.
3. Leia o **contrato** entregue pelo backend. Se faltar prop, rota ou campo, devolva ao backend; não
   edite PHP nem invente prop.
4. **Conflito** entre a spec e a base visual, as convenções ou o contrato: PARE, explique e sugira
   corrigir via `egm-resolve` (ou volta para Em revisão + `egm-review-loop`, se já Aprovada). Nunca
   resolva o conflito redesenhando a tela.

## 2. Regras do projeto

- **Tela existente é congelada (P-13):** cor, espaçamento, fonte, raio, ícone, rótulo e ordem dos
  campos não mudam sem pedido explícito, mesmo que pareçam melhoráveis.
- **Elemento novo:** a base visual da §10 e as classes e componentes da **tela vizinha do mesmo
  módulo**, que é o molde. Confira nos modos claro e escuro (`docs/arquitetura/dark_mode_guide.md`).
- **UI kit:** só o que existe em `@/components/ui/*`. Não existem `Dialog`, `Tabs`, `RadioGroup` nem
  `Command`; use os padrões atuais (`AlertDialog`/`Sheet`, abas com sublinhado, `Select` Sim/Não).
  Adicionar componente shadcn ou pacote npm exige aprovação (P-10).
- **Confirmação de exclusão:** não use `AlertDialogAction` com estado pendente limpo no fechamento.
  O reka-ui fecha antes do `@click` e a ação chega vazia (exclusão silenciosa). Reaproveite o
  `ConfirmDeleteDialog` ou emita a confirmação antes de fechar.
- **Permissão:** `usePermission` só esconde elementos; a autorização é a rota (P-07). Toda ação
  escondida precisa de rota protegida no contrato.
- **Dinheiro:** `MoneyInput` (v-model em centavos) e `formatMoney` (P-04, §10.2).
- **Enums** vêm do backend como `{ value, label }[]`; não duplique listas em TS.
- **Textos** em pt-BR (P-11). URLs só por `route()` (Ziggy).

## 3. Gates antes de dizer "pronto"

1. `vendor/bin/sail npm run type-check` e `vendor/bin/sail npm run build`.
2. Checagem visual das telas tocadas: modos claro e escuro; desktop (≥ 1280px) e mobile (≈ 390px);
   estados vazio, carregando, erro de validação e sucesso; botão some sem a permissão; console sem
   erro (`browser-logs` do Boost). Em tela existente, compare antes e depois.
3. **Revisão em contexto zerado:** chame o subagente `laravel-code-reviewer` com a pasta da feature e
   o diff (contrato, permissões e P-13). Corrija Blocker e Major.
4. Marque `- [x]` no `tasks.md`. Reporte as telas conferidas e o que não foi conferido.

Se a mudança "não aparece", pergunte se o `npm run dev` está rodando.

## 4. Não faça

- Editar PHP, rotas, migrations, `specs/` ou `docs/`.
- Redesenhar ou "melhorar" UI fora do que a spec descreve.
- Criar store global, sistema de toast paralelo ou CSS fora do Tailwind e dos tokens do tema.
- Escrever código durante `egm-brainstorm`, `egm-draft`, `egm-review` ou `egm-review-loop`.
