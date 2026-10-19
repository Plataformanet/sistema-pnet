# PRD central — Visão geral dos módulos e ordem de implementação

> **Comece por aqui.** Este documento é o ponto de entrada para o agente que vai portar os módulos do
> sistema asdocumentacoes-lv para o sistema novo. Ele explica o que cada módulo faz, em que ordem os
> PRDs devem ser seguidos e quem é dono de cada tabela. As regras detalhadas continuam nos PRDs de cada
> módulo — este documento não as substitui.

## 1. Propósito

- **Origem**: sistema asdocumentacoes-lv (Laravel legado, Blade + jQuery). Serve de **especificação de
  comportamento**, não de código a copiar.
- **Destino**: sistema novo em **Laravel 13 + Vue 3 + Inertia 2 + Tailwind v4 + shadcn-vue**.
- **Defeitos do legado não devem ser portados.** Cada módulo tem um documento (ou seção) que lista esses
  defeitos — leia junto com o PRD.

## 2. Ordem obrigatória de implementação

| # | PRD | Por que nesta posição |
|---|---|---|
| 1 | [`01-cadastros.md`](01-cadastros.md) | Cria os catálogos (bancos, cartórios, tipos, etapas, serviços) que viram **FKs** de Propostas e da Calculadora. |
| 2 | [`02-propostas.md`](02-propostas.md) | A Calculadora **exige** que a entidade `proposals` já exista. Este módulo também cria `proposal_cost_items`, os enums compartilhados e os pontos de integração usados pela Calculadora (`ProposalService::createFromQuote`, `ApplicantService::findOrCreateByCpf`, `ProposalDocumentService::storeGenerated`, `Proposal::scopeWithoutFeeEstimate`). |
| 3 | [`03-calculadora-emolumentos.md`](03-calculadora-emolumentos.md) | Reaproveita tudo o que os anteriores criaram: `proposal_cost_items`, `CostItemType`, `MaritalStatus`, `MoneyInput`, `billable_services` e os Services de Propostas. |
| 4 | [`04-protocolos-registro.md`](04-protocolos-registro.md) | Grava `registry_protocols` (FK opcional para `proposals`) e pendura um painel na timeline da proposta, na etapa com `stages.shows_registry_protocol`. Reaproveita o padrão de Combobox assíncrono de propostas da Calculadora. |
| 5 | [`05-relatorios.md`](05-relatorios.md) | Somente leitura: lista propostas finalizadas a partir da timeline, dos documentos privados e dos empreendimentos — precisa de tudo isso pronto. Não cria tabela. |
| 6 | [`06-timelines-por-banco.md`](06-timelines-por-banco.md) | **Alteração sobre o sistema já implementado**: troca a timeline única por uma timeline padrão + uma timeline personalizada por banco. Mexe no catálogo `stages` (Cadastros) e na instanciação da timeline (Propostas), por isso exige os PRDs 01 e 02 prontos. |

Regras:

1. **Não comece um módulo antes de o anterior estar com os critérios de aceite atendidos** e os testes
   de feature verdes (Cadastros → A9; Propostas → A9; Calculadora → seção 11; Protocolos → A9;
   Relatórios → A9; Timelines por banco → A9).
2. **Leia os cinco PRDs antes de codar o primeiro**, para não criar algo com nome ou schema diferente do
   que o módulo seguinte espera.
3. Dentro de cada módulo, siga as fases do próprio PRD: Cadastros **A8**, Propostas **A8**,
   Calculadora **seção 14**, Protocolos **A8**, Relatórios **A8**, Timelines por banco **A7**.
4. O CRUD de `billable_services` aparece em dois PRDs (Cadastros A6.8 e Calculadora §5.11). Ele é
   feito na etapa 1; na etapa 3 (Calculadora §14, passo 4) **só confira** que está completo.
5. **Decisões pendentes**: os PRDs 04 e 05 têm uma seção **A10** com perguntas que só o usuário pode
   responder. Faça cada pergunta **no início da fase indicada**, registre a resposta no campo
   “Decisão” do próprio PRD e só então implemente a parte afetada. Índice na seção 8.

## 3. O que cada módulo faz

### 3.1 Cadastros básicos (Catalogs) — [`01-cadastros.md`](01-cadastros.md)

CRUD administrativo dos oito catálogos de apoio: **bancos, cartórios, tipos de contrato, tipos de custo,
tipos de imóvel, empreendimentos, etapas da timeline e serviços cobráveis**. Exclusão sempre por soft
delete, com restauração, sem quebrar propostas e orçamentos que já usam o registro.

- **Dono de**: `banks`, `notaries`, `contract_types`, `cost_types`, `property_types`, `developments`,
  `stages` (tabelas, models, seeders, telas).
- **Consome**: enums `UserRole` e `ReceiptType` (definidos em Propostas — se ainda não existirem, criar
  aqui, na fase 1). Schema de `billable_services` vem da Calculadora (§6.2).
- **Defeitos do legado**: Parte B do próprio documento.

### 3.2 Propostas (Proposals) — [`02-propostas.md`](02-propostas.md)

Processo de financiamento imobiliário/documentação: cadastro da proposta, **proponentes, vendedores,
parceiros, imóvel**, acompanhamento por **etapas (timeline)**, **documentos** em disco privado, linhas
de custo (pagamentos, taxas, emolumentos), **recibos e PDFs**. Autorização por papel via `ProposalPolicy`.

- **Dono de**: `proposals`, `proposal_properties`, `proposal_stages`, `proposal_cost_items`, `receipts`,
  proponentes/vendedores/parceiros, documentos da proposta; enums `UserRole`, `ReceiptType`,
  `MaritalStatus`, `ProposalStatus`, caso `Manual` de `CostItemType`.
- **Consome**: todos os catálogos do módulo 1.
- **Defeitos do legado**: [`02-propostas-code-review.md`](02-propostas-code-review.md).

### 3.3 Calculadora de Emolumentos + Orçamento — [`03-calculadora-emolumentos.md`](03-calculadora-emolumentos.md)

Estima o custo de registro do imóvel em cartório via **API externa de emolumentos**, soma o **ITBI**
(calculado localmente por município) e os **serviços** da empresa. O resultado pode ser **vinculado a
uma proposta** (vira linhas de custo) ou virar um **orçamento** (PDF, e-mail, edição) que depois é
**convertido em proposta**.

- **Dono de**: `fee_calculations`, `fee_estimates`, `quotes`, `quote_billable_service`, schema de
  `billable_services`, `itbi_municipalities`, `itbi_rates`, `itbi_brackets`, `CostItemsBuilder`, Actions
  `CreateQuote`, `AttachFeeEstimateToProposal`, `ConvertQuoteToProposal`, telas de calculadora/orçamento.
- **Consome**: `proposals` e seus Services, `proposal_cost_items`, `banks`, `billable_services`.
- **Defeitos do legado**: [`03-calculadora-emolumentos-correcoes.md`](03-calculadora-emolumentos-correcoes.md)
  (resumo na seção 12 do PRD).

### 3.4 Protocolos de Registro (Registry Protocols) — [`04-protocolos-registro.md`](04-protocolos-registro.md)

Acompanha o **protocolo de registro do imóvel no cartório**: número, comprador, vendedor, datas de entrada
e saída e **posição** (Em registro PG, Em análise, Exigência, Em pagamento do banco, Concluído). Pode ser vinculado a
uma proposta, que preenche comprador e vendedor. O protocolo mais recente aparece na **timeline** da
proposta, na etapa configurada.

- **Dono de**: `registry_protocols`, enum `RegistryProtocolPosition`, CRUD, `RegistryProtocolPanel.vue`;
  relações `Proposal::registryProtocols()` / `latestRegistryProtocol()`.
- **Consome**: `proposals`, `applicants`, `sellers`, `stages.shows_registry_protocol`, `ProposalPolicy`.
- **Defeitos do legado**: Parte B do próprio documento.

### 3.5 Relatórios (Reports) — [`05-relatorios.md`](05-relatorios.md)

**Relatório de propostas finalizadas**: criador, proponentes, empreendimento, documento da etapa final e
data de finalização, com filtro por período e empreendimento e **download em lote (ZIP)** gerado no
servidor a partir do disco privado.

- **Dono de**: query `FinishedProposalsReport`, `ReportZipBuilder`, `ReportController`, Gate `view-reports`,
  rotas `reports.*`, relação `Proposal::finalStage()`. Não cria tabela.
- **Consome**: `proposals` (`ProposalStatus::Finished`), `proposal_stages`, `proposal_documents`,
  `proposal_properties`, `developments`, `applicants`, download de documentos do PRD de Propostas.
- **Defeitos do legado**: Parte B do próprio documento.

### 3.6 Timelines por banco — [`06-timelines-por-banco.md`](06-timelines-por-banco.md)

**Alteração de regra de negócio** sobre Cadastros e Propostas: cada banco pode ter sua timeline de
etapas (ex.: Itaú), criada vazia na tela de etapas. A proposta nasce com a timeline do seu banco; banco
sem etapas ativas usa a **timeline padrão do sistema** (as 8 etapas de hoje, `bank_id = NULL`).

- **Dono de**: coluna `stages.bank_id`, `Stage::scopeForTimeline()`, `Bank::stages()`,
  `StageService::timelineOptions()`, `IndexStageRequest`, a resolução da timeline em
  `ProposalTimelineService::instantiate()` e a recriação da timeline na troca de banco
  (`ProposalService::update()`).
- **Consome**: `banks`, `stages`, `proposal_stages`, `ProposalTimelineService`, `ProposalService`.
- **Revoga**: a decisão B-A3 de [`01-cadastros.md`](01-cadastros.md) ("etapas globais").

## 4. Nomes compartilhados (resumo)

Use **exatamente** estes nomes. As tabelas completas estão na seção **A0** de
[`01-cadastros.md`](01-cadastros.md) e de [`02-propostas.md`](02-propostas.md).

| Conceito | Tabela / classe | Dono | Consumidores |
|---|---|---|---|
| Banco | `banks` / `Bank` | Cadastros | `proposals.bank_id`, `quotes.bank_id` |
| Cartório | `notaries` / `Notary` | Cadastros | `proposal_cost_items.notary_id` |
| Tipo de contrato | `contract_types` / `ContractType` | Cadastros | `proposals.contract_type_id` |
| Tipo de custo | `cost_types` / `CostType` | Cadastros | `proposal_cost_items.cost_type_id` |
| Tipo de imóvel | `property_types` / `PropertyType` | Cadastros | `proposal_properties.property_type_id` |
| Empreendimento | `developments` / `Development` | Cadastros | `proposal_properties.development_id` |
| Etapa (catálogo) | `stages` / `Stage` | Cadastros | `proposal_stages.stage_id` |
| Timeline da etapa | `stages.bank_id` (`NULL` = padrão) / `Stage::scopeForTimeline()` | Timelines por banco | `ProposalTimelineService::instantiate()`, tela de etapas |
| Serviço cobrável | `billable_services` / `BillableService` | Schema: Calculadora · CRUD: Cadastros | `quote_billable_service`, `proposal_billable_service` |
| Linhas de custo da proposta | `proposal_cost_items` / `ProposalCostItem` | Propostas | Calculadora (linhas `Emolument`, `ExtraFee`, `Service`, `Itbi`) |
| Tipo da linha de custo | enum `CostItemType` | Calculadora + caso `Manual` de Propostas | ambos |
| Emolumento congelado | `fee_estimates` / `FeeEstimate` | Calculadora | `Proposal::feeEstimate()` |
| Estado civil | enum `MaritalStatus` | Propostas | `applicants`, `sellers`, `quotes` |
| Papel do usuário | enum `UserRole` | Propostas (A3) | Policies/Gates de todos os módulos |
| Protocolo de registro | `registry_protocols` / `RegistryProtocol` + enum `RegistryProtocolPosition` | Protocolos | timeline da proposta (`Proposal::latestRegistryProtocol()`) |
| Etapa que mostra o protocolo | `stages.shows_registry_protocol` | Cadastros | Protocolos (`StageTimeline.vue`) |
| Etapa final da proposta | `Proposal::finalStage()` (`ofMany('position', 'max')`) | Relatórios | Relatórios |
| Dinheiro | `unsignedBigInteger` em centavos + `App\Support\Money` / `formatMoney()` / `MoneyInput.vue` | Propostas (A2) | todas as colunas monetárias |

## 5. Convenções comuns a todos os módulos

Fonte: seção **A2** de [`02-propostas.md`](02-propostas.md#a2-regras-gerais-de-implementação-para-o-agente)
e seção **2** de [`03-calculadora-emolumentos.md`](03-calculadora-emolumentos.md).

- **Inspecione o sistema novo antes de criar** qualquer model, enum ou componente; se já existir,
  reutilize e só complete o que faltar. Não adicione dependências sem aprovação.
- **Nomes em inglês** (tabelas, colunas, classes, rotas, componentes Vue); **textos de UI em pt-BR**.
- **Sem camada Repository.** Regras em Services (Propostas, Cadastros não trivial) ou Actions
  (Calculadora); Actions não duplicam regra de proposta — chamam os Services de Propostas.
- **Dinheiro em centavos (inteiro)**; percentuais em `decimal:4`; nunca string formatada nem float.
- **Form Request** para toda validação; **Policy/Gate** para toda autorização; nada de `fill($request->all())`
  nem números mágicos de status/papel/tipo.
- **Arquivos em disco privado**, download por rota autenticada.
- **E-mails em fila**, disparados após o commit.
- **Testes de feature** para cada regra de negócio e para acesso negado por papel.
- Consulte a documentação da versão instalada (Laravel 13 / Inertia 2) antes de usar qualquer API citada.

## 6. Conflitos entre PRDs

| Assunto | Vale o que diz |
|---|---|
| Nome de tabela, classe ou rota compartilhada | Tabela da seção 4 deste documento / A0 dos PRDs |
| Schema de `billable_services` | [`03-calculadora-emolumentos.md`](03-calculadora-emolumentos.md) §6.2 |
| Regra de catálogo (exclusão, restauração, etapas) | [`01-cadastros.md`](01-cadastros.md) |
| Regra de proposta | [`02-propostas.md`](02-propostas.md) |
| Regra de cálculo, ITBI ou orçamento | [`03-calculadora-emolumentos.md`](03-calculadora-emolumentos.md) |
| Regra de protocolo de registro | [`04-protocolos-registro.md`](04-protocolos-registro.md) |
| Critério de proposta finalizada, timeline e documentos usados pelo relatório | [`02-propostas.md`](02-propostas.md) |
| Regra do relatório (filtros, colunas, ZIP) | [`05-relatorios.md`](05-relatorios.md) |
| Etapas por banco, timeline padrão e qual timeline a proposta recebe | [`06-timelines-por-banco.md`](06-timelines-por-banco.md) (prevalece sobre 01 e 02) |
| Qualquer caso não coberto | **Pare e pergunte** ao usuário |

## 7. Mapa de arquivos

| Arquivo | Tipo | Papel |
|---|---|---|
| [`00-visao-geral.md`](00-visao-geral.md) | Índice | Este documento: ordem, fronteiras e convenções |
| [`01-cadastros.md`](01-cadastros.md) | PRD | Cadastros básicos (Parte A: especificação · Parte B: defeitos do legado) |
| [`02-propostas.md`](02-propostas.md) | PRD | Módulo de Propostas |
| [`02-propostas-code-review.md`](02-propostas-code-review.md) | Code review | Defeitos do legado de Propostas (referências "B-xx") |
| [`03-calculadora-emolumentos.md`](03-calculadora-emolumentos.md) | PRD | Calculadora de Emolumentos, ITBI, serviços e orçamento |
| [`03-calculadora-emolumentos-correcoes.md`](03-calculadora-emolumentos-correcoes.md) | Code review | Defeitos do legado da Calculadora e do Orçamento |
| [`04-protocolos-registro.md`](04-protocolos-registro.md) | PRD | Protocolos de Registro (Parte A: especificação · Parte B: defeitos do legado) |
| [`05-relatorios.md`](05-relatorios.md) | PRD | Relatório de propostas finalizadas (Parte A: especificação · Parte B: defeitos do legado) |
| [`06-timelines-por-banco.md`](06-timelines-por-banco.md) | PRD | Timelines por banco (alteração sobre Cadastros e Propostas já implementados) |

## 8. Decisões pendentes (índice)

Detalhes, opções e impacto de cada uma na seção **A10** do PRD indicado. Ao decidir, preencha o campo
“Decisão” lá — não aqui.

| ID | Pergunta | PRD | Decidir antes da fase | Recomendação |
|---|---|---|---|---|
| PR-D1 | Uma proposta tem um ou vários protocolos? | [`04`](04-protocolos-registro.md#pr-d1-cardinalidade-proposta--protocolo) | 1 | Vários |
| PR-D2 | O número do protocolo é único (global ou por cartório)? | [`04`](04-protocolos-registro.md#pr-d2-unicidade-do-número-do-protocolo) | 1 | Não único |
| PR-D3 | O Analyst acessa os protocolos? | [`04`](04-protocolos-registro.md#pr-d3-acesso-do-analyst) | 1 | Sem acesso |
| PR-D4 | O vendedor é obrigatório no protocolo? | [`04`](04-protocolos-registro.md#pr-d4-vendedor-obrigatório) | 2 | Opcional |
| PR-D5 | Protocolos excluídos podem ser restaurados? | [`04`](04-protocolos-registro.md#pr-d5-restauração-de-protocolos-excluídos) | 2 | Não |
| PR-D6 | A data de saída é obrigatória em “Em pagamento do banco”? | [`04`](04-protocolos-registro.md#pr-d6-data-de-saída-ao-encerrar) | 2 | Opcional, com aviso |
| PR-D7 | Quem vê o painel do protocolo na timeline? | [`04`](04-protocolos-registro.md#pr-d7-quem-vê-o-painel-do-protocolo-na-timeline) | 4 | Quem vê a proposta |
| RE-D1 | Que data o filtro de período do relatório usa? | [`05`](05-relatorios.md#re-d1-data-usada-no-período-e-na-coluna-data-de-finalização) | 1 | Data da etapa final, com `finished_at` como reserva |
| RE-D2 | O Partner vê o relatório das próprias propostas? | [`05`](05-relatorios.md#re-d2-acesso-do-partner) | 1 | Não |
| RE-D3 | O Analyst acessa o relatório? | [`05`](05-relatorios.md#re-d3-acesso-do-analyst) | 2 | Sem acesso |
| RE-D4 | Quais os limites do ZIP e se ele vai para a fila | [`05`](05-relatorios.md#re-d4-limites-do-download-em-zip) | 3 | 50 na hora / 500 pela fila |
| RE-D5 | Quais documentos entram no ZIP? | [`05`](05-relatorios.md#re-d5-documentos-incluídos-no-zip) | 3 | Só o da etapa final |

As decisões sobre o Analyst (PR-D3, RE-D3) devem ser coerentes com a resposta dada à mesma pergunta no
PRD de Propostas (A3: “tratar como Admin somente leitura — confirmar com o usuário”).
