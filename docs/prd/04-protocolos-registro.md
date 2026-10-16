# PRD — Módulo de Protocolos de Registro (Registry Protocols)

> **Leia antes:** [`00-visao-geral.md`](00-visao-geral.md) — ordem de implementação e fronteiras entre os módulos.

> Documento para o agente que vai implementar o módulo no sistema novo (PHP 8.5, Laravel 13, Vue 3 + Inertia 2, Tailwind v4 + shadcn-vue). Origem: módulo `protocolos` do sistema asdocumentacoes-lv — acompanhamento do **protocolo de registro do imóvel no cartório** (número do protocolo, comprador, vendedor, datas de entrada/saída e posição), vinculado ou não a uma proposta. Referências "B-xx" apontam para a **Parte B** deste documento (defeitos do legado).

## A0. Documentos relacionados e ordem de implementação

| Documento | Conteúdo |
|---|---|
| [`01-cadastros.md`](01-cadastros.md) | Catálogo `stages` — define a flag `shows_registry_protocol` (A4) que diz em qual etapa da timeline o protocolo é exibido |
| [`02-propostas.md`](02-propostas.md) | Dono de `proposals`, `applicants`, `sellers`, `proposal_stages`, `ProposalPolicy` e da aba Acompanhamento (`StageTimeline.vue`), onde este módulo pendura um painel |
| [`03-calculadora-emolumentos.md`](03-calculadora-emolumentos.md) | Define o padrão de Combobox assíncrono de propostas (§5.8, `ProposalSearchController`) reaproveitado aqui |
| [`05-relatorios.md`](05-relatorios.md) | Módulo seguinte; não depende deste, mas vem depois na sequência |

Regras de convivência:

1. **Ordem**: implemente **depois** de Cadastros, Propostas e Calculadora (posição 4 em [`00-visao-geral.md`](00-visao-geral.md)). Este módulo precisa de `proposals`, `applicants`, `sellers`, `proposal_stages` e de `stages.shows_registry_protocol` já existentes.
2. **Fronteira de responsabilidade**:
   - **Este PRD é dono de**: tabela `registry_protocols`, model `RegistryProtocol`, enum `RegistryProtocolPosition`, CRUD de protocolos, endpoint de busca de partes da proposta e o componente `RegistryProtocolPanel.vue`.
   - **Acrescenta ao model `Proposal`** (dono: PRD de Propostas) apenas as relações `registryProtocols()` e `latestRegistryProtocol()` (A4). Não altera colunas de `proposals`.
   - **O PRD de Propostas continua dono** da timeline: este módulo só fornece o painel que `StageTimeline.vue` renderiza quando `stage.shows_registry_protocol = true`.
3. **Nomes compartilhados — use exatamente estes**:

   | Conceito | Tabela / classe | Rota (nomeada) | Consumidores |
   |---|---|---|---|
   | Protocolo de registro | `registry_protocols` / `RegistryProtocol` | `registry-protocols.*` | timeline da proposta |
   | Posição do protocolo | enum `RegistryProtocolPosition` | — | listagem, formulário, timeline |
   | Protocolos da proposta | `Proposal::registryProtocols(): HasMany` / `Proposal::latestRegistryProtocol(): HasOne` | — | `ProposalController@edit/show` (aba Acompanhamento) |
   | Flag da etapa | `stages.shows_registry_protocol` (PRD de Cadastros) | — | `StageTimeline.vue` |
   | Papel | enum `UserRole` (PRD de Propostas, A3) | — | Policy deste módulo |

4. **Conflito entre PRDs**: em regra de proposta/timeline vale o PRD de Propostas; em regra de etapa vale o de Cadastros; em regra de protocolo vale este. Se surgir conflito não coberto, **pare e pergunte**.
5. **Decisões pendentes**: a seção **A10** lista as perguntas que o usuário precisa responder (PR-D1…PR-D7) e a fase em que cada uma deve ser feita. Não implemente a parte afetada sem a resposta registrada.

## A1. Objetivo
Portar o controle de protocolos de registro para o sistema novo: cadastrar, editar, listar (com busca, filtro por posição e paginação), encerrar e excluir protocolos; vincular opcionalmente a uma proposta, preenchendo comprador/vendedor a partir dela; e exibir o protocolo mais recente na etapa da timeline configurada — corrigindo os defeitos da Parte B e nomeando tudo em inglês.

## A2. Regras gerais de implementação (para o agente)
- Valem **todas** as regras gerais da seção A2 do [PRD de Propostas](02-propostas.md#a2-regras-gerais-de-implementação-para-o-agente): sem Repository, nomes em inglês e UI em pt-BR, Laravel 13 (`casts()`, enums *backed*, Form Requests, Policies), datas ISO no transporte, testes de feature, **inspecionar o sistema novo antes de criar**.
- **Camadas**: CRUD com o Model direto no controller (`RegistryProtocol::create($request->validated())`). `RegistryProtocolService` só para a regra não trivial: montar comprador/vendedor a partir da proposta (A6.4).
- **Datas nulas são `null`.** Nunca gravar `'0000-00-00'` nem comparar datas formatadas para detectar “vazio” (B-A1).
- **Sem números mágicos de posição**: sempre `RegistryProtocolPosition::*`.

## A3. Papéis
Legado: todas as rotas exigem `acesso_id = 1` (Administrador). Mantém-se:

| Ação | Admin | Analyst | Partner / Seller / Client |
|---|---|---|---|
| Listar / criar / editar / excluir protocolos | ✔ | — *(decisão **PR-D3**)* | — |
| Ver o painel do protocolo na timeline | conforme `ProposalPolicy::view` | idem | idem *(decisão **PR-D7**)* |
| Link “Editar protocolo” no painel | ✔ | — | — |

Implementação: `RegistryProtocolPolicy` (`viewAny`, `create`, `update`, `delete` → `$user->role === UserRole::Admin`), aplicada via `authorizeResource`/`can:` nas rotas. Resposta **403** (o legado devolve uma view com status 200 — B-C1). Form Requests com `authorize()` delegando à Policy, nunca `return true` fixo.

## A4. Modelo de dados (sistema novo)

### Mapeamento de tabela
| Tabela nova | Legado | Observações |
|---|---|---|
| `registry_protocols` | `protocolo_registros` | `softDeletes`; FK para `proposals` **nullable** com `nullOnDelete` (corrige B-M5) |

### Colunas de `registry_protocols`
| Nova | Legado | Tipo |
|---|---|---|
| `id` | `id` | `id()` |
| `proposal_id` | `proposta_id` (FK `cascade`) | `foreignId → proposals` nullable, `nullOnDelete()` |
| `protocol_number` | `protocolo` | `string(50)` *(unicidade: decisão **PR-D2**; conferir o maior número da base legada antes de fixar o tamanho)* |
| `buyer_name` | `comprador` | `string(255)` |
| `seller_name` | `vendedor` | `string(255)` nullable *(decisão **PR-D4**)* |
| `entered_at` | `entrada` | `date` |
| `exited_at` | `saida` (`'0000-00-00'` quando vazio) | `date` **nullable** |
| `position` | `posicao` (int) | `unsignedTinyInteger` → `RegistryProtocolPosition` |
| `deleted_at`, timestamps | timestamps | `softDeletes()` |

Índices: `proposal_id`, `position`, `entered_at`. Índices `unique` dependem das decisões **PR-D1** (`unique(proposal_id)`) e **PR-D2** (`unique(protocol_number)` ou `unique(notary_id, protocol_number)`).

Casts (`casts()`): `entered_at` e `exited_at` → `date` (o legado usa `datetime` em colunas `date` — B-B1), `position` → `RegistryProtocolPosition::class`.

### Relacionamentos Eloquent
Escritos para a opção (a) da decisão **PR-D1** (vários protocolos por proposta).

- `RegistryProtocol::proposal(): BelongsTo` → `Proposal` com `->withTrashed()` (proposta excluída continua identificável no protocolo).
- **Acrescentar ao model `Proposal`** (PRD de Propostas):
  - `registryProtocols(): HasMany` → `RegistryProtocol`, ordenado por `entered_at desc, id desc`.
  - `latestRegistryProtocol(): HasOne` → `RegistryProtocol` via `->latestOfMany('entered_at')` (desempate por `id`). É o protocolo exibido na timeline.
- Scopes em `RegistryProtocol`: `scopeOpen()` (posição não encerrada), `scopeClosed()`, `scopeSearch(?string $term)` (número do protocolo, comprador, vendedor, nº da proposta).

## A5. Enums (`app/Enums`)
`RegistryProtocolPosition: int` — mantém os inteiros do legado para a migração de dados (`Completed` é uma posição nova, sem equivalente no legado):

| Caso | Valor | `label()` | Legado |
|---|---|---|---|
| `InRegistryPg` | 1 | “Em registro PG” | `EM_REGISTRO_PG` |
| `UnderAnalysis` | 2 | “Em análise” | `EM_ANALISE` |
| `Requirement` | 3 | “Exigência” | `EXIGENCIA` (label legado sem acento) |
| `AwaitingBankPayment` | 4 | “Em pagamento do banco” | `EM_PAGAMENTO_DO_BANCO` |
| `Completed` | 5 | “Concluído” | — (não existe no legado) |

Métodos: `label(): string`, `color(): string` (variante do Badge), `isClosed(): bool` (true para `AwaitingBankPayment` e `Completed` — `AwaitingBankPayment` é o caso que o legado esconde da listagem, ver A6.1), `static options(): array` (para o Select). Não portar `parse()` por texto (B-B1).

## A6. Regras de negócio

### A6.1 Listagem (`RegistryProtocolController@index`)
- Uma rota `GET /registry-protocols?status=open|closed|all&position=&search=&page=`, **paginação server-side** (15 por página), ordenada por `entered_at desc`.
- Abas **Em aberto** (default — esconde as posições encerradas, “Em pagamento do banco” e “Concluído”; o legado escondia “Em pagamento do banco”) / **Encerrados** / **Todos**, com contadores numa única query (`selectRaw … groupBy position`).
- Filtro por posição (Select) e busca textual (`scopeSearch`). Filtros na query string (preservados na paginação).
- Colunas: Proposta (Nº com 5 dígitos e link para a proposta, ou “—”), Protocolo, Comprador, Vendedor (ou “—”), Entrada, Saída (ou “—”), Posição (Badge), Ações (Editar, Excluir).
- Eager loading de `proposal:id` só quando a coluna precisar de dados além do `proposal_id`. Sem N+1.
- Estado vazio: “Nenhum protocolo encontrado” (o legado mostra “Nenhum relatório encontrado” — B-A3).

### A6.2 Criar / editar (`StoreRegistryProtocolRequest`, `UpdateRegistryProtocolRequest`)
Regras (arrays, mensagens pt-BR em `messages()`):

| Campo | Regras |
|---|---|
| `proposal_id` | `nullable`, `integer`, `Rule::exists('proposals', 'id')->whereNull('deleted_at')` |
| `protocol_number` | `required`, `string`, `max:50` (+ `unique` conforme **PR-D2**) |
| `buyer_name` | `required`, `string`, `min:3`, `max:255` |
| `seller_name` | `nullable`, `string`, `max:255` *(o legado marca como obrigatório na tela mas aceita vazio — decisão **PR-D4**)* |
| `entered_at` | `required`, `date` |
| `exited_at` | `nullable`, `date`, `after_or_equal:entered_at` |
| `position` | `required`, `Rule::enum(RegistryProtocolPosition::class)` |

- `prepareForValidation`: `trim` nos textos.
- Gravação sempre por `$request->validated()`.
- Sucesso → redirect para `registry-protocols.index` com flash “Protocolo de registro criado/atualizado com sucesso.”. Erro inesperado **não** é engolido por `try/catch` genérico: deixa o handler do framework registrar e responder.
- Ao mudar `position` para `AwaitingBankPayment` sem `exited_at`: aviso na tela, regra obrigatória ou nada — decisão **PR-D6**.

### A6.3 Seleção de proposta (Combobox assíncrono)
- Substitui o select com **todas** as propostas (B-M4). Mesmo padrão do PRD da calculadora §5.8: Combobox assíncrono, busca server-side por nº da proposta ou nome do proponente, `limit 20`.
- Endpoint: `GET /registry-protocols/proposals/search?q=` → `RegistryProtocolProposalSearchController` (JSON `[{ id, number, applicants }]`), autorizado por `RegistryProtocolPolicy::create`. Lista propostas não excluídas visíveis ao usuário (`Proposal::scopeVisibleTo`). Não filtra por `withoutFeeEstimate` (diferente do endpoint da calculadora — por isso é outra rota).
- Se o sistema novo já tiver um `ProposalCombobox.vue` genérico (criado para a calculadora), reutilizar passando o endpoint por prop; senão, criá-lo genérico aqui.

### A6.4 Preenchimento de comprador/vendedor a partir da proposta
- Ao escolher uma proposta, o front chama `GET /registry-protocols/proposals/{proposal}/parties` → JSON `{ buyer_name, seller_name }`.
- `RegistryProtocolService::partiesFor(Proposal $proposal): array` junta **todos** os proponentes (`applicants.name`) e **todos** os vendedores (`sellers.name`) separados por `", "` (o legado pega só o primeiro de cada — B-M2), com eager loading `applicants:id,name`, `sellers:id,name`.
- Proposta inexistente ou excluída → **404** real (route model binding). Sem `POST` para leitura.
- Os campos continuam **editáveis** (o cartório pode registrar nomes diferentes). Os valores ficam gravados no protocolo como texto (registro histórico do que foi protocolado); não são sincronizados depois.

### A6.5 Exclusão
- Soft delete pelo Admin, com Dialog de confirmação (o legado expõe `DELETE` sem ação — B-M1). Restauração pela UI: decisão **PR-D5**.
- Excluir (soft) uma proposta **não** exclui seus protocolos; excluir fisicamente uma proposta deixa `proposal_id = null` (`nullOnDelete`).

### A6.6 Painel na timeline da proposta
- Em `ProposalController@edit/show` (PRD de Propostas), carregar `latestRegistryProtocol` (eager load; Inertia 2 *deferred prop* junto com a aba Acompanhamento).
- `StageTimeline.vue` renderiza `RegistryProtocolPanel.vue` **na etapa cujo `stage.shows_registry_protocol = true`** — não fixo na etapa 7 (B-M3). Se mais de uma etapa tiver a flag, mostra em todas.
- Conteúdo: Protocolo, Comprador, Vendedor (só se houver), Entrada, Saída (**só se preenchida** — o legado mostra “30/11/-0001”, B-A1), Posição (Badge). Se houver mais de um protocolo, indicar “+N anteriores” com link para a listagem filtrada pela proposta.
- Admin vê link “Editar protocolo”; sem protocolo, Admin vê “Cadastrar protocolo” com a proposta pré-selecionada (`registry-protocols.create?proposal_id=`).

## A7. Arquitetura de arquivos no sistema novo
- **Enum**: `app/Enums/RegistryProtocolPosition.php`.
- **Model** (+ factory com states por posição e `withProposal()`): `RegistryProtocol` (`SoftDeletes`, `$fillable` explícito sem `id`, `casts()`).
- **Service**: `RegistryProtocolService` (`partiesFor`).
- **Controllers** (`app/Http/Controllers/RegistryProtocols/`):
  - `RegistryProtocolController` — `index`, `create`, `store`, `edit`, `update`, `destroy` (`resource()->except('show')`).
  - `RegistryProtocolProposalSearchController` — `__invoke` (A6.3).
  - `RegistryProtocolProposalPartiesController` — `__invoke(Proposal $proposal)` (A6.4).
- **Form Requests** (`app/Http/Requests/RegistryProtocols/`): `StoreRegistryProtocolRequest`, `UpdateRegistryProtocolRequest` (regras compartilhadas via método base/trait).
- **Policy**: `RegistryProtocolPolicy`.
- **Rotas** (`auth`):
  ```php
  Route::middleware('auth')->group(function () {
      Route::get('registry-protocols/proposals/search', RegistryProtocolProposalSearchController::class)
          ->name('registry-protocols.proposals.search');
      Route::get('registry-protocols/proposals/{proposal}/parties', RegistryProtocolProposalPartiesController::class)
          ->name('registry-protocols.proposals.parties');
      Route::resource('registry-protocols', RegistryProtocolController::class)->except('show');
  });
  ```
  Rotas literais declaradas **antes** do `resource`. Autorização via Policy (`authorizeResource` ou `$this->authorize`) e `can:` nas rotas de busca.
- **Front (Vue 3 + Inertia 2 + shadcn-vue)**:
  - Pages: `registry-protocols/Index.vue` (Tabs Em aberto/Encerrados/Todos + DataTable paginada + busca + filtro de posição), `registry-protocols/Create.vue`, `registry-protocols/Edit.vue` (seguir a convenção de pasta do sistema novo; se o padrão adotado em Cadastros for Index + Dialog, usar o mesmo).
  - Components (`components/registry-protocols/`): `RegistryProtocolForm.vue` (`useForm`; Combobox de proposta, inputs de texto, DatePicker, Select de posição), `PositionBadge.vue`, `RegistryProtocolPanel.vue` (usado pela `StageTimeline.vue` do PRD de Propostas). Reutilizar `ProposalCombobox.vue` (A6.3) e `DeleteConfirmDialog.vue` (PRD de Cadastros).
  - Menu “Protocolos de Registro” visível só para Admin.

## A8. Fases sugeridas
1. **Antes: decidir PR-D1, PR-D2, PR-D3 (A10).** Enum, migration, model, factory, Policy, relações em `Proposal`.
2. **Antes: decidir PR-D4, PR-D5, PR-D6.** CRUD (Index com abas/filtros/paginação, Create, Edit, Destroy) + Form Requests + testes.
3. Busca de proposta + preenchimento de partes (`RegistryProtocolService`) + testes.
4. **Antes: decidir PR-D7.** Painel na timeline (flag `shows_registry_protocol`) + testes.
5. (Opcional) Migração de dados do legado: preservar ids; `saida = '0000-00-00'` → `exited_at = null`; `posicao` → `position` (mesmos inteiros); `proposta_id` mantido; `entrada`/`saida` com hora → só a data.

## A9. Critérios de aceite
- Toda rota do módulo passa por Policy; testes cobrem **403** para cada papel não-Admin (index, create, store, edit, update, destroy, search, parties).
- Nenhuma data zero no banco; `exited_at` vazio é `null` e não aparece na UI.
- Validação no backend: posição inválida, `exited_at` anterior a `entered_at`, `proposal_id` inexistente/excluída → 422 com mensagem pt-BR.
- Listagem paginada no servidor, protocolos encerrados acessíveis pela aba Encerrados, sem N+1 (`Model::preventLazyLoading()` em dev).
- Preenchimento traz todos os proponentes e vendedores; proposta inexistente → 404.
- O painel aparece na etapa com `shows_registry_protocol = true` e mostra o protocolo mais recente.
- Excluir uma proposta não apaga seus protocolos.
- Nenhuma tabela/enum/classe duplicada com os PRDs 01–03 (checar a tabela da A0 e a §4 do [`00-visao-geral.md`](00-visao-geral.md)).

## A10. Decisões pendentes (perguntar ao usuário no sistema novo)

> **Instrução para o agente:** estas decisões **não** podem ser tomadas pelo agente. No **início da fase indicada** (A8), apresente ao usuário a pergunta com as opções e a recomendação, espere a resposta, **registre-a no campo “Decisão” deste arquivo** (data + opção escolhida + observação) e só então implemente. Se o usuário preferir não decidir, aplique a recomendação e registre “padrão (recomendação)”. Uma decisão registrada vale mais que o texto das seções A4–A9: ajuste a implementação conforme “Se escolher…”.

| ID | Pergunta (resumo) | Decidir antes da fase | Recomendação |
|---|---|---|---|
| PR-D1 | Uma proposta tem um ou vários protocolos? | 1 | Vários |
| PR-D2 | O número do protocolo é único? | 1 | Não único (como hoje) |
| PR-D3 | O Analyst acessa o módulo? | 1 | Sem acesso (como hoje) |
| PR-D4 | O vendedor é obrigatório? | 2 | Opcional |
| PR-D5 | Protocolos excluídos podem ser restaurados? | 2 | Não |
| PR-D6 | A data de saída é obrigatória em “Em pagamento do banco”? | 2 | Opcional, com aviso na tela |
| PR-D7 | Quem vê o painel do protocolo na timeline? | 4 | Quem pode ver a proposta |

### PR-D1. Cardinalidade proposta ↔ protocolo
- **Pergunta:** “Uma proposta pode ter **mais de um** protocolo de registro (por exemplo, um novo protocolo depois que a prenotação vence ou após uma exigência) ou sempre **um só**?”
- **Opções:**
  - **(a) Vários protocolos por proposta.** Schema da A4 como está; a timeline mostra o mais recente (`latestRegistryProtocol`) e “+N anteriores”.
  - **(b) Um protocolo por proposta.** `unique(proposal_id)` na migration; trocar `registryProtocols()`/`latestRegistryProtocol()` por `Proposal::registryProtocol(): HasOne`; o Combobox da A6.3 não lista propostas que já têm protocolo e o Form Request valida `Rule::unique('registry_protocols', 'proposal_id')->withoutTrashed()->ignore(...)`; remover o “+N anteriores” da A6.6.
- **Recomendação:** (a). É o que o schema legado permite (`hasMany`) e cobre o re-protocolo.
- **Afeta:** A4 (relações, índices), A6.3, A6.6, A9.
- **Decisão:** 2026-10-15 — padrão (recomendação): (a) vários protocolos por proposta; a timeline mostra o mais recente e "+N anteriores".

### PR-D2. Unicidade do número do protocolo
- **Pergunta:** “O número do protocolo pode se repetir? Ele é único no sistema todo ou só dentro de cada cartório?”
- **Opções:**
  - **(a) Não único.** Como o legado; sem índice `unique`.
  - **(b) Único no sistema.** `unique(protocol_number)` + `Rule::unique('registry_protocols')->withoutTrashed()->ignore(...)`, com mensagem “Já existe um protocolo com este número.”.
  - **(c) Único por cartório.** Adicionar `notary_id` (FK `notaries`, obrigatória, `restrictOnDelete`) e um Select de cartório no formulário; `unique(notary_id, protocol_number)`; a coluna Cartório entra na listagem e no painel.
- **Recomendação:** (a), que não muda o comportamento atual. Se a empresa trabalha com vários cartórios e quer registrar em qual foi protocolado, escolher (c).
- **Afeta:** A4 (colunas, índices), A6.1 (colunas), A6.2 (regras), A6.6.
- **Decisão:** 2026-10-15 — (b) único no sistema, validado no Form Request com `Rule::unique(...)->withoutTrashed()` e mensagem "Já existe um protocolo com este número.". Observação: o índice do banco em `protocol_number` é comum (não `unique`) porque, sem restauração pela tela (PR-D5), um protocolo excluído por engano precisa poder ser recadastrado com o mesmo número.

### PR-D3. Acesso do Analyst
- **Pergunta:** “O papel **Analista** deve acessar os protocolos de registro?”
- **Opções:**
  - **(a) Sem acesso.** Como o legado: só Admin.
  - **(b) Só leitura.** `viewAny` libera Analyst; a listagem esconde Novo/Editar/Excluir; `create`/`update`/`delete` continuam só para Admin.
  - **(c) Acesso total.** Analyst passa em todas as ações da Policy.
- **Recomendação:** (a). O PRD de Propostas já trata o Analyst como “Admin somente leitura, a confirmar”. Se lá a resposta tiver sido “leitura”, escolher (b) para manter a coerência.
- **Afeta:** A3, A7 (Policy, menu), A9 (testes de 403).
- **Decisão:** 2026-10-15 — (c) acesso total, coerente com a decisão de 2026-10-01 (Analista com todo o grupo `documents.*`). Como o sistema usa spatie, a autorização é pelas permissões `documents.registry_protocols.{view,create,edit,delete}` nas rotas, concedidas ao Administrador e ao Analista.

### PR-D4. Vendedor obrigatório
- **Pergunta:** “O nome do **vendedor** é obrigatório no protocolo? A tela antiga marca como obrigatório, mas o sistema aceita vazio.”
- **Opções:**
  - **(a) Opcional.** `seller_name` `nullable`, como na A4/A6.2; sem asterisco na tela.
  - **(b) Obrigatório.** Coluna `NOT NULL`, regra `required|string|min:3|max:255`; a migração de dados precisa tratar os registros legados sem vendedor (preencher “Não informado” ou corrigir à mão).
- **Recomendação:** (a). Há protocolos sem proposta e sem vendedor conhecido, e a base legada pode ter registros vazios.
- **Afeta:** A4, A6.2, A8 fase 5 (migração).
- **Decisão:** 2026-10-15 — padrão (recomendação): (a) vendedor opcional.

### PR-D5. Restauração de protocolos excluídos
- **Pergunta:** “Um protocolo excluído por engano precisa poder ser **restaurado** pela tela?”
- **Opções:**
  - **(a) Não.** Só soft delete (recuperação, se preciso, pelo banco).
  - **(b) Sim.** Filtro “Mostrar excluídos” + ação Restaurar, no mesmo padrão dos Cadastros: `PATCH registry-protocols/{registry_protocol}/restore` com `->withTrashed()`, Policy `restore`, testes.
- **Recomendação:** (a). É menos tela para um caso raro.
- **Afeta:** A6.1, A6.5, A7 (rotas, Policy).
- **Decisão:** 2026-10-15 — padrão (recomendação): (a) sem restauração pela tela (só soft delete).

### PR-D6. Data de saída ao encerrar
- **Pergunta:** “Ao mudar a posição para **Em pagamento do banco**, a **data de saída** deve ser obrigatória?”
- **Opções:**
  - **(a) Opcional, com aviso.** A tela mostra um aviso (“Informe a data de saída do cartório”) sem bloquear.
  - **(b) Obrigatória.** Regra `Rule::requiredIf(fn () => $this->enum('position', RegistryProtocolPosition::class) === RegistryProtocolPosition::AwaitingBankPayment)` em `exited_at`, com mensagem pt-BR.
  - **(c) Opcional, sem aviso.** Como o legado.
- **Recomendação:** (a).
- **Afeta:** A6.2, A9.
- **Decisão:** 2026-10-15 — padrão (recomendação): (a) opcional, com aviso "Informe a data de saída do cartório." no formulário.
- **Atualização:** 2026-10-16 — o aviso vale para qualquer posição encerrada, incluindo a nova posição “Concluído”.

### PR-D7. Quem vê o painel do protocolo na timeline
- **Pergunta:** “Na tela da proposta, quem pode ver o painel com o protocolo de registro: todos que podem ver a proposta (Parceiro, Cliente…) ou só a equipe interna?”
- **Opções:**
  - **(a) Todos que veem a proposta** (`ProposalPolicy::view`). No legado, o painel aparece para quem acessa a edição da proposta (Admin, criador, parceiro vinculado).
  - **(b) Só Admin (e Analyst, se PR-D3 ≠ a).** Nova ability `ProposalPolicy::viewRegistryProtocol` ou checagem na `RegistryProtocolPolicy`; a prop `latestRegistryProtocol` só é enviada quando autorizada.
- **Recomendação:** (a), que equivale ao legado.
- **Afeta:** A3, A6.6.
- **Decisão:** 2026-10-15 — padrão (recomendação): (a) o painel aparece para todos que veem a proposta; os links "Editar/Cadastrar protocolo" dependem das permissões do módulo.

---

# Parte B — Defeitos do legado (não reproduzir)

## 🔴 Crítico
**B-C1. Autorização só “de fachada”** — `app/Http/Middleware/Admin.php:23-27`, `app/Http/Controllers/ProtocoloRegistroController.php:26,40,54,72,82,103,121,128`, `app/Http/Requests/StoreProtocoloRegistroRequest.php:12-15`, `UpdateProtocoloRegistroRequest.php:12-15`
- O middleware `admin` chama `$next($request)` nos dois ramos (não bloqueia — mesmo achado B-C1 de Cadastros e C1 do code review de Propostas). A proteção real é um `if (acesso_id != 1) return view('app.acesso-negado')` repetido em cada método, que responde **200**. Os Form Requests autorizam qualquer usuário (`return true`).
- Novo: `RegistryProtocolPolicy` + 403 (A3).

## 🟠 Alto
**B-A1. Data de saída “zero”** — `app/Services/ProtocoloRegistroService.php:13,20`, `resources/views/app/protocolo_registro/index.blade.php:79`, `resources/views/app/proposta/_components/tab_acompanhamento.blade.php:158-161`
- Saída vazia é gravada como `'0000-00-00'` — só funciona porque `config/database.php:59` desliga o modo strict do MySQL. A listagem detecta o “vazio” comparando a data formatada com `'30/11/-0001'`; a timeline testa `format('d/m/Y') != null` (sempre verdadeiro) e exibe **“Saída: 30/11/-0001”**.
- Novo: coluna `nullable`, `null` quando vazio (A4, A6.6).
```php
// antes
$data['saida'] = $data['saida'] !== null ? $data['saida'] : '0000-00-00';
// depois
RegistryProtocol::create($request->validated()); // exited_at = null quando vazio
```

**B-A2. Validação fraca e incoerente** — `app/Http/Requests/StoreProtocoloRegistroRequest.php:24-32`, `UpdateProtocoloRegistroRequest.php:24-32`, `resources/views/app/protocolo_registro/create.blade.php:90`
- Sem regra `date` em `entrada`/`saida`, sem `after_or_equal`, `posicao` sem enum (no update é validada como `string`); no store `proposta_id` é `sometimes` sem `exists`. Mensagem `vendedor.unique` para uma regra que não existe. A tela marca **Vendedor** como obrigatório (`*`), mas o backend aceita vazio.
- Novo: regras da A6.2 com `Rule::enum` e `Rule::exists`.

**B-A3. Listagem carrega tudo e filtra no Blade** — `app/Services/ProtocoloRegistroService.php:26-29`, `resources/views/app/protocolo_registro/index.blade.php:71,106`
- `ProtocoloRegistro::all()` sem paginação, busca ou ordenação; o Blade pula as linhas “Em pagamento do banco” — esses protocolos **somem para sempre** da interface (não há outra tela que os mostre). Quando a lista está vazia, a mensagem é “Nenhum relatório encontrado”.
- Novo: abas Em aberto/Encerrados/Todos, paginação e busca server-side (A6.1).

## 🟡 Médio
**B-M1. Rotas expostas sem ação** — `ProtocoloRegistroController.php:70-75,119-124`, `resources/views/app/protocolo_registro/show.blade.php` (vazio), `routes/web.php:140`
- `Route::resource` registra `show` e `destroy`, que só fazem a checagem de acesso e retornam `null` (200 vazio). Não há como excluir um protocolo.
- Novo: `resource()->except('show')` e `destroy` com soft delete (A6.5).

**B-M2. Busca de partes da proposta frágil** — `ProtocoloRegistroController.php:126-153`, `app/Services/ProtocoloRegistroService.php:35-42`, `public/js/protocolo_registro/index.js:14-16`
- `$proposta` é sempre uma Collection (truthy), então o ramo 404 é inalcançável: id inexistente responde 200 com `data: []` e o JS quebra em `response.data[0].proponentes`. Usa `POST` para leitura e preenche só o **primeiro** proponente e o **primeiro** vendedor.
- Novo: `GET …/proposals/{proposal}/parties` com binding (404 real) e todos os nomes (A6.4).

**B-M3. Etapa fixa e protocolo arbitrário na timeline** — `resources/views/app/proposta/_components/tab_acompanhamento.blade.php:135`, `app/Http/Controllers/PropostaController.php:363`, `app/Models/Proposta.php:143-146`
- O painel aparece quando `$key + 1 === 7` (posição fixa) e usa `ProtocoloRegistro::where('proposta_id', $id)->first()` sem ordenação, embora a relação seja `hasMany` — com mais de um protocolo, o exibido é indefinido.
- Novo: flag `stages.shows_registry_protocol` + `latestRegistryProtocol()` (A4, A6.6).

**B-M4. Select com todas as propostas** — `ProtocoloRegistroController.php:44,86`, `create.blade.php:63-72`
- `Proposta::select('id')->get()` carrega todas as propostas no select (inclusive as que o usuário não deveria ver) e só mostra o número.
- Novo: Combobox assíncrono com busca e `limit 20` (A6.3).

**B-M5. Excluir proposta apaga o protocolo** — `database/migrations/2025_12_10_083108_create_protocolo_registros_table.php:16`
- FK `onDelete('cascade')`: exclusão física de uma proposta elimina o histórico do protocolo no cartório.
- Novo: `nullOnDelete()` + soft delete da proposta (A4).

## ⚪ Baixo
**B-B1. Enum e casts fora do padrão** — `app/Enums/ProtocoloRegistroEnum.php`, `app/Models/ProtocoloRegistro.php:26-30`
- Casos em SCREAMING_CASE, label “Exigencia” sem acento, `parse()` convertendo pelo texto do label, `default` inalcançável num `match` de enum. Casts `datetime` em colunas `date`.
- Novo: enum TitleCase com `label()` (A5) e cast `date`.

**B-B2. Restos copiados de outra tela** — `resources/views/app/protocolo_registro/index.blade.php:54,72,113-115`
- Tabela com `id="lista-propostas"`, linhas com `id="checkR"` repetido e botão **Download** sem script nenhum (copiado da tela de Relatórios). `try/catch` genérico nos controllers (`:58-64`, `:107-113`) troca qualquer erro por “Erro ao criar…” sem detalhe para o usuário.
- Novo: não portar; deixar o handler do framework tratar exceções.
