# Spec — Sistema PNET

> **O que é:** a especificação funcional do Sistema PNET, ou seja, **o que** o sistema faz, tanto o que já existe quanto o alvo da evolução. O **como** fica no [plan.md](plan.md).
> Obedece à [constitution.md](constitution.md).
> Consolida o comportamento atual do sistema, os PRDs `docs/prd/00…06` (com as decisões já registradas neles)
> e as **4 mudanças de regra do PRD de evolução** (`docs/prd-sistema-pnet-v2.md`, seção 3).
>
> **Estado atual × alvo:** tudo o que não está marcado já existe no código. O que está marcado **[alvo]**
> é mudança a implementar nas fases R1 a R7 do [plan.md](plan.md). Ao concluir a fase, a marca é removida.
>
> **Identificadores:**
>
> | Prefixo | Significado |
> | --- | --- |
> | `RF-<MÓD>-nn` | Requisito funcional |
> | `RN-<MÓD>-nn` | Regra de negócio |
> | `CA-<MÓD>-nn` | Critério de aceite (BDD) |
> | `MUD-nn` | Mudança de regra do PRD de evolução |
> | `Q-S-nn` | Pergunta em aberto |
>
> Valores monetários estão sempre em **centavos inteiros**. Os nomes de classe já seguem o sufixo de tipo (constitution, seção 6).

---

## 0. Sumário de módulos

| Código | Módulo | Backend | Frontend |
| --- | --- | --- | --- |
| CORE | SaaS (cadastro e provisionamento), autenticação, usuários, cargos, empresa, perfil, dashboard, exportação de listas em PDF | ✔ | ✔ (visual congelado) |
| CAD | Cadastros: contatos unificados (clientes, fornecedores, funcionários) | ✔ | ✔ |
| CAT | Catálogo: produtos e serviços | ✔ | ✔ |
| FIN | Financeiro | ✔ | ✔ |
| DRV | Drive | ✔ | ✔ |
| CRM | Funil de vendas | **✘ (pendente)** | ✔ (dados mock) |
| VEN | Vendas: vendas e orçamentos de vendas (`sales.*`) | **✘ (futuro)** | Só o grupo de menu |
| DOC | Documentações: cadastros auxiliares, etapas, propostas, calculadora/ITBI, orçamentos, protocolos e relatórios | ✔ | ✔ |

**Fora do escopo:**

- Cobrança do SaaS: Mercado Pago, assinaturas e cupons. As tabelas existem e ficam sem uso (D-11).
- Módulo **Vendas** (`sales.*`): **mantido**, para ser implementado no futuro. Hoje ele tem só o grupo de menu "Vendas" e as permissões `sales.sales.*` e `sales.quotations.*`, sem telas nem backend.
  - O menu e as permissões continuam como estão.
  - Não implemente telas nem backend de Vendas até o módulo ser especificado neste spec.
- "Serviços cobráveis" (MUD-04). **[alvo]** remover.

---

## 1. Atores e cargos

Os cargos (`RolesEnum`) são gravados no spatie pelo **rótulo em pt-BR**.

| Valor | Rótulo | Perfil | Permissões padrão (só no provisionamento) |
| --- | --- | --- | --- |
| `admin` | Administrador | Equipe interna, acesso total | Todas as permissões do plano |
| `manager` | Gestor | Equipe interna | Nenhuma (configurada na tela de cargos) |
| `financial` | Financeiro | Equipe interna | Nenhuma |
| `seller` | Vendedor | Equipe comercial (CRM) | Nenhuma |
| `analyst` | Analista | Equipe de documentação | Todo o grupo `documents.*` |
| `partner` | Parceiro | Externo (corretor ou correspondente) | `documents.proposals.{view,create}`, `documents.proposal_documents.view` |
| `client` | Cliente | Externo (proponente) | `documents.proposals.view` |
| `property_seller` | Vendedor do imóvel | Externo | `documents.proposals.view` |

**Usuários externos** são os que só têm os cargos `partner`, `client` ou `property_seller`.

- Depois do login, vão direto para a lista de propostas.
- São redirecionados se tentarem abrir o Dashboard ou o CRM (`RedirectProposalRestrictedUsersMiddleware`).
- O menu oculta os grupos marcados `internalOnly`.

---

## 2. Módulos

### 2.1 CORE

#### 2.1.1 Cadastro do SaaS e provisionamento (domínio central)

- **RF-CORE-01:** a tela pública `/cadastro` coleta Empresa, Domínio (subdomínio), Plano, Nome, E-mail e Senha.
- **RF-CORE-02:** depois do envio, a tela de provisionamento consulta `GET /cadastro/status/{tenant}` a cada 3 s (rate limit de 60 por minuto).
  - Após 90 s, mostra "demorando".
  - Após 15 min, desiste.
  - Em falha, mostra "Não foi possível provisionar sua conta" com "Tentar novamente".
  - Em sucesso, redireciona para o login do tenant.
- **RN-CORE-01:** os módulos do plano precisam satisfazer os `requires_modules` uns dos outros; se não satisfizerem, a mensagem é "Requer módulos: …".
- **RN-CORE-02:** o tenant nasce com `provisioning_status = pending`, 30 dias de trial e o domínio vinculado. O `seed` (admin + permissões do plano) fica no JSON `data`.
- **RN-CORE-03:** a pipeline em fila `CreateDatabase` → `MigrateDatabase` → `SeedTenantDatabaseJob`:
  1. Aplica os cargos e as permissões do plano.
  2. Aplica as permissões padrão dos cargos.
  3. Cria o administrador.
  4. Marca `ready` e limpa o `seed`.
  5. Em caso de falha, marca `failed` e apaga o tenant (e o banco dele).
- **RN-CORE-04:** o domínio precisa ser um host válido, com ponto.
- **CA-CORE-01:**
  - **Dado** um plano com módulos coerentes,
  - **quando** a empresa se cadastra,
  - **então** o banco `tenant<uuid>` é criado e migrado, o admin consegue logar no subdomínio e só tem as permissões dos módulos do plano.

#### 2.1.2 Planos, módulos e permissões

- **RN-CORE-05:** as tabelas centrais `plans`, `modules`, `plan_modules`, `tenant_modules` e `permissions` (catálogo, com `module_id`, `name` **único** **[alvo: índice unique]** e `display_name` em pt-BR) são criadas e populadas por migration idempotente. Os planos são basic, standard e premium (2999, 5999 e 9999 centavos).
- **RN-CORE-06:** um tenant recebe **só** as permissões dos módulos ativos dele. O `tenants:sync-permissions [--tenants=]` sincroniza os tenants existentes, apenas adicionando.
- **RN-CORE-07:** as permissões padrão dos cargos são aplicadas só no provisionamento, nunca no `sync`.
  - **[alvo] Exceção (V-12):** a migration de tenant que cria `crm.deals.view` e `crm.pipelines.view` também as atribui ao cargo Administrador, quando o plano do tenant tem o módulo. Os demais cargos são configurados na tela de cargos.
- **RF-CORE-03:** o frontend recebe `tenant.hasModules` e `permissions` nas props compartilhadas. A sidebar filtra os itens por módulo e por permissão.

#### 2.1.3 Autenticação do tenant

- **RF-CORE-04:** o login pede e-mail, senha e "Manter conectado". Mostra `flash.error`/`flash.success`, e o e-mail vem preenchido pelo `flash.email`.
- **RF-CORE-05:** em "Esqueci a senha", o usuário informa o e-mail. A tela passa ao estado "Link enviado", com o botão "Enviar novamente".
- **RF-CORE-06:** em "Definir senha", o e-mail é somente leitura e o usuário informa senha e confirmação. A mesma tela atende os dois tipos de link.
- **RN-CORE-08:** o link de "esqueci a senha" vale 60 min (`password_reset_tokens`, broker `users`). O link de boas-vindas vale 72 h (`password_set_tokens`, broker `welcome`, `AUTH_WELCOME_TOKEN_EXPIRE`).
  - Ao definir a senha, os tokens das duas tabelas são apagados.
  - O broker é criado a cada uso, dentro de `$tenant->run()` (`TenantPasswordService`).
- **RN-CORE-09:** a resposta é igual para e-mail cadastrado ou não. O rate limit `password-reset` é de 5 por minuto, com a chave domínio + IP.
- **RN-CORE-10:** a rota de destino depois do login depende do cargo: usuário externo vai para a lista de propostas; os demais vão para o dashboard. A URL pretendida é respeitada.
- **CA-CORE-02:**
  - **Dado** um usuário só com o cargo Cliente,
  - **quando** ele loga,
  - **então** cai na lista de propostas, e o acesso a `/dashboard` o redireciona para lá.

#### 2.1.4 Usuários e cargos

- **RF-CORE-07:** a lista de usuários mostra Nome, E-mail, Cargo e Status.
  - O formulário pede Nome, E-mail (login), Cargo, Status, Senha e Confirmação. No editar, a senha só é alterada se o usuário marcar a opção.
  - Tem a grade de permissões extras, com as herdadas do cargo bloqueadas.
- **RN-CORE-11 [alvo: inativar + soft delete]:** usuário tem 1 cargo e pode ter permissões diretas extras (`syncPermissions`). Excluir usuário é exclusão física *(Q-S-01)*.
- **RF-CORE-08:** cargos têm lista (Nome, Nº de usuários) e formulário (Nome + `PermissionsGrid` agrupada por módulo, com "Selecionar todos" e "Desmarcar todos" por módulo).

#### 2.1.5 Empresa e perfil

- **RF-CORE-09:** a tela "Configurações da Empresa" tem três cards:
  - Logotipo: PNG, JPG, WebP ou SVG de até 2 MB.
  - Identificação e contato: razão social, nome fantasia, CNPJ, IE, e-mail e telefone.
  - Endereço comercial: com ViaCEP.
- **RN-CORE-12:**
  - Os dados da empresa ficam em `tenant_settings` (`company.*`), com `key` **única** **[alvo: índice unique]**.
  - O logo vai para o bucket (`company/`) e é servido por `GET /settings/company/logo`, uma rota pública do tenant com cache buster.
  - O nome exibido segue a ordem `company.trade_name` → `company.name` → nome do tenant.
  - O `pdfBranding()` embute o logo nos PDFs.
- **RF-CORE-10:** a tela "Meu Perfil" tem nome, avatar (upload para o bucket `avatars/`, apagando o anterior) e e-mail somente leitura. A troca de senha exige a senha atual.
- **RN-CORE-13:** `tenant_settings` tem 19 chaves padrão por migration (`app.*`, `financial.*`, `drive.*`, `documents.*`, `security.*`, `notifications.*`, `api.*`), com tipo checado (`SettingTypeEnum`). A leitura usa cache.

#### 2.1.6 Dashboard

- **RF-CORE-11:** o Dashboard mantém o layout atual:
  - 4 KPIs: Receita do mês, Despesas do mês, Saldo atual e Inadimplência.
  - Próximos vencimentos e Faturamento anual (gráfico).
  - Últimas aprovações e Pendências críticas.
  - Chamados, Ranking da equipe e Volume por tipo.
- **RN-CORE-14 (Q-S-02) [alvo]:**
  - Os 4 KPIs e "Próximos vencimentos" usam dados reais do financeiro (parcelas do mês, saldo das contas ativas, inadimplência = parcelas `open` vencidas). São visíveis só com as permissões do financeiro correspondentes.
  - Os demais widgets ficam como placeholder, com o mesmo visual de hoje.

#### 2.1.7 Exportação de listas em PDF (Q-S-17) [alvo]

- **RF-CORE-12 [alvo]:** o botão "Exportar lista" das telas de listagem com `DataTable` gera um **PDF** da lista. Hoje o botão existe, mas não faz nada. As telas são:

  | Tela | Rota da lista | Permissão |
  | --- | --- | --- |
  | Clientes | `tenant.registrations.clients.list` | `registrations.clients.view` |
  | Fornecedores | `tenant.registrations.suppliers.list` | `registrations.suppliers.view` |
  | Funcionários | `tenant.registrations.employees.list` | `registrations.employees.view` |
  | Produtos | `tenant.products.products.list` | `products.products.view` |
  | Categorias de produtos | `tenant.products.categories.list` | `products.categories.view` |
  | Serviços | `tenant.services.services.list` | `services.services.view` |
  | Categorias de serviços | `tenant.services.categories.list` | `services.categories.view` |
  | Categorias financeiras | `tenant.finance.categories.list` | `finance.categories.view` |
  | Subcategorias financeiras | `tenant.finance.subcategories.list` | `finance.subcategories.view` |
  | Contas bancárias | `tenant.finance.bank-accounts.list` | `finance.accounts.view` |
  | Usuários | `tenant.settings.users.list` | `settings.users.view` |
  | Cargos | `tenant.settings.roles.list` | `settings.roles.view` |

  As listas com `ServerDataTable` (módulo Documentações) **não** ganham o botão. A lista de Propostas continua com "Imprimir lista" e "Imprimir selecionadas" (RF-DOC-PRO-01).

- **RN-CORE-15 [alvo]:** regras do PDF da lista:
  - **Conteúdo:**
    - cabeçalho com a marca da empresa, igual ao dos PDFs atuais;
    - título da lista, data e hora da emissão e nome do usuário logado que emitiu;
    - as mesmas colunas da tela, menos seleção e ações, com os mesmos formatos (dinheiro em R$, datas dd/mm/aaaa, documento com máscara, status por extenso);
    - total de registros no rodapé.
  - **Filtros:**
    - o PDF respeita a busca digitada no "Pesquisar..." e, em Clientes, Fornecedores e Funcionários, os chips Todos/Ativos/Inativos. As outras 9 listas têm só a busca;
    - a busca é aplicada no servidor sobre os mesmos campos exibidos nas colunas;
    - sem filtro, o PDF traz todos os registros que a tela lista.
  - **Ordem:** a ordem padrão da lista.
  - **Limite:** 500 linhas, o mesmo da lista de Propostas. Acima disso, o PDF traz as 500 primeiras e o aviso "Exibindo 500 de N registros. Refine a busca para exportar o restante."
  - **Acesso:**
    - exige a mesma permissão `.view` da lista; sem ela, a resposta é 403;
    - só traz dados do tenant atual.
  - **Entrega:**
    - o PDF abre em uma nova aba;
    - o arquivo se chama `lista-de-<recurso>.pdf` (ex.: `lista-de-clientes.pdf`);
    - todo o texto fica em pt-BR.
  - **Visual (P-10):** o botão mantém o rótulo, o ícone (`Download`), a variante `outline` e a posição de hoje. Ele só ganha a ação.
- **CA-CORE-03:**
  - **Dado** a lista de Clientes com 3 clientes ativos e 1 inativo, o chip "Ativos" marcado e a busca "maria" (que deixa 1 cliente visível),
  - **quando** o usuário clica em "Exportar lista",
  - **então** abre numa nova aba o `lista-de-clientes.pdf`, com o cabeçalho da empresa, uma única linha com as colunas da tela e "1 registro" no rodapé.
- **CA-CORE-04:**
  - **Dado** um usuário sem `registrations.clients.view`,
  - **quando** ele acessa a URL do PDF da lista de Clientes,
  - **então** recebe 403.
- **CA-CORE-05:**
  - **Dado** 600 serviços cadastrados,
  - **quando** a lista de Serviços é exportada sem filtro,
  - **então** o PDF traz 500 linhas e o aviso "Exibindo 500 de 600 registros".

---

### 2.2 CAD — Cadastros (contatos unificados)

- **RF-CAD-01:** cada papel (Clientes, Fornecedores e Funcionários) tem lista, criar e editar. A lista usa `DataTable` com os filtros Todos, Ativos e Inativos.
  - Clientes: Nome, CPF/CNPJ, E-mail, Status.
  - Fornecedores: Nome, CPF/CNPJ, Categoria, E-mail, Status.
  - Funcionários: Nome, CPF, Cargo, E-mail, Status.
- **RF-CAD-02:** os formulários são:
  - **Cliente:** alternância PF/PJ.
    - PF: CPF e Nome.
    - PJ: CNPJ, Razão social e Nome fantasia.
    - E-mail, Telefone e Celular.
    - Endereço: CEP, Logradouro, Número, Complemento, Bairro, Cidade e UF, com ViaCEP.
  - **Fornecedor:** os mesmos campos, mais Nome do contato, Descrição e Categoria de insumos.
  - **Funcionário:** só PF, mais RG, Nascimento, Cargo, Salário bruto (`MoneyInput`) e Admissão.
- **RF-CAD-03:** ao digitar um CPF/CNPJ completo, a tela busca o contato existente (`GET /registrations/{entity}/get-contact-by-cpf-cnpj/{doc}`) e preenche os dados. Se o documento mudar, os dados são limpos.
- **RN-CAD-01 [alvo]:** o contato é **único por documento**:
  - O `contacts.cpf_cnpj` é gravado **só com dígitos**, com índice `unique` considerando soft delete.
  - Hoje o documento é gravado com máscara em Cadastros e não há `unique`. A migration da fase R2 normaliza os registros existentes.
- **RN-CAD-02:** os papéis são tabelas 1:1 com `unique(contact_id)`. Cadastrar de novo um papel excluído restaura o registro.
- **RN-CAD-03:** o CPF é validado com `CpfRule` e o CNPJ com `CnpjRule`, conforme o tipo de pessoa do contato (PF/PJ).
- **RN-CAD-04:** "Ativar/Inativar" (`PATCH …/{id}/toggle-active`) altera o `active` do papel. Papel inativo **bloqueia novos lançamentos financeiros** (`InactiveContactException`).
- **RN-CAD-05:** excluir um papel:
  1. É bloqueado se existirem lançamentos financeiros do contato naquele papel (`ContactHasFinancialEntriesException`, aviso).
  2. Senão, faz soft delete do papel.
  3. O contato e o endereço só são apagados se não restar nenhum papel (cliente, fornecedor, funcionário, proponente ou vendedor) nem lançamento (`Contact::deleteIfOrphaned()`).
- **RN-CAD-06:** todo dinheiro (salário) fica em centavos.
- **CA-CAD-01:**
  - **Dado** um contato que já é cliente,
  - **quando** é cadastrado como fornecedor com o mesmo CNPJ,
  - **então** os dados base são reaproveitados e apenas a linha de `suppliers` é criada.
- **CA-CAD-02:**
  - **Dado** um cliente com conta a receber,
  - **quando** o papel de cliente é excluído,
  - **então** a operação é recusada com aviso.

Permissões: `registrations.{clients,suppliers,employees}.{view,create,edit,delete}`.

---

### 2.3 CAT — Catálogo (produtos e serviços)

- **RF-CAT-01:** categorias de produto e de serviço têm Nome e Status.
- **RF-CAT-02:** produtos têm Categoria, Nome, SKU, Código de barras, Descrição, Custo, Venda, Controla estoque, Estoque atual, Estoque mínimo, Unidade e Status.
- **RF-CAT-03:** serviços têm Categoria, Nome, SKU, Custo, Venda, Taxas ("Emolumentos" na tela), Duração (min), Descrição e Status.
- **RN-CAT-01 [alvo: `min:0` com mensagem e `services.sku` único]:**
  - Os valores (`cost_value`, `sell_value`, `fees`) ficam em centavos, com `min:0` e a mensagem "O valor de venda não pode ser inferior a zero.".
  - O par `(sku, barcode)` é único em produtos; `sku` é único em serviços *(Q-S-03)*.
- **RN-CAT-02 (MUD-04) [alvo]:** o catálogo de **serviços passa a alimentar o Orçamento e a Proposta**.
  - Só aparecem os serviços com `status = true`, sem soft delete.
  - O preço de tabela é o `sell_value`.
  - Ver seção 3.4.
- **RN-CAT-03:** um serviço em uso num orçamento ou numa proposta pode ser excluído (soft delete). Os vínculos continuam exibindo o serviço (`withTrashed`).
- **Fora do escopo (Q-S-04):** o alerta de estoque mínimo e a baixa de estoque, que o épico do catálogo promete.

Permissões: `products.{products,categories}.*` e `services.{services,categories}.*`.

---

### 2.4 FIN — Financeiro

#### 2.4.1 Cadastros de apoio

- **RF-FIN-01:** contas bancárias têm Nome, Banco, Agência, Conta, Tipo, Saldo inicial, Principal e Status. A lista mostra também o Saldo atual.
- **RN-FIN-01:**
  - `(bank, agency, account_number)` é único.
  - Só existe **uma** `main_account`; marcar outra desmarca a anterior, e sempre precisa existir uma.
  - O `initial_balance` só é aceito na criação.
  - O `current_balance` nasce igual ao inicial e é alterado **só pelo sistema**.
- **RN-FIN-02 [alvo]:** excluir uma conta bancária com lançamentos:
  - Hoje o sistema faz soft delete em cascata **sem estorno**.
  - O alvo é **bloquear** a exclusão quando houver lançamentos ativos (Q-S-05).
- **RF-FIN-02:** categorias financeiras têm Nome, Tipo (`receita`/`despesa`, valores atuais mantidos — V-03), Observações e Ativo. Subcategorias têm Categoria, Nome, Observações e Ativo.
- **RN-FIN-03:**
  - O nome é único por tipo (categoria) ou por categoria (subcategoria). Um duplicado excluído é restaurado.
  - A exclusão é bloqueada se houver lançamentos.
  - Excluir uma categoria exclui as subcategorias dela.
- **RN-FIN-04:** `cost_types` (tipos de custo) é **compartilhado** com Documentações. A FK nos lançamentos se chama `cost_id` (nome mantido).

#### 2.4.2 Contas a pagar e a receber

- **RF-FIN-03:** a lista tem:
  - filtro "Mensal" (navegador de mês) ou "Período" (data inicial e final);
  - busca, conta bancária, categoria e registros por página;
  - 5 KPIs clicáveis: Vencidos, Vencem hoje, A vencer, Pagos e Total do período;
  - tabela de **parcelas**: Nº, Conta, Vencimento, Pagamento, n/N, Categoria, Descrição, Valor da parcela, Total e Situação;
  - ações Editar, Liquidar, Visualizar e Excluir.
- **RF-FIN-04:** o formulário tem Descrição, Fornecedor/Cliente (`ComboboxRemote`; **[alvo]** o fornecedor fica opcional em pagar, RN-FIN-14), Conta, Categoria, Subcategoria, Tipo de custo, Condição (à vista ou 1x a 12x), Forma de pagamento, Total, Vencimento, Situação inicial (Em aberto/Pago), Observações e Comprovante. No editar, há um cartão por parcela.
- **RF-FIN-05:** o detalhe mostra o cabeçalho e as parcelas, com a ação de pagar.
- **RN-FIN-05:** o contato financeiro é resolvido por `(contact_id, type)`, com `fornecedor` em pagar e `cliente` em receber (`ContactTypeEnum`). Papel inativo é bloqueado. **[alvo]** Em pagar, o contato é opcional (RN-FIN-14).
- **RN-FIN-06:** parcelas:
  - A divisão usa `intdiv`, e **o resto vai para a última parcela**.
  - A parcela N vence na data inicial + (N−1) meses (`addMonthsNoOverflow`).
  - Com parcelas explícitas, a soma precisa ser igual ao total (`InstallmentsTotalMismatchException`, com a mensagem "A soma das parcelas (R$ X) deve ser exatamente igual ao valor total do título (R$ Y).").
- **RN-FIN-07:** o status inicial é só `open` ou `paid`. Criado como `paid`, cada parcela recebe `payment_date = due_date` e o saldo se move pela soma.
- **RN-FIN-08:** edição com parcelas pagas:
  - As parcelas pagas ficam imutáveis (`PaidInstallmentLockedException`).
  - Mudar o total ou a quantidade recria só as abertas, com `total − pago`.
  - Um total menor que o pago é recusado (`AccountTotalBelowPaidException`).
  - Trocar a conta estorna o pago da conta antiga e lança na nova.
- **RN-FIN-09:** a baixa (`PATCH …/installments/update {id}`) trava a parcela e recusa se ela já estiver paga (`InstallmentAlreadyPaidException`, 422). Depois move o saldo (−1 pagar / +1 receber) e grava `payment_date = hoje` e `status = paid`.
- **RN-FIN-10:** a exclusão estorna o pago, faz soft delete das parcelas e do lançamento.
- **RN-FIN-11:** o saldo só muda por `UPDATE` atômico, dentro de transação com `lockForUpdate`.
- **RN-FIN-12:** status de parcela (`AccountsEnum`): as parcelas são gravadas só como `open` e `paid`. "Vencidos", "Vencem hoje" e "A vencer" são filtros de data sobre `open`.
- **RN-FIN-13 (MUD-02) [alvo]:** os lançamentos podem nascer de uma **proposta de documentações**. Esses lançamentos guardam `proposal_id` e `proposal_cost_item_id` (seção 3.2). As contas a pagar geradas pela proposta não têm fornecedor (RN-FIN-14).
- **RN-FIN-14 [alvo] (V-11, Q-S-18):** o fornecedor é **opcional** em contas a pagar.
  - `account_payables.financial_contact_id` passa a aceitar nulo.
  - Sem fornecedor, não há checagem de papel inativo.
  - A lista, o detalhe e os relatórios mostram "—" no lugar do fornecedor.
  - Em contas a receber, o cliente continua obrigatório.

#### 2.4.3 Relatórios financeiros

- **RF-FIN-06 — Fluxo de caixa:**
  - Modos mensal, anual e personalizado.
  - Filtros por conta, categoria e tipo.
  - Cards de despesas, receitas, total e saldo atual.
  - Lista de movimentações e saldos por banco, com lançamentos futuros.
  - **RN:** o realizado são as parcelas `paid` por `payment_date`; a projeção são as `open` por `due_date`.
- **RF-FIN-07 — Fluxo de gastos:**
  - Matriz categoria de despesa × mês do ano, por `due_date`.
  - Linhas de subcategoria e "Sem subcategoria".
  - Média mensal e PDF.
- **RF-FIN-08 — Faturamento:**
  - Valor líquido = receber pago − pagar pago, por conta, ano e mês (`payment_date`).
  - Padrão: últimos 5 anos.
  - Variação anual, melhor e pior ano.
  - Hoje usa `YEAR()`/`MONTH()` do MySQL. Fica como está (MySQL é o banco do projeto).
- **CA-FIN-01:**
  - **Dado** R$ 1.000,00 em 3x,
  - **então** as parcelas são 333,33 + 333,33 + 333,34.
- **CA-FIN-02:**
  - **Dado** um lançamento de 900 em 3x com a 1ª parcela paga,
  - **quando** o total vai para 1.000,
  - **então** as 2 parcelas abertas passam a 350 cada e o saldo não muda.
- **CA-FIN-03:** uma baixa duplicada é recusada e o saldo é debitado uma única vez.

Permissões:

- `finance.{categories,subcategories,accounts,accounts_payable,accounts_receivable}.*`
- `finance.{cash_flow,spending_flow,billing}.view`

---

### 2.5 DRV — Drive

- **RF-DRV-01:** o gerenciador de arquivos tem:
  - navegação por pastas (breadcrumb), busca e upload múltiplo (botão e arrastar do sistema operacional, com overlay);
  - seleção com Ctrl/Cmd e Shift, e barra de ações em lote;
  - mover por arrastar ou por modal;
  - renomear, baixar, compartilhar e mandar para a lixeira.
  - No celular, um toque entra na pasta.
- **RF-DRV-02:** a lixeira permite restaurar, excluir definitivamente e esvaziar. Há uma tela de logs.
- **RN-DRV-01:**
  - Arquivo não fica na raiz; só pastas ficam.
  - Cada pasta tem um registro `drives` do tipo `folder`.
  - Nomes repetidos viram `nome (n).ext`.
- **RN-DRV-02:** acesso (`DriveService::userCanAccess`):
  - O dono sempre acessa.
  - `somente_proprietario` num item ou em **qualquer pasta acima** bloqueia quem não tem `acesso_total` explícito.
  - Os tipos (`PermissionTypeDriveEnum`) são `somente_proprietario`, `somente_leitura` e `acesso_total` (valores atuais mantidos — V-03).
- **RN-DRV-03:** mover:
  - Não pode ir para a raiz (arquivos) nem para dentro de si mesmo ou de uma descendente (pastas).
  - O banco é atualizado numa transação, e o disco depois do commit.
  - No S3, mover é copiar e apagar, exigindo `PutObjectAcl` e `retain_visibility=false`.
- **RN-DRV-04:** a lixeira usa soft delete. Restaurar traz as pastas acima e a subárvore. Excluir definitivamente grava o log e apaga do disco depois do commit.
- **RN-DRV-05 [alvo: log estruturado]:** todo upload, download, movimentação, exclusão e restauração gera um log com usuário e IP *(Q-S-06)*.
- **RN-DRV-06 [alvo]:** tudo vai para o disco `config('bucket.disk')`. Hoje as pastas são criadas no disco `public`.

Permissões: `drive.{drives,folders,trash,logs}.*`.

---

### 2.6 CRM — somente frontend (pendente)

- **RF-CRM-01:** o Kanban (`/crm/kanban`) tem:
  - 4 métricas (Total em aberto, Vendas ganhas no mês, Taxa de conversão e Propostas ativas);
  - seletor de funil e busca;
  - alternância Kanban/Tabela;
  - colunas por estágio, com cor, contagem e total;
  - cards arrastáveis.
- **RF-CRM-02:** a lista (`/crm/list`) mostra Proposta, Cliente, Vendedor, Estágio, Status, Valor e Ações.
- **RF-CRM-03:** o modal "Nova Proposta Comercial" tem Título, Cliente (com Cadastro rápido PF/PJ), Valor, Prioridade, Funil e Estágio.
- **RF-CRM-04:** a gaveta de detalhe tem:
  - status, prioridade e valor;
  - seletor de estágio e botões Ganho/Perdido;
  - as abas Visão geral e timeline (nota, ligação, reunião), Itens (produto, serviço ou livre; recalcula o total) e Histórico.
- **RN-CRM-01:** sem backend.
  - O `TenantCrmController` devolve dados mock, no mesmo formato dos tipos `types/crm/Deal.ts`.
  - Nada é gravado; arrastar, ganhar ou perder muda só o estado local.
- **RN-CRM-02 [alvo]:** criar as permissões `crm.deals.view` e `crm.pipelines.view` para controlar o menu *(Q-S-07)*.
- **Contratos pendentes — backlog do CRM** (o backend é especificado quando o CRM for definido). Esta lista **é** o backlog do CRM:
  - **MUD-01:** oportunidade/proposta do CRM → proposta de documentações (seção 3.1; contrato em RN-MUD01-02, aceite em CA-MUD01-01).
  - **MUD-03:** orçamento da calculadora → oportunidade no funil (seção 3.3):
    - listener `CreateCrmDealFromQuoteListener` sobre `QuoteCreatedEvent` (RN-MUD03-02);
    - efeitos de `QuoteConvertedEvent` e `QuoteDeletedEvent` no negócio (RN-MUD03-03 e RN-MUD03-05);
    - aceite em CA-MUD03-01.
  - **MUD-02:** o gatilho "conversão a partir do CRM" (RN-MUD02-01).

---

### 2.7 DOC-CAT — Cadastros auxiliares de Documentações

São 7 catálogos. **[alvo]** "Serviços cobráveis", que ainda existe hoje como 8º catálogo, é removido (MUD-04).

**Padrão comum:**

- `CatalogService` base.
- `ServerDataTable` (paginação, busca e "Mostrar excluídos").
- Colunas "Em uso" (`in_use_count`) e Status (Ativo/Excluído).
- Ações Editar, Excluir (soft delete) e Restaurar (`PATCH /{id}/restore`, com a permissão `delete`).

| Catálogo | Campos | Regras |
| --- | --- | --- |
| Bancos | `name` (100, único) | Seed: Banco do Brasil, Bradesco, Itaú, Santander, Caixa |
| Cartórios | `name`, `zip_code` (8 dígitos), `street`, `number`, `complement`, `neighborhood`, `city`, `state` (`BrazilianStateEnum`), `reference_point`, `business_hours` | Nome com `min:3`; CEP com ViaCEP |
| Tipos de contrato | `name` (único), `requires_financing` | Seed com os ids da origem: 1 CCFGTS, 2 PRÓ-COTISTA, 3 CCSBPE, 4 À VISTA COM FGTS (`false`), 5 MCMV, 6 CCSBPE IPCA, 8 CCSBPE + Poupança, 9 Cartório de Notas |
| Tipos de custo | `name` (único), `requires_notary`, `receipt_type` (`advisory`/`courier`/null) | Seed: Registro, ITBI, Assessoria (advisory), Matrícula, Motoboy (courier), Outros, Cartório de Notas |
| Tipos de imóvel | `name` (único), `shows_number`, `shows_complement`, `requires_development`, `shows_unit`, `shows_block` | Cada flag mostra ou oculta o campo do imóvel; `requires_development` torna o empreendimento obrigatório |
| Empreendimentos | `name` (255, único) | — |
| Etapas | ver 2.8 | — |

- **RN-DOC-CAT-01:** o nome passa por `trim` e colapso de espaços. Se o nome existir num registro excluído, a mensagem é "Já existe um cadastro excluído com este nome — restaure-o." (`UniqueCatalogNameRule`).
- **RN-DOC-CAT-02:** excluir um registro em uso não quebra os consumidores: as relações usam `withTrashed()`, e os selects mostram o valor atual mesmo excluído (`CatalogService::options`).
- **RN-DOC-CAT-03:** FKs dos consumidores usam `restrictOnDelete`.

Permissões: `documents.{banks,notaries,contract_types,cost_types,property_types,developments}.*`.

---

### 2.8 DOC-STG — Etapas da timeline (timeline por banco)

- **RF-DOC-STG-01:** a lista tem:
  - um seletor de timeline: "Padrão do sistema", mais os bancos com o badge "Personalizada (N etapas)" ou "Usa a padrão";
  - as colunas Ordem, Nome, Data, Upload, Conclusão (h), Alerta (h), Em uso e Status;
  - reordenação por arrastar (`PATCH /move {target_id}`) ou por cima/baixo (`{direction}`).
- **RF-DOC-STG-02:** o formulário tem:
  - Timeline (somente leitura);
  - Nome e Ordem;
  - Possui data / Data obrigatória;
  - Possui upload / Arquivo obrigatório / Título obrigatório;
  - Observação obrigatória;
  - Prazo de conclusão (h) e Prazo de alerta (h);
  - Exibir dados do imóvel e Exibir protocolo de registro.
- **RN-DOC-STG-01:** `stages.bank_id = NULL` é a timeline padrão (8 etapas no seed): Cadastro/Aprovação, Solicitação de Engenharia (`shows_property_data`), Saque de FGTS (data obrigatória), Entrevista, Conformidade, Assinatura de Escritura, Envio para Registro (`shows_registry_protocol`) e Finalização.
- **RN-DOC-STG-02:** a timeline de um banco nasce vazia. O `bank_id` é definido na criação e **não muda nunca**.
- **RN-DOC-STG-03:** `order` é único entre as etapas **ativas da mesma timeline**. Isso é validado no request e checado de novo com `lockForUpdate` (`StageOrderTakenException`).
- **RN-DOC-STG-04:** regras cruzadas:
  - `date_required` ⇒ `has_date`.
  - `upload_required` e `title_required` ⇒ `has_upload`.
  - `alert_deadline_hours` ≤ `completion_deadline_hours` quando ambos > 0.
- **RN-DOC-STG-05:** mover, restaurar e reordenar só afetam a timeline da própria etapa. Ao restaurar uma etapa cuja ordem já foi ocupada, ela vai para `max + 1`.
- **RN-DOC-STG-06:** editar uma etapa em uso mostra um aviso, porque as obrigatoriedades são lidas ao vivo na conclusão.
- **CA-DOC-STG-01:**
  - **Dado** que o Itaú tem 3 etapas ativas,
  - **quando** uma proposta do Itaú é criada,
  - **então** ela recebe as 3 etapas na ordem do Itaú.
  - Um banco sem etapas recebe a padrão.

Permissões: `documents.stages.*`.

---

### 2.9 DOC-PRO — Propostas

#### 2.9.1 Lista, cadastro e edição

- **RF-DOC-PRO-01:** a lista tem:
  - os chips "Todas (n)" e um por status, com contagem;
  - busca por nº, proponente ou CPF;
  - `ServerDataTable` com seleção;
  - "Imprimir lista" e "Imprimir selecionadas (n)";
  - as colunas Nº (5 dígitos), Cadastro, Proponentes, Criador, Imóvel, Valor de compra, Status (pílula colorida) e Etapa atual;
  - as ações Visualizar, Editar, Informativo PDF e Excluir.
- **RF-DOC-PRO-02:** o formulário de criação tem as seções:
  - **Dados da proposta:** Criador, Analista, Banco, Tipo de contrato, Tabela, Prazo, Condição do imóvel, valores (`MoneyInput`), perguntas Sim/Não e campos condicionais de FGTS e taxa de documentação.
  - **Parceiros.**
  - **Imóvel:** campos conforme as flags do tipo.
  - **Proponentes:** repetível, com busca por CPF e conta bancária.
  - **Vendedores:** PF/PJ.
  - **Observações:** 8 campos.
- **RF-DOC-PRO-03:** a edição tem 6 abas:
  1. Informações: formulário, `ApplicantEditor` e `SellerEditor`.
  2. Documentos.
  3. Particularidades.
  4. Acompanhamento.
  5. Pagamentos e Taxas.
  6. Recibos.
  - Botões Informativo e Acompanhamento (PDF).
  - Parceiro vê a tela em modo leitura.
- **RF-DOC-PRO-04:** a tela "Ver" (somente leitura) tem os cards Resumo, Acompanhamento, Documentos e Pagamentos.
- **RN-DOC-PRO-01:** validação (`StoreProposalRequest`):
  - `creator_id` e `analyst_id` precisam ter o cargo Administrador ou Analista.
  - Se o autor só tem cargos externos, `creator_id` = ele.
  - `partner_ids` precisam ter o cargo Parceiro.
  - Banco, contrato, `purchase_value` e `down_payment_value` são obrigatórios.
  - Se `requires_financing`: `amortization_table` e `payment_term` (1–600) também são obrigatórios.
  - Se `requires_development`: o empreendimento é obrigatório.
  - Pelo menos 1 proponente, com CPFs válidos e distintos.
  - O documento do vendedor é validado por `person_type`.
- **RN-DOC-PRO-02:** criação, numa transação:
  1. A proposta nasce `new`.
  2. Os proponentes passam por `ApplicantService::findOrCreateByCpf`. O existente é reaproveitado (e restaurado, se estava excluído) sem sobrescrever dados. O novo cria Contato PF, Usuário (cargo Cliente; reaproveita pelo e-mail, senha inutilizável) e Proponente, mais a conta bancária.
  3. Os vendedores passam por `SellerService::findOrCreateByDocument`, sem criar usuário.
  4. Parceiros, imóvel e timeline (RN-DOC-STG).
  - A criação manual **não gera linhas de custo**, então não distribui nada no financeiro. A distribuição (MUD-02) acontece quando as linhas são lançadas depois (RN-MUD02-01).
- **RN-DOC-PRO-03:** e-mails **depois do commit**: usuário novo recebe `ApplicantWelcomeMail` (link de 72 h); existente recebe `ProposalCreatedMail`.
- **RN-DOC-PRO-04:** status (`ProposalStatusEnum`): `new`, `in_progress`, `awaiting_property`, `canceled`, `finished` e `restricted`.
  - Manualmente, só `in_progress`, `awaiting_property` (mês 1–12 e ano), `canceled` (motivo) e `restricted` (motivo). Os motivos que não se aplicam são limpos.
  - `finished` só pela conclusão da última etapa. Depois disso, o status e o `finished_at` ficam travados.
- **RN-DOC-PRO-05:** trocar o banco antes de iniciar o acompanhamento recria a timeline. Depois de iniciado, a timeline é mantida e a tela avisa: "A timeline atual será mantida. Use Restaurar acompanhamento…".
- **RN-DOC-PRO-06:** proponentes e vendedores na edição:
  - Adicionar recusa duplicado (`PersonAlreadyInProposalException`).
  - Remover só desvincula; remover o último proponente é bloqueado (`LastApplicantException`).
  - CPF e tipo de pessoa não mudam.
- **RN-DOC-PRO-07:** excluir é soft delete; os usuários dos proponentes são mantidos. *(MUD-02 [alvo]: os lançamentos financeiros da proposta — ver 3.2.)*
- **RN-DOC-PRO-08:** visibilidade (`Proposal::scopeVisibleTo`), só para usuários externos, com as condições somadas por OR:
  - Parceiro: criador ou parceiro da proposta.
  - Vendedor do imóvel: `sellers.user_id`.
  - Cliente: `applicants.user_id`.
- **RN-DOC-PRO-09:** documentos visíveis (`DocumentOwnerEnum::visibleTo`):
  - Parceiro: comprador, vendedor e imóvel.
  - Cliente: tudo, menos vendedor.
  - Vendedor do imóvel: tudo, menos comprador.
  - Equipe: tudo.
  - No PDF informativo, CPF/CNPJ, e-mail e telefone do lado oculto aparecem mascarados (`DocumentMaskHelper`).

#### 2.9.2 Acompanhamento (timeline)

- **RN-DOC-PRO-10:**
  - **Iniciar:** marca `started_at` na etapa atual. Se `new`, passa para `in_progress`. Envia `ProposalTrackingStartedMail` aos proponentes (com o criador em cópia oculta).
- **RN-DOC-PRO-11:** **concluir** (`CompleteProposalStageRequest`):
  - Só vale para a etapa atual iniciada, ou para editar uma já concluída.
  - As obrigatoriedades vêm da etapa: data, título e arquivo (a menos que já exista documento) e observação.
  - Arquivos aceitos: pdf, doc, docx, jpg, jpeg, png, bmp e zip, até `bucket.proposal_document_max_kb` (20 MB).
  - O documento é gravado com `owner = stage`.
  - Ao concluir: `completed_at`, a próxima etapa por `position` vira a atual com `started_at`, e é enviado `ProposalStageCompletedMail`.
- **RN-DOC-PRO-12:** a última etapa concluída:
  - leva a `finished` com `finished_at`;
  - envia `ProposalStagesFinishedMail`;
  - envia `MissingSellerDocumentsMail` ao criador se faltam documentos obrigatórios do vendedor.
- **RN-DOC-PRO-13:** **restaurar** (com confirmação):
  - Apaga as etapas e os documentos de etapa (os arquivos, depois do commit).
  - Recria a timeline com o banco atual, sem iniciar nenhuma etapa.
  - Se o status era `in_progress` ou `finished`, volta para `new` com `finished_at = null`.
- **RN-DOC-PRO-14:** o prazo exibido é `completion_deadline_hours − horas desde o início`: "Faltam Nh", "Vence hoje" ou "Atrasada Nh".
- **RN-DOC-PRO-15 [alvo: envio único]:** o alerta de atraso (`AlertOverdueProposalStagesCommand`) roda em dias úteis às 07:00, para etapas em andamento com `alert_deadline_hours > 0` vencido.
  - O alvo é **registrar o envio** (`proposal_stages.overdue_alerted_at`) para não reenviar todo dia *(Q-S-08)*.
- **RN-DOC-PRO-16:** as etapas com `shows_property_data` exibem o imóvel; as com `shows_registry_protocol` exibem o painel do protocolo mais recente (2.12).

#### 2.9.3 Documentos

- **RN-DOC-PRO-17:** o disco é privado, no caminho `proposals/{id}/{uuid}.ext`.
  - `owner` ∈ {`buyer`, `seller`, `property`, `stage`, `general`}.
  - `buyer` e `seller` exigem pessoa vinculada.
  - O arquivo é apagado depois do commit.
- **RN-DOC-PRO-18:** o checklist de obrigatórios (`DocumentTypeEnum::requiredFor`):
  - **Comprador:** RG/CNH, Certidão de estado civil, Comprovante de endereço, Renda (3 últimos) e CTPS.
  - **Vendedor PF:** RG/CNH, Certidão de estado civil, Dados da conta de crédito, Matrícula e Capa do IPTU.
  - **Vendedor PJ:** Matrícula, ART, SCPO, Habite-se e Alvará.
  - **Imóvel:** Dados da conta de crédito, Matrícula e Capa do IPTU.
- **RF-DOC-PRO-05:** a aba Documentos tem:
  - o checklist por pessoa;
  - o formulário de envio: Pertence a, Pessoa, Tipo, Título e Arquivos múltiplos;
  - a tabela de enviados, com download e exclusão.

#### 2.9.4 Pagamentos e taxas, e recibos

- **RN-DOC-PRO-19:** as linhas de custo (`proposal_cost_items`, `CostItemTypeEnum`) são `emolument`, `extra_fee`, `service`, `itbi` (geradas pela calculadora ou pelo orçamento) e `manual`.
  - Só as `manual` são excluídas.
  - O valor de qualquer linha é editável (`PATCH …/cost-items/{costItem}`, *scoped*).
- **RN-DOC-PRO-20:** lançamento manual:
  - Campos: tipo de custo, cartório (obrigatório se `requires_notary`), rubrica, data, valor, observação e boleto anexo.
  - Opção de notificar parceiro e/ou proponente (`ProposalCostItemCreatedMail`).
- **RN-DOC-PRO-21:** o cliente anexa o comprovante (`proof_path`) só da própria proposta.
- **RN-DOC-PRO-22:** totais:
  - Emolumentos = soma de `emolument` + `extra_fee`.
  - Total geral = soma de todas as linhas.
- **RN-DOC-PRO-23:** recibos (`ReceiptTypeEnum`: `general`, `advisory`, `courier`):
  - **Geral:** exige total gasto e valor depositado.
  - **Vinculado a uma linha:** só para linha `manual` cujo tipo de custo tem `receipt_type`. **[alvo]** Linhas `service` deixam de gerar recibo (MUD-04 / D-03); os recibos já emitidos continuam válidos.
  - O `notary_name` é copiado como texto.
  - O documento é validado como CPF ou CNPJ.
  - Há PDF por recibo.
- **RN-DOC-PRO-24 (MUD-02) [alvo]:** toda linha criada, editada ou excluída reflete no financeiro (seção 3.2).

#### 2.9.5 PDFs

- **RF-DOC-PRO-06:** os PDFs (dompdf, A4) são:
  - informativo (com máscara por cargo);
  - acompanhamento;
  - lista (até 500 linhas, paisagem);
  - selecionadas;
  - recibo.

Permissões:

- `documents.proposals.*` (`edit` usa `view` para abrir a tela)
- `documents.proposal_timeline.{edit,delete}`
- `documents.proposal_documents.{view,create,delete}`
- `documents.proposal_financial.{view,create,edit,delete}`

A `ProposalPolicy` tem: `viewAny`, `view`, `create`, `update`, `delete`, `manageTimeline`, `manageFinancial`, `viewFinancial`, `uploadDocument`, `deleteDocument`, `downloadDocument`, `uploadProof`, `viewAttachment` e `print`.

---

### 2.10 DOC-CALC — Calculadora de emolumentos e ITBI

- **RF-DOC-CALC-01:** o início tem a UF (11 suportadas: AM, BA, ES, GO, MG, MS, PA, PR, RJ, RS, SP) e o município do IBGE (capital pré-selecionada), mais os cards dos tipos permitidos para a UF.
- **RF-DOC-CALC-02:** o formulário de cálculo tem:
  - valor do imóvel;
  - valor do financiamento (tipo 2);
  - SFH/SFI (tipo 2);
  - primeiro imóvel (se o município é `first_property_rate`);
  - desconto legal (`DiscountDialog`, com "Remover seleção" que realmente limpa);
  - observações do tipo.
- **RF-DOC-CALC-03:** o resultado mostra a `FeeResultTable`: atos, SUBTOTAIS, taxas extras, total do cálculo, ITBI, serviços e TOTAL geral. Ações: Voltar, Vincular à proposta e Gerar orçamento.
- **RN-DOC-CALC-01:** tipos (`CalculationTypeEnum`):
  - 1 Registro em geral (todas as UFs, com ITBI).
  - 2 Compra e venda com alienação fiduciária (todas, exceto PA; com ITBI).
  - 3 Averbação (só RJ; sem ITBI).
  - Descontos: EP só no RJ e HAP só no AM.
  - `financing_value ≤ property_value`.
- **RN-DOC-CALC-02:** o ITBI (`ItbiCalculator`) é calculado **antes** da API paga.
  - O município é casado pelo `ibge_code`.
  - Sem cadastro → `ItbiNotConfiguredException`, com a mensagem de validação "Município não encontrado…".
  - As fórmulas, com `V` = valor do imóvel, `F` = financiamento e `pct = %/100`, arredondando só no fim, com `max(0, …)`:
    - **Alíquota cheia** (registro em geral ou SFI): `V×own`. Em `value_brackets`, `V×full_rate`.
    - **`financed_cap` + SFH:** se `F > cap`, `(V−cap)×own + cap×financed`; senão, `(V−F)×own + F×financed`.
    - **`first_property_rate` + SFH:** `(V−F)×own + F×(primeiro ? first : other)`.
    - **`standard` + SFH:** `(V−F)×own + F×financed`.
    - **`value_brackets` + SFH:** usa a faixa com `min ≤ V ≤ max` (inclusiva) e calcula `V×rate − discount`. Sem faixa, dá erro.
- **RN-DOC-CALC-03:** a API (`RegistryFeeApiClient`):
  - Payload: `{codigo_municipio, consulta_id, valor_imovel = intdiv(cents,100), valor_financiamento (tipo 2), desconto "CODE/UF"}`.
  - TLS ligado, timeout de 15 s e retry só em falha de conexão.
  - 401/403/404/429/5xx → "servidor da calculadora está fora do ar…".
  - `errorMessage ?? message` → erro no campo `property_value`.
- **RN-DOC-CALC-04:** o resultado fica em `fee_calculations`:
  - UUID, imutável e expira em 7 dias (`fee-calculations:prune`).
  - Só o dono ou o admin vê (`FeeCalculationPolicy`); um cálculo expirado dá 404.
  - O navegador só carrega o UUID.
- **RN-DOC-CALC-05:** o `extra_information` (HTML da API) é sanitizado no servidor. Os totais são montados no servidor (`FeeBreakdownBuilder`).
- **RN-DOC-CALC-06:** **vincular à proposta** (`AttachFeeEstimateToProposalAction`):
  - Lista só propostas visíveis e **sem** `fee_estimate` (Combobox, limite de 20).
  - Trava a proposta e revalida (`ProposalAlreadyHasFeeEstimateException`).
  - Cria o `FeeEstimate` (`valid_until = null`).
  - Gera as linhas (`CostItemsBuilder`) e anexa os **serviços do catálogo** (MUD-04 **[alvo]**; hoje, os serviços cobráveis).
  - **[alvo]** Dispara a **distribuição financeira** das linhas novas (MUD-02).
  - Rate limit de 20 por minuto em "calcular".
- **RF-DOC-CALC-04:** o ITBI tem cadastro de municípios (lista, criar e editar com o `StateMunicipalityPicker` e o módulo), alíquotas (campos por módulo) e faixas (repetidor, com "Alíquota cheia").
- **RN-DOC-CALC-07:** cadastro de ITBI:
  - Um município, um módulo. Trocar o módulo apaga as alíquotas e faixas anteriores, na mesma transação.
  - As faixas têm `min < max` e não se sobrepõem (`NonOverlappingBracketsRule`).
  - O `ibge_code` é único.
- **RN-DOC-CALC-08:** os municípios do IBGE vêm do backend (`IbgeLocalityClient`), com cache **global** de 30 dias por UF.
- **CA-DOC-CALC-01:** os casos de teste atuais (`ItbiCalculatorTest`) continuam valendo. Exemplo: `FinancedCap` com SFH, V=500.000, F=300.000, 3%/0,5% e teto 400.000 → 7.500,00.

Permissões: `documents.itbi_calculator.{view,create}` e `documents.itbi_municipalities.*`.

---

### 2.11 DOC-QUO — Orçamentos

- **RF-DOC-QUO-01:** a lista tem busca por nome ou CPF, e as colunas Nº, Cliente, CPF, Validade e Status (Em aberto, Expirado, Convertido). O botão "Nova calculadora" leva à calculadora.
- **RF-DOC-QUO-02:** o formulário tem Nome, CPF, E-mail, Telefone, Profissão, Estado civil, Banco e Validade, mais os **Serviços** (`ServicePicker`; **[alvo]** sobre o catálogo `services`, MUD-04; hoje, sobre os serviços cobráveis) e a prévia da `FeeResultTable`.
- **RF-DOC-QUO-03:** a tela "Ver" tem Imprimir, Enviar por e-mail, Editar e Gerar proposta (com confirmação), e "Abrir proposta" quando convertido. Mostra os alertas de convertido e de expirado.
- **RN-DOC-QUO-01:** criar (`CreateQuoteAction`):
  - Normaliza os dados: nome em Title Case, CPF só com dígitos, e-mail em minúsculas.
  - Exige `valid_until ≥ hoje`.
  - Cria o `FeeEstimate` com o snapshot.
  - Grava os serviços no pivot com o valor efetivo (negociado ou preço de tabela). **[alvo]** O pivot passa a ser `quote_service`, e o preço de tabela, o `sell_value` (MUD-04); hoje é `quote_billable_service`.
  - **[alvo] Dispara `QuoteCreatedEvent` depois do commit (MUD-03).**
- **RN-DOC-QUO-02:** o status é derivado:
  - `converted`: `fee_estimate.proposal_id` preenchido.
  - `expired`: `valid_until < hoje`.
  - Senão, `open`.
- **RN-DOC-QUO-03:**
  - **Editar:** cliente, serviços e validade. Bloqueado se convertido.
  - **Excluir:** soft delete do orçamento e da estimativa. Bloqueado se convertido. **[alvo]** Dispara `QuoteDeletedEvent` depois do commit (RN-MUD03-05).
- **RN-DOC-QUO-04:** o e-mail (`QuoteSummaryMail`, em fila) leva o PDF anexo, gerado no worker. O PDF tem cabeçalho com o logo e os dados da empresa, "Orçamento Nº 00012", os blocos de administrador e proponente, a tabela e o aviso legal.
- **RN-DOC-QUO-05:** **converter em proposta** (`ConvertQuoteToProposalAction`, POST, idempotente):
  1. `lockForUpdate` e checagem de status `open` (`QuoteNotOpenException`).
  2. `ProposalService::createFromQuote`: criador e analista = autor; banco do orçamento; contrato (hoje o `proposals.default_contract_type_id`, id 4; **[alvo]** o escolhido no diálogo, RN-DOC-QUO-06); `new`; imóvel usado; valores zerados; proponente por CPF; timeline.
  3. As linhas de custo vêm da estimativa e dos serviços, com o valor negociado.
  4. Os serviços vão para a proposta (**[alvo]** `proposal_service`; hoje `proposal_billable_service`).
  5. `estimate.proposal_id = proposta`.
  6. **[alvo]** Distribuição financeira (MUD-02).
  7. Depois do commit: `AttachQuotePdfToProposalJob` (o PDF vira documento `fee_estimate`) e o e-mail ao cliente. **[alvo]** Também dispara `QuoteConvertedEvent` (RN-MUD03-05).
  8. *(MUD-03, pendente: avançar a oportunidade do CRM para "Ganho".)*
- **RN-DOC-QUO-06 [alvo] — diálogo de conversão (Q-S-09):**
  - "Gerar proposta", na tela Ver, abre o diálogo de confirmação atual, que ganha o select "Tipo de contrato" com os tipos ativos.
  - O select vem pré-selecionado com o `proposals.default_contract_type_id`, se ele estiver ativo; senão, vem vazio.
  - O contrato é obrigatório e vai no POST da conversão.
  - Um contrato com `requires_financing` é aceito. A tabela de amortização e o prazo ficam vazios e são exigidos na próxima edição da proposta, como os valores zerados.
  - Visual (P-10): o mesmo `AlertDialog` de hoje, com o select usando os componentes já existentes.
- **CA-DOC-QUO-01:** uma segunda chamada de conversão não cria outra proposta.
- **CA-DOC-QUO-02:** um orçamento com um serviço de `sell_value` 150000 e valor negociado vazio grava `amount = 150000`.
- **CA-DOC-QUO-03:**
  - **Dado** um orçamento em aberto,
  - **quando** "Gerar proposta" é confirmado com o tipo de contrato "CCSBPE",
  - **então** a proposta nasce com esse contrato. Sem contrato informado, a conversão é recusada com erro de validação.

Permissões: `documents.quotes.*` (e-mail e converter usam `edit`).

---

### 2.12 DOC-REG — Protocolos de registro

Decisões já registradas: PR-D1 a PR-D7.

- **RF-DOC-REG-01:** a lista tem:
  - as abas Em aberto, Encerrados e Todos, com contagens;
  - o filtro de posição, a busca e o chip "filtro de proposta";
  - as colunas Proposta, Protocolo, Comprador, Vendedor, Entrada, Saída e Posição (badge).
- **RF-DOC-REG-02:** o formulário tem:
  - Proposta (Combobox, opcional, preenchendo as partes);
  - Nº do protocolo, Posição, Comprador, Vendedor, Data de entrada e Data de saída;
  - o aviso "Informe a data de saída do cartório." em posição encerrada.
- **RN-DOC-REG-01:** as posições (`RegistryProtocolPositionEnum`) são 1 Em registro PG, 2 Em análise, 3 Exigência, 4 Em pagamento do banco e 5 Concluído. A 4 e a 5 são **encerradas**.
- **RN-DOC-REG-02:**
  - `protocol_number` é único entre os protocolos ativos (validação, sem índice unique).
  - `exited_at ≥ entered_at`.
  - Comprador com `min:3`; vendedor opcional.
  - `proposal_id` é opcional (FK `nullOnDelete`).
  - Sem restauração pela tela.
- **RN-DOC-REG-03:** as partes vêm de todos os proponentes e vendedores, separados por vírgula, e podem ser editadas.
- **RN-DOC-REG-04:** a timeline mostra o protocolo mais recente (`latestRegistryProtocol`) e "+N anteriores" a quem vê a proposta.

Permissões: `documents.registry_protocols.*`.

---

### 2.13 DOC-REP — Relatório de propostas finalizadas

Decisões já registradas: RE-D1 a RE-D5.

- **RF-DOC-REP-01:** os filtros por GET são data inicial, data final e empreendimento. A tabela tem seleção por página ou "todos do filtro", e as colunas Nº, Criado por, Proponentes, Empreendimento, Documento e Data de finalização.
- **RN-DOC-REP-01:**
  - O critério é `status = finished`, sem soft delete.
  - A data é `COALESCE(data da etapa final, DATE(finished_at))`.
  - 25 por página e respeita o `visibleTo`.
- **RN-DOC-REP-02:** o ZIP contém só o documento da etapa final.
  - Até 50 arquivos: síncrono.
  - De 51 a 500: fila (`BuildFinishedProposalsZipJob`), com e-mail e link assinado de 24 h.
  - Acima de 500: 422.
  - Os arquivos ausentes vão para um `LEIA-ME.txt`.
  - O `reports:prune-zips` roda de hora em hora.

Permissões: `documents.reports.view`.

---

## 3. Mudanças de regra do PRD de evolução **[alvo]**

### 3.1 MUD-01 — Proposta do CRM vira proposta de documentações *(contrato; backend pendente)*

**Regra:** uma proposta comercial criada no CRM pode ser convertida numa **proposta de documentações** (DOC-PRO).

- **RN-MUD01-01:**
  - A ação "Gerar proposta de documentações" aparece na gaveta de detalhe do negócio (`DealDetailDrawer`) **só quando o negócio está "Ganho"** (Q-S-10).
  - Ela é visível com `documents.proposals.create`.
- **RN-MUD01-02 — contrato do backend** (implementado só quando o CRM existir):
  - Rota: `POST /crm/deals/{deal}/convert-to-proposal` → `TenantConvertCrmDealToProposalController`.
  - Action: `ConvertCrmDealToProposalAction`, que chama `ProposalService::createFromCrmDeal(CrmDealDto $deal, User $actor): Proposal`.
  - **Mapeamento:**
    - O contato do negócio vira o proponente (`ApplicantService::findOrCreateByCpf`). Exige CPF; se o contato for PJ, a conversão pede um proponente PF.
    - `total_value` vira `purchase_value` (Q-S-10).
    - Os itens de serviço do negócio viram `proposal_service` e linhas `service`.
    - Banco e contrato são escolhidos num diálogo antes da conversão.
  - **Idempotência:** `crm_deals.proposal_id` (único). Uma segunda conversão é recusada.
  - Depois do commit: os mesmos e-mails da criação manual; o negócio guarda o link da proposta.
- **RN-MUD01-03 — interino (sem backend de CRM)** (Q-S-10, adotado): o botão abre `tenant.documents.proposals.create` com o nome do cliente e o valor pré-preenchidos via query string, sem vínculo persistido.
- **CA-MUD01-01 (quando o CRM existir):**
  - **Dado** um negócio com contato PF de CPF válido,
  - **quando** "Gerar proposta de documentações" é acionado,
  - **então** é criada uma proposta `new`, com o proponente e a timeline do banco escolhido, e o negócio passa a apontar para ela.

### 3.2 MUD-02 — Proposta distribui valores para o financeiro

**Regra (PRD):** quando uma proposta é gerada, todos os valores de emolumentos, pagamentos/taxas e serviços são distribuídos automaticamente no Financeiro, em contas a pagar ou a receber.

O desenho abaixo foi **confirmado** (Q-S-11 a Q-S-14):

- distribuição em toda linha criada;
- receber tudo do cliente e pagar os repasses;
- categorias fixas criadas pelo sistema, sem tela de configuração (Q-S-21);
- um lançamento por linha.

- **RN-MUD02-01 — Gatilhos:** sempre na **mesma transação** da operação que cria as linhas:
  - conversão de orçamento;
  - vínculo de cálculo à proposta;
  - lançamento manual em "Pagamentos e Taxas";
  - conversão a partir do CRM (MUD-01, pendente).
  - Ou seja: **em toda linha criada**, na criação da proposta e depois dela (Q-S-11). A criação manual da proposta não gera linhas, então não é gatilho (RN-DOC-PRO-02).
- **RN-MUD02-02 — Mapeamento** (Q-S-12):

  | Linha (`CostItemTypeEnum`) | Conta a **receber** (do cliente) | Conta a **pagar** (repasse) |
  | --- | --- | --- |
  | `emolument` | ✔ | ✔ (custo de cartório) |
  | `extra_fee` | ✔ | ✔ (custo de cartório) |
  | `itbi` | ✔ | ✔ (imposto municipal) |
  | `manual` | ✔ | ✔ (custo do tipo de custo da linha) |
  | `service` | ✔ | — (receita da empresa) |

  A conta a pagar **não tem fornecedor** (`financial_contact_id = null`, RN-FIN-14, Q-S-18). O destino do pagamento é identificado pela descrição e pelo tipo de custo.

- **RN-MUD02-03 — Dados do lançamento:**

  | Campo | Valor |
  | --- | --- |
  | Contato financeiro | **Receber:** o 1º proponente (contato), papel `cliente` (`ContactTypeEnum`). Se o contato ainda não tem o papel, ele é criado (`ClientService::persist`). Se o papel está **inativo**, o lançamento a receber da linha é pulado com aviso (`warning`), sem bloquear a operação (Q-S-19). **Pagar:** nenhum (RN-FIN-14, Q-S-18) |
  | Categoria / subcategoria | As **categorias fixas** da RN-MUD02-09: **receber** em "Documentações", com a subcategoria do tipo da linha; **pagar** em "Documentações — Repasses", com a subcategoria do tipo da linha (Q-S-21) |
  | Conta bancária | A conta principal (`main_account`) |
  | Tipo de custo | O `cost_type_id` da linha (manual) ou o tipo de custo correspondente (Registro, ITBI…) |
  | Condição | À vista (1 parcela), status `open` |
  | Forma de pagamento | `pix` (`FinancialPaymentMethodEnum::PIX`). O tenant pode alterá-la editando o lançamento (Q-S-21) |
  | Vencimento | A `date` da linha, ou hoje quando a linha não tem data (Q-S-21) |
  | Descrição | `Proposta Nº 00042 — <descrição da linha>` |
  | Vínculo | `proposal_id` e `proposal_cost_item_id` nas tabelas `account_payables`/`account_receivables` (FKs nulas, `nullOnDelete`) |

- **RN-MUD02-04 — Agrupamento** (Q-S-14): **um lançamento por linha**, o que permite sincronizar edição e exclusão.
- **RN-MUD02-09 — Categorias fixas (Q-S-21):** **não há tela de configuração**. Uma migration idempotente de tenant cria as categorias financeiras abaixo, que valem para todos os tenants:

  | Categoria (tipo) | Subcategorias |
  | --- | --- |
  | "Documentações" (`receita`) | Emolumentos, Taxas extras, ITBI, Lançamentos manuais, Serviços |
  | "Documentações — Repasses" (`despesa`) | Emolumentos, Taxas extras, ITBI, Lançamentos manuais |

  - A subcategoria é escolhida pelo tipo da linha (`emolument` → Emolumentos, `extra_fee` → Taxas extras, `itbi` → ITBI, `manual` → Lançamentos manuais, `service` → Serviços).
  - Os ids ficam gravados em `tenant_settings` (`documents.finance.*`) pela própria migration. Assim, renomear a categoria na tela de categorias não quebra a distribuição.
  - Nenhum fornecedor é configurado (Q-S-18).
  - O tenant pode mudar a categoria de um lançamento já criado editando o lançamento.
  - **Adendo (evolução futura):** se algum tenant precisar controlar quais categorias a distribuição usa, a escolha poderá virar configurável (por exemplo, um card em Configurações → Empresa), gravando nas mesmas chaves `documents.finance.*`. Isso exige uma nova decisão registrada neste spec.
- **RN-MUD02-05 — Sincronização:**
  - **Editar o valor da linha** atualiza o lançamento vinculado se as parcelas estiverem `open`. Se já houver parcela paga, a edição da linha é recusada com aviso.
  - **Excluir uma linha manual** exclui os lançamentos vinculados, com estorno se pagos (a regra RN-FIN-10 já faz isso).
  - **Excluir a proposta** (soft delete): os lançamentos abertos vinculados são excluídos; os pagos ficam (histórico) (Q-S-14).
- **RN-MUD02-06 — Pré-condições:**
  - Sem conta principal (nenhuma conta bancária cadastrada), ou com uma categoria ou subcategoria fixa excluída ou inativa, a operação **não** é bloqueada: a distribuição é pulada, e um aviso (`warning`) pede para cadastrar a conta ou restaurar/ativar a categoria.
  - Com o papel de cliente do 1º proponente inativo, só o lançamento a receber é pulado, com aviso (Q-S-19).
  - **Sem o módulo Financeiro** (`finance`) no plano do tenant, a distribuição é pulada **sem aviso** e nenhum lançamento é criado (Q-S-22). Se o tenant contratar o módulo depois, as linhas novas passam a ser distribuídas, e as propostas antigas podem ser trazidas uma a uma pelo botão "Distribuir no financeiro".
  - Um botão "Distribuir no financeiro" na aba Pagamentos reprocessa as linhas sem lançamento (idempotente por `proposal_cost_item_id`).
- **RN-MUD02-07 — Idempotência:** cada linha gera no máximo um lançamento por direção (`unique(proposal_cost_item_id)` em cada tabela).
- **RN-MUD02-08 — Telas:**
  - Aba "Pagamentos e Taxas": colunas "A receber" e "A pagar", com o status da parcela e um link para o lançamento. As colunas e o botão "Distribuir no financeiro" só aparecem quando o tenant tem o módulo Financeiro (Q-S-22).
  - Listas do financeiro: badge "Proposta Nº 00042", com link.
- **CA-MUD02-01:**
  - **Dado** um orçamento com 2 atos, 1 taxa extra, ITBI e 1 serviço,
  - **quando** é convertido,
  - **então** são criados 5 lançamentos a receber e 4 a pagar, com os mesmos valores em centavos.
- **CA-MUD02-02:**
  - **Dado** uma linha com lançamento aberto,
  - **quando** o valor dela é editado,
  - **então** o total e a parcela do lançamento acompanham.

### 3.3 MUD-03 — Orçamento da calculadora entra no funil do CRM *(contrato; backend pendente)*

**Regra (PRD):** quando um orçamento é gerado pela calculadora, ele **precisa** ir para o funil de vendas do CRM.

- **RN-MUD03-01:** `CreateQuoteAction` dispara `QuoteCreatedEvent(quoteId)` **depois do commit**. É implementado na fase R5.
- **RN-MUD03-02:** o listener `CreateCrmDealFromQuoteListener` fica **pendente**. Ele só é registrado quando o backend do CRM existir.
- **Contrato:**
  - Cria (idempotente por `quote_id`) uma oportunidade:
    - título "Orçamento Nº 00012 — {cliente}";
    - contato encontrado ou criado pelo CPF do orçamento;
    - `total_value` = TOTAL geral do orçamento;
    - funil padrão, **1º estágio** (Q-S-15);
    - responsável = quem criou o orçamento;
    - itens = os serviços do orçamento.
- **RN-MUD03-03:** os efeitos do ciclo de vida do orçamento no negócio, também pendentes:
  - convertido em proposta → negócio "Ganho";
  - expirado → nada (Q-S-15);
  - excluído → negócio "Perdido" com o motivo "Orçamento excluído" (Q-S-15).
- **RN-MUD03-04 — Interino:** enquanto não houver backend, o Kanban mock não recebe orçamentos. A pendência fica no backlog do CRM (seção 2.6, "Contratos pendentes").
- **RN-MUD03-05 (Q-S-20):** além do `QuoteCreatedEvent`, a fase R5 dispara, depois do commit:
  - `QuoteConvertedEvent(quoteId)`, pelo `ConvertQuoteToProposalAction`;
  - `QuoteDeletedEvent(quoteId)`, pela exclusão do orçamento.
  - Nenhum dos três tem listener até o backend do CRM existir; os efeitos no negócio são os da RN-MUD03-03.
- **CA-MUD03-01 (quando o CRM existir):**
  - **Dado** um cálculo,
  - **quando** o orçamento é salvo,
  - **então** aparece um card no 1º estágio do funil padrão com o valor do orçamento.

### 3.4 MUD-04 — Remover "Billable Service" e ligar o orçamento ao catálogo de Serviços

- **RN-MUD04-01:** deixam de existir (fase R3):
  - a tabela `billable_services`, o model, o service, o controller, os requests, a factory, as telas e o item de menu "Serviços Cobráveis";
  - as permissões `documents.billable_services.*`.
- **RN-MUD04-02:** os pivots passam a ser:
  - `quote_service (quote_id, service_id, amount)`, com unique no par;
  - `proposal_service (proposal_id, service_id, amount)`, com unique no par.
  - FK para `services` com `restrictOnDelete` (o serviço só sofre soft delete).
- **RN-MUD04-03:** o `ServicePicker` lista os serviços **ativos** do catálogo (`status = true`), com o `sell_value` como preço de tabela. O valor negociado é opcional (vazio = `sell_value`).
- **RN-MUD04-04:** a linha de custo `service` recebe:
  - `description = service.name`;
  - `service_description = service.description` (pode ser nula);
  - `generates_receipt = false`. As linhas de serviço **não geram recibo**.
- **RN-MUD04-05:** editar o valor de uma linha `service` também atualiza o `proposal_service.amount` correspondente (hoje os dois divergem).
- **RN-MUD04-06:** o `ServicesService` atual vira `ServiceCatalogService`. O método `resolveSelection()` (ids válidos, sem duplicados, valor efetivo) passa para ele.
- **CA-MUD04-01:** o menu de Documentações não tem "Serviços Cobráveis", e a permissão `documents.billable_services.view` não existe no catálogo.

---

## 4. Catálogo de permissões (alvo)

Formato: `<modulo>.<recurso>.<acao>`. O catálogo mantém só o que tem tela ou rota. A exceção é `sales.*`, que fica reservado para o módulo Vendas futuro.

| Módulo | Recursos (ações) |
| --- | --- |
| registrations | clients, suppliers, employees (view, create, edit, delete) |
| products | products, categories (CRUD) |
| services | services, categories (CRUD) |
| finance | categories, subcategories, accounts, accounts_payable, accounts_receivable (CRUD); cash_flow, spending_flow, billing (view) |
| documents | proposals, proposal_documents, proposal_financial, quotes, banks, notaries, contract_types, cost_types, property_types, developments, stages, itbi_municipalities, registry_protocols (CRUD); proposal_timeline (edit, delete); itbi_calculator (view, create); reports (view) |
| drive | drives, folders, trash, logs (CRUD atual) |
| settings | company (view, edit); roles, users (CRUD) |
| crm | deals (view), pipelines (view) — só para o menu *(Q-S-07)*; atribuídas ao Administrador pela migration (V-12) |
| sales | sales, quotations (CRUD) — mantidas como estão para o módulo Vendas futuro |

**A remover do catálogo atual [alvo]:**

- `documents.contracts.*` (sem uso).
- `documents.billable_services.*` (MUD-04).

---

## 5. Mudanças de dados [alvo]

O schema atual está em `docs/arquitetura/database_dictionary.md`. As mudanças abaixo entram por migrations novas de tenant ou central (dados de homologação — V-02; os tenants podem ser recriados se preciso). Alterações:

| Tabela | Mudança |
| --- | --- |
| `contacts` | `cpf_cnpj` só com dígitos e **unique** |
| `products`, `services`, `employees`, `plans` | Dinheiro passa a `unsignedBigInteger` (D-06) |
| `account_payables.total`, `account_receivables.total`, `installments.value` | `bigInteger` → `unsignedBigInteger`, mantendo o `nullable` dos totais (D-06, V-10) |
| `bank_accounts.initial_balance`, `current_balance` | **Continuam** `bigInteger` com sinal: o saldo pode ficar negativo (V-10) |
| `services` | Passa a ser referenciada por `quote_service` e `proposal_service` |
| `account_payables`, `account_receivables` | `cost_id` mantido; `bank_account_out` (int sem FK) é removido (Q-S-16); **novas** `proposal_id` e `proposal_cost_item_id` (nulas, `nullOnDelete`, unique por item); em `account_payables`, `financial_contact_id` passa a aceitar nulo (RN-FIN-14) |
| `drive_logs` | Colunas estruturadas: `user_id`, `drive_id`, `action`, `ip_address`, `meta` (json) *(Q-S-06)* |
| `tenant_settings` | `key` **unique**; o model passa a usar `SoftDeletes` |
| `permissions` (central) | `name` **unique** |
| `proposal_stages` | **Nova** `overdue_alerted_at` (RN-DOC-PRO-15) |
| `users` (tenant) | **Novo** `softDeletes()`. Excluir = `status = false` + soft delete (Q-S-01) |
| `services` | `sku` **unique**, considerando soft delete (Q-S-03) |
| `financial_categories`, `financial_subcategories` | **Novas** categorias fixas "Documentações" (`receita`) e "Documentações — Repasses" (`despesa`), com as subcategorias por tipo de linha, criadas por migration idempotente (RN-MUD02-09) |
| `tenant_settings` | Novas chaves `documents.finance.*` com os ids das categorias e subcategorias fixas, gravadas pela mesma migration (RN-MUD02-09). Sem tela de edição |
| `billable_services`, `quote_billable_service`, `proposal_billable_service` | **Removidas** depois da migração de dados: cada serviço cobrável vira um `service` na categoria "Documentação" (`price` → `sell_value`), e os vínculos passam para `quote_service` / `proposal_service` (V-09) |
| `proposal_cost_items` | **Nova** `service_id` (nullable), preenchida para as linhas `service` |
| `subscriptions`, `subscription_items`, `payments`, `payment_methods`, `mp_webhooks`, `coupons`, `coupon_redemptions` | **Mantidas sem uso**, fora do escopo (D-11 / V-08) |
| `stages` | `unique(bank_id, order)` continua sem índice (há etapas excluídas repetidas); a regra é validada no service |
| CRM | Nenhuma tabela (P-14) |

---

## 6. Integrações e agendamentos

| Item | Regra |
| --- | --- |
| API de emolumentos | Como em RN-DOC-CALC-03. Chaves `REGISTRY_FEE_CALCULATOR_URL`, `…_TOKEN` e `…_TIMEOUT` (token novo, nunca no código) |
| IBGE | Backend, cache global de 30 dias por UF |
| ViaCEP | Direto do navegador (`useCepLookup`) |
| Storage | `config('bucket.disk')` (MinIO), prefixo por tenant, disco privado; `BUCKET_SIGNED_URLS` / `BUCKET_URL_TTL` documentados no `.env.example` |
| E-mail | 11 Mailables com `ShouldQueue` e `afterCommit` |
| `proposals:alert-overdue-stages` | Dias úteis, 07:00, sem reenvio (RN-DOC-PRO-15) |
| `fee-calculations:prune` | Diário |
| `reports:prune-zips` | De hora em hora |
| `bucket:ensure`, `tenants:sync-permissions` | Manuais |

---

## 7. Requisitos não funcionais

| ID | Requisito |
| --- | --- |
| RNF-01 | Isolamento total entre tenants: dados, disco, cache, sessão e fila (constitution, P-01) |
| RNF-02 | Listagens que crescem sem limite usam paginação no servidor e não têm N+1 (`Model::preventLazyLoading()` em dev) |
| RNF-03 | Responsividade como a atual (mobile: drive por toque, tabelas com rolagem horizontal) |
| RNF-04 | Dark mode em todas as telas, sem flash no carregamento (script inline no `app.blade.php`) |
| RNF-05 | Rate limits: `password-reset` 5/min, `documents-lookup` 30/min, `fee-calculator` 20/min, status do cadastro 60/min |
| RNF-06 | Arquivos de pessoas só em disco privado, com download autorizado (LGPD) |
| RNF-07 | Ações críticas idempotentes: baixa de parcela, conversão de orçamento, vínculo de cálculo e conversão de CRM |
| RNF-08 | Toda regra (RN) com teste Pest; os 487 testes atuais são a base de regressão de toda mudança |

---

## 8. Defeitos atuais a corrigir [alvo]

1. `contacts.cpf_cnpj` sem unique e com máscara inconsistente → RN-CAD-01.
2. Excluir conta bancária sem estorno → RN-FIN-02.
3. Diretórios do Drive no disco `public` → RN-DRV-06.
4. Alerta de etapa reenviado todo dia → RN-DOC-PRO-15.
5. `proposal_billable_service.amount` divergente da linha → RN-MUD04-05.
6. Controllers que consultam Eloquent, ou renderizam páginas inexistentes → constitution, seção 4.
7. Nomes de classe sem sufixo de tipo → D-01 (fase R1). Os valores de enum e as query strings em português **ficam** (V-03).
8. Tipos monetários mistos → D-06.
9. `tenant_settings.key` e `permissions.name` sem unique → seção 5.
10. CRM sem permissão → seções 2.6 e 4. O menu "Vendas" **não** é defeito: ele fica para o módulo futuro (seção 0).
11. Fonte Instrument Sans não carregada; botão "Exportar lista" sem ação → RF-CORE-12 (Q-S-17).
12. Arquivos mortos no front (`login-04`, `SearchForm.vue`, `PermissionGroupCard.vue`, `central/Dashboard.vue`, páginas `drive_folder/*` e `show` de cadastros referenciadas sem existir) e o `TenantSettingController` sem rota.
13. `PermissionsEnum` sem uso → removido na R1.
14. `DriveService` recebe FormRequest em vez de array, misturando validação e regra → constitution P-05.

---

## 9. Decisões registradas (perguntas do spec)

As perguntas Q-S-01 a Q-S-17 foram respondidas em **2026-10-26**, e as Q-S-18 a Q-S-22 em **2026-10-27** (auditoria de consistência). **Em todas foi adotada a recomendação, exceto na Q-S-17**, que o usuário revisou no mesmo dia, e nas **Q-S-18 e Q-S-21**, que têm partes definidas pelo próprio usuário. A Q-S-04 teve o texto corrigido em 2026-10-27. Onde o texto acima diz *(Q-S-nn)*, vale a decisão desta tabela.

| ID | Pergunta | Decisão (= recomendação) |
| --- | --- | --- |
| Q-S-01 | Excluir usuário: manter a exclusão física ou passar a inativar/soft delete? | Inativar (`status = false`) + soft delete |
| Q-S-02 | Dashboard: alimentar os KPIs financeiros e "Próximos vencimentos" com dados reais? | Sim; o resto fica como placeholder |
| Q-S-03 | `services.sku` único? | Sim (validado e com índice) |
| Q-S-04 | Alerta de estoque mínimo e baixa de estoque entram no escopo? | Não; ficam fora do escopo, sem fase definida |
| Q-S-05 | Conta bancária com lançamentos: bloquear a exclusão? | Bloquear |
| Q-S-06 | `drive_logs` estruturado (usuário, ação, IP)? | Sim |
| Q-S-07 | Criar as permissões `crm.*` só para o menu? | Sim (`crm.deals.view` e `crm.pipelines.view`) |
| Q-S-08 | Alerta de etapa: enviar uma vez só (`overdue_alerted_at`) ou todo dia útil, como hoje? | Uma vez |
| Q-S-09 | Contrato padrão na conversão de orçamento: manter o id 4 por config ou escolher num diálogo? | Diálogo com o padrão pré-selecionado |
| Q-S-10 | MUD-01: em qual estágio aparece a conversão? `total_value` vira `purchase_value`? Usar o interino via query string enquanto o CRM não tem backend? | Só em "Ganho"; sim; sim |
| Q-S-11 | MUD-02: distribuir só na criação da proposta, ou também nas linhas adicionadas depois? | Em toda linha criada |
| Q-S-12 | MUD-02: o mapeamento pagar/receber da tabela RN-MUD02-02 está correto? | Como na tabela |
| Q-S-13 | MUD-02: quem é o fornecedor (cartório? prefeitura?), qual categoria/subcategoria e qual vencimento usar? | ~~Configuração em `tenant_settings`, com fornecedor genérico por tipo~~. **Revista pela Q-S-18** (sem fornecedor) **e pela Q-S-21** (categorias fixas, sem tela; vencimento na data da linha ou hoje) |
| Q-S-14 | MUD-02: um lançamento por linha ou um por proposta? O que fazer com os lançamentos ao excluir a proposta? | Um por linha; excluir os abertos e manter os pagos |
| Q-S-15 | MUD-03: estágio inicial do negócio; efeitos de expirar ou excluir o orçamento no negócio | 1º estágio do funil padrão; expirado = nada; excluído = Perdido |
| Q-S-16 | Remover `bank_account_out` (sem uso)? | Sim |
| Q-S-17 | Botão "Exportar lista" do `DataTable`: implementar (CSV) ou remover? | **Revista:** implementar a exportação em **PDF** nas 12 listas com `DataTable`, sem mudar o visual (RF-CORE-12, RN-CORE-15). A recomendação original era remover o botão |
| Q-S-18 | MUD-02: quem é o fornecedor da conta a pagar gerada pela proposta? | **Nenhum.** O fornecedor não recebe repasse configurado, e passa a ser opcional no cadastro de contas a pagar (RN-FIN-14, V-11). A tela Empresa não tem configuração de fornecedor |
| Q-S-19 | MUD-02: e se o 1º proponente não tiver o papel de cliente, ou se o papel estiver inativo? | Sem o papel: ele é criado (`ClientService::persist`). Papel inativo: o lançamento a receber da linha é pulado com aviso; "Distribuir no financeiro" reprocessa depois |
| Q-S-20 | MUD-03: disparar já o `QuoteConvertedEvent` e o `QuoteDeletedEvent`, mesmo sem listener? | Sim, os três eventos na R5 (RN-MUD03-05) |
| Q-S-21 | MUD-02: as categorias da distribuição são configuráveis numa tela, ou fixas? Qual forma de pagamento usar? | **Fixas**, criadas por migration ("Documentações" e "Documentações — Repasses", com subcategoria por tipo de linha), sem tela. Fica o adendo: poderá virar configurável se algum tenant precisar desse controle. Forma de pagamento `pix`, que o tenant pode alterar no lançamento. Vencimento: data da linha, ou hoje (RN-MUD02-03/09) |
| Q-S-22 | MUD-02: e se o plano do tenant não tem o módulo Financeiro? | A distribuição é pulada sem aviso, e as colunas e o botão "Distribuir" ficam ocultos na aba Pagamentos. Se o módulo for contratado depois, as linhas novas são distribuídas, e as antigas podem ser trazidas pelo "Distribuir no financeiro" (RN-MUD02-06/08) |
