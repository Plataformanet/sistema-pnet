# Épico: Módulo de CRM (Funil de Vendas e Oportunidades)

---

## 1. Visão Geral do Recurso
*   **Nome do Épico:** Módulo de CRM - MVP (Funil de Vendas, Oportunidades e Itens Comercializáveis)
*   **Status:** Em Especificação / Em Revisão (Rascunho Inicial)
*   **Autor/Responsável:** Equipe PNET
*   **Módulo Associado:** CRM / Comercial

### 1.1. Contexto de Negócio (Por que estamos fazendo isso?)
O módulo de CRM do **Sistema PNET** permite que cada empresa inquilina (tenant) gerencie todo o seu processo comercial de prospecção e vendas de ponta a ponta. Através de um funil visual estilo Kanban, histórico de interações e composição flexível de orçamento com Produtos e Serviços, os vendedores reduzem o tempo de fechamento e a empresa evita a perda de oportunidades comerciais.

### 1.2. Atores Envolvidos (Quem usa?)
*   **Gestor Comercial / Administrador do Tenant:** Pode criar e configurar os funis de vendas (`crm_pipelines`), personalizar estágios, visualizar relatórios globais de conversão e acessar todas as oportunidades do time (permissões `crm.pipelines.manage`, `crm.deals.view_all`).
*   **Vendedor / Consultor Comercial:** Pode criar e movimentar suas próprias oportunidades no Kanban, adicionar produtos/serviços da proposta, agendar atividades (tarefas, reuniões, ligações) e registrar os motivos de ganho ou perda (`crm.deals.view_own`, `crm.deals.create`, `crm.deals.edit`).
*   **Cliente Final:** Interage via proposta comercial/contrato gerado pelo sistema.

---

## 2. Regras de Negócio e Requisitos Funcionais

1.  **Reuso do Cadastro Unificado de Contatos (`contacts`):** Toda oportunidade (*Deal*) criada no CRM deve ser vinculada a um contato existente (`contact_id`). Não há duplicação de cadastros de pessoa física/jurídica.
2.  **Pipelines e Estágios Flexíveis:** Cada tenant pode ter múltiplos funis de vendas (`crm_pipelines`) com seus respectivos estágios ordenáveis (`crm_stages`), possuindo cores, percentuais de probabilidade e prazos máximos.
3.  **Quadro Kanban com Drag & Drop Reativo:** O vendedor pode alternar os negócios entre estágios arrastando os cards na interface Vue 3. A movimentação aciona atualizações no banco e registra um evento na tabela de auditoria (`crm_deal_histories`).
4.  **Itens da Proposta Comercial (`crm_deal_items`):** Uma oportunidade pode conter múltiplos itens do catálogo:
    *   **Produtos (`products`):** Seleção por SKU ou nome com exibição do saldo em estoque (`current_stock`) e unidade de medida.
    *   **Serviços (`services`):** Seleção do catálogo de serviços com valor cobrado e duração estimada em minutos.
    *   **Itens Livres (Ad-hoc):** Possibilidade de digitar descrições e valores avulsos.
    *   **Recálculo Automático:** O valor total da oportunidade (`total_value`) é atualizado instantaneamente somando os itens menos os descontos.
5.  **Histórico de Atividades e Linha do Tempo (`crm_activities`):** Cada negócio possui uma linha do tempo para registro de chamadas, reuniões, envio de propostas, anotações internas e tarefas com alertas de vencimento.
6.  **Motivo Obrigatório em Perdas:** Ao alterar a situação do negócio para `Perdido` (*Lost*), o sistema exige a seleção do motivo da perda (ex: *Preço*, *Concorrente*, *Prazo*, *Desistência*).
7.  **Automação na Vitória (`Fechado / Ganho`):** Ao marcar o negócio como `Ganho` (*Won*):
    *   Gera a baixa automática no estoque (`current_stock`) para produtos com controle ativo (`manage_stock = true`).
    *   Oferece a criação automática do lançamento financeiro em **Contas a Receber** discriminando os itens comercializados.
    *   Permite salvar anexos de contratos e propostas na pasta do cliente no **Meu Drive**.

---

## 3. Especificação Técnica e Modelagem (Banco do Tenant)

### 3.1. Dicionário de Dados do Banco de Dados (`database/migrations/tenant/`)

*   **Tabela:** `crm_pipelines`
    *   `id`: BigInt (PK, Auto-increment)
    *   `name`: String (Nome do funil, ex: *Vendas Diretas*)
    *   `is_default`: Boolean (Funil padrão do tenant)
    *   `status`: Boolean (Ativo/Inativo)

*   **Tabela:** `crm_stages`
    *   `id`: BigInt (PK, Auto-increment)
    *   `crm_pipeline_id`: BigInt (FK -> `crm_pipelines.id`)
    *   `name`: String (Nome do estágio, ex: *Prospecção*, *Proposta Enviada*)
    *   `order`: Integer (Ordem de exibição na coluna)
    *   `probability`: Integer (Probabilidade estimada de fechamento 0-100%)
    *   `color`: String (Cor hexadecimal da badge)

*   **Tabela:** `crm_deals`
    *   `id`: BigInt (PK, Auto-increment)
    *   `contact_id`: BigInt (FK -> `contacts.id`)
    *   `user_id`: BigInt (FK -> `users.id` - Vendedor responsável)
    *   `crm_pipeline_id`: BigInt (FK -> `crm_pipelines.id`)
    *   `crm_stage_id`: BigInt (FK -> `crm_stages.id`)
    *   `title`: String (Título da oportunidade)
    *   `total_value`: Integer (Valor em centavos)
    *   `status`: Enum (`open`, `won`, `lost`)
    *   `lost_reason`: String (Nullable)
    *   `expected_close_date`: Date (Nullable)
    *   `won_at`: Timestamp (Nullable)
    *   `lost_at`: Timestamp (Nullable)

*   **Tabela:** `crm_deal_items`
    *   `id`: BigInt (PK, Auto-increment)
    *   `crm_deal_id`: BigInt (FK -> `crm_deals.id`)
    *   `item_type`: Enum (`product`, `service`, `custom`)
    *   `item_id`: BigInt (FK Nullable para `products.id` ou `services.id`)
    *   `name`: String (Nome do produto/serviço ou descrição livre)
    *   `unit_price`: Integer (Preço unitário em centavos)
    *   `quantity`: Decimal (Quantidade ou horas)
    *   `discount`: Integer (Desconto em centavos)
    *   `total_price`: Integer (Total em centavos)

*   **Tabela:** `crm_activities`
    *   `id`: BigInt (PK, Auto-increment)
    *   `crm_deal_id`: BigInt (FK -> `crm_deals.id`)
    *   `user_id`: BigInt (FK -> `users.id`)
    *   `type`: Enum (`call`, `meeting`, `email`, `task`, `note`)
    *   `description`: Text
    *   `due_date`: DateTime (Nullable)
    *   `completed_at`: DateTime (Nullable)

---

## 4. Estrutura de Código no PNET

### 4.1. Backend (Laravel 13)
*   **Controllers:**
    *   `App\Http\Controllers\Tenant\CrmDealController`
    *   `App\Http\Controllers\Tenant\CrmPipelineController`
    *   `App\Http\Controllers\Tenant\CrmActivityController`
*   **Services:**
    *   `App\Services\CrmDealService`
    *   `App\Services\CrmPipelineService`
*   **Form Requests:**
    *   `App\Http\Requests\StoreCrmDealRequest`
    *   `App\Http\Requests\UpdateCrmDealRequest`
*   **Rotas (`routes/tenant.php`):**
    *   `GET /crm/kanban` -> `tenant.crm.kanban` (`permission:crm.deals.view`)
    *   `POST /crm/deals` -> `tenant.crm.deals.store` (`permission:crm.deals.create`)
    *   `PUT /crm/deals/{deal}/stage` -> `tenant.crm.deals.update_stage` (`permission:crm.deals.edit`)

### 4.2. Frontend (Vue 3 + Inertia v2 + Tailwind v4 + Shadcn)
*   **Páginas:**
    *   `resources/js/pages/tenant/crm/kanban/Kanban.vue` (Quadro Kanban com drag & drop)
    *   `resources/js/pages/tenant/crm/list/List.vue` (Listagem em tabela TanStack)
    *   `resources/js/pages/tenant/crm/components/DealDetailDrawer.vue` (Painel lateral Sheet de detalhes)
    *   `resources/js/pages/tenant/crm/components/DealItemForm.vue` (Tabela dinâmica de produtos/serviços)

---

## 5. Cenários BDD de Teste e Aceite

### Cenário 1: Criação de oportunidade vinculada a produto e serviço
*   **Dado que** um vendedor autenticado acessa a tela de Kanban do CRM
*   **Quando** clica em "Nova Oportunidade", seleciona um contato existente, escolhe 1 Produto e 1 Serviço no orçamento e clica em "Salvar"
*   **Então** o negócio é criado no primeiro estágio do funil selecionado
*   **E** o valor total da oportunidade é calculado automaticamente com a soma dos itens.

### Cenário 2: Movimentação de estágio no Kanban (Drag & Drop)
*   **Dado que** uma oportunidade está no estágio "Prospecção"
*   **Quando** o vendedor arrasta o card para a coluna "Proposta Enviada"
*   **Então** a rota `tenant.crm.deals.update_stage` é disparada em segundo plano
*   **E** a oportunidade atualiza seu `crm_stage_id` e registra a mudança na tabela `crm_deal_histories`.

### Cenário 3: Vitória da Oportunidade e Baixa de Estoque
*   **Dado que** um negócio contém um produto com controle de estoque ativo (`manage_stock = true`)
*   **Quando** o vendedor altera o status para `Ganho` (*Won*)
*   **Então** o sistema realiza a baixa automática na coluna `current_stock` do produto
*   **E** solicita a criação da conta a receber no módulo Financeiro.
