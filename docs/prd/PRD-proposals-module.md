# PRD — Módulo de Propostas (Proposals)

> Documento para o agente que vai implementar o módulo no sistema novo (PHP 8.5, Laravel 13, Vue 3 + Inertia 2, Tailwind v4 + shadcn-vue). Origem: módulo `propostas` do sistema asdocumentacoes-lv. Referências "B-xx" apontam para [`CODE-REVIEW-proposals-module.md`](CODE-REVIEW-proposals-module.md).

## A0. Documentos relacionados e ordem de implementação

Este módulo tem um módulo irmão já especificado: **Calculadora de Emolumentos + Orçamento**.

| Documento | Conteúdo |
|---|---|
| [`prd-modulo-calculadora-emolumentos.md`](prd-modulo-calculadora-emolumentos.md) | PRD da calculadora (API de emolumentos, ITBI), serviços cobráveis, orçamento, vínculo com proposta e conversão de orçamento em proposta |
| [`correcoes-modulo-calculadora-emolumentos.md`](correcoes-modulo-calculadora-emolumentos.md) | Code review/correções da calculadora e do orçamento no legado |
| [`CODE-REVIEW-proposals-module.md`](CODE-REVIEW-proposals-module.md) | Code review deste módulo no legado |

**Leia os dois PRDs antes de começar.** Regras de convivência:

1. **Ordem**: implemente **este módulo (Propostas) primeiro**. O PRD da calculadora exige que a entidade de proposta já exista (“Não inventar a entidade proposta… se não houver, pare e pergunte”) e usa `proposals`, `banks`, arquivos da proposta e timeline.
2. **Fronteira de responsabilidade**:
   - **Este PRD é dono de**: `proposals`, proponentes, vendedores, parceiros, imóvel, timeline, documentos, **a tabela de linhas de custo da proposta (`proposal_cost_items`)**, recibos e PDFs da proposta.
   - **O PRD da calculadora é dono de**: `fee_calculations`, `fee_estimates`, `quotes`, `quote_billable_service`, `billable_services`, `itbi_*`, `CostItemsBuilder`, `AttachFeeEstimateToProposal`, `ConvertQuoteToProposal` e as telas de calculadora/orçamento.
3. **Nomes compartilhados — use exatamente estes** (definidos no PRD da calculadora; este PRD foi alinhado a eles):

   | Conceito | Tabela / classe | Observação |
   |---|---|---|
   | Linhas de custo da proposta | `proposal_cost_items` / `ProposalCostItem` | **uma tabela só** para linhas da calculadora e lançamentos manuais (ver A4) |
   | Tipo da linha | enum `CostItemType` | casos da calculadora (`Emolument`, `ExtraFee`, `Service`, `Itbi`) **+ `Manual`** (este módulo) |
   | Emolumento congelado da proposta | `fee_estimates` / `FeeEstimate` | `Proposal::feeEstimate(): HasOne` |
   | Serviços cobráveis | `billable_services` / `BillableService` | pivot `proposal_billable_service`; `Proposal::billableServices()` |
   | Estado civil | enum `MaritalStatus` (int: `Single=1`, `Married=2`, `Widowed=3`, `JudiciallySeparated=4`, `Divorced=5`) | reutilizar em `applicants`, `sellers` e `quotes` |
   | Edição de valor da linha | `ProposalCostItemController@update` — `PATCH proposals/{proposal}/cost-items/{costItem}` | mesma rota nos dois PRDs |
   | Dinheiro | `unsignedBigInteger` em **centavos** + `App\Support\Money` / `formatMoney()` (ver A2) | **sobrepõe** o `decimal(12,2)`/`decimal:2` do PRD da calculadora em todas as colunas monetárias: `proposal_cost_items.amount`, `billable_services.price`, `quote_billable_service.amount`, `fee_calculations.fees_total/itbi_amount`, `fee_estimates.fees_total/itbi_amount`. Alíquotas (`decimal:4`) continuam decimais. Valores vindos da API de emolumentos (reais com centavos) são convertidos para centavos ao gravar |

4. **Camadas**: neste módulo a regra fica em **Services** (sem Repository). O PRD da calculadora usa **Actions** (`CreateQuote`, `ConvertQuoteToProposal`, `AttachFeeEstimateToProposal`) — mantenha como lá está. Mas **as Actions da calculadora não podem duplicar regra de proposta**: `ConvertQuoteToProposal` deve criar a proposta, o proponente e a timeline chamando os Services deste módulo (`ProposalService::createFromQuote`, `ApplicantService::findOrCreateByCpf`, `ProposalTimelineService::instantiate`), e o PDF do orçamento entra como documento via `ProposalDocumentService`.
5. **Conflito entre os dois PRDs**: em nomes/tabelas compartilhados, vale a tabela acima; em regra de proposta, vale este PRD; em regra de cálculo/orçamento, vale o da calculadora. Se surgir conflito não coberto, **pare e pergunte**.

## A1. Objetivo
Portar o módulo de propostas de financiamento imobiliário/documentação (cadastro da proposta, proponentes, vendedores, parceiros, imóvel, acompanhamento por etapas, documentos, pagamentos/taxas/emolumentos, recibos e PDFs) para o sistema novo, corrigindo os defeitos listados na Parte B e nomeando tudo em inglês.

## A2. Regras gerais de implementação (para o agente)
- **Sem camada Repository.** Consultas e regras ficam em `app/Services/*`; queries reutilizáveis viram *local scopes* no Model. Controllers só orquestram (Form Request → Service → `Inertia::render`/redirect).
- **Nomes em inglês** para tabelas, colunas, classes, arquivos, rotas e componentes Vue. Textos de UI e mensagens de validação em **pt-BR**.
- **Laravel 13**: casts via método `casts()`, enums PHP *backed*, Form Requests para toda validação, Policies para toda autorização, e-mails `ShouldQueue`, agendamento em `routes/console.php`. Consultar `search-docs` (Boost) do sistema novo antes de usar APIs de L13/Inertia 2.
- **Antes de criar qualquer coisa, inspecionar o sistema novo**: se já existem `User`, papéis/perfis, `Bank`, `Notary`(cartório), `Development`(empreendimento), `PropertyType`, layout Inertia, convenção de pastas (`resources/js/pages` vs `Pages`), componentes shadcn já instalados, lib de PDF. Reutilizar; não duplicar. Não adicionar dependências sem aprovação.
- **Dinheiro em centavos (inteiro)**: toda coluna monetária é `unsignedBigInteger` com o valor em **centavos** (R$ 1.234,56 → `123456`), cast `integer`. Regras:
  - O nome da coluna **não** leva sufixo (`purchase_value`, `amount`); o PHPDoc/`casts()` e este PRD documentam que é centavos.
  - O front (`MoneyInput`) exibe a máscara pt-BR e **envia centavos inteiros**; o backend valida `integer|min:0` e nunca converte string formatada (nada de `Utils::format_coin_sql`).
  - Somas e totais em inteiro no banco (`sum('amount')`); nunca `float` no meio do caminho. Multiplicação por percentual (ex.: ITBI) → `(int) round($cents * $rate)` num único helper.
  - Exibição (Vue, PDF, e-mail) por um helper único: `App\Support\Money::format(int $cents): string` no PHP e `formatMoney(cents)` no front (`Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })` sobre `cents / 100`).
  - Migração de dados do legado: `(int) round($valor * 100)`.
- **Datas**: ISO no transporte; formatação pt-BR no front.
- **Arquivos**: disco **privado** (`local`/S3 privado), download via rota autenticada + Policy. Nunca no disco `public` (são RG, IRPF, CTPS — dados pessoais/LGPD).
- **Testes**: feature tests (PHPUnit ou Pest — seguir o que o sistema novo usa) para cada regra da seção A6.

## A3. Papéis (mapeamento do legado `acessos`)
Legado usa `info_users.acesso_id` com números mágicos. Criar/reutilizar enum `UserRole`:

| Legado id | Nome legado | `UserRole` |
|---|---|---|
| 1 | Administrador | `Admin` |
| 2 | Atendimento | `Attendant` |
| 3 | Parceiro | `Partner` |
| 4 | Cliente (proponente) | `Client` |
| 5 | Vendedor | `Seller` |
| 6 | Analista | `Analyst` |

Matriz de permissões (implementar em `ProposalPolicy`):

| Ação | Admin | Partner | Seller | Client | Analyst |
|---|---|---|---|---|---|
| Listar | todas | só as vinculadas a ele (`proposal_partner`) | só as que têm um `Seller` com `user_id` = ele | — (área do cliente, fora do módulo) | tratar como Admin somente leitura *(confirmar com o usuário)* |
| Ver (show) | ✔ | se vinculado ou criador | se vinculado | se for proponente da proposta (PDFs informativo/acompanhamento) | ✔ |
| Criar | ✔ | ✔ | — | — | — |
| Editar dados | ✔ | somente leitura (aba Documentos) | — | — | — |
| Excluir | ✔ | — | — | — | — |
| Acompanhamento/etapas, Particularidades, Pagamentos, Recibos | ✔ | — | — | anexar **comprovante** de pagamento da própria proposta | — |
| Imprimir lista/seleção | ✔ | lista | — | — | — |

## A4. Modelo de dados (sistema novo)

### Tabelas do módulo
| Tabela nova | Legado | Observações |
|---|---|---|
| `proposals` | `propostas` | ver colunas abaixo; `softDeletes` |
| `proposal_partner` | `parceiro_propostas` | pivot `proposal_id`, `user_id`, `unique(proposal_id,user_id)`, FKs `cascadeOnDelete` |
| `applicants` | `proponentes` | proponente (comprador) |
| `applicant_proposal` | `proponente_propostas` | pivot, `unique` + FKs |
| `sellers` | `vendedores` | vendedor do imóvel |
| `proposal_seller` | `proposta_vendedores` | pivot, `unique` + FKs |
| `bank_accounts` | `conta_bancos` + `banco_proponentes` + `banco_vendedores` | **polimórfica** `morphs('accountable')` (Applicant/Seller) |
| `proposal_properties` | `empreendimento_propostas` | 1:1 com proposta (`unique(proposal_id)`) |
| `stages` | `etapas` | catálogo de etapas (se o sistema novo ainda não tiver) |
| `proposal_stages` | `timelines` | etapas instanciadas por proposta |
| `proposal_documents` | `arquivos` | documentos |
| `proposal_cost_items` | `pagamentos_taxas` **+** `emolumento_pagamento_e_taxa` | **uma tabela só** (corrige bug de merge, ver B-H1). Nome e colunas-base vêm do PRD da calculadora (§6.2); este módulo **acrescenta** as colunas de lançamento manual (ver abaixo) |
| `receipts` | `recibos` | recibos |
| `contract_types` | `contratos` | catálogo; adicionar `requires_financing` (bool) |
| `cost_types` | `custos` | catálogo; manter `requires_notary` (legado `relacionar_cartorio`) |
| `proposal_billable_service` | `proposta_servico` | pivot definido no PRD da calculadora (§3) |
| `fee_estimates` | `emolumentos` | **criada pelo PRD da calculadora** (§6.2); aqui só o relacionamento `Proposal::feeEstimate()` |

Dependências externas ao módulo (reutilizar se existirem no sistema novo): `users`, `banks`(`bancos`), `notaries`(`cartorios`), `developments`(`empreendimentos`), `property_types`(`tipo_imoveis`). Do módulo da calculadora (ver A0): `billable_services`(`servicos`), `quotes`(`orcamentos`), `fee_estimates`(`emolumentos`). Integrações que referenciam `proposal_id` no legado: `protocolo_registros`, `contas_a_pagar`, `contas_a_receber` (fora do escopo; manter FK `nullable` quando forem portadas).

### `proposals` (mapeamento de colunas)
| Nova | Legado | Tipo |
|---|---|---|
| `creator_id` | `criador_id` (int sem FK) | `foreignId → users` |
| `analyst_id` | `analista_id` (int sem FK) | `foreignId → users` (default = creator) |
| `bank_id` | `banco_id` | `foreignId → banks` |
| `contract_type_id` | `contrato_id` (sem FK) | `foreignId → contract_types` |
| `amortization_table` | `tabela_id` (0/1/2, sem FK) | enum `AmortizationTable` (`Price`,`Sac`) nullable |
| `status` | `status_proposta_id` → tabela `status_propostas` | enum `ProposalStatus` (string), **indexado** |
| `property_condition` | `tipo_imovel` ('Novo'/'Usado') | enum `PropertyCondition` |
| `has_other_property` | `possui_imovel` | bool |
| `purchase_value` | `valor_compra` | `unsignedBigInteger` (centavos) |
| `down_payment_value` | `valor_entrada` | `unsignedBigInteger` (centavos) |
| `financing_value` | `valor_financiamento` | `unsignedBigInteger` nullable (centavos) |
| `expenses_value` | `valor_despesas` | `unsignedBigInteger` nullable (centavos) |
| `subsidy_value` | `valor_subsidio` | `unsignedBigInteger` nullable (centavos) |
| `financed_value` | `valor_financiado` | `unsignedBigInteger` nullable (centavos) |
| `intended_installment_value` | `valor_prestacao_pretendida` | `unsignedBigInteger` nullable (centavos) |
| `fgts_value` | `valor_fundo_garantia` | `unsignedBigInteger` nullable (centavos) |
| `uses_fgts` | `utiliza_fgts` | bool |
| `is_first_financing` | `p_financiamento` | bool |
| `finance_documentation_fee` | `financiar_taxa_doc` | bool |
| `documentation_fee_to_finance` | `valor_taxa_doc_a_financiar` | `unsignedBigInteger` nullable (centavos) |
| `payment_term` | `prazo_pagamento` (string 45) | `unsignedSmallInteger` meses *(confirmar se é sempre número)* |
| `declares_income_tax` | `utiil_ir` | bool |
| `declared_income` | `renda_info` | `unsignedBigInteger` nullable (centavos) |
| `expected_delivery_month` / `_year` | `previsao_mes` / `previsao_ano` | tinyint / year, nullable |
| `cancellation_reason` | `motivo_status_cancelado` | text nullable |
| `restriction_reason` | `motivo_status_restrito` | text nullable |
| `contract_notes`, `purchase_value_notes`, `down_payment_notes`, `fgts_notes`, `documentation_fee_notes`, `documentation_financing_notes`, `income_tax_notes`, `general_notes` | `obs_contrato`, `obs_valor_compra`, `obs_valor_entrada`, `in_obs_fgts`, `obs_taxa_doc`, `obs_financia_doc`, `obs_ir`, `obs_geral` | text nullable |
| `particularities` | tabela `particularidades` (1:1) | text nullable (vira coluna) |
| `finished_at` | `data_fin` (preenchido errado na criação) | datetime nullable, setado ao finalizar |
| `deleted_at`, timestamps | | |

Índices: `status`, `creator_id`, `created_at`. Remover o índice inútil legado `(id, valor_compra, valor_entrada, tipo_imovel)`.

### Demais tabelas (colunas-chave)
- **applicants**: `user_id` (nullable FK users, `nullOnDelete`), `name`, `cpf` (**unique** — o legado reaproveita o proponente pelo CPF, então a unicidade é regra), `birth_date` nullable, `marital_status` (unsignedTinyInteger → `MaritalStatus`), `profession`, `phone`, `email`, `family_income` e `declared_income` (`unsignedBigInteger` centavos, nullable), `declares_income_tax` bool, `income_tax_notes`, `by_power_of_attorney` bool.
- **sellers**: `user_id` nullable, `creator_id` nullable, `person_type` enum `PersonType` (`Individual`=PF, `Company`=PJ), `name` (nome/razão social), `document` (CPF/CNPJ), `marital_status` nullable (→ `MaritalStatus`), `profession` nullable, `declared_income` (`unsignedBigInteger` centavos, nullable), `declares_income_tax`, `income_tax_notes`, `email`, `phone` nullable, `by_power_of_attorney`.
- **bank_accounts**: `accountable_type/id`, `bank_name`, `account_type` enum `BankAccountType` (`Checking`=1, `Savings`=0 no legado), `branch`, `number`, `notes`.
- **proposal_properties**: `proposal_id` unique, `property_type_id` FK, `development_id` nullable FK, `address`, `number`, `complement`, `block`, `unit`. Regra de UI: se o tipo de imóvel é de empreendimento → mostra empreendimento/bloco/unidade; senão → número/complemento.
- **stages**: `order`, `name`, `has_date`, `date_required`, `has_upload`, `upload_required`, `title_required`, `notes_required`, `completion_deadline_hours`, `alert_deadline_hours`, `softDeletes`. Seed: o seeder legado `database/seeders/EtapaSeeder.php` (14 etapas, algumas soft-deleted).
- **proposal_stages**: `proposal_id` FK cascade, `stage_id` FK, `position` (cópia de `order`), `proposal_document_id` nullable, `date` nullable, `notes` nullable, `is_current` bool, `started_at` nullable, `completed_at` nullable; índice `(proposal_id, is_current)`. Substitui as flags legadas `etapa_atual/etapa_iniciada/concluido` (status derivado: `completed_at` ⇒ concluída; `started_at` ⇒ em andamento; senão bloqueada).
- **proposal_documents**: `proposal_id`, `uploaded_by` (FK users), `owner` enum `DocumentOwner`, `documentable_type/id` nullable (Applicant/Seller a quem o doc pertence — legado `pessoa`), `proposal_stage_id` nullable (legado `etapa`=1), `type` enum `DocumentType` nullable, `title`, `disk`, `path`, `original_name`, `mime_type`, `size`.
- **proposal_cost_items**:
  - Colunas-base (PRD da calculadora §6.2, **não renomear**): `proposal_id` FK cascade, `type` string(20) → `CostItemType`, `description`, `extra_fee_description`, `service_description`, `generates_receipt`, `amount` (**`unsignedBigInteger` centavos** — sobrepõe o `decimal(12,2)` do PRD da calculadora, ver A0), timestamps, índice `(proposal_id, type)`.
  - Colunas acrescentadas por este módulo (todas `nullable`, usadas quando `type = Manual`): `cost_type_id` FK `cost_types`, `notary_id` FK `notaries`, `date`, `notes`, `bill_path` (boleto, legado `anexo_doc`), `proof_path` (comprovante, legado `anexo_comp`).
  - Quem implementar primeiro cria a tabela com **todas** as colunas; o outro módulo reaproveita (o PRD da calculadora já prevê isso na nota do §6.2).
- **receipts**: `proposal_id`, `proposal_cost_item_id` nullable FK (legado `pagamentos_taxas_id` default 0 sem FK), `type` enum `ReceiptType` (`General`, `Advisory`=Assessoria, `Courier`=Motoboy), `name`, `document` (CPF/CNPJ), `registration_number` (matrícula), `notary_name`, `total_spent` e `amount_deposited` (`unsignedBigInteger` centavos), `date`.

### Relacionamentos Eloquent (`Proposal`)
`creator()`, `analyst()`, `bank()`, `contractType()` → BelongsTo; `partners()` → BelongsToMany(User, `proposal_partner`); `applicants()`, `sellers()` → BelongsToMany; `billableServices()` → BelongsToMany(BillableService, `proposal_billable_service`); `property()` → HasOne(ProposalProperty); `feeEstimate()` → HasOne(FeeEstimate); `stages()` → HasMany(ProposalStage)->orderBy('position'); `currentStage()` → HasOne(ProposalStage)->where('is_current', true); `documents()`, `costItems()`, `receipts()` → HasMany. `Applicant`/`Seller`: `proposals()` BelongsToMany, `bankAccount()` MorphOne, `user()` BelongsTo. Scopes: `Proposal::scopeVisibleTo(User $user)`, `scopeWithStatus(ProposalStatus)`.

## A5. Enums (`app/Enums`)
- `ProposalStatus` (string): `New`(1 “Nova Proposta”), `InProgress`(2 “Em Andamento”), `AwaitingProperty`(3 “Aguardando Imóvel”), `Canceled`(4 “Cancelada”), `Finished`(5 “Finalizada”), `Restricted`(6 “Com Restrição”). Métodos `label()`, `color()` (badge), `legacyId()` para migração de dados; `manuallySelectable()` → [InProgress, AwaitingProperty, Canceled, Restricted].
- `UserRole` (ver A3), `PropertyCondition` (`New`,`Used`), `AmortizationTable` (`Price`,`Sac`), `PersonType` (`Individual`,`Company`), `BankAccountType`, `DocumentOwner` (`Buyer`,`Seller`,`Property`,`Stage`,`General`), `DocumentType` (lista abaixo, com `label()` e `isRequiredFor(DocumentOwner, ?PersonType)`), `ReceiptType`.
- **Compartilhados com a calculadora** (ver A0; não criar versões paralelas): `CostItemType` — casos da calculadora `Emolument`, `ExtraFee`, `Service`, `Itbi` **+ `Manual`** (“Lançamento manual”) adicionado por este módulo; `MaritalStatus` (int, valores do legado).
- `DocumentType` inclui também `FeeEstimate` (“Emolumento”) — usado pelo PDF do orçamento anexado na conversão (PRD da calculadora §5.9, passo 7).
- `DocumentType` — obrigatórios marcados com *:
  - Comprador: RG/CNH*, Certidão estado civil*, Comprovante de endereço*, Documento de renda (3 últimos)*, IRPF, CTPS*, Simulação, Outros.
  - Vendedor PF: RG/CNH*, Certidão estado civil*, Endereço, Dados conta crédito*, Matrícula*, Capa IPTU*, Outros.
  - Vendedor PJ: Matrícula*, ART*, SCPO*, Habite-se*, Alvará*, Outros.
  - Imóvel: Dados conta crédito*, Matrícula*, Capa IPTU*, Outros.

## A6. Regras de negócio

### Criação (`ProposalService::create`)
1. Formulário único (Inertia `useForm`) com: dados da proposta, **lista de proponentes** e **lista de vendedores** montadas no front (substitui o vai-e-vem de sessão do legado: rotas `create-/update-/delete-proponentes|vendedores`), parceiros (multi-select de usuários `Partner`), criador (select Admin/Analyst), analista (default = criador), imóvel opcional.
2. Busca de proponente por CPF (`GET /applicants/lookup?cpf=`): se existir, preenche e marca como existente (reaproveita, inclusive conta bancária).
3. Validação (`StoreProposalRequest`):
   - Sempre: `creator_id`, `bank_id`, `contract_type_id`, `purchase_value`, `down_payment_value` obrigatórios (`integer|min:0`, centavos; idem para todo campo monetário); `applicants` com ao menos 1 item.
   - Se `contractType.requires_financing` (legado: todo contrato ≠ id 4 “AQUISIÇÃO À VISTA COM FGTS”): também `amortization_table` e `payment_term`.
   - Proponente novo: `name` min:3, `cpf` (CPF válido, único entre novos), `email` email, `phone`, `declared_income`, `marital_status` (`Rule::enum(MaritalStatus::class)`), `profession`.
   - Vendedor: `name` min:3, `email` email; `document` CPF/CNPJ conforme `person_type`.
4. Em **uma transação**: cria a proposta com `status = New`; para cada proponente chama `ApplicantService::findOrCreateByCpf` (novo → `User` papel `Client` + `Applicant` + `BankAccount`; existente → só anexa) — o mesmo método usado pela conversão de orçamento (A0); cria vendedores + contas; anexa parceiros; cria `ProposalProperty` se informado; instancia a timeline (A6 “Acompanhamento”).
5. **Depois do commit** (`DB::afterCommit`/listener): e-mails **em fila** — usuário novo recebe e-mail de boas-vindas com **link de definição de senha** (não senha em texto); proponente existente recebe aviso de nova proposta.

### Edição (`ProposalService::update`)
- Só Admin edita dados; Partner vê a tela em modo leitura (aba Documentos ativa).
- Sincroniza parceiros (`sync`), faz upsert do imóvel, atualiza campos via `$request->validated()` (nunca `$request->all()`).
- Status manual (select só com `ProposalStatus::manuallySelectable()`; `Finished` só aparece se já estiver finalizada):
  - `AwaitingProperty` exige `expected_delivery_month/year` (MM/AAAA).
  - `Canceled` exige `cancellation_reason`; `Restricted` exige `restriction_reason`.
  - `Finished` mostra a data de finalização (`finished_at`).
- Aba **Particularidades**: texto livre (coluna `particularities`), salvo pelo Admin.

### Exclusão
- Soft delete da proposta (Admin). **Não** excluir usuários dos proponentes (o legado exclui — ver B-C6).

### Acompanhamento / timeline (`ProposalTimelineService`)
- **Instanciar**: uma `proposal_stage` por etapa ativa (não soft-deleted) ordenada por `order`; a primeira com `is_current = true`, nenhuma iniciada.
- **Iniciar acompanhamento** (Admin): `started_at = now()` na etapa atual; se status = `New` → `InProgress`; e-mail “acompanhamento iniciado” para proponentes (cópia oculta ao criador).
- **Concluir etapa** (Admin, `CompleteProposalStageRequest` com regras dinâmicas pela etapa): `date` se `date_required`; arquivo se `upload_required`; título se `title_required`; observação se `notes_required`. Salva documento (`owner = Stage`), marca `completed_at`, `is_current=false`; a próxima etapa (por `position`, não por `id`) vira `is_current` com `started_at = now()`; e-mail “etapa X concluída”.
- **Última etapa concluída**: status → `Finished`, `finished_at = now()`, e-mail “todas as etapas finalizadas”; se a proposta tem vendedores e faltam documentos obrigatórios do vendedor (checagem por conjunto, ver B-M3) → e-mail “falta de documentos do vendedor” ao criador.
- **Editar etapa concluída**: permite substituir o arquivo/dados.
- **Restaurar acompanhamento** (Admin, com confirmação): apaga etapas e documentos de etapa (inclusive arquivos no disco), reinstancia e já inicia a primeira.
- Na etapa de posição 2 exibir dados do imóvel; na etapa 7 exibir o protocolo de registro (se o módulo existir) — tornar isso configurável por etapa em vez de posição fixa.
- **Alerta de prazo** (command `proposals:alert-overdue-stages`, agendado seg–sex 07:00): para etapas em andamento cujo `now() - started_at` em horas ≥ `alert_deadline_hours` (> 0), envia e-mail ao criador e proponentes com o nome da etapa.
- Indicador de prazo (dashboard): `completion_deadline_hours - horas decorridas`; 0 = vence hoje, < 0 = atrasada.

### Documentos (`ProposalDocumentService`)
- Aba Documentos com três blocos: **Comprador** (um por proponente), **Vendedor** (um por vendedor; tipos dependem de PF/PJ), **Imóvel**; mais upload múltiplo geral (Admin).
- Validação: `mimes:pdf,doc,docx,jpg,jpeg,png,bmp,zip`, tamanho máximo definido em config (legado diz 3 MB mas valida 300 MB — decidir, sugerido 20 MB).
- Mostrar checklist de obrigatórios por pessoa (tipos já enviados vs obrigatórios).
- Partner só vê documentos com `owner` definido; download sempre por rota autorizada.

### Pagamentos e taxas (`ProposalCostItemService`)
- Lançamento manual (Admin) → linha `type = Manual`: tipo de custo (Registro, ITBI, Assessoria, Matrícula, Motoboy, Outros), cartório (habilitado quando `cost_type.requires_notary`), rubrica (`description`), data, valor, observação, boleto anexo; opção de notificar **parceiro** e/ou **proponente** por e-mail (fila).
- Linhas vindas da calculadora (`Emolument`, `ExtraFee`, `Service`, `Itbi`) são geradas pelo `CostItemsBuilder` do PRD da calculadora (§5.10) — **este módulo não gera essas linhas**, só as exibe, edita o valor e as usa em recibos.
- Editar valor (modal, `PATCH proposals/{proposal}/cost-items/{costItem}`, *scoped binding*), excluir (só `Manual`; linhas da calculadora só têm o valor editável), e **cliente anexa comprovante** (`proof_path`) somente da própria proposta.
- Total de emolumentos = soma de `amount` onde `type ∈ {Emolument, ExtraFee}` (mesma regra do PRD da calculadora §5.10).
- Checkbox “gerar recibo” por linha: Assessoria/Motoboy (manuais, pelo `cost_type`) ou `generates_receipt = true` (serviços).
- Aba lista todas as linhas numa única query (`$proposal->costItems()->with(['costType','notary'])`) — sem `merge` de coleções.

### Recibos (`ReceiptService` + PDFs)
- `General`: nome, CPF, matrícula, total gasto, valor depositado, data → PDF com todas as cobranças, total de emolumentos e proponentes.
- `Advisory` (Assessoria): vinculado a uma cobrança; nome, CPF/CNPJ, matrícula, cartório, data → PDF.
- `Courier` (Motoboy): vinculado a uma cobrança → PDF.
- Listagem por tipo, exclusão (Admin).

### Listagens e PDFs
- **Uma** rota `GET /proposals?status=&search=&page=` (substitui `novas-propostas`, `em-andamento`, etc.) com abas/filtro por status, **paginação server-side**, busca por nº/proponente. Contadores por status (1 query `groupBy status`) para as abas e o dashboard.
- Colunas: Nº (5 dígitos com zero à esquerda), data de cadastro, proponentes, criador (só Admin), condição do imóvel, valor de compra, status (badge), etapa atual (ou “Não iniciada”), ações por permissão.
- PDFs (`ProposalPdfService`, lib de PDF já existente no sistema novo): **informativo**, **acompanhamento**, **lista** (A4 paisagem, por status) e **seleção** (IDs marcados na tabela), recibos. Lista grande → gerar em Job e notificar/baixar.

### Integração com Calculadora/Orçamento (especificada no outro PRD)
Os fluxos legados `GeraPropostaService` (orçamento → proposta) e `EmolumentoPropostaService` (vincular emolumento a proposta existente) **estão especificados no PRD da calculadora** (§5.8 e §5.9) e não devem ser reimplementados aqui. O que este módulo precisa **expor** para eles:

| Ponto de integração | Onde | Contrato |
|---|---|---|
| Criar proposta a partir de orçamento | `ProposalService::createFromQuote(Quote $quote, User $actor): Proposal` | Cria com defaults (banco do orçamento, `status = New`, criador = `$actor`, valores zerados, `property_condition = Used`, contrato padrão configurável), anexa o proponente e instancia a timeline. **Não** gera linhas de custo (o `CostItemsBuilder` faz) nem envia e-mail (a Action faz após commit). Roda dentro da transação da Action. |
| Proponente pelo CPF | `ApplicantService::findOrCreateByCpf(array $data): Applicant` | Reaproveita pelo CPF; se novo, cria `User` (papel `Client`, senha inutilizável) + `Applicant`. Mesmo método usado na criação manual (A6). |
| Timeline | `ProposalTimelineService::instantiate(Proposal $proposal): void` | Mesma regra da A6. |
| PDF do orçamento como documento | `ProposalDocumentService::storeGenerated(Proposal, string $contents, DocumentType::FeeEstimate, string $title)` | Grava no disco privado, `owner = General`. |
| Seleção de proposta para vincular cálculo | `Proposal::scopeWithoutFeeEstimate()` + busca server-side | Usado pelo Combobox do §5.8 da calculadora. |
| Exibição na proposta | aba Pagamentos e Taxas + alerta na tela de edição | Mostrar as linhas da calculadora; se a proposta veio de orçamento, link para o `Quote`. |

Testes deste módulo cobrem esses métodos isoladamente; os testes de ponta a ponta (orçamento → proposta) ficam com o módulo da calculadora.

## A7. Arquitetura de arquivos no sistema novo
- **Enums**: `app/Enums/{ProposalStatus,UserRole,PropertyCondition,AmortizationTable,PersonType,BankAccountType,DocumentOwner,DocumentType,ReceiptType}.php`. Compartilhados com a calculadora (criar uma vez só, por quem vier primeiro): `CostItemType` (com `Manual`), `MaritalStatus`.
- **Models**: `Proposal`, `Applicant`, `Seller`, `BankAccount`, `ProposalProperty`, `Stage`, `ProposalStage`, `ProposalDocument`, `ProposalCostItem` (compartilhado — ver A0), `Receipt`, `ContractType`, `CostType` (+ factories e seeders: `StageSeeder`, `ContractTypeSeeder`, `CostTypeSeeder`). `FeeEstimate`, `BillableService` e `Quote` vêm do PRD da calculadora.
- **Services**: `ProposalService` (create/createFromQuote/update/delete/changeStatus), `ProposalQueryService` (listagem, contadores, visibilidade), `ApplicantService` (findOrCreateByCpf/attach/lookup), `SellerService`, `ProposalTimelineService` (instantiate/start/complete/restore/overdue), `ProposalDocumentService` (store/storeGenerated/download/delete), `ProposalCostItemService` (lançamento manual, valor, comprovante), `ReceiptService`, `ProposalPdfService`.
- **Controllers** (`app/Http/Controllers/Proposals/`): `ProposalController` (resource), `ProposalTimelineController` (`start`, `complete`, `restore`), `ProposalDocumentController` (`store`, `download`, `destroy`), `ProposalCostItemController` (`store`, `update`, `destroy`, `uploadProof` — o `update` é o mesmo citado no PRD da calculadora), `ReceiptController`, `ProposalPdfController`, `ApplicantLookupController`.
- **Form Requests**: `StoreProposalRequest`, `UpdateProposalRequest`, `CompleteProposalStageRequest`, `StoreProposalDocumentRequest`, `StoreProposalCostItemRequest`, `UpdateProposalCostItemRequest` (mesmo nome do PRD da calculadora), `UploadCostItemProofRequest`, `StoreReceiptRequest`.
- **Policy**: `ProposalPolicy` (`viewAny`, `view`, `create`, `update`, `delete`, `manageTimeline`, `manageFinancial`, `uploadDocument`, `downloadDocument`, `print`).
- **Mail (queued)**: `ApplicantWelcomeMail`, `ProposalCreatedMail`, `ProposalTrackingStartedMail`, `ProposalStageCompletedMail`, `ProposalStagesFinishedMail`, `MissingSellerDocumentsMail`, `ProposalStageOverdueMail`, `ProposalCostItemCreatedMail`. (`ApplicantWelcomeMail` com link de definição de senha é o mesmo usado pela conversão de orçamento — não criar outro.)
- **Command**: `AlertOverdueProposalStagesCommand` + schedule.
- **Rotas** (nomeadas, `auth` + Policy): `proposals.*`, `proposals.timeline.start|complete|restore`, `proposals.documents.*`, `proposals.cost-items.*` (URI `proposals/{proposal}/cost-items/{costItem}`, igual ao PRD da calculadora), `proposals.receipts.*`, `proposals.pdf.info|tracking|list|selection|receipt`, `applicants.lookup`.
- **Front (Vue 3 + Inertia 2 + shadcn-vue)**:
  - Pages: `proposals/Index.vue` (Tabs de status + DataTable paginada + seleção p/ imprimir), `proposals/Create.vue`, `proposals/Edit.vue` (Tabs: Informações, Documentos, Particularidades, Acompanhamento, Pagamentos e Taxas, Recibos), `proposals/Show.vue`.
  - Components (`components/proposals/`): `ProposalForm.vue`, `ApplicantList.vue` + `ApplicantDialog.vue`, `SellerList.vue` + `SellerDialog.vue`, `BankAccountFields.vue`, `PropertyFields.vue`, `StatusFields.vue` (campos condicionais por status), `StageTimeline.vue` + `CompleteStageForm.vue`, `DocumentChecklist.vue` + `DocumentUpload.vue`, `CostItemTable.vue` + `CostItemDialog.vue`, `ReceiptDialog.vue`, `MoneyInput.vue` (máscara pt-BR, `v-model` em **centavos inteiros** — compartilhar com a calculadora; o PRD dela também pede input monetário).
  - Usar Inertia 2 *deferred props* para abas pesadas (documentos, cobranças) e `router.reload({ only: [...] })` após ações.

## A8. Fases sugeridas
1. Enums, migrations, models, factories, seeders, Policy.
2. `ProposalService` + `ApplicantService` + `SellerService` + CRUD (Index/Create/Edit/Show) + testes.
3. Timeline (instanciar/iniciar/concluir/restaurar) + e-mails + command de alerta + testes.
4. Documentos (storage privado, checklist) + testes.
5. Linhas de custo (`proposal_cost_items` com as colunas dos dois PRDs, lançamento manual) + recibos + PDFs + testes.
6. Pontos de integração da seção “Integração com Calculadora/Orçamento” (`createFromQuote`, `findOrCreateByCpf`, `storeGenerated`, `scopeWithoutFeeEstimate`) + testes.
7. **Depois**: implementar o [PRD da calculadora](prd-modulo-calculadora-emolumentos.md), reaproveitando `proposal_cost_items`, `CostItemType`, `MaritalStatus`, `MoneyInput` e os Services do passo 6.
8. (Opcional) Script de migração de dados do legado usando `ProposalStatus::legacyId()` e demais mapeamentos da A4 (valores monetários `(int) round($valor * 100)`).

## A9. Critérios de aceite
- Toda rota do módulo passa por Policy; testes cobrem acesso negado para cada papel.
- Nenhum `fill($request->all())`; nenhum número mágico de status/papel/contrato.
- Listagem paginada no servidor; sem N+1 (verificar com `Model::preventLazyLoading()` em dev).
- E-mails em fila e disparados após commit.
- Documentos inacessíveis sem login/permissão.
- Feature tests verdes para as regras da A6.
- Nenhuma tabela, enum ou classe duplicada entre este módulo e o da calculadora (checar a tabela de nomes compartilhados da A0).

