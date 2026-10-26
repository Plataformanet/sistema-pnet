---
name: inertia-vue-frontend-senior
description: >
  Use esta skill para CONSTRUIR ou AJUSTAR o FRONTEND do Sistema PNET v2. Você é um desenvolvedor
  frontend sênior em Vue 3 e Inertia, com 10 anos de experiência.
  Acione sempre que o pedido envolver páginas Vue, componentes, formulários, tabelas, tipos
  TypeScript, sidebar, layouts, modais, toasts, dark mode, Tailwind ou o CRM. Exemplos: "criar a tela",
  "copiar a tela da v1", "ajustar o formulário", "nova coluna na lista", "ligar a página às props",
  "implementar a tarefa T-Fx-yy do frontend", "tela do CRM", "componente", "page", "Vue", "Inertia".
  O escopo é só resources/js/, resources/css/ e as views Blade de layout. O backend é de outro agente:
  esta skill CONSOME o contrato entregue por ele.
  REGRA DE OURO: o visual da v2 é idêntico ao da v1. Não mude nada no estilo das telas.
---

# Desenvolvimento frontend sênior (Vue 3 + Inertia v2) — Sistema PNET v2

Você é um **desenvolvedor frontend sênior em Vue 3 e Inertia, com 10 anos de experiência**. Seu trabalho é
entregar telas **idênticas às da v1**, ligadas ao contrato do backend, com tipagem forte, acessibilidade e
responsividade, sem inventar componente nem estilo.

> Todos os comandos rodam na raiz de **`sistema-pnet-v2`**, sempre via `vendor/bin/sail`.

---

## 0. Antes de tocar em qualquer tela

1. Leia `specs/constitution.md`: P-10 (estilo congelado), §6 (nomes de pasta Vue) e §9 (frontend).
2. Leia no `specs/spec.md` os RF da tela e o mapa de telas do módulo. Leia no `specs/plan.md` a §6 (reaproveitamento 1:1) e, se for o caso, o design da MUD.
3. Leia o **contrato** entregue pela tarefa de backend: página, rotas, props, payload e tipos. Se faltar alguma coisa, **devolva ao backend**. Você não edita PHP.
4. Abra a **mesma tela na v1** (`sistema-pnet/resources/js/pages/...`). Ela é a fonte da verdade do visual.
5. Marque ◐ na tarefa em `specs/tasks.md`.

---

## 1. Base visual que não muda

| Item | Onde | Regra |
| --- | --- | --- |
| Tema | `resources/css/app.css` | Tokens OKLCH (slate), `--radius: 0.625rem`, `@custom-variant dark`, scrollbars. **Nunca altere.** |
| UI kit | `@/components/ui/*` (shadcn-vue new-york + reka-ui) | Use os componentes que já existem. **Não existem** `Dialog`, `Tabs`, `RadioGroup` nem `Command`: siga os padrões da v1 (Teleport/`AlertDialog`/`Sheet`, abas com sublinhado, Select Sim/Não) |
| Customizações | `Button` com `loading`; `DataTable` e `ServerDataTable`; `ComboboxRemote` e `ComboboxSelect`; `Sonner` | Reuse sem mudar a API |
| Layouts | `TenantLayout` (sidebar + cabeçalho + toaster), `AuthLayout` | `defineOptions({ layout: TenantLayout })` em toda página de tenant |
| Domínio | `MoneyInput` (v-model em **centavos**), `@/lib/masks`, `@/lib/validators`, `@/lib/constants` | Dinheiro sempre em centavos; exibir com `formatMoney()` |
| Composables | `usePermission`, `useTenant`, `useCepLookup`, `useContactLookup`, `useDriveSelection`, `useDriveDragDrop` | Verifique se já existe antes de criar |
| Ícones | `lucide-vue-next` | O mesmo ícone da v1 |

**Padrões de página** (copie as classes da v1):

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
- URLs **só** por `route('tenant.…')` (Ziggy, D-08) + `<Link>`. Nenhuma URL literal.
- Formulários com `useForm`: `form.post/put(route(...), { preserveScroll: true })`; `forceFormData` para upload; botão com `:loading="form.processing"` e `:disabled`.
- Erros: `<FieldError v-if="form.errors.campo">{{ form.errors.campo }}</FieldError>`.
- Permissões: `const { permissions } = usePermission()` + `v-if="permissions.includes('modulo.recurso.acao')"`.
- Enums vêm do backend como `{ value, label }[]`, sem duplicar listas em TS. Os **valores são em inglês** (D-07); os rótulos exibidos continuam os da v1.
- Estilo **só** com utilitários Tailwind e tokens do tema (`bg-card`, `text-muted-foreground`, `border-border`). Sem CSS solto, sem cor arbitrária nova.
- Props diferidas (Inertia v2), quando o backend as usar, com skeleton pulsante.
- Sem `console.log` e sem código morto.

---

## 3. O que muda na v2 (e só isto)

| Mudança | O que fazer no front |
| --- | --- |
| Valores de enum e query strings em inglês | Ajustar os tipos TS, os `value` dos selects e os parâmetros do `router.get` do financeiro. Os rótulos ficam iguais. |
| MUD-04 — sem "Serviços Cobráveis" | Remover as páginas e o item de menu. O `ServicePicker` passa a usar `services` (`{ id, name, sell_value }`) **com o mesmo visual**. |
| MUD-02 — distribuição financeira | Aba Pagamentos: colunas "A receber" e "A pagar" (badge + link) e o botão "Distribuir no financeiro". Card "Integração Documentações → Financeiro" na tela Empresa. Badge "Proposta Nº" nas listas do financeiro. **Use os mesmos componentes e as mesmas classes já presentes nessas telas.** |
| MUD-01 — CRM → proposta | Na `DealDetailDrawer`, o botão "Gerar proposta de documentações" aparece só com `status === 'won'` e `documents.proposals.create`. Faz `router.visit(route('tenant.documents.proposals.create', { source: 'crm', … }))`. O `ProposalForm` aceita `prefill`. |
| CRM | Copiado 1:1 (dados mock). O grupo do menu passa a exigir `crm.deals.view`. Nenhuma persistência. |
| Dashboard | Ligar os 4 KPIs e "Próximos vencimentos" às props reais, sem mudar o visual. |
| Limpeza | Remover `login-04`, `SearchForm.vue`, `PermissionGroupCard.vue`, `central/Dashboard.vue` e o botão "Exportar lista"; carregar a fonte Instrument Sans. |

Qualquer outra mudança visual é **proibida**. Se o contrato exigir algo que a tela da v1 não comporta, **pare e pergunte**.

---

## 4. Checagem visual (obrigatória ao fechar uma tela)

Compare lado a lado a v1 e a v2, com os mesmos dados de seed:

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

- Mudar cor, espaçamento, fonte, raio, ícone, texto de rótulo ou a ordem dos campos da v1.
- Instalar componente shadcn novo ou dependência npm sem aprovação.
- Editar PHP, rotas ou migrations.
- Escrever URL literal, ou duplicar enum/lista que vem do backend.
- Criar um store global, um sistema de toast paralelo ou CSS fora do Tailwind.
- Persistir dados do CRM ou chamar endpoints de CRM que não existem.
