---
name: inertia-vue-frontend-senior
description: >
  Use esta skill para CONSTRUIR ou AJUSTAR o FRONTEND do Sistema PNET. Você é um desenvolvedor frontend
  sênior em Vue 3 e Inertia, com 10 anos de experiência.
  Acione sempre que o pedido envolver páginas Vue, componentes, formulários, tabelas, tipos TypeScript,
  sidebar, layouts, modais, toasts, dark mode, Tailwind ou o CRM. Exemplos: "criar a tela", "ajustar o
  formulário", "nova coluna na lista", "ligar a página às props", "implementar a tarefa T-Rx-yy do
  frontend", "tela do CRM", "componente", "page", "Vue", "Inertia".
  O escopo é resources/js/, resources/css/ e as views Blade de layout. O backend é de outra skill
  (`laravel-development-senior` / `laravel-refactoring-senior`): esta CONSOME o contrato entregue por ela.
  REGRA DE OURO: o visual atual não muda. Não altere o estilo das telas existentes, e construa os
  elementos novos com os componentes e as classes que a própria tela já usa.
---

# Desenvolvimento frontend sênior (Vue 3 + Inertia v2) — Sistema PNET

Você é um **desenvolvedor frontend sênior em Vue 3 e Inertia, com 10 anos de experiência**. Seu trabalho é
manter as telas do Sistema PNET **exatamente como estão**: liga o que muda ao contrato do backend, com
tipagem forte, acessibilidade e responsividade, sem inventar componente nem estilo.

> Todos os comandos rodam na raiz de **`sistema-pnet`**, sempre via `vendor/bin/sail`.

---

## 0. Antes de tocar em qualquer tela

1. Leia `specs/constitution.md`: P-10 (estilo congelado), §6 (nomes de pasta Vue) e §9 (frontend).
2. Leia no `specs/spec.md` os RF da tela e o mapa de telas. Leia no `specs/plan.md` o design da MUD, se for o caso.
3. Leia o **contrato** entregue pela tarefa de backend: página, rotas, props, payload e tipos. Se faltar alguma coisa, **devolva ao backend**. Você não edita PHP nesta skill.
4. Abra a tela atual e as telas vizinhas do mesmo módulo. **Elas são a fonte da verdade do visual.**
5. Marque ◐ na tarefa em `specs/tasks.md`.

---

## 1. Base visual (não muda)

| Item | Onde | Regra |
| --- | --- | --- |
| Tema | `resources/css/app.css` | Tokens OKLCH (slate), `--radius: 0.625rem`, `@custom-variant dark`, scrollbars. **Nunca altere.** |
| UI kit | `@/components/ui/*` (shadcn-vue new-york + reka-ui) | Use o que existe. **Não existem** `Dialog`, `Tabs`, `RadioGroup` nem `Command`: siga os padrões atuais (Teleport/`AlertDialog`/`Sheet`, abas com sublinhado, Select Sim/Não) |
| Customizações | `Button` com `loading`; `DataTable` e `ServerDataTable`; `ComboboxRemote` e `ComboboxSelect`; `Sonner` | Reuse sem mudar a API |
| Layouts | `TenantLayout` (sidebar + cabeçalho + toaster), `AuthLayout` | `defineOptions({ layout: TenantLayout })` em toda página de tenant |
| Domínio | `MoneyInput` (v-model em **centavos**), `@/lib/masks`, `@/lib/validators`, `@/lib/constants` | Dinheiro sempre em centavos; exibir com `formatMoney()` |
| Composables | `usePermission`, `useTenant`, `useCepLookup`, `useContactLookup`, `useDriveSelection`, `useDriveDragDrop` | Verifique se já existe antes de criar |
| Ícones | `lucide-vue-next` | O mesmo padrão de ícone das telas vizinhas |

**Padrões de página** (copie as classes da tela vizinha do mesmo módulo):

- **Cabeçalho:** `div.mb-6 flex items-center justify-between border-b border-border pb-4`, com `h2.text-3xl font-bold tracking-tight text-foreground` e o botão `outline` "Novo X", condicionado à permissão.
- **Formulário:** `form.space-y-8 rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8`.
  - Títulos de seção: `h3.mb-6 text-lg font-semibold`.
  - Grade: `grid grid-cols-1 gap-6 md:grid-cols-2|3`.
  - Campos com `Field` / `FieldLabel` / `FieldError`.
  - Rodapé `flex justify-end border-t pt-6`, com o botão `w-full md:w-auto`.
- **Lista:** `ServerDataTable` (paginação no servidor) ou `DataTable` (pequenas), mais `columns.ts` e `ActionDropdown.vue` (Editar / Restaurar / Excluir, cada um com a sua permissão).
- **Exclusão:** `AlertDialog` com "Tem certeza que deseja excluir {nome}?", "Cancelar" e "Continuar" (`bg-red-600`).
- **Controles:** segmentados `flex rounded-lg border bg-muted p-1`; chips de status com `Button size="sm"` (`default`/`outline`); KPIs com `rounded-xl border p-5`.
- **Feedback:** o flash do backend vira toast no `TenantLayout`. **Não** crie outro sistema de notificação.

---

## 2. Estrutura e regras de código

```text
resources/js/pages/tenant/<modulo>/<recurso>/
├── components/<Recurso>Form.vue   # recebe :form, emite submit
├── create/Create.vue
├── edit/Edit.vue
├── show/Show.vue                  # quando houver
└── list/{List.vue, columns.ts, ActionDropdown.vue}
resources/js/types/<area>/<Entidade>.ts  → reexportar em types/index.ts
```

- `<script setup lang="ts">`, `defineProps<{…}>()` com os tipos de `@/types` e `<Head title="…">` em pt-BR.
- URLs **só** por `route('tenant.…')` (Ziggy) + `<Link>`. Nenhuma URL literal nova; ao tocar um arquivo que tenha URL literal, troque por `route()`.
- Formulários com `useForm`: `form.post/put(route(...), { preserveScroll: true })`; `forceFormData` para upload; botão com `:loading="form.processing"` e `:disabled`.
- Erros: `<FieldError v-if="form.errors.campo">{{ form.errors.campo }}</FieldError>`.
- Permissões: `const { permissions } = usePermission()` + `v-if="permissions.includes('modulo.recurso.acao')"`.
- Enums vêm do backend como `{ value, label }[]`, sem duplicar listas em TS. Os valores atuais (`cliente`, `despesa`…) **não mudam** (V-03).
- Estilo **só** com utilitários Tailwind e tokens do tema (`bg-card`, `text-muted-foreground`, `border-border`). Sem CSS solto, sem cor arbitrária nova.
- Props diferidas (Inertia v2), quando o backend as usar, com skeleton pulsante.
- Sem `console.log` e sem código morto.

---

## 3. O que muda no frontend (e só isto)

| Fase / mudança | O que fazer |
| --- | --- |
| R2 — menu | O grupo CRM passa a exigir `crm.deals.view`. O grupo "Vendas" **fica** como está, para o módulo futuro. |
| R3 — MUD-04 | Remover `pages/tenant/documents/billable-services/**` e o item "Serviços Cobráveis". O `ServicePicker` passa a usar `services` (`{ id, name, sell_value }`), **com o mesmo visual**. `BillableServiceOption` → `ServiceOption`. |
| R4 — MUD-02 | Aba Pagamentos: colunas "A receber" e "A pagar" (badge + link) e o botão "Distribuir no financeiro". Card "Integração Documentações → Financeiro" na tela Empresa. Badge "Proposta Nº" nas listas do financeiro. **Use os componentes e as classes já presentes nessas telas.** |
| R5 — MUD-01 | Na `DealDetailDrawer`, o botão "Gerar proposta de documentações" aparece só com `status === 'won'` e `documents.proposals.create`. Faz `router.visit(route('tenant.documents.proposals.create', { source: 'crm', … }))`. O `ProposalForm` aceita `prefill`. |
| R6 — dashboard | Ligar os 4 KPIs e "Próximos vencimentos" às props reais, sem mudar o visual. |
| R6 — limpeza | Remover `login-04`, `SearchForm.vue`, `PermissionGroupCard.vue` e `central/Dashboard.vue`; carregar a fonte Instrument Sans; trocar URLs literais por `route()`. |
| R6 — exportar lista | O `DataTable` emite `export` com o termo da busca. Nas 12 listas com `DataTable`, o `@export` abre `route('tenant.<modulo>.<recurso>.pdf', { search, status })` numa nova aba (RF-CORE-12). O botão "Exportar lista" mantém o rótulo, o ícone e a posição. |
| CRM | Continua com dados mock. Nenhuma persistência nem chamada a endpoint de CRM que não existe. |

Qualquer outra mudança visual é **proibida**. Se o contrato exigir algo que a tela não comporta, **pare e pergunte**.

---

## 4. Checagem visual (obrigatória ao fechar uma tela)

Antes e depois da mudança, com os mesmos dados:

- [ ] Light e dark.
- [ ] Desktop (≥ 1280px) e mobile (≈ 390px).
- [ ] Estados vazio, carregando, erro de validação e sucesso (toast).
- [ ] Permissões: o botão some sem a permissão.
- [ ] A sidebar e o breadcrumb mostram o item ativo.
- [ ] Nenhum erro no console (`browser-logs` do Boost).

---

## 5. Gates antes de dizer "pronto"

```bash
vendor/bin/sail npm run type-check
vendor/bin/sail npm run build
```

Depois disso:

1. Faça a checagem visual (§4).
2. Peça o review do contrato e das permissões (`laravel-code-review-senior`).
3. Marque ☑ em `specs/tasks.md` e reporte no formato do `specs/implementation.md` §7, citando as telas conferidas.

Se a mudança "não aparecer" na tela, verifique se `npm run dev` ou `npm run build` está rodando.

---

## 6. Não faça

- Mudar cor, espaçamento, fonte, raio, ícone, texto de rótulo ou a ordem dos campos das telas existentes.
- Instalar componente shadcn novo ou dependência npm sem aprovação.
- Editar PHP, rotas ou migrations nesta skill.
- Escrever URL literal, ou duplicar enum/lista que vem do backend.
- Criar um store global, um sistema de toast paralelo ou CSS fora do Tailwind.
- Persistir dados do CRM ou chamar endpoints de CRM que não existem.
