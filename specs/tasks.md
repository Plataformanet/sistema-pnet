# Tasks — Sistema PNET (evolução incremental)

> **O que é:** o backlog executável da evolução, na ordem das fases do [plan.md](plan.md#9-fases-e-marcos).
> Cada tarefa referencia o [spec.md](spec.md) e é feita pela **skill responsável**. O fluxo de cada
> tarefa está no [implementation.md](implementation.md).
>
> **Todos os comandos rodam na raiz de `sistema-pnet`.**

## Legenda

| Sigla | Skill |
| --- | --- |
| **BE** | `laravel-development-senior` (backend novo) |
| **FE** | `inertia-vue-frontend-senior` (Vue/Inertia/Tailwind) |
| **RF** | `laravel-refactoring-senior` (mudar código que já existe) |
| **CR** | `laravel-code-review-senior` (revisão, só relatório) |
| **QA** | `qa-senior` (plano de testes, regressão, aceite) |

**Pronto quando:** atende à Definition of Done da [constitution.md §14](constitution.md#14-definition-of-done-global).

**Regras de execução:**

- Toda tarefa BE, RF ou FE termina com CR.
- Toda fase termina com uma tarefa QA de aceite.
- A tarefa BE ou RF já sai com os próprios testes Pest, e roda **só os arquivos afetados** (V-05).

**Status:** ☐ pendente · ◐ em andamento · ☑ concluída. Quem conclui a tarefa marca ☑ aqui.

### Comandos recorrentes

```bash
vendor/bin/sail artisan migrate --path=database/migrations/central
vendor/bin/sail artisan tenants:migrate
vendor/bin/sail artisan tenants:sync-permissions
vendor/bin/sail artisan test --compact tests/Feature/<Arquivo>Test.php
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail npm run type-check
```

---

## R0 — Adoção do SDD

| Status | ID | Tarefa | Skill |
| --- | --- | --- | --- |
| ☑ | T-R0-01 | Mover o SDD para `specs/` e adaptá-lo à evolução da v1 | — |
| ☑ | T-R0-02 | Atualizar `CLAUDE.md` e `AGENTS.md` (`specs/` primeiro, mapa de skills) | — |
| ☑ | T-R0-03 | Atualizar as skills em `.claude/skills/` e sincronizar `.agents/skills/` | — |

## R1 — Renomeações com sufixo de tipo (sem mudança de comportamento)

| Status | ID | Tarefa | Ref. | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-R1-01 | Enums: os 19 sem sufixo → `+Enum`; remover o `PermissionsEnum` sem uso | plan §4 | R0 | RF |
| ☐ | T-R1-02 | Actions da calculadora → `+Action` | plan §4 | R0 | RF |
| ☐ | T-R1-03 | Jobs (`AttachQuotePdfToProposalJob`, `SeedTenantDatabaseJob`), `EnsureBucketCommand`, Rules (`+Rule`), `RedirectProposalRestrictedUsersMiddleware` | plan §4 | R0 | RF |
| ☐ | T-R1-04 | Services: `SupplierService`, `EmployeeService`, `ServiceCatalogService`; remover `CoastService` e `PaidMarketService`; renomear os testes correspondentes | plan §4 | R0 | RF |
| ☐ | T-R1-05 | DTOs (`+Dto`), `FeeBreakdownBuilder`, `MoneyHelper`, `DocumentMaskHelper`, `FinishedProposalsReportQuery` | plan §4 | R0 | RF |
| ☐ | T-R1-06 | Varredura final: `grep` sem nomes antigos em `app`, `config`, `routes`, `database`, `tests`, `resources/views`; Pint | plan §4 | 01..05 | RF |
| ☐ | T-R1-07 | Code review da fase (nomes, referências por string) | — | 06 | CR |
| ☐ | T-R1-08 | Aceite: a suíte completa verde (rodada com o pedido do usuário) | — | 07 | QA |

## R2 — Padronizações de dados

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-R2-01 | Dinheiro em `unsignedBigInteger` (produtos, serviços, salário, planos) | D-06, spec §5 | R1 | RF |
| ☐ | T-R2-02 | `contacts.cpf_cnpj` só com dígitos + unique (a migration normaliza e falha listando duplicados); normalizar no request e na busca | RN-CAD-01 | R1 | RF |
| ☐ | T-R2-03 | `tenant_settings.key` unique + `SoftDeletes` no model; central `permissions.name` unique | spec §5 | R1 | RF |
| ☐ | T-R2-04 | Usuários: `deleted_at`; excluir = inativar + soft delete | RN-CORE-11 | R1 | RF |
| ☐ | T-R2-05 | `proposal_stages.overdue_alerted_at`; o comando de alerta não reenvia | RN-DOC-PRO-15 | R1 | RF |
| ☐ | T-R2-06 | `drive_logs` estruturado; pastas do Drive no disco do bucket | RN-DRV-05/06 | R1 | RF |
| ☐ | T-R2-07 | Remover `bank_account_out`; bloquear a exclusão de conta bancária com lançamentos | RN-FIN-02 | R1 | RF |
| ☐ | T-R2-08 | `services.sku` unique; validação `min:0` com mensagem nos valores do catálogo | RN-CAT-01 | R1 | RF |
| ☐ | T-R2-09 | Permissões: remover `documents.contracts.*` (`sales.*` fica, para o módulo Vendas futuro); criar `crm.deals.view` e `crm.pipelines.view` (par central + tenant); rota do CRM com a permissão | spec §4, RN-CRM-02 | R1 | BE |
| ☐ | T-R2-10 | Sidebar: grupo CRM com `crm.deals.view`; o grupo "Vendas" fica como está | spec §4 | 09 | FE |
| ☐ | T-R2-11 | Aceite R2: testes novos e afetados verdes; migrations aplicadas em todos os tenants | — | 01..10 | QA |

## R3 — MUD-04: serviços do catálogo no orçamento e na proposta

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-R3-01 | Migration de dados: categoria "Documentação", `billable_services` → `services`, pivots → `quote_service`/`proposal_service`, `proposal_cost_items.service_id`, drop das tabelas antigas | 3.4, V-09, plan 6.1 | R1 | BE |
| ☐ | T-R3-02 | Permissões: remover `documents.billable_services.*` (central + tenant + cargos) | RN-MUD04-01 | 01 | BE |
| ☐ | T-R3-03 | Código: remover BillableService (model, service, controller, requests, factory, rotas); `Quote::services()`/`Proposal::services()`; `resolveSelection`; `CostItemsBuilder`; sincronização do pivot; recibo recusado para linha `service` | RN-MUD04-02..06 | 01 | RF |
| ☐ | T-R3-04 | Front: remover as páginas e o menu de serviços cobráveis; `ServicePicker` sobre `services` (mesmo visual); tipos TS | RN-MUD04-03 | 03 | FE |
| ☐ | T-R3-05 | Aceite: testes de calculadora e orçamento atualizados + testes novos (migração, `resolveSelection`, sem recibo, pivot) | CA-MUD04-01, CA-DOC-QUO-02 | 03, 04 | QA |

## R4 — MUD-02: distribuição financeira

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-R4-01 | Migration: `proposal_id` e `proposal_cost_item_id` em pagar/receber; chaves `documents.finance.*` em `tenant_settings` | RN-MUD02-03/09 | R2, R3 | BE |
| ☐ | T-R4-02 | `ProposalFinanceSettingsService` + `UpdateFinanceIntegrationSettingsRequest` + rota `finance-integration.update` | RN-MUD02-09 | 01 | BE |
| ☐ | T-R4-03 | Card "Integração Documentações → Financeiro" na tela Empresa | RN-MUD02-09 | 02 | FE |
| ☐ | T-R4-04 | `ProposalFinancialDistributionService` + `LinkedEntryAlreadyPaidException` + mapeamento no `CostItemTypeEnum` | RN-MUD02-01..07 | 01 | BE |
| ☐ | T-R4-05 | Ligar a distribuição em `ProposalCostItemService`, `CostItemsBuilder`, Actions de vínculo e conversão e `ProposalService::delete`; aviso quando faltar configuração | RN-MUD02-01/05/06 | 04 | RF |
| ☐ | T-R4-06 | Rota e controller `cost-items/distribute` | RN-MUD02-06 | 04 | BE |
| ☐ | T-R4-07 | Aba Pagamentos: colunas "A receber" e "A pagar" + botão "Distribuir"; badge "Proposta Nº" nas listas do financeiro | RN-MUD02-08 | 05, 06 | FE |
| ☐ | T-R4-08 | Aceite: CA-MUD02-01/02 + idempotência, sincronização, parcela paga, exclusões, configuração ausente | CA-MUD02-* | 05..07 | QA |

## R5 — MUD-03 e MUD-01

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-R5-01 | `QuoteCreatedEvent`, `QuoteConvertedEvent` e `QuoteDeletedEvent` (depois do commit) disparados pelas Actions e pela exclusão; **sem** listener | RN-MUD03-01..04 | R3 | BE |
| ☐ | T-R5-02 | `CreateProposalRequest` + `prefill` em `TenantProposalController@create` (`source=crm`) | RN-MUD01-03 | R1 | BE |
| ☐ | T-R5-03 | `ProposalForm` aceita `prefill`; botão "Gerar proposta de documentações" na `DealDetailDrawer` (só `won` + permissão) | RN-MUD01-01/03 | 02 | FE |
| ☐ | T-R5-04 | Registrar no backlog do CRM os contratos pendentes (RN-MUD01-02, listener e efeitos da MUD-03) | 3.1, 3.3 | — | QA |
| ☐ | T-R5-05 | Aceite: eventos com `Event::fake`; prefill testado | — | 01..03 | QA |

## R6 — Correções e decisões do spec

| Status | ID | Tarefa | Spec | Dep. | Skill |
| --- | --- | --- | --- | --- | --- |
| ☐ | T-R6-01 | `DashboardService` (KPIs reais + próximos vencimentos) + `TenantController@dashboard` | RN-CORE-14 | R2 | BE |
| ☐ | T-R6-02 | Dashboard: ligar os widgets às props sem mudar o visual | RN-CORE-14 | 01 | FE |
| ☐ | T-R6-03 | Tirar consultas Eloquent dos controllers (cadastros, financeiro); `DriveService` sem FormRequest | constitution §4 | R1 | RF |
| ☐ | T-R6-04 | Diálogo de contrato padrão na conversão de orçamento | Q-S-09 | R3 | BE + FE |
| ☐ | T-R6-05 | Back: remover `TenantSettingController` e as renderizações de páginas inexistentes | spec §8 | R1 | RF |
| ☐ | T-R6-06 | Front: remover arquivos mortos; carregar a fonte Instrument Sans; URLs literais → `route()` | spec §8 | R1 | FE |
| ☐ | T-R6-07 | Exportação de listas em PDF: `ListPdfService` + `pdf/list.blade.php`, `forPrint`/`printColumns` nos 12 services, `ExportListPdfRequest`, método `pdf()` e rota `tenant.<modulo>.<recurso>.pdf`, com testes | RF-CORE-12, RN-CORE-15 | R1 | BE |
| ☐ | T-R6-08 | Front: ligar o "Exportar lista" do `DataTable` ao PDF nas 12 listas (busca + status), sem mudar o visual | RF-CORE-12 | 07 | FE |
| ☐ | T-R6-09 | Aceite da R6 (inclui CA-CORE-03..05) | — | 01..08 | QA |

## R7 — Fechamento

| Status | ID | Tarefa | Dep. | Skill |
| --- | --- | --- | --- | --- |
| ☐ | T-R7-01 | Code review geral por módulo (tenancy, N+1, segurança, nomes) | R1..R6 | CR |
| ☐ | T-R7-02 | Corrigir os achados 🔴/🟠 | 01 | RF |
| ☐ | T-R7-03 | Regressão completa + testes de isolamento entre tenants (com o pedido do usuário) | 02 | QA |
| ☐ | T-R7-04 | Atualizar `docs/` (dicionário de dados, rotas, módulos, convenções) e remover as marcas **[alvo]** concluídas do spec | 03 | BE |
| ☐ | T-R7-05 | Relatório final de QA | 03, 04 | QA |
