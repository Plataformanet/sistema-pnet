# PRD — Módulo de Relatórios (Reports)

> **Leia antes:** [`00-visao-geral.md`](00-visao-geral.md) — ordem de implementação e fronteiras entre os módulos.

> Documento para o agente que vai implementar o módulo no sistema novo (PHP 8.5, Laravel 13, Vue 3 + Inertia 2, Tailwind v4 + shadcn-vue). Origem: módulo `relatorios` do sistema asdocumentacoes-lv — **relatório de propostas finalizadas**, com o documento da etapa final, filtro por período e empreendimento e download em lote (ZIP). Escopo: **portar o relatório existente** com as correções; relatórios novos e exportação CSV/PDF estão fora. Referências "B-xx" apontam para a **Parte B** deste documento (defeitos do legado).

## A0. Documentos relacionados e ordem de implementação

| Documento | Conteúdo |
|---|---|
| [`01-cadastros.md`](01-cadastros.md) | Catálogo `developments` (filtro do relatório, A6.6 de lá) e `stages` |
| [`02-propostas.md`](02-propostas.md) | Dono de `proposals`, `ProposalStatus::Finished`, `finished_at`, `proposal_stages`, `proposal_documents`, `proposal_properties`, `applicants`, `ProposalPolicy`, `ProposalDocumentService` e da DataTable com seleção de `proposals/Index.vue` |
| [`04-protocolos-registro.md`](04-protocolos-registro.md) | Módulo anterior na sequência; este não depende dele |

Regras de convivência:

1. **Ordem**: **último módulo** da sequência (posição 5 em [`00-visao-geral.md`](00-visao-geral.md)). É **somente leitura** sobre dados que os PRDs 01 e 02 criam; precisa da timeline (conclusão da última etapa com `date` e documento) e do armazenamento privado de documentos já funcionando.
2. **Fronteira de responsabilidade**:
   - **Este PRD é dono de**: `FinishedProposalsReport` (query), `ReportZipBuilder`, `ReportController`, Gate `view-reports`, rotas `reports.*`, telas de relatório e o download em ZIP. **Não cria tabela.**
   - **Acrescenta ao model `Proposal`** (dono: PRD de Propostas) a relação `finalStage()` e ao model `ProposalStage` a relação `document()`, se ainda não existir (A4). Não altera colunas.
   - **O PRD de Propostas continua dono** do critério de finalização (`status = Finished`, `finished_at`) e do download individual de documento (`proposals.documents.download` + `ProposalPolicy::downloadDocument`), que este módulo reutiliza.
3. **Nomes compartilhados — use exatamente estes**:

   | Conceito | Tabela / classe | Rota (nomeada) | Origem |
   |---|---|---|---|
   | Proposta finalizada | `proposals.status = ProposalStatus::Finished` | — | PRD de Propostas (A5, A6 “Última etapa concluída”) |
   | Etapa final da proposta | `Proposal::finalStage(): HasOne` → `ProposalStage` | — | **este PRD** (A4) |
   | Documento da etapa | `ProposalStage::document(): BelongsTo` → `ProposalDocument` (`proposal_stages.proposal_document_id`) | — | PRD de Propostas (A4); relação criada aqui se faltar |
   | Download de documento | `ProposalDocumentController@download` | `proposals.documents.download` | PRD de Propostas (A7) |
   | Empreendimento | `developments` / `Development` | — | PRD de Cadastros |
   | Papel | enum `UserRole` | — | PRD de Propostas (A3) |

4. **Conflito entre PRDs**: em critério de finalização, timeline e documentos vale o PRD de Propostas; em catálogo vale o de Cadastros; em regra do relatório vale este. Se surgir conflito não coberto, **pare e pergunte**.
5. **Decisões pendentes**: a seção **A10** lista as perguntas que o usuário precisa responder (RE-D1…RE-D5) e a fase em que cada uma deve ser feita. Não implemente a parte afetada sem a resposta registrada.

## A1. Objetivo
Portar o relatório de propostas finalizadas: listar, com paginação no servidor, as propostas cuja timeline foi concluída — com criador, proponentes, empreendimento, documento final e data de finalização —, filtrar por período e empreendimento por query string, e baixar em lote os documentos finais selecionados num ZIP gerado no servidor a partir do disco privado, corrigindo os defeitos da Parte B.

## A2. Regras gerais de implementação (para o agente)
- Valem **todas** as regras gerais da seção A2 do [PRD de Propostas](02-propostas.md#a2-regras-gerais-de-implementação-para-o-agente): sem Repository, nomes em inglês e UI em pt-BR, Laravel 13, Form Requests, Policies/Gates, datas ISO no transporte, **arquivos só em disco privado**, testes de feature, **inspecionar o sistema novo antes de criar**.
- **Consulta em Eloquent com eager loading**, num *query object* (`app/Queries/FinishedProposalsReport.php`) ou Service — seguir a convenção que o sistema novo já tiver. Nada de `DB::table` com sete `leftJoin` + `->get()->unique('id')` (B-A3).
- **Filtros na query string (GET)**, nunca na sessão (B-A2). A URL do relatório filtrado pode ser compartilhada e recarregada.
- **Não adicionar dependência** para o ZIP: usar `ZipArchive` (extensão `zip` do PHP — confirmar que está habilitada no container do sistema novo). Sem JSZip no front.

## A3. Papéis
Legado: tela e filtros exigem `acesso_id = 1` (Administrador). Mantém-se:

| Ação | Admin | Analyst | Partner / Seller / Client |
|---|---|---|---|
| Ver o relatório e filtrar | ✔ | — *(decisão **RE-D3**)* | — *(Partner: decisão **RE-D2**)* |
| Baixar documentos (individual ou ZIP) | ✔ | idem | — |

Implementação: Gate `view-reports` (`fn (User $user) => $user->role === UserRole::Admin`) no grupo de rotas (`->middleware('can:view-reports')`), resposta **403** (o legado devolve uma view com status 200 — B-C1). Além do Gate, cada documento incluído no ZIP passa por `ProposalPolicy::downloadDocument` (defesa em profundidade, caso o relatório venha a ser aberto a outros papéis).

## A4. Dados (sem tabela nova)

### Critério de “proposta finalizada”
- `proposals.status = ProposalStatus::Finished` — definido pelo PRD de Propostas ao concluir a última etapa (`finished_at = now()`). Substitui as flags de timeline do legado (`concluido = 1 AND etapa_atual = 1 AND etapa_iniciada = 1` — B-M2). Propostas soft-deleted ficam de fora.

### Relações acrescentadas
- `Proposal::finalStage(): HasOne` → `ProposalStage` via `->ofMany('position', 'max')` — a etapa de maior `position` da proposta (a que, concluída, finalizou a proposta).
- `ProposalStage::document(): BelongsTo` → `ProposalDocument` (`proposal_document_id`), se o PRD de Propostas ainda não a tiver criado.

### Data de finalização (coluna e filtro de período)
- `report_date = COALESCE(final_stage.date, DATE(proposals.finished_at))`: a **data informada pelo Admin** ao concluir a última etapa (equivale a `timelines.data`, usada pelo legado) e, se vazia, a data em que o sistema marcou a finalização. *(Decisão **RE-D1**.)*
- Implementar como subquery/`addSelect` no query object para permitir `where`/`orderBy` no banco, nunca em PHP.

### Índices (conferir — criados pelos PRDs donos das tabelas)
- `proposals (status, finished_at)` — o PRD de Propostas indexa `status`; propor índice composto se o `EXPLAIN` mostrar varredura.
- `proposal_stages (proposal_id, position)` — necessário para o `ofMany`.
- `proposal_properties (development_id)` — FK já indexa.
- Qualquer índice novo vai numa migration deste módulo, justificada por `EXPLAIN` no sistema novo.

## A5. Enums
Nenhum enum novo. Reutiliza `ProposalStatus` e `UserRole` (PRD de Propostas).

## A6. Regras de negócio

### A6.1 Filtros (`FinishedProposalsReportRequest`)
| Campo | Regras |
|---|---|
| `start_date` | `nullable`, `date`, `required_with:end_date` |
| `end_date` | `nullable`, `date`, `required_with:start_date`, `after_or_equal:start_date` |
| `development_id` | `nullable`, `integer`, `exists:developments,id` (inclui empreendimentos soft-deleted — o filtro precisa achar propostas antigas) |
| `page` | `nullable`, `integer`, `min:1` |

- Mensagens pt-BR em `messages()`.
- Filtro por **id** do empreendimento, não pelo nome (B-M1). O Select lista os empreendimentos ativos e, se houver filtro por um excluído, mostra-o também.
- Sem nenhum filtro: lista **todas** as finalizadas (como o `findAll` do legado), ordenadas por `report_date desc, id desc`.

### A6.2 Conteúdo da listagem
- Rota `GET /reports/finished-proposals?start_date=&end_date=&development_id=&page=` → `ReportController@finishedProposals` → `Inertia::render('reports/FinishedProposals')`.
- **Paginação server-side** (25 por página), filtros preservados na paginação (`withQueryString()`).
- Eager loading: `creator:id,name`, `applicants:id,name`, `property.development:id,name` (com `withTrashed`), `finalStage.document:id,proposal_id,title,original_name,mime_type,size`. Zero N+1.
- Colunas:

  | Coluna | Origem | Observação |
  |---|---|---|
  | Seleção | — | Checkbox só quando há documento final |
  | Nº | `proposals.id` | 5 dígitos com zero à esquerda (padrão do PRD de Propostas) |
  | Criador | `creator.name` | o legado rotula “Administrador / Parceiro”, mas mostra só o criador — rotular **“Criado por”** (B-B1) |
  | Proponentes | `applicants.name` | **todos**, separados por vírgula (o legado mostra um arbitrário — B-A1/B-A3) |
  | Empreendimento | `property.development.name` | “—” quando a proposta não tem imóvel ou empreendimento |
  | Documento | `finalStage.document.title` | link para `proposals.documents.download`; “Sem documento” quando a etapa final não tem arquivo |
  | Data de finalização | `report_date` | formato pt-BR |
  | Ações | — | “Ver proposta” (`proposals.show`) |

- Inclusão:
  - Propostas **sem imóvel/empreendimento** aparecem quando não há filtro de empreendimento (o legado as exclui porque parte de `empreendimento_propostas` — B-M1).
  - Propostas finalizadas **sem documento** na etapa final aparecem, sem link e sem checkbox (o legado as esconde — B-M2).
- Estado vazio: “Nenhuma proposta finalizada no período.”

### A6.3 Seleção
- Checkbox por linha (só com documento) e “Selecionar todos **desta página**”.
- Ação “Selecionar todos os N resultados do filtro” (estilo Gmail), que envia `all_matching = true` + filtros em vez de ids.
- Botão **Baixar selecionados (ZIP)** visível quando há seleção, com o total de arquivos.
- A seleção é estado local do componente (não sessão, não `localStorage`); trocar de página limpa a seleção por página, exceto no modo “todos do filtro”.

### A6.4 Download em ZIP (`DownloadFinishedProposalsRequest` + `ReportZipBuilder`)
- Rota `POST /reports/finished-proposals/download` → `ReportController@downloadFinishedProposals`.
- Entrada (uma das duas):
  - `proposal_ids`: `required_without:all_matching`, `array`, `min:1`, `max:config('reports.zip_max_files')`; cada item `integer`, `distinct`.
  - `all_matching`: `boolean` + os mesmos filtros da A6.1.
- Regras:
  1. Reaplica o query object com os filtros (ou `whereKey($ids)`) — **só entram propostas que realmente atendem ao critério de finalizada**; ids fora dele são ignorados e, se nenhum sobrar, retorna 422 “Nenhum documento disponível para download.”.
  2. Para cada proposta com `finalStage.document` (outros documentos só se a decisão **RE-D5** mandar), checa `ProposalPolicy::downloadDocument` e lê do **disco privado** do documento (`$document->disk`, `$document->path`). Arquivo ausente no disco → entra num `LEIA-ME.txt` dentro do ZIP com a lista de faltantes (não aborta o lote) e gera log de aviso.
  3. Nome de cada entrada: `proposta-{id 5 dígitos}-{slug do título}.{extensão}` — sem colisão (o legado usa o nome final da URL, que pode repetir).
  4. ZIP com `ZipArchive` num arquivo temporário (`storage/app/tmp`), resposta `response()->download($path, 'relatorio-propostas-finalizadas-{AAAA-MM-DD}.zip')->deleteFileAfterSend()`.
  5. Acima de `config('reports.zip_sync_limit')` arquivos (valores e existência da fila: decisão **RE-D4**), em vez de responder na hora: despacha `BuildFinishedProposalsZipJob` (fila), que grava o ZIP no disco privado e notifica o usuário (e-mail em fila com link assinado e temporário `URL::temporarySignedRoute`, 24h). O link aponta para uma rota autenticada que confere o dono.
- Config nova `config/reports.php`: `zip_max_files`, `zip_sync_limit`, `zip_link_ttl_hours`.

### A6.5 Download individual
- O link da coluna Documento usa a rota do PRD de Propostas (`proposals.documents.download`). Nunca `Storage::url` nem disco `public` (B-C2).

## A7. Arquitetura de arquivos no sistema novo
- **Query**: `app/Queries/FinishedProposalsReport.php` — `__construct(array $filters)`, `query(): Builder` (com `report_date` em `addSelect` e os eager loads), `paginate(int $perPage): LengthAwarePaginator`. Se o sistema novo não usar `app/Queries`, implementar como `ReportService::finishedProposals(array $filters)`.
- **Service**: `app/Services/Reports/ReportZipBuilder.php` — `build(Collection $proposals): string` (caminho do ZIP temporário).
- **Job** (opcional, fase 4): `BuildFinishedProposalsZipJob` (`ShouldQueue`) + Mail `FinishedProposalsZipReadyMail` (queued).
- **Controller** (`app/Http/Controllers/Reports/`): `ReportController` — `finishedProposals(FinishedProposalsReportRequest)`, `downloadFinishedProposals(DownloadFinishedProposalsRequest)`; e `ReportDownloadController@__invoke` para o link assinado do ZIP assíncrono (fase 4).
- **Form Requests** (`app/Http/Requests/Reports/`): `FinishedProposalsReportRequest`, `DownloadFinishedProposalsRequest` (reaproveita as regras de filtro da primeira).
- **Autorização**: Gate `view-reports` (registrado onde o sistema novo registra Gates — o mesmo lugar do `manage-catalogs` do PRD de Cadastros).
- **Config**: `config/reports.php`.
- **Rotas** (`auth` + `can:view-reports`):
  ```php
  Route::middleware(['auth', 'can:view-reports'])->prefix('reports')->name('reports.')->group(function () {
      Route::get('finished-proposals', [ReportController::class, 'finishedProposals'])->name('finished-proposals');
      Route::post('finished-proposals/download', [ReportController::class, 'downloadFinishedProposals'])
          ->name('finished-proposals.download');
      Route::get('downloads/{file}', ReportDownloadController::class)->middleware('signed')->name('downloads.show'); // fase 4
  });
  ```
- **Front (Vue 3 + Inertia 2 + shadcn-vue)**:
  - Page: `reports/FinishedProposals.vue` — filtros no topo, DataTable paginada com seleção, barra de ação do ZIP.
  - Components (`components/reports/`): `ReportFilters.vue` (DatePicker de início/fim + Select de empreendimento; envia com `router.get(..., { preserveState: true, replace: true })`), `ZipDownloadBar.vue`. Reutilizar a DataTable com seleção de `proposals/Index.vue` (PRD de Propostas, também usada no PDF de “seleção”) em vez de criar outra.
  - O ZIP síncrono é baixado com um `<form method="post">` normal (ou `fetch` + blob), não via `router.post` do Inertia, porque a resposta é um arquivo.
  - Menu “Relatórios” visível só para quem passa no Gate.

## A8. Fases sugeridas
1. **Antes: decidir RE-D1 e RE-D2 (A10).** `Proposal::finalStage()`, `ProposalStage::document()`, query object com `report_date` e filtros + testes de critério e de filtros (período, empreendimento, sem empreendimento, sem documento, excluída).
2. **Antes: decidir RE-D3.** Tela (filtros por query string, paginação, colunas, estado vazio) + testes de acesso (403) e de props Inertia.
3. **Antes: decidir RE-D4 e RE-D5.** ZIP síncrono (`ReportZipBuilder`, `DownloadFinishedProposalsRequest`) + testes (ids fora do critério, proposta sem documento, arquivo ausente no disco, limite de arquivos, nomes sem colisão) com `Storage::fake`.
4. (Só se RE-D4 = (a)) ZIP assíncrono acima do limite (Job + e-mail em fila + link assinado) + testes com `Queue::fake`/`Mail::fake`.

## A9. Critérios de aceite
- Toda rota do módulo exige `auth` + `can:view-reports`; testes cobrem **403** para cada papel não-Admin.
- Filtros por GET; recarregar a página ou abrir a URL em outra aba mostra o mesmo resultado; nada gravado em sessão.
- Validação de datas no backend (início > fim → 422 pt-BR).
- Listagem paginada no servidor, sem N+1 (`Model::preventLazyLoading()` em dev) e sem deduplicação em PHP.
- Todos os proponentes aparecem; propostas sem empreendimento e sem documento seguem as regras da A6.2.
- Nenhum documento servido por URL pública; o ZIP só contém documentos de propostas finalizadas que o usuário pode baixar.
- Nenhuma tabela, enum ou classe duplicada com os PRDs 01–04 (checar a tabela da A0 e a §4 do [`00-visao-geral.md`](00-visao-geral.md)).

## A10. Decisões pendentes (perguntar ao usuário no sistema novo)

> **Instrução para o agente:** estas decisões **não** podem ser tomadas pelo agente. No **início da fase indicada** (A8), apresente ao usuário a pergunta com as opções e a recomendação, espere a resposta, **registre-a no campo “Decisão” deste arquivo** (data + opção escolhida + observação) e só então implemente. Se o usuário preferir não decidir, aplique a recomendação e registre “padrão (recomendação)”. Uma decisão registrada vale mais que o texto das seções A4–A9: ajuste a implementação conforme “Se escolher…”.

| ID | Pergunta (resumo) | Decidir antes da fase | Recomendação |
|---|---|---|---|
| RE-D1 | Que data o filtro de período usa? | 1 | Data da etapa final, com `finished_at` como reserva |
| RE-D2 | O Partner vê o relatório das próprias propostas? | 1 | Não (como hoje) |
| RE-D3 | O Analyst acessa o relatório? | 2 | Sem acesso (como hoje) |
| RE-D4 | Quais os limites do ZIP e se ele vai para a fila | 3 | Até 50 arquivos na hora, até 500 pela fila |
| RE-D5 | Quais documentos entram no ZIP? | 3 | Só o da etapa final (como hoje) |

### RE-D1. Data usada no período e na coluna “Data de finalização”
- **Pergunta:** “O filtro de período deve usar a **data informada pelo Admin ao concluir a última etapa** (como o sistema atual) ou a **data em que o sistema marcou a proposta como finalizada**?”
- **Opções:**
  - **(a) Data da etapa final, com reserva.** `COALESCE(final_stage.date, DATE(finished_at))`, como na A4. Reproduz o legado (`timelines.data`) e não deixa linha sem data.
  - **(b) Só `finished_at`.** Remove a subquery: `whereBetween(DB::raw('DATE(finished_at)'), …)`, ou um intervalo `>= início 00:00` e `< fim+1 00:00` para usar o índice `(status, finished_at)`. A data mostrada pode diferir da informada na etapa.
- **Recomendação:** (a). Mantém o que os usuários já veem hoje.
- **Afeta:** A4 (“Data de finalização”, índices), A6.1, A6.2, A8 fase 1 (testes).
- **Decisão:** 2026-10-15 — padrão (recomendação): (a) `COALESCE(data da etapa final, DATE(finished_at))`.

### RE-D2. Acesso do Partner
- **Pergunta:** “O **Parceiro** deve ver este relatório, restrito às propostas vinculadas a ele?”
- **Opções:**
  - **(a) Não.** Só Admin (e Analyst, conforme RE-D3).
  - **(b) Sim, só as próprias.** O Gate `view-reports` aceita Partner; o query object aplica `Proposal::scopeVisibleTo($user)` (PRD de Propostas); o ZIP já checa `ProposalPolicy::downloadDocument` por documento; testes de isolamento (o Partner A não vê nem baixa propostas do Partner B, nem por `all_matching`). Pela matriz do PRD de Propostas (A6 “Documentos”), o Partner só vê documentos com `owner` definido — conferir se o documento de etapa (`owner = Stage`) é permitido.
- **Recomendação:** (a). Abrir para o Partner exige rever a regra de documentos de etapa no PRD de Propostas.
- **Afeta:** A3, A4, A6.4 (passo 2), A7 (Gate), A9.
- **Decisão:** 2026-10-15 — padrão (recomendação): (a) o Parceiro não acessa o relatório.

### RE-D3. Acesso do Analyst
- **Pergunta:** “O papel **Analista** deve acessar o relatório?”
- **Opções:**
  - **(a) Sem acesso.** Como o legado.
  - **(b) Ver e baixar.** Igual ao Admin.
  - **(c) Só ver, sem baixar.** Gate `view-reports` para ver e um Gate `download-reports` separado para o ZIP e os links de documento.
- **Recomendação:** (a). Seguir o que tiver sido decidido para o Analyst no PRD de Propostas (A3) e em Protocolos (PR-D3), para manter a coerência.
- **Afeta:** A3, A7 (Gate, menu), A9 (testes de 403).
- **Decisão:** 2026-10-15 — (b) ver e baixar, igual ao Administrador, coerente com PR-D3. O Gate `view-reports` foi implementado como a permissão spatie `documents.reports.view` nas rotas (padrão do sistema), concedida ao Administrador e ao Analista.

### RE-D4. Limites do download em ZIP
- **Pergunta:** “Quantos documentos, no máximo, podem ir num ZIP? Acima de quantos o ZIP deve ser gerado em segundo plano e enviado por e-mail?”
- **Opções:**
  - **(a) Síncrono até o limite e fila acima dele.** `zip_sync_limit` (sugerido **50**) e `zip_max_files` (sugerido **500**); fase 4 obrigatória.
  - **(b) Só síncrono.** Um teto único (sugerido **100**); acima dele, 422 “Selecione no máximo N documentos.”; a fase 4 (Job, e-mail, link assinado) sai do escopo.
- **Recomendação:** (a). Ajustar os números ao tamanho médio dos arquivos e ao `max_execution_time`/timeout do servidor do sistema novo.
- **Afeta:** A6.4 (passo 5, config), A7 (Job, Mail, `ReportDownloadController`, rota assinada), A8 fases 3–4.
- **Decisão:** 2026-10-15 — (a) síncrono até 50 arquivos (`zip_sync_limit`) e fila até 500 (`zip_max_files`), com e-mail em fila e link assinado válido por 24 h (`zip_link_ttl_hours`), em `config/reports.php`.

### RE-D5. Documentos incluídos no ZIP
- **Pergunta:** “O ZIP deve conter só o **documento da etapa final** de cada proposta (como hoje) ou mais documentos?”
- **Opções:**
  - **(a) Só o documento da etapa final.** Como o legado; um arquivo por proposta, na raiz do ZIP.
  - **(b) Documento final + documentos do Imóvel** (`owner = Property`, ex.: matrícula, IPTU). Uma pasta por proposta (`proposta-00042/…`); a coluna Documento lista a quantidade.
  - **(c) Todos os documentos da proposta.** Uma pasta por proposta, com subpastas por `owner`; o volume cresce muito, o que torna a fila (RE-D4 (a)) praticamente obrigatória.
- **Recomendação:** (a). Escopo combinado: portar o relatório existente.
- **Afeta:** A6.2 (coluna Documento), A6.4 (passos 2–3), A8 fase 3 (testes).
- **Decisão:** 2026-10-15 — padrão (recomendação): (a) só o documento da etapa final.

---

# Parte B — Defeitos do legado (não reproduzir)

## 🔴 Crítico
**B-C1. Autorização só “de fachada”** — `app/Http/Middleware/Admin.php:23-27`, `app/Http/Controllers/RelatorioController.php:31-33,56-58,72-74`
- O middleware `admin` chama `$next($request)` nos dois ramos (não bloqueia — mesmo achado B-C1 de Cadastros). A checagem real devolve `view('app.acesso-negado')` com **200**; em `filterRelatorio` (endpoint AJAX) devolve HTML de página onde o JS espera JSON.
- Novo: Gate `view-reports` + 403 (A3).

**B-C2. Documentos pessoais em URL pública** — `app/Repositories/ArquivoRepository.php:29`, `resources/views/app/relatorio/index.blade.php:94`, `public/js/relatorios/show-button-download.js:116`
- Os arquivos das propostas (RG, IRPF, CTPS, matrícula…) são gravados no disco **`public`** e o relatório gera o link com `Storage::url($relatorio->arquivo)`. Qualquer pessoa com a URL baixa o documento, sem login. O ZIP do navegador busca esses mesmos links públicos.
- Novo: disco privado e download por rota autorizada (A6.4, A6.5 — regra do PRD de Propostas A2).

## 🟠 Alto
**B-A1. Join errado no filtro: proponente errado** — `app/Repositories/RelatorioRepository.php:53`
- No `listRelatorios` o join é `proponentes as p ON p.id = epp.proposta_id` — compara o **id do proponente** com o **id da proposta**. Com filtro aplicado, a coluna Proponente mostra um proponente qualquer cujo id coincide com o da proposta (ou nenhum). O `findAll` (`:25`) usa o join certo (`p.id = pp.proponente_id`).
- Novo: relação `applicants()` com eager loading (A6.2).

**B-A2. Filtro guardado na sessão** — `app/Http/Controllers/RelatorioController.php:38-44,60-62,76-77`, `public/js/relatorios/filtra-relatorios.js:24-31,59-79`, `routes/web.php:39,64-65`
- O filtro vai por `POST` AJAX e é **acumulado** na sessão com `push`; o `index` percorre todos os filtros acumulados e fica com o último. A limpeza é feita pelo método `show` do `resource` (`GET /relatorios/{qualquer-coisa}`), chamado pelo JS quando detecta *reload* via `performance.getEntriesByType('navigation')`. O resultado depende do histórico da sessão; a URL não reflete o filtro; abrir em outra aba mostra outro resultado.
- Novo: filtros na query string (A2, A6.1).

**B-A3. Joins que multiplicam linhas e deduplicação em PHP** — `app/Repositories/RelatorioRepository.php:21-43,49-75`
- Sete `leftJoin` (proponentes × arquivos × timelines) geram uma linha por combinação; `->get()->unique('id')` carrega **tudo** na memória e mantém a primeira linha de cada proposta — proponente e arquivo exibidos são arbitrários. Sem paginação, sem `limit`.
- Novo: query object com eager loading e paginação (A2, A6.2).

## 🟡 Médio
**B-M1. Propostas sem empreendimento: regra inconsistente** — `app/Repositories/RelatorioRepository.php:21,41,49,69,71-73`, `resources/views/app/relatorio/index.blade.php:43`
- A consulta parte de `empreendimento_propostas`: propostas sem registro de imóvel **nunca** aparecem. Sem filtro, imóveis sem empreendimento aparecem (a condição está comentada em `:41`); com filtro, o `where('epp.empreendimento_id', '!=', "")` os exclui mesmo quando nenhum empreendimento foi escolhido. O filtro compara pelo **nome** do empreendimento (`ep.nome_empreendimento`), sujeito a duplicatas e renomeações.
- Novo: partir de `proposals`, filtro por `development_id` (A6.1, A6.2).

**B-M2. Critério de “finalizada” por flags e via arquivo** — `app/Repositories/RelatorioRepository.php:27-28,38-40,55-56,66-68`
- A etapa é encontrada por `timelines.arquivo_id = arquivos.id` e filtrada por `concluido/etapa_atual/etapa_iniciada = 1`. O `where` sobre a tabela do `leftJoin` transforma o join em *inner*: propostas finalizadas cuja última etapa não tem arquivo **somem** do relatório. O critério depende das flags da timeline, não do status da proposta.
- Novo: `status = Finished` + `finalStage()` (A4).

**B-M3. Datas sem validação** — `app/Http/Controllers/RelatorioController.php:70-77`, `app/Services/RelatorioService.php:21-24`
- Sem Form Request: início depois do fim é aceito (resultado vazio silencioso); `data_inicio`/`data_termino` ausentes chegam como `null` ao `whereBetween`. A UI marca as datas como `required` mas o backend não.
- Novo: `FinishedProposalsReportRequest` (A6.1).

**B-M4. Seleção e ZIP no navegador com bugs** — `public/js/relatorios/show-button-download.js`, `public/js/check-all.js:4-18,61-69`
- `removeCheck` (`:68-76`) usa `array_link.splice(item, 1)` passando a **URL** como índice: desmarcar uma linha remove o item errado (ou o primeiro) da lista de downloads. O contador `array_checks` (`:4,12,78-94`) só cresce e soma/subtrai cliques, então o botão Download aparece/some de forma inconsistente. O “Todos” é tratado por **dois** scripts: `check-all.js` marca as linhas e `show-button-download.js:42-46` dispara `click` em cada uma, o que alterna o estado de novo — linhas já marcadas ficam desmarcadas e vice-versa. Erro em qualquer arquivo faz `throw` (`:118`) sem feedback e o ZIP nunca é gerado. O `addEventListener` do botão é registrado uma vez com a lista mutável e o `removeEventListener` (`:135-141`) roda só na carga.
- Novo: seleção em estado do componente Vue + ZIP no servidor (A6.3, A6.4).

## ⚪ Baixo
**B-B1. Restos e rótulos enganosos** — `resources/views/app/relatorio/index.blade.php:77,87`, `app/Repositories/RelatorioRepository.php:10-12`, `routes/web.php:39`, `resources/views/app/template/index.blade.php:184-191`
- Coluna “Administrador / Parceiro” mostra só o criador (`u.name` de `criador_id`). `id="checkR"` repetido em todas as linhas. Construtor vazio no repository. `Route::resource` inteiro registrado para usar só `index` e (indevidamente) `show`. JSZip, JSZip-utils, download.js e FileSaver carregados como plugins estáticos só para esta tela.
- Novo: rótulo “Criado por”, rotas explícitas (A7), sem dependências de front para o ZIP.
