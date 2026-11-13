# Constituição do Sistema PNET

Versão 1.0.0 · Ratificada em 2026-11-12 · Última emenda 2026-11-12

## 1. Propósito e alcance

Esta constituição reúne as regras que o Sistema PNET não negocia: as que justificam bloquear uma integração na `main`. Ela é seguida pelos agentes de IA que geram código no repositório e por quem revisa cada integração na `main`.

- O **responsável técnico do repositório** aprova emendas, dependências e exceções.
- Substitui a constituição anterior, removida no commit `e5fad17`. Os IDs `P-NN` e `V-NN` daquela versão não equivalem aos desta.
- Vocabulário: **DEVE** (obrigatório), **NÃO DEVE** (proibido), **PODE** (permitido).
- Integração na `main`: qualquer conjunto de commits integrado à `main`, por PR ou por merge direto.
- As violações já existentes estão em [`specs/violacoes-conhecidas.md`](violacoes-conhecidas.md).

## 2. Documentação

### D-01 — Precedência e natureza dos documentos
- **Regra:** A ordem DEVE ser: constituição > artefatos de uma feature não concluída (`design.md` e seus derivados `spec.md` > `plan.md` > `tasks.md`) > convenções > documentos descritivos. O código descreve o estado atual; a constituição define o estado permitido. Código que diverge de um documento normativo é violação: NÃO DEVE ser replicado, é apontado ao usuário e registrado em `specs/violacoes-conhecidas.md`. Código que diverge de um documento descritivo leva à atualização do documento, com pedido explícito. Só a seção "Afastamentos de convenção" do `design.md` de uma feature (copiada para o `plan.md`) PODE afastar uma regra das convenções, e só para o código daquela feature, citando a seção e o motivo; nunca uma regra que só aponta para um princípio desta constituição. Em divergência entre o `design.md` e um derivado, vale o `design.md`. A pasta de uma feature Concluída ou Abandonada é histórico: os afastamentos de uma Concluída valem só para o código que ela criou, e os de uma Abandonada perdem efeito. Nas convenções, só as regras no imperativo são normativas; exemplos, contagens e inventários são descritivos. Dentro das convenções, a seção específica de um módulo vence a seção geral. Instruções de ferramenta nos índices (`CLAUDE.md`, `AGENTS.md`: bloco do Boost, RTK) são subordinadas a esta constituição e às convenções. O bloqueio vale para linhas novas ou alteradas numa integração na `main`: linha com mudança de comportamento; reformatação do Pint e linha só movida não contam; código novo dentro de um bloco que já viola a regra cumpre a regra só nas linhas novas.
- **Por quê:** Sem precedência, o código prevalece sobre qualquer regra escrita e nenhuma violação pode ser bloqueada.
- **Escopo:** Todos os documentos do repositório e todo código integrado na `main`.
- **Como verificar:** Revisão da integração.
- **Exceções:** Nenhuma.
- **Detalhe:** Convenções §0, §1 (regra 10) e §15.

### D-02 — Idioma e alteração de documentos
- **Regra:** Todo arquivo em `specs/` e `docs/` DEVE ser escrito em pt-BR (identificadores, comandos e caminhos ficam como estão). Esses arquivos só PODEM ser criados ou alterados com pedido explícito.
- **Por quê:** O projeto é documentado em pt-BR, e documento normativo não pode mudar sem decisão.
- **Escopo:** Arquivos em `specs/` e `docs/`.
- **Como verificar:** Revisão da integração.
- **Exceções:** (a) `specs/violacoes-conhecidas.md` muda junto com a correção ou o registro da violação, sem pedido à parte; (b) a pasta `specs/NNN-nome/` e a linha dela em `specs/README.md` mudam dentro de uma feature que o usuário pediu: o pedido da feature conta como pedido explícito.
- **Detalhe:** —

### D-03 — Fonte única
- **Regra:** Cada princípio DEVE morar num só lugar. Convenções, `CLAUDE.md` e `AGENTS.md` citam o ID em vez de repetir o texto. Uma emenda MINOR ou MAJOR DEVE atualizar, no mesmo commit, quem cita os IDs afetados.
- **Por quê:** Regras repetidas divergem com o tempo.
- **Escopo:** Constituição, convenções, `CLAUDE.md` e `AGENTS.md`.
- **Como verificar:** `grep` pelo ID nos documentos e leitura do trecho que o cita.
- **Exceções:** Nenhuma.
- **Detalhe:** —

### D-04 — Emendas e versionamento
- **Regra:** Esta constituição só PODE mudar com pedido explícito do responsável técnico. Semver: MAJOR remove ou redefine um princípio ou muda a precedência; MINOR adiciona um princípio; PATCH ajusta o texto sem mudar o sentido. Cada emenda DEVE atualizar a versão, a data da última emenda e o histórico. Um princípio revogado vira lápide (`P-NN — revogado em X.Y.Z`), e o ID nunca é reutilizado.
- **Por quê:** A versão diz se uma mudança quebra o que já valia.
- **Escopo:** `specs/constitution.md`.
- **Como verificar:** `git log -p specs/constitution.md`: cada commit muda a versão e o histórico.
- **Exceções:** Nenhuma.
- **Detalhe:** —

### D-05 — Artefatos do SDD
- **Regra:** Toda mudança que altera comportamento observável (rota, tela, regra de negócio, schema, config com efeito em execução) DEVE ter `specs/NNN-nome/` com status Aprovada em `specs/README.md` antes do primeiro commit de código. A pasta é produzida pelo fluxo EGM (`egm-brainstorm` → `egm-draft` → `egm-review`) e pelo procedimento das convenções §15. O commit de código cita a pasta na mensagem. Ao concluir a feature, o `egm-drift-report` confere os documentos contra o código.
- **Por quê:** A especificação revisada antes do código evita retrabalho e deixa registrado o porquê de cada mudança.
- **Escopo:** Toda mudança cujo primeiro commit tem data igual ou posterior à data "Ratificada em" desta constituição e que altera rota, tela, regra de negócio, schema ou config com efeito em execução; as pastas `specs/NNN-nome/` e o `specs/README.md`.
- **Como verificar:** Revisão da integração: (1) a pasta `specs/NNN-nome/` da mudança existe; (2) a linha dela em `specs/README.md` está em Aprovada, Em implementação ou Concluída; (3) o diff não traz código de uma feature em Rascunho ou Em revisão. Uma integração que muda comportamento sem pasta só passa se cair numa exceção.
- **Exceções:** Fechada: (a) a ratificação da versão 1.0.0 desta constituição e mudanças cujo primeiro commit tem data anterior à data "Ratificada em"; (b) correção de bug que restaura o comportamento já descrito em `docs/` ou num `spec.md`; (c) mudança só de documentação.
- **Detalhe:** Convenções §15.

## 3. Princípios de engenharia

### 3.1 Isolamento

#### P-01 — Contexto de tenant
- **Regra:** Dado de tenant DEVE ser lido ou escrito só dentro de um contexto de tenant inicializado: (a) no HTTP, pelo `InitializeTenancyByDomain`; (b) na fila, pelo `QueueTenancyBootstrapper`; (c) em commands, migrations e testes, por `$tenant->run()`. Um service que escreve DEVE receber o `Tenant` ou declarar no PHPDoc que exige contexto já inicializado, e nesse caso só é chamado dentro dele. Quando há transação, ela fica dentro do contexto.
- **Por quê:** Um banco por tenant só isola se nenhuma leitura ou escrita acontecer fora do contexto certo.
- **Escopo:** Todo acesso a dado de tenant: HTTP, fila, commands, migrations, services e testes.
- **Como verificar:** Revisão. Próximo passo: um teste por módulo de que o tenant A não lê dados do tenant B.
- **Exceções:** Nenhuma.
- **Detalhe:** Convenções §5.

#### P-02 — Arquivos de tenant isolados
- **Regra:** Todo disco usado para arquivo de tenant DEVE estar em `tenancy.filesystem.disks`, inclusive o nome de disco gravado em coluna `disk`. O storage é sempre resolvido em contexto de tenant.
- **Por quê:** Um disco fora da lista do tenancy grava todos os tenants no mesmo prefixo.
- **Escopo:** Todo disco que guarda arquivo de tenant, configurado ou gravado em coluna `disk`.
- **Como verificar:** `config('bucket.disk')` está em `config('tenancy.filesystem.disks')`; `SELECT DISTINCT disk` nas tabelas com essa coluna.
- **Exceções:** Nenhuma.
- **Detalhe:** Convenções §5.

#### P-08 — Dados pessoais (LGPD)
- **Regra:** Dado pessoal (CPF/CNPJ, renda, documentos) NÃO DEVE cruzar tenants nem ser gravado em log. As chamadas `Log::` escritas pelo projeto registram contexto (ação, ID do tenant, ID do registro), não o payload, não a mensagem de uma `QueryException` (para ela, só a classe e o SQLSTATE) e não o objeto de exceção inteiro no contexto.
- **Por quê:** LGPD: CPF/CNPJ e renda não podem vazar entre empresas nem ficar em arquivo de log.
- **Escopo:** Dados de tenant em consultas e chamadas `Log::` escritas pelo projeto.
- **Como verificar:** Revisão de `Log::` em linhas novas, conforme o padrão das convenções §7.
- **Exceções:** Nenhuma.
- **Detalhe:** Convenções §7.

### 3.2 Dados

#### P-03 — Efeitos irreversíveis após o commit
- **Regra:** Apagar ou mover arquivo, enviar e-mail e despachar job DEVEM acontecer só depois do commit da transação de banco. Mailables e jobs disparados em contexto de tenant usam `afterCommit()` e levam o tenant (`QueueTenancyBootstrapper`). Uma falha em operação de disco depois do commit DEVE ser registrada com nível warning ou maior, com o ID do tenant e o caminho, e NÃO DEVE desfazer a resposta de sucesso.
- **Por quê:** Um rollback não desfaz efeito fora do banco.
- **Escopo:** Apagar ou mover arquivo, enviar e-mail e despachar job a partir de código que abre transação.
- **Como verificar:** `grep -L afterCommit app/Mail/*.php` deve sair vazio; revisão de `::dispatch(` e de `->delete(` em disco dentro de transação.
- **Exceções:** Nenhuma.
- **Detalhe:** Convenções §5.

#### P-04 — Dinheiro em centavos
- **Regra:** Todo valor monetário DEVE ser inteiro em centavos, no banco, no backend e no frontend.
- **Por quê:** Ponto flutuante gera erro de centavos.
- **Escopo:** Colunas, propriedades e campos de formulário que guardam valor em reais.
- **Como verificar:** `tests/Feature/MoneyColumnsTest.php`; `grep` por `decimal`, `float` ou `double` em migrations novas.
- **Exceções:** Taxas e percentuais não são dinheiro (ex.: alíquotas de ITBI em `decimal(7,4)`).
- **Detalhe:** Convenções §3.

#### P-05 — Dados de referência e permissões por migration
- **Regra:** Módulos, permissões, planos e catálogos DEVEM entrar só por migration idempotente, nunca por seeder. No banco central, via query builder. No tenant, permissões só pelo `TenantPermissionService`, filtradas pelos módulos do plano. Nenhuma migration PODE conceder permissão "para todos".
- **Por quê:** Só o `migrate` chega a todos os ambientes, e o plano contratado só vale se cada tenant receber só as permissões dos seus módulos.
- **Escopo:** Módulos, permissões, planos, catálogos e configurações padrão, no banco central e no do tenant.
- **Como verificar:** `grep` por `Permission::create` ou `Role::create` em `database/seeders`; revisão.
- **Exceções:** Seeders de dados fictícios de desenvolvimento.
- **Detalhe:** Convenções §3 e §9.

#### P-06 — Migrations central × tenant
- **Regra:** A pasta DEVE definir o escopo: `database/migrations/central` ou `database/migrations/tenant`. O deploy roda `migrate` antes de `tenants:migrate`. Uma migration já aplicada NÃO DEVE ser editada: a mudança entra em migration nova.
- **Por quê:** Editar migration aplicada deixa ambientes com esquemas diferentes.
- **Escopo:** `database/migrations/central` e `database/migrations/tenant`.
- **Como verificar:** Revisão de diffs em arquivos de migration já presentes na `main`.
- **Exceções:** Nenhuma.
- **Detalhe:** Convenções §3.

### 3.3 Acesso

#### P-07 — Autorização no backend
- **Regra:** Toda rota autenticada que opera recurso de módulo DEVE ter `permission:<modulo>.<recurso>.<acao>`, com a ação compatível com a operação (escrita não usa `view`). Policy é adicionada quando o acesso depende do registro. No frontend, `usePermission` só esconde elementos e NÃO DEVE ser tratado como autorização.
- **Por quê:** As rotas são a única barreira que aplica o plano e a permissão no backend.
- **Escopo:** Rotas de `routes/tenant.php` com `Authenticate`, e as telas que escondem ações por permissão.
- **Como verificar:** `tests/Feature/DocumentsRoutesTest.php` (grupo Documentações) e o comando de rotas sem permissão das convenções §8, que só deve listar as exceções.
- **Exceções:** Rotas autenticadas sem recurso de módulo: dashboard e perfil do próprio usuário (dados, foto, senha).
- **Detalhe:** Convenções §8 e §9.

### 3.4 Qualidade e stack

#### P-09 — Testes
- **Regra:** Toda mudança de comportamento DEVE ter teste novo ou atualizado, executado e passando. A suíte completa DEVE passar antes de integrar uma branch na `main`.
- **Por quê:** Sem teste executado, uma regressão só aparece em produção.
- **Escopo:** Todo código PHP, Vue e TypeScript integrado na `main`.
- **Como verificar:** Saída do `artisan test` registrada na integração.
- **Exceções:** Mudanças só de documentação.
- **Detalhe:** Convenções §11.

#### P-10 — Stack fechada e MySQL
- **Regra:** Nenhuma dependência composer ou npm nova e nenhuma pasta-base nova em `app/` ou `resources/js/` PODE entrar sem aprovação explícita do responsável técnico. O banco DEVE ser o MySQL em todos os ambientes, testes incluídos. Todo comando roda via Sail.
- **Por quê:** Dependência nova e segundo banco aumentam o que precisa ser mantido e testado.
- **Escopo:** `composer.json`, `package.json`, pastas-base de `app/` e `resources/js/`, config de banco e comandos de desenvolvimento.
- **Como verificar:** Diff de `composer.json` e `package.json`; `DB_CONNECTION` no `.env.example` e os fallbacks em `config/database.php`, `config/queue.php` e `config/tenancy.php`.
- **Exceções:** Nenhuma.
- **Detalhe:** Convenções §1 e §12.

#### P-11 — Idioma do produto
- **Regra:** Texto escrito pelo projeto e visto pelo usuário (`messages()` dos Form Requests, flash, telas, e-mails, PDFs) DEVE ser em pt-BR. Identificadores novos DEVEM ser em inglês.
- **Por quê:** O produto é brasileiro, e o código segue a convenção em inglês do ecossistema.
- **Escopo:** Texto visto pelo usuário (requests, flash, telas, e-mails, PDFs do projeto) e identificadores novos.
- **Como verificar:** Revisão.
- **Exceções:** Mensagens do próprio framework e o locale.
- **Detalhe:** Convenções §6 e §10.

### 3.5 Nomes e interface

#### P-12 — Sufixo de tipo
- **Regra:** Toda classe criada para o projeto DEVE terminar com o seu tipo. Controllers de tenant usam o prefixo `Tenant`. Models não têm sufixo. A lista de tipos está nas convenções §4a.
- **Por quê:** O nome diz o papel da classe sem abrir o arquivo.
- **Escopo:** Classes PHP criadas para o projeto em `app/`, `database/` e `tests/`.
- **Como verificar:** Para cada classe nova, conferir se o nome termina com um dos sufixos da §4a (não com o nome da pasta); revisão.
- **Exceções:** Classes de scaffold do framework e dos pacotes mantêm o nome original (lista nas convenções §4a).
- **Detalhe:** Convenções §4a.

#### P-13 — Estilo das telas congelado
- **Regra:** Tela existente NÃO DEVE mudar visualmente sem pedido explícito. Telas e elementos novos DEVEM usar a base visual listada nas convenções §10, nos modos claro e escuro.
- **Por quê:** Mudança visual sem pedido quebra a experiência que os clientes já conhecem.
- **Escopo:** Telas Vue e CSS em `resources/`.
- **Como verificar:** Revisão visual nos modos claro e escuro; diff de `resources/css/app.css`.
- **Exceções:** Nenhuma.
- **Detalhe:** Convenções §10.

## 4. Cumprimento e próximos passos

- Na versão 1.0.0, o cumprimento é feito pela revisão de cada integração na `main`, e a suíte segue a P-09.
- Próximos passos declarados: CI; `tests/Arch/ConstitutionTest.php`; um teste de isolamento entre tenants por módulo; um helper central de log; um `report` para `QueryException` em `bootstrap/app.php`.

## Histórico

| Versão | Data | Mudança |
| --- | --- | --- |
| 1.0.0 | 2026-11-12 | Versão inicial (substitui a constituição removida em e5fad17). |
