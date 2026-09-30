# PRD — Módulo de Cadastros Básicos (Catalogs)

> **Leia antes:** [`00-visao-geral.md`](00-visao-geral.md) — ordem de implementação e fronteiras entre os módulos.

> Documento para o agente que vai implementar o módulo no sistema novo (PHP 8.5, Laravel 13, Vue 3 + Inertia 2, Tailwind v4 + shadcn-vue). Origem: os cadastros de apoio do sistema asdocumentacoes-lv — **tipos de custo, tipos de contrato, tipos de imóvel, empreendimentos, etapas da timeline, bancos, cartórios e serviços**. Referências "B-xx" apontam para a **Parte B** deste documento (defeitos do legado).

## A0. Documentos relacionados e ordem de implementação

| Documento | Conteúdo |
|---|---|
| [`02-propostas.md`](02-propostas.md) | PRD de Propostas — **consome** `banks`, `contract_types`, `cost_types`, `stages`, `notaries`, `developments`, `property_types` |
| [`03-calculadora-emolumentos.md`](03-calculadora-emolumentos.md) | PRD da Calculadora + Orçamento — define o schema de `billable_services` (§6.2) e o CRUD (§5.11); consome `banks` |
| [`02-propostas-code-review.md`](02-propostas-code-review.md) | Code review de Propostas no legado (os achados de autorização valem também aqui — ver B-C1) |

Regras de convivência:

1. **Ordem**: implemente **este módulo primeiro**, depois Propostas, depois Calculadora. Propostas e Calculadora precisam das FKs para estes catálogos já existirem.
2. **Fronteira de responsabilidade**:
   - **Este PRD é dono de**: `banks`, `notaries`, `contract_types`, `cost_types`, `property_types`, `developments`, `stages` (catálogo) — tabelas, models, seeders, telas de CRUD.
   - **`billable_services`**: o **schema** é o do PRD da calculadora (§6.2) — **usar idêntico, sem renomear**. A tela de CRUD está descrita aqui (A6.8) e repete o §5.11 de lá; se já tiver sido feita pelo módulo da calculadora, só conferir.
   - **O PRD de Propostas continua dono** das tabelas que *referenciam* estes catálogos: `proposals`, `proposal_properties`, `proposal_stages`, `proposal_cost_items`, `receipts`.
3. **Nomes compartilhados — use exatamente estes**:

   | Conceito | Tabela / classe | Rota (nomeada) | Consumidores |
   |---|---|---|---|
   | Banco | `banks` / `Bank` | `banks.*` | `proposals.bank_id`, `quotes.bank_id` |
   | Cartório | `notaries` / `Notary` | `notaries.*` | `proposal_cost_items.notary_id` |
   | Tipo de contrato | `contract_types` / `ContractType` | `contract-types.*` | `proposals.contract_type_id` |
   | Tipo de custo | `cost_types` / `CostType` | `cost-types.*` | `proposal_cost_items.cost_type_id`, contas a pagar/receber (quando portadas) |
   | Tipo de imóvel | `property_types` / `PropertyType` | `property-types.*` | `proposal_properties.property_type_id` |
   | Empreendimento | `developments` / `Development` | `developments.*` | `proposal_properties.development_id` |
   | Etapa (catálogo) | `stages` / `Stage` | `stages.*` | `proposal_stages.stage_id` |
   | Serviço cobrável | `billable_services` / `BillableService` | `billable-services.*` | `quote_billable_service`, `proposal_billable_service` |
   | Tipo de recibo | enum `ReceiptType` (PRD de Propostas) | — | reutilizado em `cost_types.receipt_type` |
   | Papel | enum `UserRole` (PRD de Propostas, A3) | — | Gate deste módulo |
   | Dinheiro | `unsignedBigInteger` em **centavos** + `App\Support\Money` / `formatMoney()` / `MoneyInput.vue` | — | `billable_services.price` |

4. **Conflito entre PRDs**: em nome/tabela compartilhado vale a tabela acima; no schema de `billable_services` vale o PRD da calculadora; em regra de proposta vale o PRD de Propostas. Se surgir conflito não coberto, **pare e pergunte**.

## A1. Objetivo
Portar os oito cadastros básicos (CRUD administrativo) para o sistema novo, nomeados em inglês, com Form Requests, autorização real, exclusão segura (soft delete, sem cascata sobre dados de propostas) e sem números mágicos — corrigindo os defeitos da Parte B e deixando os catálogos prontos para os módulos de Propostas e Calculadora.

## A2. Regras gerais de implementação (para o agente)
- Valem **todas** as regras gerais da seção A2 do [PRD de Propostas](02-propostas.md#a2-regras-gerais-de-implementação-para-o-agente): sem Repository, nomes em inglês e UI em pt-BR, Laravel 13 (`casts()`, enums *backed*, Form Requests, Policies/Gates), dinheiro em centavos, datas ISO, testes de feature, **inspecionar o sistema novo antes de criar** (se já houver `Bank`, `Notary` etc., reutilizar e só completar colunas).
- **Camadas**: CRUD trivial pode usar o Model direto no controller (`Bank::create($request->validated())`) — criar Service só onde há regra não trivial (`StageService`, A7). Nada de Repository nem de “Validate” no estilo legado.
- **Exclusão = soft delete em todos os catálogos.** Nunca exclusão física pela UI. FKs dos consumidores com `restrictOnDelete()` (proteção extra caso alguém use `forceDelete`).
- **Registros excluídos**: somem das listagens do CRUD (com filtro “Mostrar excluídos” + ação **Restaurar**) e dos selects dos outros módulos, mas **continuam aparecendo** onde já estão em uso — relações dos consumidores com `->withTrashed()` (ex.: `Proposal::bank()`), e o select mostra o valor atual mesmo excluído.
- Scope reutilizável nos catálogos com ordenação: `scopeOrdered()` (por `name`, ou por `order` em `Stage`) para alimentar selects dos outros módulos.
- Remover as colunas legadas `excluido` (nunca foram lidas — B-M2); o papel delas passa a ser do `deleted_at`.

## A3. Papéis
Legado: todas as telas exigem `acesso_id = 1` (Administrador). Mantém-se:

| Ação | Admin | Analyst | Demais |
|---|---|---|---|
| Listar / criar / editar / excluir / restaurar catálogos | ✔ | — *(confirmar se deve ter leitura)* | — |
| Ver opções nos selects de outros módulos | conforme a Policy do módulo consumidor | idem | idem |

Implementação: um Gate `manage-catalogs` (`fn (User $user) => $user->role === UserRole::Admin`) aplicado no grupo de rotas (`->middleware('can:manage-catalogs')`), resposta **403** (o legado devolvia uma view com status 200 — B-C1). Os dados para selects dos outros módulos vêm como props dos controllers daqueles módulos, não das rotas deste.

## A4. Modelo de dados (sistema novo)

### Mapeamento de tabelas
| Tabela nova | Legado | Observações |
|---|---|---|
| `banks` | `bancos` | `excluido` → `deleted_at` |
| `notaries` | `cartorios` | `municipio` + `cidade` → `city` (B-M6) |
| `contract_types` | `contratos` | + `requires_financing` (substitui o id mágico 4 — B-A2) |
| `cost_types` | `custos` | `relacionar_cartorio` → `requires_notary`; + `receipt_type` (substitui ids 3/5 — B-A2) |
| `property_types` | `tipo_imoveis` | flags renomeadas; `empreendimento` → `requires_development` |
| `developments` | `empreendimentos` | `nome_empreendimento` → `name` |
| `stages` | `etapas` | colunas já fixadas no PRD de Propostas (A4) + 2 flags de exibição |
| `billable_services` | `servicos` | schema do PRD da calculadora §6.2 |
| — | `tabelas` (PRICE/SAC) | **não portar**: vira enum `AmortizationTable` (PRD de Propostas) |
| — | `etapas_default` / `EtapasDefault` | **não portar**: migration comentada, model órfão (B-B3) |

### Colunas

**`banks`**
| Nova | Legado | Tipo |
|---|---|---|
| `name` | `nome` | string(100), `unique` |
| `deleted_at`, timestamps | `excluido` | softDeletes |

**`notaries`**
| Nova | Legado | Tipo |
|---|---|---|
| `name` | `nome` | string(191) |
| `zip_code` | `cep` | string(8) — só dígitos |
| `street` | `endereco` | string(191) |
| `number` | `numero` | string(20) |
| `complement` | `complemento` | string(45) nullable |
| `neighborhood` | `bairro` | string(45) |
| `city` | `cidade` (+ `municipio`) | string(80) |
| `state` | `estado` | char(2) → enum `BrazilianState` |
| `reference_point` | `localizacao` | string(100) nullable |
| `business_hours` | `horarios_atendimento` (“Horários de atendimento / Observação”) | text nullable |
| `deleted_at`, timestamps | | |

**`contract_types`**
| Nova | Legado | Tipo |
|---|---|---|
| `name` | `tipo` | string(191), `unique` |
| `requires_financing` | regra `contrato_id != 4` em `PropostaController.php:196` | boolean default `true` |
| `deleted_at`, timestamps | `excluido` | |

**`cost_types`**
| Nova | Legado | Tipo |
|---|---|---|
| `name` | `tipo` | string(100), `unique` |
| `requires_notary` | `relacionar_cartorio` (tinyint nullable) | boolean default `false` |
| `receipt_type` | ids fixos 3 (Assessoria) / 5 (Motoboy) em `tab_pagamentos_e_taxas.blade.php:146` | string nullable → `ReceiptType` (`Advisory`, `Courier`); `General` não se aplica |
| `deleted_at`, timestamps | | |

**`property_types`**
| Nova | Legado | Tipo |
|---|---|---|
| `name` | `nome` | string(191), `unique` |
| `shows_number` | `numero` | boolean default `false` |
| `shows_complement` | `complemento` | boolean default `false` |
| `requires_development` | `empreendimento` | boolean default `false` |
| `shows_unit` | `unidade` | boolean default `false` |
| `shows_block` | `bloco` | boolean default `false` |
| `deleted_at`, timestamps | | |

**`developments`**
| Nova | Legado | Tipo |
|---|---|---|
| `name` | `nome_empreendimento` | string(255), `unique` |
| `deleted_at`, timestamps | `excluido` | |

**`stages`** (nomes idênticos ao PRD de Propostas A4 — não renomear)
| Nova | Legado | Tipo |
|---|---|---|
| `order` | `ordem` | unsignedSmallInteger, indexado |
| `name` | `nome` | string(191) |
| `has_date` | `data` | boolean |
| `date_required` | `data_required` | boolean |
| `has_upload` | `upload` | boolean |
| `upload_required` | `upload_required` | boolean |
| `title_required` | `titulo_required` | boolean |
| `notes_required` | `observacao_required` | boolean |
| `completion_deadline_hours` | `prazo_conclusao` | unsignedInteger default 0 |
| `alert_deadline_hours` | `prazo_alerta` | unsignedInteger default 0 (0 = sem alerta) |
| `shows_property_data` | — (fixo “etapa de posição 2” no legado) | boolean default `false` |
| `shows_registry_protocol` | — (fixo “etapa 7” no legado) | boolean default `false` |
| `deleted_at`, timestamps | `deleted_at` | softDeletes (já existia) |

Índice legado inútil `(nome, prazo_conclusao, prazo_alerta)` não é portado.

**`billable_services`** — exatamente como o PRD da calculadora §6.2: `name` string(191), `description` text, `price` `unsignedBigInteger` (**centavos**), `generates_receipt` boolean default `false`, softDeletes, timestamps. Legado: `nome`, `descricao_servico`, `valor` (`double(11,2)`), `gera_recibo`.

### Relacionamentos Eloquent
- `Bank`: `proposals()` HasMany, `quotes()` HasMany. (A relação legada `Banco::etapas()` **não** é portada: as etapas são globais — B-A3.)
- `Notary`: `costItems()` HasMany(`ProposalCostItem`).
- `ContractType`: `proposals()` HasMany.
- `CostType`: `costItems()` HasMany; cast `receipt_type` → `ReceiptType`; helper `generatesReceipt(): bool` (`receipt_type !== null`).
- `PropertyType`: `proposalProperties()` HasMany.
- `Development`: `proposalProperties()` HasMany.
- `Stage`: `proposalStages()` HasMany; `scopeOrdered()`.
- `BillableService`: `quotes()` / `proposals()` BelongsToMany (definidos no PRD da calculadora).
- Lado consumidor (PRDs de Propostas/Calculadora): toda `BelongsTo` para estes catálogos usa `->withTrashed()`.

### FKs dos consumidores (registrar para os outros PRDs)
| Coluna | Legado | Novo |
|---|---|---|
| `proposals.bank_id` | FK sem `onDelete` | `constrained('banks')->restrictOnDelete()` |
| `proposals.contract_type_id` | **sem FK** (B-A1) | `constrained('contract_types')->restrictOnDelete()` |
| `proposal_properties.property_type_id` | `tipo_do_imovel`, **sem FK**, default 0 (B-A1) | `constrained('property_types')->restrictOnDelete()` |
| `proposal_properties.development_id` | FK **`onDelete('cascade')`** (B-C3) | `nullable()->constrained('developments')->restrictOnDelete()` |
| `proposal_cost_items.cost_type_id` / `.notary_id` | FKs sem `onDelete` | `nullable()->constrained(...)->restrictOnDelete()` |
| `proposal_stages.stage_id` | FK sem `onDelete` | `constrained('stages')->restrictOnDelete()` |
| `quote_billable_service` / `proposal_billable_service` | `cascadeOnDelete` | manter como no PRD da calculadora (serviço só sofre soft delete, então a cascata não dispara) |

Fora do escopo: `conta_bancarias.banco` e `conta_bancos.banco` guardam o **nome** do banco como string, sem FK (módulo financeiro e contas de proponente/vendedor). Não mudar agora; registrar como débito (B-B4).

## A5. Enums (`app/Enums`)
- **`BrazilianState`** (string, 27 casos `AC`…`TO`) com `label()` (“São Paulo”). Usado em `notaries.state`. Se o sistema novo já tiver um enum de UF, reutilizar. O `SupportedState` da calculadora (11 UFs da API) pode virar um subconjunto (`BrazilianState::supportedByFeeApi()`) — decidir ao implementar a calculadora; **não** criar dois enums de UF com os mesmos 27 casos.
- **`ReceiptType`** — definido no PRD de Propostas (`General`, `Advisory`, `Courier`); aqui só é reutilizado em `cost_types.receipt_type`. Método auxiliar `forCostTypes(): array` → `[Advisory, Courier]` para o select.
- **`UserRole`** — definido no PRD de Propostas; usado no Gate.
- Nenhum outro enum: os catálogos em si são dados editáveis, não enums.

## A6. Regras de negócio

### A6.0 Comuns a todos os cadastros
- Tela única por cadastro: listagem + botão “Novo” abrindo Dialog; editar no mesmo Dialog; excluir via AlertDialog de confirmação (“Tem certeza que deseja excluir **{nome}**?”). Flash de sucesso/erro em toast.
- Listagem **paginada no servidor**, busca por nome, ordenada por nome (etapas: por `order`). Filtro “Mostrar excluídos” com ação **Restaurar**.
- `name` com `trim` e colapso de espaços (`prepareForValidation`). Unicidade (onde houver) **considerando excluídos**: se o nome existir num registro excluído, a mensagem é “Já existe um cadastro excluído com este nome — restaure-o.” (evita duplicar e respeita o `unique` do banco).
- Validação sempre em Form Request (`Store*Request`/`Update*Request`), mensagens pt-BR; update usa `$request->validated()` — nunca `fill($request->all())` (B-C2). `$fillable` sem `id`.
- Excluir registro **em uso** é permitido (soft delete) e não afeta propostas/orçamentos existentes; a listagem mostra uma coluna “Em uso” (`withCount` da relação principal) para o admin saber o impacto.

### A6.1 Bancos (`banks`)
- Campos: `name` obrigatório, `max:100`, único. Mensagens: “O nome do banco precisa ser preenchido”, “Este banco já está cadastrado”.
- Seed: Banco do Brasil, Bradesco, Itaú (legado grava “Itáu” — corrigir), Santander, Caixa.

### A6.2 Cartórios (`notaries`)
- Obrigatórios: `name` (`min:3`, `max:191` — o legado exige `min:10`, B-M6), `zip_code` (8 dígitos), `street`, `number`, `neighborhood`, `city`, `state` (`Rule::enum(BrazilianState::class)`). Opcionais: `complement`, `reference_point`, `business_hours`.
- CEP com máscara `00000-000` no front; gravado só com dígitos. Busca automática de endereço pelo CEP **não** existe no legado — só implementar se o sistema novo já tiver o serviço (não adicionar dependência).
- Listagem: nome, cidade/UF, em uso.
- Uso: select de cartório na linha de custo manual da proposta aparece/é obrigatório quando `cost_type.requires_notary` (PRD de Propostas A6 “Pagamentos e taxas”). O recibo de assessoria grava o **nome** do cartório como texto (snapshot) — editar/excluir cartório não altera recibos emitidos.

### A6.3 Tipos de contrato (`contract_types`)
- Campos: `name` obrigatório, único; `requires_financing` (Switch “Exige dados de financiamento”, default ligado).
- Efeito: na criação/edição de proposta, se `requires_financing` → `amortization_table` e `payment_term` obrigatórios (PRD de Propostas A6 “Criação”, item 3). Substitui o `contrato_id != 4` do legado.
- Seed (preservar ids do legado para migração de dados): 1 CCFGTS, 2 PRÓ-COTISTA, 3 CCSBPE, 4 AQUISIÇÃO À VISTA COM FGTS (`requires_financing = false`), 5 MCMV, 6 CCSBPE IPCA, 8 CCSBPE + Poupança, 9 Cartório de Notas. *(Confirmar se “Cartório de Notas” também não exige financiamento.)*

### A6.4 Tipos de custo (`cost_types`)
- Campos: `name` obrigatório, único; `requires_notary` (Switch “Vincular este custo a cartório?”); `receipt_type` (select opcional “Gera recibo de”: Nenhum / Assessoria / Motoboy).
- Efeitos no PRD de Propostas:
  - `requires_notary` → habilita e torna obrigatório o select de cartório no lançamento manual (legado: `habilita-cartorio.js`).
  - `receipt_type` → mostra o checkbox “gerar recibo” da linha e define o tipo do recibo (legado: `@switch($pt->custo_id) case 3 / case 5`).
- Seed: Registro, ITBI, Assessoria (`Advisory`), Matrícula, Motoboy (`Courier`), Outros, Cartório de Notas (existe na base de produção, não no seeder). Todos com `requires_notary = false` como na base atual *(confirmar se Registro/Matrícula deveriam exigir cartório)*.

### A6.5 Tipos de imóvel (`property_types`)
- Campos: `name` obrigatório (`min:3`), único; cinco Switches: “Preencher número”, “Preencher complemento”, “Preencher empreendimento”, “Preencher unidade”, “Preencher bloco”.
- Efeito no formulário de imóvel da proposta (`PropertyFields.vue` do PRD de Propostas): **cada flag mostra/oculta o seu campo**; `requires_development` torna `development_id` obrigatório. Ao trocar de tipo, limpar os campos que ficaram ocultos (legado: `troca-tipo-imovel.js`). O legado só lê a flag `empreendimento` e ignora as outras (B-M4); o PRD de Propostas descreve a regra simplificada (“empreendimento → empreendimento/bloco/unidade; senão → número/complemento”). **Recomendação: honrar as cinco flags** *(confirmar)*.
- Seed: não há seeder no legado; a migração de dados traz os 7 registros de produção. Há nomes duplicados e com erro de digitação (“CASA” ×2 com flags diferentes, “CADA DE CONDOMINIO”, “APARTAMENTO SEM CONOMINIO”) — **não corrigir automaticamente**: listar para o usuário decidir e, se fundir, remapear `proposal_properties.property_type_id`.

### A6.6 Empreendimentos (`developments`)
- Campos: `name` obrigatório, `max:255`, único. Mensagem: “O empreendimento precisa ser preenchido”.
- Excluir **não** apaga nada das propostas (legado apaga os dados de imóvel via cascade — B-C3).
- Consumo: select no imóvel da proposta (quando `property_type.requires_development`) e filtro do relatório por empreendimento (módulo de relatórios, fora do escopo).
- Seed: o legado lê `storage/app/dados-jsons/empreendimento.json`; no sistema novo, vir pela migração de dados (7 registros).

### A6.7 Etapas da timeline (`stages`)
Catálogo **global** (o vínculo por banco existiu e foi comentado no legado — B-A3; não portar).

- Campos: `name` obrigatório; `order` obrigatório `integer|min:1`, **único entre etapas ativas** (`Rule::unique('stages')->withoutTrashed()->ignore(...)`); Switches `has_date`, `date_required`, `has_upload`, `upload_required`, `title_required`, `notes_required`, `shows_property_data`, `shows_registry_protocol`; `completion_deadline_hours` e `alert_deadline_hours` `integer|min:0`.
- **Regras cruzadas** (em `after()` do Form Request, mensagem pt-BR por campo) — o legado não valida e tem dado inconsistente (B-M1):
  - `date_required` ⇒ `has_date`.
  - `upload_required` ⇒ `has_upload`; `title_required` ⇒ `has_upload` (título é do arquivo).
  - `alert_deadline_hours` ≤ `completion_deadline_hours` quando ambos > 0.
  - No front: desligar `has_date`/`has_upload` desliga e desabilita os “obrigatórios” dependentes.
- **Reordenar**: além do campo `order`, ação “mover para cima/baixo” (ou arrastar) que troca posições em transação (`StageService::move`). A ordem só afeta **timelines instanciadas depois** — `proposal_stages.position` é cópia (PRD de Propostas A4).
- **Editar etapa já usada**: as flags de obrigatoriedade são lidas **ao vivo** pelo `CompleteProposalStageRequest` (comportamento legado; vale para etapas em andamento). Mostrar aviso no Dialog quando a etapa tiver `proposal_stages` não concluídas.
- **Excluir etapa**: soft delete; deixa de entrar em novas timelines (`ProposalTimelineService::instantiate` usa só ativas); timelines existentes continuam exibindo a etapa (`withTrashed`). Excluir não reordena as demais.
- Prazos: `completion_deadline_hours` alimenta o indicador de prazo do dashboard; `alert_deadline_hours` (> 0) alimenta o command `proposals:alert-overdue-stages` (ambos no PRD de Propostas).
- Listagem: ordem, nome, data (Sim/Não, “obrigatória”), upload (idem), conclusão (h), alerta (h), em uso.
- Seed: `StageSeeder` a partir de `database/seeders/EtapaSeeder.php` / base de produção, com **`trim` nos nomes** (o seeder legado grava espaços e tabs à esquerda — B-M3) e normalizando a etapa “Saque de FGTS” (`has_date = 0` e `date_required = 1` → decidir com o usuário qual vale). Ativas hoje, por ordem: 1 Cadastro/Aprovação, 2 Solicitação de Engenharia, 3 Saque de FGTS, 4 Entrevista, 5 Conformidade, 6 Assinatura de Escritura, 7 Envio para Registro, 8 Finalização. Marcar `shows_property_data` na etapa 2 e `shows_registry_protocol` na etapa 7 (equivalente ao comportamento fixo do legado).

### A6.8 Serviços (`billable_services`)
Idêntico ao PRD da calculadora §5.11:
- Campos: `name` obrigatório `max:191`; `description` obrigatório (“Pequena descrição”); `price` obrigatório `integer|min:0` em **centavos** via `MoneyInput` (o legado converte string com `Utils::format_coin_sql` e grava `double` — B-M5); `generates_receipt` Switch “Gerar recibo”, default desligado.
- Listagem: serviço, descrição, valor (`formatMoney`), gera recibo, em uso.
- Efeitos: preço de tabela sugerido no orçamento (pode ser negociado no pivot `quote_billable_service.amount`); `generates_receipt` copiado para a linha de custo gerada (`proposal_cost_items.generates_receipt`). Alterar o preço **não** altera orçamentos/propostas existentes.
- Seed: via migração de dados (5 registros), `price = (int) round(valor * 100)`.

## A7. Arquitetura de arquivos no sistema novo
- **Enums**: `app/Enums/BrazilianState.php` (novo). `ReceiptType`, `UserRole` vêm do PRD de Propostas — se este módulo for implementado antes, **criar aqui** com os casos definidos lá.
- **Models** (+ factories): `Bank`, `Notary`, `ContractType`, `CostType`, `PropertyType`, `Development`, `Stage`, `BillableService` — todos `SoftDeletes`, `$fillable` explícito sem `id`, `casts()` para booleans/enums/inteiros.
- **Seeders**: `BankSeeder`, `ContractTypeSeeder`, `CostTypeSeeder`, `StageSeeder` (valores da A6). `NotarySeeder`, `PropertyTypeSeeder`, `DevelopmentSeeder`, `BillableServiceSeeder` só se o sistema novo não for receber a migração de dados do legado.
- **Service**: `StageService` (`create`, `update`, `move` — reordenação em transação). Demais CRUDs sem Service (A2).
- **Controllers** (`app/Http/Controllers/Catalogs/`): `BankController`, `NotaryController`, `ContractTypeController`, `CostTypeController`, `PropertyTypeController`, `DevelopmentController`, `StageController` (+ `move`), `BillableServiceController` (mesmo nome do PRD da calculadora). Cada um: `index`, `store`, `update`, `destroy`, `restore`.
- **Form Requests** (`app/Http/Requests/Catalogs/`): `Store/UpdateBankRequest`, `Store/UpdateNotaryRequest`, `Store/UpdateContractTypeRequest`, `Store/UpdateCostTypeRequest`, `Store/UpdatePropertyTypeRequest`, `Store/UpdateDevelopmentRequest`, `Store/UpdateStageRequest`, `Store/UpdateBillableServiceRequest` (mesmos nomes do PRD da calculadora). Store e Update podem compartilhar regras via trait/método base; o que muda é o `ignore()` do unique.
- **Autorização**: Gate `manage-catalogs` em `AppServiceProvider` (ou onde o sistema novo registra Gates).
- **Rotas** (`auth` + `can:manage-catalogs`):
  ```php
  Route::middleware(['auth', 'can:manage-catalogs'])->group(function () {
      Route::resource('banks', BankController::class)->only(['index', 'store', 'update', 'destroy']);
      Route::patch('banks/{bank}/restore', [BankController::class, 'restore'])->withTrashed()->name('banks.restore');
      // idem: notaries, contract-types, cost-types, property-types, developments, stages, billable-services
      Route::patch('stages/{stage}/move', [StageController::class, 'move'])->name('stages.move'); // direction=up|down
  });
  ```
  Se o sistema novo agrupa cadastros sob prefixo (ex.: `/settings`), seguir a convenção dele mantendo os nomes das rotas.
- **Front (Vue 3 + Inertia 2 + shadcn-vue)**:
  - Pages: `catalogs/banks/Index.vue`, `catalogs/notaries/Index.vue`, `catalogs/contract-types/Index.vue`, `catalogs/cost-types/Index.vue`, `catalogs/property-types/Index.vue`, `catalogs/developments/Index.vue`, `catalogs/stages/Index.vue`, `catalogs/billable-services/Index.vue` (seguir a convenção de pasta do sistema novo — `pages` vs `Pages`). O PRD da calculadora cita `BillableServices/{Index,Create,Edit}.vue`: usar **um** dos dois padrões para todos os catálogos, preferindo Dialog (Index só).
  - Components (`components/catalogs/`): `CatalogTable.vue` (DataTable paginada + busca + “Mostrar excluídos” + ações editar/excluir/restaurar, genérico por colunas), `CatalogFormDialog.vue` (casca do Dialog com `useForm`), `DeleteConfirmDialog.vue`, `StateSelect.vue` (UFs), `StageRequirementFields.vue` (Switches dependentes). Reutilizar `MoneyInput.vue` (compartilhado com Propostas/Calculadora).
  - Menu “Cadastros” com os 8 itens, visível só para Admin.

## A8. Fases sugeridas
1. Enums (`BrazilianState`; `ReceiptType`/`UserRole` se ainda não existirem), migrations, models, factories, seeders, Gate.
2. CRUDs simples (bancos, tipos de contrato, empreendimentos, tipos de custo, tipos de imóvel, serviços) + `CatalogTable`/`CatalogFormDialog` + testes.
3. Cartórios (UF, CEP) + testes.
4. Etapas (`StageService`, regras cruzadas, reordenação) + testes.
5. Conferir as FKs/`withTrashed` descritas na A4 ao implementar Propostas e Calculadora.
6. (Opcional) Migração de dados do legado: preservar ids; `trim` em nomes; `excluido = 1` → `deleted_at = now()`; `relacionar_cartorio` null → `false`; custos 3/5 → `receipt_type`; contrato 4 → `requires_financing = false`; `servicos.valor` → centavos; `cartorios.municipio` → `city` quando `cidade` vazio *(confirmar)*.

## A9. Critérios de aceite
- Toda rota do módulo exige `auth` + `can:manage-catalogs`; testes cobrem **403** para cada papel não-Admin.
- Nenhum `fill($request->all())`; nenhum `id` em `$fillable`; toda validação em Form Request.
- Nenhum id mágico de contrato ou custo em código (buscar por `contract_type_id == ` / `cost_type_id == `).
- Excluir qualquer catálogo em uso **não** altera nem quebra propostas, orçamentos, linhas de custo ou timelines existentes (teste por catálogo: cria consumidor, exclui, abre o consumidor).
- Restaurar funciona; criar com nome de registro excluído sugere restaurar.
- Regras cruzadas de etapa validadas no backend (testes para cada combinação inválida).
- `billable_services` e demais nomes compartilhados batem com a tabela da A0 (nenhuma tabela/classe duplicada entre os três PRDs).
- Listagens paginadas no servidor e sem N+1 (`withCount` para “Em uso”).
- Feature tests verdes para as regras da A6.

---

# Parte B — Defeitos do legado (não reproduzir)

## 🔴 Crítico
**B-C1. Autorização só “de fachada”** — `app/Http/Middleware/Admin.php:24-27` e todos os controllers do módulo (ex.: `app/Http/Controllers/BancoController.php:21`)
- O middleware `admin` retorna `$next($request)` nos dois caminhos (não bloqueia — mesmo achado C1 do code review de Propostas). A proteção real é um `if (acesso_id != 1) return view('app.acesso-negado')` repetido em cada método, que responde **200** e, em `store`/`update`/`destroy`, depende de o desenvolvedor não esquecer a linha. As Policies `EtapaPolicy`, `TipoImovelPolicy`, `BancoPolicy`, `CartorioPolicy`, `CustoPolicy` são stubs vazios e não estão registradas.
- Novo: Gate `manage-catalogs` no grupo de rotas, resposta 403 (A3).

**B-C2. Mass assignment e PK alterável** — `app/Repositories/BancoRepository.php:37`, `CartorioRepository.php:44`, `ContratoRepository.php:34`, `CustoRepository.php:37`, `EmpreendimentoRepository.php:34`, `EtapaRepository.php:48`, `TipoImovelRepository.php:42`
- Todo `update` faz `$model->fill($request->all())`, e `id` está em `$fillable` de `Banco` (`app/Models/Banco.php:13`), `Contrato` (`app/Models/Contrato.php:13`) e `TipoImovel` (`app/Models/TipoImovel.php:15`). Um PATCH com `id=…` troca a chave primária, quebrando propostas que apontam para o registro.
- Novo: `$request->validated()` e `$fillable` sem `id`.
```php
// antes
$banco->fill($request->all());
$banco->save();
// depois
$bank->update($request->validated());
```

**B-C3. Excluir empreendimento apaga dados de imóvel das propostas** — `database/migrations/2023_04_26_105900_create_empreendimento_propostas_table.php:21`
- `empreendimento_id` tem `onDelete('cascade')` e o `destroy` faz exclusão física: remover um empreendimento apaga as linhas de `empreendimento_propostas` (endereço, tipo, bloco, unidade) de todas as propostas vinculadas.
- Novo: soft delete + FK `restrictOnDelete` (A4).

## 🟠 Alto
**B-A1. Exclusão física com FK: erro 500 ou órfãos**
- `bancos` e `custos` referenciados por FK (`propostas.banco_id`, `pagamentos_taxas.custo_id`, `contas_a_pagar/receber.custo_id`): `BancoRepository.php:51` / `CustoRepository.php:51` chamam `delete()` sem tratamento → exceção de integridade → 500. `CartorioService` engole a exceção e mostra mensagem genérica.
- `propostas.contrato_id` e `empreendimento_propostas.tipo_do_imovel` **não têm FK**: excluir contrato ou tipo de imóvel deixa propostas apontando para ids inexistentes.
- Novo: soft delete em tudo + FKs com `restrictOnDelete` (A4).

**B-A2. Números mágicos no lugar de atributos do catálogo**
- `app/Http/Controllers/PropostaController.php:196` — `if ($request->contrato_id != 4)` decide se valida dados de financiamento. Novo: `contract_types.requires_financing`.
- `resources/views/app/proposta/_components/tab_pagamentos_e_taxas.blade.php:146` — `@switch($pt->custo_id) case 3 (Assessoria) / case 5 (Motoboy)` decide o recibo. Novo: `cost_types.receipt_type`.
- Consequência hoje: renomear/recadastrar esses registros muda a regra silenciosamente.

**B-A3. Código de etapas por banco quebrado e XSS** — `app/Http/Controllers/EtapaController.php:187` (`listTableSteps`), `app/Repositories/EtapaRepository.php:105,138`, `app/Services/EtapaService.php:128-155`
- `findByBancoId` / `getBancoIdById` consultam `etapas.banco_id`, que **não existe** (a coluna foi comentada na migration) → erro SQL se a rota `/v1/etapa/lista-etapas/{id}` for chamada (o select que a chamava está comentado; `public/js/utils/etapa-ajax.js` ficou morto).
- `showTableSteps` monta a tabela HTML inteira em string no Service, concatenando `$etapa->nome` e `$nome_banco->nome` sem `e()` → XSS armazenado.
- `EtapaController.php:139`: no ramo “etapa não existe” acessa `$etapa->banco_id` com `$etapa === null` → erro.
- `EtapaRepository::stepsStartedNotCompleted` (`:116-120`) usa `proponentes.proposta_id` e `propostas.nome`, que não existem.
- Novo: etapas globais, sem `banco_id`; nada de HTML em Service; remover o código morto.

## 🟡 Médio
**B-M1. Flags de etapa sem validação cruzada** — `app/Models/Etapa.php` (`rules()` só exige `nome` e `ordem`)
- A base tem a etapa “Saque de FGTS” com `data = 0` e `data_required = 1` (campo obrigatório que não é exibido). Nada impede `upload_required` sem `upload` nem alerta maior que o prazo de conclusão. Novo: regras cruzadas da A6.7.

**B-M2. Coluna `excluido` nunca usada** — `bancos`, `contratos`, `empreendimentos`
- Gravada como 0 e nunca lida; a exclusão é física. Novo: `softDeletes`.

**B-M3. Dados sujos na origem** — `database/seeders/EtapaSeeder.php` e base de produção
- Nomes de etapas com espaço/tab à esquerda (o seeder faz `explode(',')` sem `trim`); “Itáu” no `BancoSeeder`; tipos de imóvel duplicados/com typo. Novo: `trim` em `prepareForValidation` e nos seeders; revisão manual dos tipos de imóvel (A6.5).

**B-M4. Flags de tipo de imóvel ignoradas** — `public/js/proposta/troca-tipo-imovel.js`, `resources/views/app/proposta/_components/inputs_informacoes.blade.php:386`
- O admin configura número/complemento/unidade/bloco, mas a tela de proposta só lê `data-empreendimento` e mostra um bloco fixo de campos. Novo: honrar as cinco flags (A6.5, *confirmar*).

**B-M5. Valor de serviço em `double` com parse de string** — `database/migrations/2025_03_21_100458_create_servicos_table.php`, `app/Http/Requests/ServicoRequest.php:36`
- `double(11,2)` + `Utils::format_coin_sql` no `prepareForValidation` (validação `required` roda sobre o valor já convertido; string inválida vira 0/erro de cast). Novo: centavos inteiros, `MoneyInput` envia inteiro (A6.8).

**B-M6. Cartório com campos duplicados e validação arbitrária** — `database/migrations/2021_10_25_165725_create_cartorios_table.php`, `app/Models/Cartorio.php:29`
- `cidade` (obrigatória) e `municipio` (opcional) guardam a mesma informação; `estado` é `string(2)` livre no banco (o select de 27 UFs só existe na view); CEP sem máscara/normalização; `nome` exige `min:10`. Novo: `city`, enum `BrazilianState`, CEP só dígitos, `min:3` (A6.2).

**B-M7. Contratos e Empreendimentos via AJAX com tratamento de erro quebrado** — `app/Http/Controllers/ContratoController.php:68,119`, `app/Http/Controllers/EmpreendimentoController.php:69,120`, `public/js/contratos/*`, `public/js/empreendimento/*`
- O Service devolve a própria `Exception` em caso de erro; como objeto é *truthy*, o controller responde “cadastrado/atualizado com sucesso” mesmo quando falhou, e o ramo de erro (`$x->getMessage()`) é inalcançável. O JSON vem com HTTP 200 e `status` no corpo; `delete-contrato.js:25` recarrega a página por `setTimeout` antes de a requisição terminar. Validação (`$request->validate`) em requisição AJAX `x-www-form-urlencoded` devolve redirect 302 em vez de 422. Os demais cadastros usam form + redirect — dois padrões para a mesma coisa.
- Novo: todos via Inertia `useForm` + redirect com flash (A6.0).

## ⚪ Baixo
**B-B1. `ordem` sem unicidade nem desempate** — `app/Repositories/EtapaRepository.php:82`
- `orderBy('ordem')` sem segundo critério; com ordens repetidas (existem entre as excluídas) a sequência da timeline fica indefinida. Novo: `order` único entre ativas + reordenação (A6.7).

**B-B2. Regras de validação no Model + classes `*Validate`** — `app/Models/*::rules()/feedback()`, `app/Validates/AbstractValidate.php`
- `verifyPatch` recria validação parcial a partir de `rules()` do model; `create()` e `show()` de vários controllers são vazios ou devolvem JSON sem uso. Novo: Form Requests e `resource()->only(...)`.

**B-B3. Artefatos órfãos** — `app/Models/EtapasDefault.php` (tabela nunca criada: migration `2023_03_13_113754` comentada), `app/Models/Tabela.php` / `tabelas` (vira enum `AmortizationTable`), `Banco::etapas()`, `ContratoRepository::getNameContrato` e `EmpreendimentoRepository::getNameEmpreendimento` (selecionam coluna `nome` inexistente, sem uso). Não portar.

**B-B4. Banco por nome em contas bancárias (fora do escopo)** — `conta_bancarias.banco` (string, parte do `unique`) e `conta_bancos.banco` (string)
- Renomear um banco não reflete nessas contas. Registrar para quando o módulo financeiro e as contas de proponente/vendedor forem portados (PRD de Propostas já define `bank_accounts.bank_name` como texto).

---

## Pontos a confirmar com o usuário
1. **Tipos de imóvel**: honrar as cinco flags individualmente (recomendado) ou manter a regra simplificada do PRD de Propostas (empreendimento → empreendimento/bloco/unidade; senão → número/complemento)?
2. **Cartórios**: fundir `municipio` em `city` (recomendado) ou manter os dois?
3. **Etapas**: catálogo global (como hoje) ou voltar a ter etapas por banco?
4. **Etapa “Saque de FGTS”**: a data é exibida e obrigatória, ou nenhum dos dois?
5. **Contrato “Cartório de Notas”** exige dados de financiamento? **Custos “Registro”/“Matrícula”** exigem cartório?
6. **Analista** tem acesso de leitura aos cadastros?
7. **Tipos de imóvel duplicados** (“CASA” ×2 etc.): fundir na migração de dados?
