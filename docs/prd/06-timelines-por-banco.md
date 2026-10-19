# PRD — Timelines por banco

> **Leia antes:** [`00-visao-geral.md`](00-visao-geral.md), [`01-cadastros.md`](01-cadastros.md) §A6.7 (catálogo `stages`) e [`02-propostas.md`](02-propostas.md) (timeline da proposta).

> Documento para o agente que vai **alterar o sistema já implementado** (PHP 8.5, Laravel 13, Vue 3 + Inertia 2, Tailwind v4 + shadcn-vue). Diferente dos PRDs 01–05, aqui não há porte do legado: a timeline de etapas já existe e funciona. Este PRD muda a regra de negócio — hoje há **uma única timeline global** com 8 etapas; passa a existir **uma timeline padrão do sistema** e, opcionalmente, **uma timeline personalizada por banco**.

## A0. Documentos relacionados e fronteiras

| Documento | Relação com este PRD |
|---|---|
| [`01-cadastros.md`](01-cadastros.md) | Dono do catálogo `stages` e do CRUD de etapas. **Este PRD revoga a decisão B-A3** ("a relação legada `Banco::etapas()` não é portada: as etapas são globais") e a regra de §A6.7 de que `order` é único entre todas as etapas ativas. |
| [`02-propostas.md`](02-propostas.md) | Dono de `proposal_stages` e de `ProposalTimelineService`. Este PRD muda **apenas** qual conjunto de etapas é copiado na instanciação. |
| [`04-protocolos-registro.md`](04-protocolos-registro.md) / [`05-relatorios.md`](05-relatorios.md) | **Sem mudança.** Leem a etapa via `proposal_stages.stage_id` (`shows_registry_protocol`, `Proposal::finalStage()`), que continua válido. |

O que **não muda** (não mexer):

- Schema de `proposal_stages` e o fluxo iniciar → concluir → avançar → finalizar.
- `CompleteProposalStageRequest` (obrigatoriedades lidas ao vivo da etapa).
- `ReportService` / `ReportZipBuilder` (`finalStage` = maior `position`).
- `RegistryProtocolService` e o painel da timeline.
- Command `proposals:alert-overdue-stages` / `ProposalTimelineService::overdueStages()`.
- Rotas e permissões `documents.stages.*` (não criar rota nem permissão nova).

## A1. Objetivo

Permitir que o administrador monte uma sequência de etapas específica para cada banco (ex.: o Itaú tem um fluxo diferente da Caixa) e que cada proposta nasça com a timeline do seu banco. Bancos sem timeline própria continuam usando a timeline padrão — que é exatamente a timeline única de hoje.

## A2. Regras de negócio

1. **Timeline padrão do sistema** = etapas com `stages.bank_id = NULL`. As 8 etapas atuais (Cadastro/Aprovação … Finalização) viram a timeline padrão **sem nenhuma alteração de dados**.
2. **Timeline do banco X** = etapas com `stages.bank_id = X`. Não existe entidade "timeline" separada: a timeline de um banco é o conjunto das suas etapas.
3. **Criar a timeline de um banco**: na tela de etapas, o administrador seleciona o banco (ex.: Itaú) e cadastra a primeira etapa. A timeline **nasce vazia** — não copia a padrão.
4. **Resolução na instanciação** (criação da proposta, conversão de orçamento, restauração do acompanhamento, troca de banco):
   - se o banco da proposta tem **pelo menos 1 etapa ativa** (não excluída), usa as etapas desse banco;
   - senão, usa a timeline padrão.
   - Consequência: um banco cujas etapas foram todas excluídas volta a usar a padrão automaticamente.
5. **Uma etapa pertence a uma única timeline.** `bank_id` é definido na criação e **não pode ser alterado** depois (nem pelo update).
6. **Ordem por timeline**: `order` é único entre as etapas **ativas da mesma timeline**. Itaú e padrão podem ter, cada um, uma etapa de ordem 1.
7. **Reordenar, restaurar e mover só afetam a timeline da própria etapa.** Nenhuma operação pode trocar ordem com etapa de outro banco.
8. **Snapshot preservado**: `proposal_stages` continua sendo uma cópia feita na instanciação. Criar, editar, reordenar ou excluir etapas de qualquer timeline **não altera** timelines já instanciadas.
9. **Troca de banco na edição da proposta**:
   - se o acompanhamento **não foi iniciado** (nenhuma `proposal_stage` com `started_at` ou `completed_at`), a timeline é apagada e recriada com a resolução do novo banco, na mesma transação do update;
   - se **já foi iniciado**, a timeline atual é mantida, sem erro. A ação "Restaurar acompanhamento" (que já recria a timeline) passa a aplicar a timeline do banco **atual** da proposta.
10. **Banco excluído** (soft delete): as etapas dele continuam no banco de dados; propostas antigas seguem exibindo-as (`ProposalStage::stage()` já usa `withTrashed`). O banco excluído some do seletor de timeline, exceto quando é o selecionado na URL.

## A3. Modelo de dados

### Decisão: coluna `bank_id` em `stages` (sem tabela nova)

Reaproveita todo o CRUD e o `StageService` existentes; "timeline vazia" e "banco sem timeline" são o mesmo estado, o que torna o fallback da regra A2.4 natural.

### Migration (tenant)

Criar com `vendor/bin/sail artisan make:migration add_bank_id_to_stages_table --path=database/migrations/tenant --no-interaction`:

```php
Schema::table('stages', function (Blueprint $table) {
    $table->foreignId('bank_id')->nullable()->after('id')->constrained('banks')->restrictOnDelete();
    $table->dropIndex(['order']);
    $table->index(['bank_id', 'order']);
});
```

- `down()`: remove o índice composto, recria `index('order')`, `dropConstrainedForeignId('bank_id')`.
- Registros existentes ficam com `bank_id = NULL` (timeline padrão). Não há backfill.
- A migration `2026_10_01_100009_seed_documents_catalogs.php` **não muda**: em tenants novos ela roda antes desta e as 8 etapas ficam com `bank_id = NULL`.

### Models

- `app/Models/Stage.php`
  - `bank_id` no `$fillable` e `'bank_id' => 'integer'` em `casts()`.
  - `bank(): BelongsTo` → `$this->belongsTo(Bank::class)->withTrashed()`.
  - `scopeForTimeline(Builder $query, ?int $bankId): Builder` → `whereNull('bank_id')` quando `null`, senão `where('bank_id', $bankId)`.
- `app/Models/Bank.php`: `stages(): HasMany`.
- `database/factories/StageFactory.php`: state `forBank(Bank $bank)`; o `definition()` continua com `bank_id` nulo (timeline padrão).

## A4. Backend — mudanças por arquivo

### `app/Services/StageService.php`

- **`findAll()`** (sobrescrever o de `CatalogService`): mesma lógica da base, aplicando `->forTimeline($filters['bank_id'] ?? null)`. A forma mais limpa é adicionar à `CatalogService` um hook `protected function scopeListing(Builder $query, array $filters): Builder` (default: retorna a query) e sobrescrevê-lo aqui — evita duplicar o `findAll`.
- **`move()`**: a busca da vizinha passa a ter `->forTimeline($stage->bank_id)`. **Crítico**: sem isso a etapa pode trocar de ordem com uma etapa de outro banco.
- **`moveTo()`**: carregar a etapa primeiro e depois `Stage::query()->forTimeline($stage->bank_id)->ordered()->lockForUpdate()->get()`. Alvo fora da timeline → `ModelNotFoundException` (comportamento atual para alvo inexistente).
- **`restore()`**: a checagem de colisão de ordem e o `max('order')` passam a usar `forTimeline($stage->bank_id)`.
- **Novo `timelineOptions(Tenant $tenant, ?int $currentBankId = null): array`** — opções do seletor:
  ```php
  [
      ['bank_id' => null, 'name' => 'Padrão do sistema', 'stages_count' => 8],
      ['bank_id' => 3, 'name' => 'Itaú', 'stages_count' => 5],
      ['bank_id' => 4, 'name' => 'Caixa', 'stages_count' => 0], // usa a padrão
  ]
  ```
  Bancos ativos ordenados por nome (`Bank::ordered()->withCount('stages')`; o `SoftDeletes` de `Stage` já exclui as etapas excluídas da contagem), mais o banco de `$currentBankId` mesmo se excluído.
- `hasOpenProposalStages()` e `usageRelations()` não mudam.

### `app/Http/Requests/IndexStageRequest.php` (novo)

Estende `IndexCatalogRequest`, acrescentando `'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')]` (aceita banco excluído, para conseguir ver a timeline dele) e a mensagem `'bank_id.exists' => 'O banco selecionado não existe.'`.

### `app/Http/Requests/StoreStageRequest.php` / `UpdateStageRequest.php`

- Store: `'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')->whereNull('deleted_at')]` — só banco ativo recebe etapa nova.
- `order` único **por timeline**:
  ```php
  Rule::unique('stages', 'order')
      ->withoutTrashed()
      ->ignore($this->ignoreId())
      ->where(fn ($query) => $this->timelineBankId() === null
          ? $query->whereNull('bank_id')
          : $query->where('bank_id', $this->timelineBankId())),
  ```
- Adicionar `protected function timelineBankId(): ?int` — no Store vem do input; no `UpdateStageRequest` vem da **etapa gravada** (`Stage::find($this->route('id'))?->bank_id`).
- Update: `bank_id` **não** entra nas regras do `UpdateStageRequest` (sobrescrever `rules()` removendo a chave), então nunca chega ao `validated()` e não pode ser alterado (regra A2.5).
- Mensagem `order.unique` passa a ser: "Já existe uma etapa ativa com esta ordem nesta timeline."

### `app/Http/Requests/MoveStageRequest.php`

`target_id` deve existir entre as etapas ativas **da mesma timeline** da etapa movida (`$this->route('id')`): `Rule::exists('stages', 'id')->whereNull('deleted_at')` + `where`/`whereNull` em `bank_id` igual ao da etapa de origem. Mensagem: "A etapa de destino não existe, foi excluída ou pertence a outra timeline."

### `app/Http/Controllers/TenantStageController.php`

- `index(IndexStageRequest $request)`: passa também `bankId` (selecionado, `null` = padrão) e `timelines` (`$this->stageService->timelineOptions(tenant(), $bankId)`).
- `create()`: recebe `bank_id` pela query string (`?bank_id=3`) e o envia como prop `bankId` + o nome da timeline para exibição.
- `edit()`: inclui `stage.bank` (nome da timeline, somente leitura).
- Redirects de `store`, `update`, `destroy` e `restore`: voltar para `tenant.documents.stages.list` **com o mesmo `bank_id`** da etapa (`['bank_id' => $stage->bank_id]`, omitido quando nulo). Os services de store/update/delete/restore precisam devolver (ou o controller precisa obter) o `bank_id` da etapa para isso.

### `app/Services/ProposalTimelineService.php`

- `instantiate()`: trocar `Stage::ordered()->get(['id', 'order'])` por um método privado `stagesFor(Proposal $proposal): Collection` que implementa a regra A2.4:
  ```php
  $bankStages = Stage::forTimeline($proposal->bank_id)->ordered()->get(['id', 'order']);

  return $bankStages->isNotEmpty()
      ? $bankStages
      : Stage::forTimeline(null)->ordered()->get(['id', 'order']);
  ```
- `restore()` já chama `instantiate()` → passa a usar o banco atual sem mudança extra.
- Atualizar o docblock da classe e de `instantiate()` (hoje dizem "copiadas do catálogo").

### `app/Services/ProposalService.php`

- `store()` e `createFromQuote()`: **sem mudança** — já chamam `instantiate()` com o `bank_id` da proposta/orçamento.
- `update()` (regra A2.9): antes do `save()`, guardar `$bankChanged = $proposal->isDirty('bank_id')`. Depois do `save()`, se `$bankChanged` e o acompanhamento não foi iniciado:
  ```php
  $started = $proposal->stages()
      ->where(fn ($query) => $query->whereNotNull('started_at')->orWhereNotNull('completed_at'))
      ->exists();

  if ($bankChanged && ! $started) {
      $proposal->stages()->delete();
      $this->proposalTimelineService->instantiate($proposal, $tenant);
  }
  ```
  A closure do `update()` hoje usa `use ($data, $id)` — incluir `$tenant`. Antes de iniciar não há documentos de etapa (eles só nascem na conclusão), então não há arquivo a apagar.

## A5. Frontend (contrato)

- `resources/js/types/documents/Catalog.ts`: `Stage.bank_id: number | null` e `Stage.bank?: { id: number; name: string } | null`; novo tipo `TimelineOption { bank_id: number | null; name: string; stages_count: number }`.
- `resources/js/pages/tenant/documents/stages/list/List.vue`:
  - Seletor de timeline no topo (Select do shadcn-vue): "Padrão do sistema" + bancos. Ao lado de cada banco, badge "Personalizada (N etapas)" ou "Usa a padrão" (`stages_count = 0`).
  - Trocar a timeline faz `router.get(route('tenant.documents.stages.list'), { ...filters, bank_id, page: undefined }, { preserveState: true })`.
  - Estado vazio para banco sem etapas: "Este banco usa a timeline padrão. Adicione a primeira etapa para criar uma timeline personalizada."
  - Botão "Nova etapa" leva `bank_id` na query; arrastar/mover continua igual (o backend restringe à timeline).
  - Título da página: "Etapas da Timeline — {nome da timeline}". Opcional: renomear o item de menu em `TenantLayout.vue` para "Timelines".
- `create/Create.vue`: exibe a timeline (somente leitura) e envia `bank_id` no payload.
- `edit/Edit.vue`: exibe a timeline (somente leitura); **não** envia `bank_id`.
- Formulário da proposta (opcional, recomendado): ao trocar o banco de uma proposta com acompanhamento já iniciado, mostrar aviso "A timeline atual será mantida. Use Restaurar acompanhamento para aplicar a timeline do novo banco."

## A6. Testes (Pest)

Seguir o padrão do projeto: testar os services direto com `sharedTenant()` + `$tenant->run()`, sem testes HTTP. Escrever os testes e rodar **apenas os arquivos afetados** (`vendor/bin/sail artisan test --compact tests/Feature/<Arquivo>.php`).

- `tests/Feature/StageServiceTest.php`
  - a listagem filtra pela timeline (`bank_id` nulo = padrão; banco = só as etapas dele);
  - `move` não troca de posição com etapa de outra timeline;
  - `moveTo` com alvo de outra timeline lança `ModelNotFoundException` e não altera nada;
  - `MoveStageRequest` rejeita `target_id` de outra timeline;
  - a ordem é única por timeline (mesma ordem aceita em bancos diferentes; rejeitada na mesma timeline);
  - `restore` só considera colisão de ordem na mesma timeline;
  - o update não altera `bank_id`;
  - `timelineOptions` traz "Padrão do sistema" + bancos ativos com a contagem de etapas ativas.
- `tests/Feature/ProposalTimelineServiceTest.php`
  - proposta de banco com timeline instancia só as etapas do banco, na ordem dele;
  - proposta de banco sem etapas ativas instancia a padrão;
  - banco com todas as etapas excluídas volta a usar a padrão;
  - restaurar o acompanhamento recria com a timeline do banco atual.
- `tests/Feature/ProposalServiceTest.php`
  - trocar o banco antes de iniciar recria a timeline com as etapas do novo banco;
  - trocar o banco depois de iniciar mantém a timeline intacta;
  - `createFromQuote` instancia a timeline do banco do orçamento.
- Rodar `vendor/bin/sail bin pint --dirty --format agent` ao final.

## A7. Fases sugeridas

1. Migration + `Stage`/`Bank` + factory (`forBank`).
2. `StageService` (escopo por timeline em `findAll`/`move`/`moveTo`/`restore` + `timelineOptions`), Form Requests e `TenantStageController`.
3. `ProposalTimelineService::instantiate` (resolução com fallback) e `ProposalService::update` (troca de banco).
4. Frontend (A5).
5. Testes (A6) e atualização de documentação (A8).

## A8. Documentação a atualizar (pt-BR)

Já feito junto com este PRD: notas em [`01-cadastros.md`](01-cadastros.md) (B-A3, relacionamentos e §A6.7), [`02-propostas.md`](02-propostas.md) (instanciar, restaurar, trocar banco), [`00-visao-geral.md`](00-visao-geral.md) (ordem, seção 3.6, nomes, conflitos, mapa) e [`docs/arquitetura/database_dictionary.md`](../arquitetura/database_dictionary.md), marcado como **previsto**.

Ao concluir a implementação:

- [`docs/arquitetura/database_dictionary.md`](../arquitetura/database_dictionary.md): remover as marcações "previsto" / "ainda não implementado" de `stages.bank_id`, da relação `STAGES }o--o| BANKS` e de `proposal_stages`, e conferir que o texto bate com o código.
- [`docs/arquitetura/system_routes.md`](../arquitetura/system_routes.md): registrar o parâmetro `bank_id` na listagem e no create de etapas.

## A9. Critérios de aceite

- [ ] As 8 etapas existentes passam a ser a timeline padrão, sem perda nem alteração de dados.
- [ ] Selecionar o Itaú na tela de etapas e cadastrar uma etapa cria a timeline personalizada do Itaú.
- [ ] Proposta do Itaú (criada pelo formulário ou convertida de orçamento) instancia somente as etapas do Itaú, na ordem do Itaú.
- [ ] Proposta de banco sem timeline própria instancia a timeline padrão.
- [ ] Reordenar, mover, excluir ou restaurar etapa de um banco não altera nenhuma outra timeline.
- [ ] Duas timelines diferentes podem ter etapas com a mesma ordem; a mesma timeline não.
- [ ] `bank_id` de uma etapa não muda após a criação.
- [ ] Trocar o banco de uma proposta antes de iniciar o acompanhamento recria a timeline; depois de iniciado, mantém.
- [ ] "Restaurar acompanhamento" usa a timeline do banco atual da proposta.
- [ ] Timelines já instanciadas continuam intactas após qualquer alteração no catálogo.
- [ ] Protocolos de registro, relatórios e alertas de etapas atrasadas continuam funcionando sem alteração.
- [ ] Testes de A6 escritos e verdes nos arquivos afetados; Pint aplicado.
