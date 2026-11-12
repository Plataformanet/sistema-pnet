# Constituição do Sistema PNET

*   **Autor:** Eduardo Sousa
*   **Status:** Pronto para implementação. Revisão EGM concluída (estágios 5 a 9 e review-loop do SDD por feature, encerrado na rodada 4 por decisão do autor) em 2026-11-12
*   **Data:** 2026-11-11

--------------------------------------------------------------------------------

## Visão geral

Criar o arquivo `specs/constitution.md` no formato do Spec Kit. Ele reúne os
princípios de engenharia e de documentação que o Sistema PNET não negocia: as
regras que justificam bloquear um PR. O mesmo arquivo define como ele próprio
muda, com versão semântica e histórico.

A constituição passa a ser o documento de maior precedência do repositório:

*   o `CLAUDE.md` e o `AGENTS.md` viram índices que apontam para ela;
*   `docs/convencoes_de_desenvolvimento.md` vira o detalhamento operacional
    (o "como fazer");
*   `docs/arquitetura/` e `docs/modulos/` continuam descrevendo o estado atual
    do sistema;
*   um arquivo novo, `specs/violacoes-conhecidas.md`, registra as violações que
    já existem no código. Ele é descritivo e não tem versão;
*   a constituição é a raiz do SDD (desenvolvimento guiado por especificação).
    Cada mudança de evolução do sistema ganha uma pasta `specs/NNN-nome/` com
    o `design.md` do EGM e os derivados `spec.md`, `plan.md` e `tasks.md`, e
    `specs/README.md` é o índice delas.
    Criar essas features fica para depois desta mudança.

O documento tem dois leitores:

*   **os agentes de IA que geram código no repositório:**
    *   o Claude Code recebe a constituição por import no `CLAUDE.md`;
    *   o Codex e o Antigravity recebem, no `AGENTS.md`, a instrução de lê-la
        no início da sessão;
*   **as pessoas que revisam o que é integrado à `main`.**

### Glossário

*   **Spec Kit:** convenção de documentação guiada por especificação, em que
    uma "constitution" lista os princípios do projeto. Usamos só o formato. A
    ferramenta e o caminho `.specify/memory/` não são usados.
*   **Laravel Boost:** pacote que gera instruções para agentes. Ele escreve no
    `CLAUDE.md` e no `AGENTS.md` o bloco entre as tags
    `<laravel-boost-guidelines>`, e o comando `boost:install` regenera esse
    bloco.
*   **stancl/tenancy:** pacote de multi-tenancy com um banco por tenant. O
    contexto de tenant é aberto pelo middleware de domínio, pelo bootstrapper
    da fila ou por `$tenant->run()`.
*   **`sharedTenant()` / `createTenant()`:** helpers dos testes
    (`tests/Pest.php`). O primeiro reaproveita um tenant já migrado. O segundo
    cria e migra um banco novo.
*   **Recurso-espelho:** um recurso que já existe e serve de molde para um novo
    (ex.: Produtos), conforme as convenções §0.
*   **V-NN:** IDs de decisão da constituição anterior, removida no commit
    `e5fad17`. Não equivalem a nenhum ID novo.
*   **SDD por feature:** cada mudança que altera comportamento tem
    `specs/NNN-nome/` com `design.md` (a fonte, escrita pelo `egm-draft`),
    `reviews/` (relatórios do `egm-review`) e três arquivos derivados do
    design: `spec.md` (o quê e por quê), `plan.md` (como) e `tasks.md`
    (mudanças por arquivo). O procedimento completo fica nas convenções §15.
*   **Integração na `main`:** qualquer conjunto de commits integrado à `main`,
    por PR ou por merge direto. É o momento em que o bloqueio se aplica.

## O problema

Hoje as regras do projeto estão espalhadas em quatro lugares que se contradizem
e não têm ordem de precedência:

*   a §1 das convenções;
*   as seções 4 a 6 do `architecture_guide.md`;
*   o `CLAUDE.md`;
*   o `AGENTS.md`.

1.  **A regra "o código é o estado real" impede bloquear uma violação.** Ela
    está no `CLAUDE.md:13`, no `AGENTS.md:24` e na linha 19 das convenções.
    Se o código grava dinheiro em `decimal`, essa regra diz que o código
    prevalece sobre a convenção "dinheiro em centavos". As convenções, além
    disso, mandam copiar o recurso-espelho "como molde literal", o que
    replica as violações.
2.  **As mesmas regras aparecem em vários lugares com textos diferentes, e os
    textos já divergem:**
    *   as convenções citam uma Policy, e o código tem três;
    *   a regra 6 das convenções exige executar o teste, sem dizer quais, e a
        prática combinada é rodar só os testes afetados;
    *   os docs dizem "MySQL/PostgreSQL", e a config usa `sqlite` como padrão.
3.  **O `CLAUDE.md` e o `AGENTS.md` apontam para arquivos que não existem mais:**
    *   `specs/spec.md`, `specs/plan.md`, `specs/tasks.md` e
        `specs/implementation.md` (o conteúdo anterior de `specs/` foi apagado
        no commit `e5fad17`);
    *   `docs/prd-sistema-pnet-v2.md`;
    *   no `CLAUDE.md`, cinco skills que não estão em `.claude/skills/`. Esse
        item fica fora do escopo deste design (ver Não-metas e Riscos).

    O `AGENTS.md` ainda diz que "`specs/` manda" e lista uma ordem de leitura
    de arquivos apagados.
4.  **Parte das regras "inegociáveis" já é violada no código:**
    *   `CompanySettingService` e `UserProfileService` apagam arquivo dentro
        da transação;
    *   três migrations de tenant usam models;
    *   uma rota de escrita é protegida só pela permissão `view`. Há também 13
        rotas sem `permission:`, mas todas são exceções legítimas: dashboard,
        perfil próprio e rotas públicas (conferido com `route:list --json`);
    *   a config de banco não fixa o MySQL.

    Uma regra que já nasce violada e sem registro ensina o agente a ignorá-la,
    porque o código ao redor faz diferente.

A solução tem quatro partes:

*   um único documento normativo, curto, em que cada princípio diz como é
    verificado;
*   uma regra explícita de precedência entre documentos **normativos**
    (constituição e convenções) e **descritivos** (`docs/arquitetura`,
    `docs/modulos`, `specs/violacoes-conhecidas.md`);
*   um registro de violações fora da constituição, para que o bloqueio valha
    para código novo sem exigir refatoração imediata;
*   ajustes no `CLAUDE.md`, no `AGENTS.md`, nas convenções e na config, para
    que os documentos parem de se contradizer no mesmo commit.

## Metas

*   **M1.** Existe o arquivo `specs/constitution.md`, em pt-BR, com:
    *   cabeçalho com versão `1.0.0`, data de ratificação e data da última
        emenda;
    *   exatamente 18 princípios (D-01 a D-05 e P-01 a P-13);
    *   `wc -l specs/constitution.md` ≤ 200, contando tudo o que o import
        carrega.

    O brief propunha de 8 a 12 princípios e de 60 a 100 linhas. O limite subiu
    porque o brief não contava os princípios de documentação (D-NN) como
    princípios, e porque o rascunho acrescentou a D-04 (emendas), a D-05
    (artefatos do SDD), a P-12 (sufixo) e a P-13 (estilo).
*   **M2.** Cada princípio tem um ID permanente (`D-NN` para documentação,
    `P-NN` para engenharia) e seis campos: **Regra**, **Por quê**,
    **Escopo**, **Como verificar**, **Exceções** e **Detalhe**. As Exceções
    são uma lista fechada ou "nenhuma". A Regra usa DEVE / NÃO DEVE / PODE.
*   **M3.** A constituição define a ordem: constituição > artefatos da
    feature (`spec.md` > `plan.md` > `tasks.md`) > convenções > documentos
    descritivos. O `plan.md` de uma feature pode afastar uma regra das
    convenções só para aquela feature, citando a seção afastada e o motivo.
    A constituição também define o que fazer em cada divergência:
    *   código que diverge de um documento normativo é violação;
    *   código que diverge de um documento descritivo leva à atualização do
        documento.
*   **M4.** Cada princípio, como foi escrito, é cumprido no código atual ou tem
    a violação em `specs/violacoes-conhecidas.md`. Cada entrada traz o
    princípio, o símbolo (arquivo e classe, ou `Classe::metodo`) e a receita
    de `grep` que a reencontra. Quando a mesma violação se repete em muitos
    arquivos, uma entrada agregada é aceita, desde que a receita liste cada
    ocorrência. Nenhuma violação conhecida fica sem registro.
*   **M5.** O bloqueio vale para **linhas novas ou alteradas** em uma
    integração na `main`.
    *   "Linha alterada" é linha com mudança de comportamento. Reformatação
        do Pint e linha só movida não contam.
    *   Código novo dentro de um bloco que já viola a regra cumpre a regra só
        nas linhas novas.
    *   Linhas antigas que não foram tocadas ficam no registro como dívida.
    *   Essa regra é coerente com a §13 das convenções (sem refatoração fora
        do escopo).
*   **M6.** O `CLAUDE.md` e o `AGENTS.md`:
    *   apontam para a constituição. O `CLAUDE.md` usa
        `@specs/constitution.md`, fora do bloco `<laravel-boost-guidelines>`.
        O `AGENTS.md` traz a instrução "No início de toda sessão, antes de
        planejar ou mudar código, leia `specs/constitution.md`";
    *   trocam a regra "o código é o estado real" por um ponteiro para a D-01;
    *   deixam de citar `specs/spec.md`, `specs/plan.md`, `specs/tasks.md`,
        `specs/implementation.md` e `docs/prd-sistema-pnet-v2.md`. A exceção
        são as seções depois do bloco do Boost ("Mapa de skills" e "Code
        Review" nos dois arquivos, e "Instructions" no `AGENTS.md`), que ficam
        como estão (ver Não-metas e Riscos).
*   **M7.** A constituição declara que substitui a versão anterior, removida no
    commit `e5fad17`, e que os IDs `P-NN` e `V-NN` daquela versão não
    equivalem aos novos.
*   **M8.** Nas convenções:
    *   o cabeçalho, a linha 19, a "Regra número um" e a regra 1 da §1 deixam
        de tratar o código como verdade. Onde o código viola a constituição ou
        está no registro de violações, o código novo segue a constituição;
    *   a §1 aponta para os IDs da constituição em vez de repetir as regras;
    *   a regra 10 passa a dizer que a seção específica de um módulo, dentro
        das convenções, vence a seção geral, e nunca a constituição;
    *   a §9 reflete as três Policies que existem no código e o nome
        `SeedTenantDatabaseJob`;
    *   a regra de testes fica alinhada à P-09.
*   **M9.** O MySQL fica fixado no `.env.example` (usuário `sail`) e nos
    fallbacks de `DB_CONNECTION` (`config/database.php`, `config/queue.php`
    e `config/tenancy.php`). Um script de init versionado,
    `database/mysql/grant-tenant-databases.sh`, montado no `compose.yaml`, dá
    ao usuário do Sail todos os privilégios sobre os bancos `tenant%`. O
    `phpunit.xml` não fixa `DB_CONNECTION`: os testes usam o `.env`.
*   **M10.** Os testes afetados pela troca de config passam num volume MySQL
    novo, criado a partir do `.env.example`, em que só o nome do projeto e as
    portas mudam.
*   **M11.** Existe `specs/README.md`, o índice do SDD por feature, com uma
    tabela vazia (Feature, Pasta, Status, Última atualização). A primeira
    feature o preenche.

## Não-metas

*   Corrigir as violações registradas em `specs/violacoes-conhecidas.md`. Elas
    ficam como dívida para outra tarefa.
*   Criar a primeira feature do SDD (`specs/001-…/`). Esta mudança só define o
    formato e cria o índice vazio.
*   Corrigir o "Mapa de skills" e a "Code Review" do `CLAUDE.md` e do
    `AGENTS.md` (e o "Instructions" do `AGENTS.md`), ou recriar as
    skills que faltam. A referência a `specs/implementation.md` §2 continua lá
    (ver Riscos).
*   Atualizar `docs/arquitetura/architecture_guide.md` e os demais documentos
    descritivos. O guia continua com divergências registradas para um próximo
    pedido:
    *   o "MySQL/PostgreSQL";
    *   as regras repetidas nas seções 4 a 6;
    *   a citação a `App\Support\Money` (a classe é `MoneyHelper`).
*   Criar CI (`.github/workflows`), template de PR, `tests/Arch/ConstitutionTest.php`
    ou um helper central de log. Ficam como próximos passos declarados.
*   Trocar o locale do Laravel para `pt_BR` ou traduzir as mensagens do
    framework.
*   Manter as regras do SDD antigo que estão no `AGENTS.md` sobre backend de
    CRM e MUD-01 a MUD-04. Elas saem sem substituto, por decisão do autor. A
    regra "na dúvida, pare e pergunte" fica, e passa a valer nos dois índices.
*   Levar para a constituição listas de tipos, componentes, pastas do frontend,
    exemplos de código ou a anatomia de feature. Essas listas moram só nas
    convenções.
*   Citar métodos do próprio projeto na constituição. São permitidos só nomes
    de API do framework e dos pacotes quando a regra depende deles para ser
    verificada.
*   Listar Policies pelo nome na constituição.

## Jornadas críticas (CUJs)

### CUJ 1: Agente encontra uma violação ao gerar uma feature

*   **Dado** que um agente (Claude Code, Codex ou Antigravity) inicia uma sessão
    e recebe a constituição (por import ou pela instrução de leitura);
*   **Quando** o código ao redor da feature viola um princípio, por exemplo
    apagando arquivo dentro da transação;
*   **Então** o agente escreve as linhas novas seguindo a constituição e cita o
    princípio violado (`P-03`) na resposta. Ele não replica o padrão e não
    corrige as linhas antigas. Se a violação ainda não está em
    `specs/violacoes-conhecidas.md`, ele acrescenta a entrada no mesmo commit
    (D-01 e a exceção da D-02).

### CUJ 2: Revisor bloqueia uma integração na `main`

*   **Dado** um conjunto de commits que adiciona uma coluna de valor como
    `decimal(10,2)`;
*   **Quando** o revisor aplica o "Como verificar" da `P-04`;
*   **Então** a integração é bloqueada, com a citação de `P-04`. Não cabe o
    argumento "outro arquivo já faz assim": a linha é nova, e a M5 só deixa
    fora do bloqueio as linhas antigas que não foram tocadas. O registro de
    violações não protege nada; ele só avisa o agente para não copiar.

### CUJ 3: Documento descritivo diverge da constituição

*   **Dado** que o `architecture_guide.md` diz "MySQL/PostgreSQL" e a
    constituição fixa o MySQL;
*   **Quando** um agente encontra a divergência;
*   **Então** vale a constituição (D-01). O agente aponta que o guia está
    desatualizado e não altera `docs/` sem pedido explícito (D-02).

### CUJ 4: Emenda à constituição

*   **Dado** que o responsável técnico quer adicionar um princípio;
*   **Quando** a emenda é feita;
*   **Então**, pela D-04, a versão sobe um MINOR, e a data da última emenda e o
    histórico são atualizados. Pela D-03, os documentos que citam os IDs
    afetados (convenções, `CLAUDE.md`, `AGENTS.md`) mudam no mesmo commit.

### CUJ 5: Código diverge de documento descritivo

*   **Dado** que `docs/modulos/financeiro/` descreve um campo que o código
    renomeou;
*   **Quando** um agente encontra a divergência;
*   **Então** vale o código (D-01). O agente aponta que o documento está
    desatualizado e só o atualiza com pedido explícito (D-02).

### CUJ 6: Violação registrada é corrigida

*   **Dado** uma entrada em `specs/violacoes-conhecidas.md`;
*   **Quando** uma tarefa corrige essa violação;
*   **Então** o mesmo commit remove a entrada. Isso não é emenda e não muda a
    versão da constituição.

### CUJ 7: Feature afasta uma convenção

*   **Dado** que a feature `specs/004-webhook-banco/` recebe um webhook e a
    §11 das convenções manda testar o service, e não a rota HTTP;
*   **Quando** o `design.md` (e, por derivação, o `plan.md`) traz, em
    "Afastamentos de convenção": "afasta a §11 (teste só do
    service) nesta feature, porque a assinatura do webhook só é validada na
    rota", e a feature chega a Aprovada no `egm-review`;
*   **Então** os testes da feature podem ir pela rota HTTP (D-01). Sem essa
    declaração, vale a convenção. A P-09 (testes) não pode ser afastada.

## Plano técnico

### Visão de documentos e precedência

```text
                 ┌──────────────────────────────┐
  normativo      │  specs/constitution.md       │  D-01..D-05, P-01..P-13
  (código que    │  (o que é permitido)         │  (importada em toda sessão)
   diverge é     └──────────────┬───────────────┘
   violação)                    │ cada feature cumpre a constituição
                 ┌──────────────▼───────────────┐
                 │ specs/NNN-nome/              │  design.md (fonte) →
                 │ (artefatos da feature)       │  spec > plan > tasks; afasta
                 └──────────────┬───────────────┘  convenção, de forma explícita
                                │
                 ┌──────────────▼───────────────┐
                 │ docs/convencoes_de_          │  só as regras no imperativo;
                 │ desenvolvimento.md           │  seção de módulo > seção geral
                 └──────────────────────────────┘
  ─────────────────────────────────────────────────────────────────────
  descritivo     docs/arquitetura/*, docs/modulos/*,
  (doc que       specs/violacoes-conhecidas.md (dívida; muda sem emenda)
   diverge do    exemplos, contagens e inventários dentro das convenções
   código é atualizado, com pedido explícito)

  índices        CLAUDE.md (@specs/constitution.md)   AGENTS.md (instrução de leitura)
  (não são elo da cadeia; regras de ferramenta — Boost, RTK — são subordinadas)
```

### Estrutura de `specs/constitution.md`

1.  **Título e cabeçalho:** "Constituição do Sistema PNET", seguido da linha
    `Versão 1.0.0 · Ratificada em AAAA-MM-DD · Última emenda AAAA-MM-DD`.
2.  **§1 Propósito e alcance:**
    *   o que é o documento e quem o segue;
    *   o papel **responsável técnico do repositório**, que aprova emendas e
        exceções;
    *   a nota de substituição da versão anterior (`e5fad17`, IDs sem
        equivalência);
    *   o vocabulário normativo DEVE / NÃO DEVE / PODE;
    *   a definição de "integração na `main`";
    *   o ponteiro para `specs/violacoes-conhecidas.md`.
3.  **§2 Documentação:** D-01 a D-05, cada um num título `### D-NN`.
4.  **§3 Princípios de engenharia:** os IDs não têm prefixo de grupo, e cada
    princípio fica num título `#### P-NN` dentro destas subseções H3:
    *   3.1 Isolamento: P-01, P-02 e P-08;
    *   3.2 Dados: P-03, P-04, P-05 e P-06;
    *   3.3 Acesso: P-07;
    *   3.4 Qualidade e stack: P-09, P-10 e P-11;
    *   3.5 Nomes e interface: P-12 e P-13.
5.  **§4 Cumprimento e próximos passos:**
    *   na versão 1.0.0, o cumprimento é feito pela revisão de cada integração
        na `main` (a suíte, ver P-09);
    *   próximos passos declarados:
        *   CI;
        *   `tests/Arch/ConstitutionTest.php`;
        *   um teste de isolamento entre tenants por módulo;
        *   um helper central de log;
        *   um `report` para `QueryException` em `bootstrap/app.php`.
6.  **Apêndice — Histórico:** uma tabela com versão, data e mudança.

**Modelo de cada princípio.** Não há blocos de código. Podem ser citados nomes
de classe, middleware, comando, pasta e config, e nomes de API do framework e
dos pacotes quando a regra depende deles. Métodos do próprio projeto não podem
ser citados.

```text
### D-NN — Título        (no §2)        #### P-NN — Título   (no §3.x)
- **Regra:** frase com DEVE / NÃO DEVE / PODE.
- **Por quê:** uma ou duas frases.
- **Escopo:** onde vale.
- **Como verificar:** comando, grep ou item de revisão.
- **Exceções:** lista fechada ou "nenhuma".
- **Detalhe:** convenções §N, ou "—".
```

### Princípios (texto-base que vai para a constituição)

A coluna "Regra" é um resumo informal. No texto final, toda Regra usa
DEVE / NÃO DEVE / PODE. "Por quê" e "Escopo" de cada princípio estão na tabela
do passo 3 do Plano de implementação.

| ID | Título | Regra (resumo) | Como verificar | Exceções | Detalhe |
| --- | --- | --- | --- | --- | --- |
| D-01 | Precedência e natureza dos documentos | A ordem é constituição > artefatos de uma feature não concluída (`spec.md` > `plan.md` > `tasks.md`) > convenções > documentos descritivos. Só o `plan.md` PODE afastar uma regra das convenções, e só para o código daquela feature, numa seção "Afastamentos de convenção" que vem do `design.md` da feature e cita a seção e o motivo (convenções §15); nunca uma regra que só aponta para um princípio da constituição. Sem essa citação, vale a convenção. Os derivados (`spec.md`, `plan.md`, `tasks.md`) têm a força do `design.md` de onde vêm; em divergência, vale o `design.md`. A pasta de uma feature Concluída ou Abandonada é histórico (descritivo); os afastamentos de uma Concluída continuam valendo só para o código que ela criou, e os de uma Abandonada perdem efeito. O código descreve o estado atual, e a constituição define o estado permitido. Código que diverge de um documento normativo é violação: não se replica, aponta-se ao usuário e registra-se em `specs/violacoes-conhecidas.md`. Código que diverge de um documento descritivo leva à atualização do documento, com pedido explícito. Nas convenções, só as regras no imperativo são normativas; exemplos, contagens e inventários são descritivos. Dentro das convenções, a seção específica de um módulo vence a seção geral. Instruções de ferramenta nos índices (bloco do Boost, RTK) são subordinadas à constituição e às convenções. O bloqueio vale para linhas novas ou alteradas (M5) numa integração na `main`. | Revisão da integração. | Nenhuma | — |
| D-02 | Idioma e alteração de documentos | Todo arquivo em `specs/` e `docs/` DEVE ser escrito em pt-BR (identificadores, comandos e caminhos ficam como estão). Esses arquivos só são criados ou alterados com pedido explícito. | Revisão da integração. | (a) `specs/violacoes-conhecidas.md` muda junto com a correção ou o registro da violação, sem pedido à parte; (b) a pasta `specs/NNN-nome/` e a linha dela em `specs/README.md` mudam dentro de uma feature que o usuário pediu: o pedido da feature conta como pedido explícito. | — |
| D-03 | Fonte única | Cada princípio mora num só lugar. Convenções, `CLAUDE.md` e `AGENTS.md` citam o ID em vez de repetir o texto. Uma emenda MINOR ou MAJOR atualiza, no mesmo commit, quem cita os IDs afetados. | `grep` pelo ID nos documentos e leitura do trecho que o cita. | Nenhuma | — |
| D-04 | Emendas e versionamento | A constituição só muda com pedido explícito do responsável técnico. Semver: MAJOR remove ou redefine um princípio ou muda a precedência; MINOR adiciona um princípio; PATCH ajusta o texto sem mudar o sentido. Cada emenda atualiza a versão, a data da última emenda e o histórico. Um princípio revogado vira lápide (`P-NN — revogado em X.Y.Z`), e o ID nunca é reutilizado. | `git log -p specs/constitution.md`: cada commit muda a versão e o histórico. | Nenhuma | — |
| D-05 | Artefatos do SDD | Toda mudança que altera comportamento observável (rota, tela, regra de negócio, schema, config com efeito em execução) DEVE ter `specs/NNN-nome/` com status Aprovada em `specs/README.md` antes do primeiro commit de código. A pasta é produzida pelo fluxo EGM (`egm-brainstorm` → `egm-draft` → `egm-review`) e pelo procedimento das convenções §15. O commit de código cita a pasta na mensagem. Ao concluir a feature, `egm-drift-report` confere os documentos contra o código. | Revisão da integração: (1) a pasta `specs/NNN-nome/` da mudança existe; (2) a linha dela em `specs/README.md` está em Aprovada, Em implementação ou Concluída; (3) o diff não traz código de uma feature em Rascunho ou Em revisão. Uma integração que muda comportamento sem pasta só passa se cair numa exceção. | Fechada: (a) a ratificação da versão 1.0.0 desta constituição e mudanças cujo primeiro commit tem data anterior à data "Ratificada em" do cabeçalho; (b) correção de bug que restaura o comportamento já descrito em `docs/` ou num `spec.md`; (c) mudança só de documentação. | §15 |
| P-01 | Contexto de tenant | Dado de tenant só é lido ou escrito dentro de um contexto de tenant inicializado. Esse contexto é aberto: (a) no HTTP, pelo `InitializeTenancyByDomain`; (b) na fila, pelo `QueueTenancyBootstrapper`; (c) em commands, migrations e testes, por `$tenant->run()`. Um service que escreve DEVE receber o `Tenant` ou declarar no PHPDoc que exige contexto já inicializado, e nesse caso só é chamado dentro dele. Quando há transação, ela fica dentro do contexto. | Revisão. Próximo passo: um teste por módulo de que o tenant A não lê dados do tenant B. | Nenhuma | §5 |
| P-02 | Arquivos de tenant isolados | Todo disco usado para arquivo de tenant DEVE estar em `tenancy.filesystem.disks`, inclusive o nome de disco gravado em coluna `disk`. O storage é sempre resolvido em contexto de tenant. | `config('bucket.disk')` está em `config('tenancy.filesystem.disks')`; `SELECT DISTINCT disk` nas tabelas com essa coluna. | Nenhuma | §5 |
| P-03 | Efeitos irreversíveis após o commit | Apagar ou mover arquivo, enviar e-mail e despachar job só acontecem depois do commit da transação de banco. Mailables e jobs disparados em contexto de tenant usam `afterCommit()` e levam o tenant (`QueueTenancyBootstrapper`). Uma falha em operação de disco depois do commit DEVE ser registrada com nível warning ou maior, com o ID do tenant e o caminho, e NÃO DEVE desfazer a resposta de sucesso. | `grep -L afterCommit app/Mail/*.php` deve sair vazio; revisão de `::dispatch(` e de `->delete(` em disco dentro de transação. | Nenhuma | §5 |
| P-04 | Dinheiro em centavos | Todo valor monetário DEVE ser inteiro em centavos, no banco, no backend e no frontend. | `tests/Feature/MoneyColumnsTest.php`; `grep` por `decimal\|float\|double` em migrations novas. | Taxas e percentuais não são dinheiro (ex.: alíquotas de ITBI em `decimal(7,4)`). | §3 |
| P-05 | Dados de referência e permissões por migration | Módulos, permissões, planos e catálogos entram só por migration idempotente, nunca por seeder. No banco central, via query builder. No tenant, permissões só pelo `TenantPermissionService`, filtradas pelos módulos do plano. Nenhuma migration concede permissão "para todos". | `grep` por `Permission::create\|Role::create` em `database/seeders`; revisão. | Seeders de dados fictícios de desenvolvimento. | §3 e §9 |
| P-06 | Migrations central × tenant | A pasta define o escopo: `database/migrations/central` ou `database/migrations/tenant`. O deploy roda `migrate` antes de `tenants:migrate`. Uma migration já aplicada NÃO DEVE ser editada: a mudança entra em migration nova. | Revisão de diffs em arquivos de migration já presentes na `main`. | Nenhuma | §3 |
| P-07 | Autorização no backend | Toda rota autenticada que opera recurso de módulo DEVE ter `permission:<modulo>.<recurso>.<acao>`, com a ação compatível com a operação (escrita não usa `view`). Policy é adicionada quando o acesso depende do registro. No frontend, `usePermission` só esconde elementos e nunca é a autorização. | `tests/Feature/DocumentsRoutesTest.php` (grupo Documentações) e o comando de rotas sem permissão das convenções §8 (via `vendor/bin/sail php`, porque o `jq` não está instalado), que só deve listar as exceções. | Rotas autenticadas sem recurso de módulo: dashboard e perfil do próprio usuário (dados, foto, senha). | §8 e §9 |
| P-08 | Dados pessoais (LGPD) | Dado pessoal (CPF/CNPJ, renda, documentos) NÃO DEVE cruzar tenants nem ser gravado em log. Para isso, as chamadas `Log::` escritas pelo projeto registram contexto (ação, ID do tenant, ID do registro), não o payload, não a mensagem de uma `QueryException` (para ela, só a classe e o SQLSTATE) e não o objeto de exceção inteiro no contexto. | Revisão de `Log::` em linhas novas, conforme o padrão das convenções §7. | Nenhuma | §7 |
| P-09 | Testes | Toda mudança de comportamento DEVE ter teste novo ou atualizado, executado e passando. A suíte completa DEVE passar antes de integrar uma branch na `main`. | Saída do `artisan test` registrada na integração. | Mudanças só de documentação. | §11 |
| P-10 | Stack fechada e MySQL | Nenhuma dependência composer ou npm nova e nenhuma pasta-base nova em `app/` ou `resources/js/` entram sem aprovação explícita do responsável técnico. O banco é o MySQL em todos os ambientes, testes incluídos. Todo comando roda via Sail. | Diff de `composer.json` e `package.json`; `DB_CONNECTION` no `.env.example` e o default em `config/database.php`. | Nenhuma | §1 e §12 |
| P-11 | Idioma do produto | Texto escrito pelo projeto e visto pelo usuário (`messages()` dos Form Requests, flash, telas, e-mails, PDFs) DEVE ser em pt-BR. Identificadores novos DEVEM ser em inglês. | Revisão. | Mensagens do próprio framework e o locale. | §6 e §10 |
| P-12 | Sufixo de tipo | Toda classe criada para o projeto DEVE terminar com o seu tipo. Controllers de tenant usam o prefixo `Tenant`. Models não têm sufixo. A lista de tipos está nas convenções §4a. | Para cada classe em `app/`, conferir se o nome termina com um dos sufixos da §4a (não com o nome da pasta); revisão. | Classes de scaffold do framework e dos pacotes mantêm o nome original. | §4a |
| P-13 | Estilo das telas congelado | Nenhuma mudança visual em tela existente sem pedido explícito. Telas e elementos novos DEVEM usar a base visual listada nas convenções §10, nos modos claro e escuro. | Revisão visual nos modos claro e escuro; diff de `resources/css/app.css`. | Nenhuma | §10 |

### Conteúdo inicial de `specs/violacoes-conhecidas.md`

O arquivo é descritivo e não tem versão. Cada entrada traz:

*   o princípio violado;
*   o símbolo (arquivo e classe, ou `Classe::metodo`);
*   a descrição;
*   a receita de `grep` que a reencontra.

A entrada sai no mesmo commit que corrige a violação. O conteúdo inicial abaixo
foi verificado no código em 2026-11-11. A implementação refaz a auditoria
(passo 1 do plano).

| Princípio | Símbolo | Violação | Receita |
| --- | --- | --- | --- |
| P-03 | `app/Services/CompanySettingService.php` (`CompanySettingService`) | Apaga o logo antigo dentro de `DB::transaction`. | `grep -n "delete(" app/Services/CompanySettingService.php` |
| P-03 | `app/Services/UserProfileService.php` (`UserProfileService`) | Apaga a foto antiga dentro de `DB::transaction`. | `grep -n "delete(" app/Services/UserProfileService.php` |
| P-03 | `DriveService` (movimentação depois do commit) | A falha ao mover é registrada só com `Log::info`, sem o ID do tenant; a falha secundária é ignorada sem log. | `grep -n "Log::info" app/Services/DriveService.php` |
| P-03 | `ProposalTimelineService::complete` e `ProposalDocumentService::delete` | Apagam arquivo depois do commit sem try/log. Com o disco `minio` (`throw => true`), a falha devolve "Erro" com os dados já gravados. | `grep -n "disk()->delete" app/Services/ProposalTimelineService.php app/Services/ProposalDocumentService.php` |
| P-03 | `TenantReportController` | Despacha `BuildFinishedProposalsZipJob` sem `afterCommit()`. | `grep -n "::dispatch(" app/Http/Controllers/TenantReportController.php` |
| P-05 | `database/migrations/tenant/2026_10_01_200015_add_proposal_roles.php`, `database/migrations/tenant/2026_10_15_100002_add_registry_protocols_and_reports_permissions.php`, `database/migrations/tenant/2026_10_28_100009_sync_crm_permissions_and_remove_contracts.php` | Usam os models `Permission`/`Role` do Spatie. Já foram aplicadas: pela P-06, não são editadas. | `grep -rln "Spatie\\\\Permission\\\\Models" database/migrations/tenant` |
| P-07 | Rota `tenant.documents.proposals.cost-items.proof.store` | Escrita (POST) protegida por `documents.proposals.view`. A Policy faz a checagem real. | comando `vendor/bin/sail php -r` da entrada V7 de `specs/violacoes-conhecidas.md` (a rota de download de relatório também aparece: é leitura em POST, não é violação) |
| P-08 | Entrada agregada: chamadas `Log::` com `getMessage()` em `app/` (controllers, services, commands) | A mensagem da `QueryException` inclui os valores de bind do SQL. | `grep -rn -A3 "Log::" app \| grep "getMessage()"` (pega também as chamadas quebradas em várias linhas, como em `TenantDriveController` e `DriveService`) |
| P-08 | `DriveService` (upload) | Loga `['exception' => $th]`, que serializa a mensagem com os binds. | `grep -rn "'exception' => \$th" app` |
| P-08 | `bootstrap/app.php` (handler padrão) | Sem `report` para `QueryException`: o handler grava a mensagem completa de toda exceção não capturada. | `grep -n "QueryException" bootstrap/app.php` (saída vazia = violação presente) |
| P-12 | `app/Utils/Utils.php` (`Utils`) | Classe do projeto sem sufixo de tipo. | `ls app/Utils` |
| P-12 | `tests/Support/TenantRegistry.php` (`TenantRegistry`) | Classe do projeto sem sufixo de tipo. | `ls tests/Support` |

### Ajustes nos outros arquivos (resumo; o detalhe por arquivo fica no Plano de implementação)

*   **`CLAUDE.md`:**
    *   a seção "documentação (`docs/`)" vira "Constituição e documentação",
        com `@specs/constitution.md`, os ponteiros para a D-01 e a D-02 e um
        lembrete de que as instruções de ferramenta são subordinadas;
    *   saem as referências mortas;
    *   o bloco do Boost, o "Mapa de skills" e a "Code Review" não mudam.
*   **`AGENTS.md`:**
    *   a seção "SDD (`specs/`) e documentação" é reescrita com a instrução de
        leitura e os mesmos ponteiros;
    *   a subseção "Regras do PRD de evolução" sai inteira.
*   **Convenções:**
    *   o cabeçalho, a linha 19, a "Regra número um" e a regra 1 da §1 deixam
        de mandar copiar violações;
    *   a §1 vira "Regras operacionais", com ponteiros para os IDs;
    *   a regra 10 é reescrita;
    *   §4a nova (lista de sufixos), §7 (log), §9 (Policies e
        `SeedTenantDatabaseJob`), §10 (base visual), §11 (seleção de testes) e
        §13 (ponteiro para o registro de violações).
*   **Config:**
    *   `.env.example` com `mysql` e o usuário `sail`;
    *   `config/database.php` com default `mysql`;
    *   script `database/mysql/grant-tenant-databases.sh`, montado no
        `compose.yaml`;
    *   `phpunit.xml` sem mudança.

## Alternativas consideradas

### A. Ampliar a §1 das convenções em vez de criar a constituição

*   **Prós:** custa menos. A §1 já se chama "Princípios inegociáveis", e não
    seria preciso criar arquivo novo nem mexer na precedência.
*   **Contras:** as regras continuariam misturadas com detalhe operacional
    (nomes de pasta, exemplos), que muda com frequência. Também não resolveria
    a regra "o código é o estado real", que hoje vale para todo o `docs/`.
*   **Decisão:** descartada. Sem um documento acima das convenções, não há onde
    escrever a precedência que torna o código divergente uma violação. Escolha
    registrada em [`specs/concepts_brief.md`](concepts_brief.md#decisões-tomadas-e-próximos-passos).

### B. Restaurar a constituição anterior (`e5fad17`) e emendá-la

*   **Prós:**
    *   aproveita regras já decididas: sufixo de tipo (V-17), estilo das telas
        congelado, Service/Action sem Repository, disco privado;
    *   mantém o registro de decisões `V-NN`.
*   **Contras:**
    *   tem 354 linhas e 16 seções, acima do limite da M1;
    *   depende de `spec.md`, `plan.md` e `implementation.md`, que não existem
        mais;
    *   traz princípios que o código atual também viola, como o
        `$tenant->run` literal.
*   **Decisão:** descartada. Herdaria 354 linhas e três dependências apagadas.
    Os IDs antigos não equivalem aos novos (M7).

### C. Usar a constituição anterior como fonte de candidatos

*   **Prós:** recupera regras úteis, decidindo uma por uma.
*   **Contras:** acrescenta uma rodada de decisões, com risco de passar do
    limite de princípios.
*   **Decisão:** descartada como processo. Duas regras dela voltaram com texto
    novo, por decisão do autor: o sufixo de tipo (P-12) e o estilo congelado
    (P-13).

### D. Constituição com todo o conteúdo das convenções

*   **Prós:** um só arquivo para ler.
*   **Contras:** envelheceria a cada mudança de pasta ou de padrão de código,
    e cada ajuste operacional viraria emenda versionada.
*   **Decisão:** descartada. Só entra o que justifica bloquear uma integração.

### E. Manter "o código é o estado real" como regra geral

*   **Prós:** nenhuma regra fica violada no papel, e nenhum documento precisa
    mudar.
*   **Contras:** anula qualquer regra normativa. Um código que grava dinheiro
    em `decimal` prevaleceria sobre a P-04.
*   **Decisão:** descartada. Foi substituída pela separação entre normativo e
    descritivo (D-01).

### F. Fronteira de bloqueio: "só arquivos novos" ou "arquivo tocado = corrigir"

*   **Só arquivos novos.** Prós: é a regra mais simples de verificar. Contras:
    permite replicar uma violação dentro de um arquivo antigo, como um método
    novo no `CompanySettingService` que apaga arquivo dentro da transação.
*   **Arquivo tocado = corrigir.** Prós: paga a dívida mais rápido. Contras:
    contradiz a §13 das convenções (sem refatoração fora do escopo) e não pode
    ser cumprida para migrations já aplicadas.
*   **Decisão:** as duas foram descartadas em favor de "linhas novas ou
    alteradas" (M5), que não contradiz a §13 e impede replicar a violação.

### G. Banco neutro de dialeto, ou PostgreSQL

*   **Prós:** a neutralidade deixa a porta aberta para outro banco.
*   **Contras:** exige proibir SQL específico e testar em mais de um banco. O
    Sail, o `.env` e os dados do projeto já são MySQL.
*   **Decisão:** descartada. Fixar o MySQL tira o custo de testar dois
    dialetos.

### H. Rodar a suíte completa a cada mudança

*   **Prós:** pega regressão em qualquer ponto.
*   **Contras:** a suíte leva cerca de 9 minutos, e não há CI que rode isso
    sem ocupar quem desenvolve.
*   **Decisão:** descartada. A P-09 exige teste novo ou atualizado em cada
    mudança e a suíte completa antes de integrar na `main`. A seleção dos
    testes afetados é detalhe operacional (convenções §11).

### I. Deixar o `AGENTS.md` fora dos ajustes

*   **Prós:** menos arquivos para alterar.
*   **Contras:** o Codex e o Antigravity (listados no `boost.json`) seguiriam
    lendo arquivos que não existem e tratando a ordem antiga como válida.
*   **Decisão:** descartada. Os dois índices citam os mesmos IDs (D-03), sem
    repetir regras.

### J. Levar o mapa de skills para a constituição

*   **Prós:** daria um lugar normativo à etapa obrigatória de revisão.
*   **Contras:** o mapa descreve o fluxo dos agentes, não uma regra do produto,
    e hoje cita cinco skills que não existem.
*   **Decisão:** descartada. O mapa fica fora do escopo e aparece como risco.

### K. Versionamento só com data e lista de mudanças, sem semver

*   **Prós:** dá menos trabalho para um time pequeno.
*   **Contras:** não diz se uma mudança quebra o que já estava valendo.
*   **Decisão:** descartada em favor do semver com histórico (D-04).

### L. Manter o registro de violações dentro da constituição

*   **Prós:** um só arquivo.
*   **Contras:** pela D-04, cada correção ou novo registro viraria emenda
    versionada. O registro seria importado em toda sessão, fora do limite de
    linhas, e duplicaria a §13 das convenções.
*   **Decisão:** descartada. O registro foi para `specs/violacoes-conhecidas.md`.
    A §13 das convenções também foi descartada como lugar do registro: as
    convenções são normativas, e cada correção ou registro de violação mudaria
    um documento normativo.

--------------------------------------------------------------------------------

### M. SDD de sistema inteiro (como o antigo)

*   **Prós:** uma visão única do que falta no sistema, com itens "[alvo]".
*   **Contras:** repete o que `docs/arquitetura/` e `docs/modulos/` descrevem
    (fere a D-03), e o template do `egm-draft` é de feature, não de sistema.
    O `spec.md` de sistema da versão anterior tinha 1.039 linhas e foi apagado
    com o resto do SDD no commit `e5fad17`.
*   **Decisão:** descartada em favor do SDD por feature (D-05), com o
    `specs/README.md` como índice.

### N. Convenções acima dos artefatos da feature

*   **Prós:** nenhuma feature reabre o padrão do projeto.
*   **Contras:** uma feature com necessidade legítima de fugir do padrão
    precisaria de uma mudança nas convenções antes de existir.
*   **Decisão:** descartada pelo autor, como no Spec Kit: o `plan.md` pode
    afastar uma convenção, de forma explícita e só para a feature (D-01,
    CUJ 7).

## Riscos e mitigações

| Risco | Impacto | Mitigação |
| --- | --- | --- |
| O `@import` some do `CLAUDE.md` | O Claude Code deixa de carregar a constituição. | O `GuidelineWriter` do Boost só substitui o trecho entre as tags. A verificação confere o import depois de qualquer `boost:install`. |
| A constituição cresce além de 200 linhas | Mais tokens fixos por sessão. | O teto da M1 é verificado com `wc -l` a cada emenda. Listas e detalhes ficam nas convenções. |
| O script de grant só roda em volume MySQL vazio | Ambientes que já existem dependem de grants manuais não registrados. O do autor tem um grant manual sobre `*.*`. | O script cobre todo clone novo. Ambientes antigos continuam como estão; recriar o volume aplica o script. |
| Exceções não capturadas gravam binds em `storage/logs` | CPF/CNPJ chega ao log pelo handler padrão do framework. | Registrado em `specs/violacoes-conhecidas.md`. Um `report` para `QueryException` em `bootstrap/app.php` é próximo passo declarado. |
| Cumprimento sem CI (risco aceito) | Uma violação passa se a revisão falhar. | Revisão de cada integração na `main`. CI é próximo passo declarado. |
| Registro de violações desatualizado | O agente não é avisado e replica uma violação não registrada. | Cada entrada tem uma receita de `grep`. O agente registra a violação que encontrar (CUJ 1), e a entrada sai no commit que a corrige (CUJ 6). |
| O Codex ou o Antigravity ignoram a instrução de leitura | As regras não chegam a esses agentes. | Instrução explícita no topo do `AGENTS.md`. O revisor confere o resultado como no CUJ 2. |
| Dados pessoais já gravados em `storage/logs` | Exposição de CPF/CNPJ já registrado. | Fora do escopo desta mudança. Fica registrado para uma tarefa de retenção e expurgo. |
| Mapa de skills quebrado no `CLAUDE.md` e no `AGENTS.md` | O agente procura skills que não existem e cita `specs/implementation.md` e `.agents/skills/`. | Não-meta. Fica para a tarefa própria. |
| Disco `s3` fora de `tenancy.filesystem.disks` | Se `BUCKET_DISK=s3`, os arquivos de tenant deixam de ser isolados por prefixo. | A P-02 obriga a conferir antes de trocar de disco. Hoje o valor é `minio`, que está conforme. |
| Isolamento entre tenants sem teste | Um vazamento entre tenants não é detectado automaticamente. | Revisão (P-01). O teste por módulo é próximo passo declarado. |
| `design.md` e os derivados (`spec`, `plan`, `tasks`) divergem | O agente segue um derivado desatualizado. | Os derivados não são editados à mão, exceto para marcar `- [x]` no `tasks.md`: muda-se o `design.md` e regeram-se os três (§15). |
| Duas branches pegam o mesmo `NNN` | Pastas com número repetido. | Quem integra por último renumera a própria pasta antes do merge (§15). |
| Feature concluída sem `egm-drift-report` | Os documentos da feature e os de `docs/modulos/` afetados divergem do código sem aviso. | A mudança para Concluída exige rodar o `egm-drift-report` sobre `specs/NNN-nome/` e sobre cada `docs/modulos/<modulo>/` que a feature tocou (§15). |
| Cerimônia do SDD em mudança pequena | O fluxo completo do EGM vira gargalo. | Exceções fechadas da D-05 (bug que restaura comportamento descrito, documentação). |

## Requisitos não-funcionais

*   **Segurança e privacidade:** a P-08 e o novo padrão de log da §7 reduzem o
    risco de CPF/CNPJ chegar ao `storage/logs`. As linhas antigas com
    `getMessage()` e o handler padrão do framework continuam gravando binds,
    e ficam registrados.
*   **Custo de contexto dos agentes:** a constituição é importada em toda
    sessão do Claude Code. O teto de 200 linhas (M1) limita esse custo, e o
    registro de violações fica fora do import.
*   **Rollout:** é um único commit de documentação e config. As mudanças com
    efeito em execução são duas:
    *   `config/database.php` só muda o *default*, e quem já tem
        `DB_CONNECTION` no `.env` não percebe diferença;
    *   num volume MySQL novo, o script de init dá ao usuário `sail` o
        privilégio sobre `tenant%`, e os testes conseguem criar os bancos de
        tenant. Volumes existentes não mudam.
*   **Rollback:** `git revert` do commit. Não há migration nem dado envolvido.

## Questões em aberto

*   **Q4: Exclusão de dados pessoais junto com o tenant.** O revisor do
    brainstorm sugeriu incluir na P-08 que "o dado é apagado junto com o
    tenant". Não está verificado se o `TenantDeleted` do stancl remove o banco
    e o prefixo do bucket nesta configuração. Até isso ser confirmado, a P-08
    não faz essa afirmação.
*   **Resolvida, Q1:** "código novo" quer dizer linhas novas ou alteradas
    (M5).
*   **Decidida, Q2 (confirmada só depois da verificação da M10):** os testes num clone novo usam o `.env`, com MySQL e o
    usuário `sail`. O script `database/mysql/grant-tenant-databases.sh` dá a
    esse usuário o privilégio sobre `tenant%` no primeiro boot do volume. O
    `phpunit.xml` não fixa a conexão. A confirmação vem da verificação da M10.
*   **Resolvida, Q3:** o `GuidelineWriter` do Boost substitui só o trecho
    entre as tags `<laravel-boost-guidelines>`, então um import escrito fora
    delas é preservado.

## Plano de implementação detalhado

O passo 1 é uma auditoria e não gera arquivo; ele alimenta o passo 2. Os passos
2 a 7 vão num único commit. O passo 8 é a verificação, rodada depois do commit.

**Convenções deste plano:**

*   `<DATA>` é a data do commit dos passos 2 a 7, no formato `AAAA-MM-DD`;
*   os números de linha citados são os da versão atual dos arquivos, antes de
    qualquer mudança, e cada trecho também é ancorado pelo título da seção;
*   todo comando roda com `vendor/bin/sail`;
*   o único PHP alterado está no passo 7: `config/database.php`,
    `config/queue.php` e `config/tenancy.php`;
*   a exclusão de `docs/prd-sistema-pnet-v2.md`, já feita na cópia de
    trabalho, entra no mesmo commit (o design trata o arquivo como removido);
*   a pasta `specs/` ainda não está no git. Entram no commit os arquivos
    criados nos passos 2 e 3 e os artefatos desta revisão (este design,
    `concepts_brief.md` e os relatórios). `design_draft.md` é cópia de trabalho
    deste design e é apagado antes do commit;
*   o pedido de implementação deste design conta como o pedido explícito da
    D-02 para todos os arquivos de `specs/` e `docs/` citados aqui.

### Passo 1. Auditoria (antes do commit, sem arquivo próprio)

Para cada princípio que tem comando no "Como verificar" (P-02, P-03, P-04,
P-05, P-07, P-10 e P-12), roda-se esse comando. Também rodam os comandos de
verificação da tabela "Conteúdo inicial de `specs/violacoes-conhecidas.md`".
Um acerto só é violação depois de descontar as Exceções do princípio e o que
os passos 2 a 7 corrigem no mesmo commit. Regras de classificação:

*   **P-04:** `grep -rnE "decimal|float|double" database/migrations`. Um
    acerto é dinheiro, e portanto violação, quando a coluna guarda valor em
    reais (nome com `value`, `amount`, `price`, `total`, `balance`, `salary`,
    `fee` ou `cost`). É taxa, e não violação, quando guarda percentual ou
    alíquota (`rate`, `percent`, `aliquot`). Caso ambíguo vai para a resposta
    ao usuário, e não para o registro. Já classificados: `reported_income` e
    `family_income` (`decimal(10,2)`) em
    `2026_05_19_171549_create_proponents_table.php` e no `down()` de
    `2026_10_01_200001_drop_proponents_table.php` não são violação viva: a
    tabela `proponents` foi removida, e migrations aplicadas não mudam (P-06).
*   **P-07:** o comando com `jq` lista, por definição, as Exceções da P-07
    (dashboard e perfil próprio: `tenant.dashboard`, `tenant.profile.edit`,
    `tenant.profile.update`, `tenant.profile.avatar`,
    `tenant.profile.password.update`). Só o que sobrar é violação.
*   **P-10:** `.env.example:23` e o fallback de `config/database.php:20` (e os de
    `config/queue.php` e `config/tenancy.php`) são corrigidos no passo 7 e não
    entram no registro.
*   **P-12:** conferir cada classe em `app/`, `database/` e `tests/` contra a
    tabela da §4a definida no passo 6 deste plano (a auditoria usa essa tabela
    antes de ela ser escrita nas convenções). Já classificado:
    `tests/Support/TenantRegistry.php` (classe `TenantRegistry`, sem sufixo) é
    violação e entra no registro, como `app/Utils/Utils.php`.
*   **P-08:** só tem verificação por revisão. A auditoria roda apenas os
    comandos das entradas de P-08 já listadas.

Todo acerto que não estiver na tabela "Conteúdo inicial de
`specs/violacoes-conhecidas.md`" entra nesse arquivo no passo 2. Uma entrada da
tabela cujo comando não devolve mais o resultado esperado sai.

### Passo 2. `specs/violacoes-conhecidas.md` (criar)

*   **Tipo:** documento descritivo, em pt-BR, sem versão.
*   **Conteúdo, nesta ordem:**
    1.  `# Violações conhecidas da constituição`
    2.  Parágrafo: "Lista o código existente que viola algum princípio de
        [`specs/constitution.md`](constitution.md). Serve de aviso para o agente
        não copiar o padrão. Não é importado em sessão. Uma entrada entra no
        commit que encontra a violação e sai no commit que a corrige; isso não
        é emenda da constituição (D-02)."
    3.  `Verificado em <DATA>.`
    4.  A tabela com as colunas Princípio, Símbolo, Violação, Comando de
        verificação e Resultado esperado. A última coluna diz o que o comando
        mostra enquanto a violação existe (ex.: "lista a linha do `delete(`",
        "saída vazia").
*   **Por quê:** M4 e E2.

**`specs/README.md` (criar), no mesmo passo:**

    # Especificações por feature (SDD)

    Índice das features do SDD (D-05). O procedimento está nas [convenções §15](../docs/convencoes_de_desenvolvimento.md). A constituição está em [constitution.md](constitution.md).

    Status: Rascunho → Em revisão → Aprovada → Em implementação → Concluída (ou Abandonada).

    | Feature | Pasta | Status | Última atualização |
    | --- | --- | --- | --- |

*   **Por quê:** M11 e D-05.

### Passo 3. `specs/constitution.md` (criar)

*   **Tipo:** documento normativo, em pt-BR.
*   **Formatação:** um campo por linha, sem quebra manual de linha dentro de um
    campo. Assim o arquivo fica em cerca de 180 linhas, abaixo do teto de 200
    da M1.
*   **Conteúdo, nesta ordem:**
    1.  `# Constituição do Sistema PNET` e a linha
        `Versão 1.0.0 · Ratificada em <DATA> · Última emenda <DATA>`.
    2.  `## 1. Propósito e alcance`:
        *   quem segue o documento: os agentes de IA e quem revisa cada
            integração na `main`;
        *   "O **responsável técnico do repositório** aprova emendas,
            dependências e exceções.";
        *   "Substitui a constituição anterior, removida no commit `e5fad17`.
            Os IDs `P-NN` e `V-NN` daquela versão não equivalem aos desta.";
        *   o vocabulário: DEVE (obrigatório), NÃO DEVE (proibido), PODE
            (permitido);
        *   "Integração na `main`: qualquer conjunto de commits integrado à
            `main`, por PR ou por merge direto.";
        *   "As violações já existentes estão em
            [`specs/violacoes-conhecidas.md`](violacoes-conhecidas.md)."
    3.  `## 2. Documentação`, com D-01 a D-05, cada um como
        `### D-NN — Título`.
    4.  `## 3. Princípios de engenharia`, com as subseções `### 3.1 Isolamento`
        a `### 3.5 Nomes e interface`, e P-01 a P-13 como `#### P-NN — Título`.
    5.  `## 4. Cumprimento e próximos passos`:
        *   "Na versão 1.0.0, o cumprimento é feito pela revisão de cada
            integração na `main`, e a suíte segue a P-09.";
        *   os próximos passos: CI, `tests/Arch/ConstitutionTest.php`, um teste
            de isolamento entre tenants por módulo, um helper central de log e
            um `report` para `QueryException` em `bootstrap/app.php`.
    6.  `## Histórico`, com a tabela Versão, Data e Mudança, e a linha
        `1.0.0 | <DATA> | Versão inicial (substitui a constituição removida em e5fad17).`
*   **Campos de cada princípio:** os seis da M2. Regra, Como verificar,
    Exceções e Detalhe vêm da tabela "Princípios" do Plano técnico, com a Regra
    reescrita usando DEVE / NÃO DEVE / PODE. Escopo e Por quê vêm da tabela
    abaixo.

| ID | Escopo | Por quê |
| --- | --- | --- |
| D-01 | Todos os documentos do repositório e todo código integrado na `main`. | Sem precedência, o código prevalece sobre qualquer regra escrita e nenhuma violação pode ser bloqueada. |
| D-02 | Arquivos em `specs/` e `docs/`. | O projeto é documentado em pt-BR, e documento normativo não pode mudar sem decisão. |
| D-03 | Constituição, convenções, `CLAUDE.md` e `AGENTS.md`. | Regras repetidas divergem com o tempo (como hoje, com as Policies e os testes). |
| D-04 | `specs/constitution.md`. | A versão diz se uma mudança quebra o que já valia. |
| D-05 | Toda mudança cujo primeiro commit tem data igual ou posterior à data "Ratificada em" da constituição e que altera rota, tela, regra de negócio, schema ou config com efeito em execução; as pastas `specs/NNN-nome/` e o `specs/README.md`. | A especificação revisada antes do código evita retrabalho e deixa registrado o porquê de cada mudança. |
| P-01 | Todo acesso a dado de tenant: HTTP, fila, commands, migrations, services e testes. | Um banco por tenant só isola se nenhuma leitura ou escrita acontecer fora do contexto certo. |
| P-02 | Todo disco que guarda arquivo de tenant, configurado ou gravado em coluna `disk`. | Um disco fora da lista do tenancy grava todos os tenants no mesmo prefixo. |
| P-03 | Apagar ou mover arquivo, enviar e-mail e despachar job a partir de código que abre transação. | Um rollback não desfaz efeito fora do banco. |
| P-04 | Colunas, propriedades e campos de formulário que guardam valor em reais. | Ponto flutuante gera erro de centavos. |
| P-05 | Módulos, permissões, planos, catálogos e configurações padrão, no banco central e no do tenant. | Só o `migrate` chega a todos os ambientes, e o plano contratado só vale se cada tenant receber só as permissões dos seus módulos. |
| P-06 | `database/migrations/central` e `database/migrations/tenant`. | Editar migration aplicada deixa ambientes com esquemas diferentes. |
| P-07 | Rotas de `routes/tenant.php` com `Authenticate`, e as telas que escondem ações por permissão. | As rotas são a única barreira que aplica o plano e a permissão no backend. |
| P-08 | Dados de tenant em consultas e chamadas `Log::` escritas pelo projeto. | LGPD: CPF/CNPJ e renda não podem vazar entre empresas nem ficar em arquivo de log. |
| P-09 | Todo código PHP, Vue e TypeScript integrado na `main`. | Sem teste executado, uma regressão só aparece em produção. |
| P-10 | `composer.json`, `package.json`, pastas-base de `app/` e `resources/js/`, config de banco e comandos de desenvolvimento. | Dependência nova e segundo banco aumentam o que precisa ser mantido e testado. |
| P-11 | Texto visto pelo usuário (requests, flash, telas, e-mails, PDFs do projeto) e identificadores novos. | O produto é brasileiro, e o código segue a convenção em inglês do ecossistema. |
| P-12 | Classes PHP criadas para o projeto em `app/`, `database/` e `tests/`. | O nome diz o papel da classe sem abrir o arquivo. |
| P-13 | Telas Vue e CSS em `resources/`. | Mudança visual sem pedido quebra a experiência que os clientes já conhecem. |

*   **Limites:** sem blocos de código; `wc -l` ≤ 200; sem métodos do próprio
    projeto; sem listas de tipos, componentes ou pastas (ficam nas convenções).
*   **Por quê:** M1, M2 e M7.

### Passo 4. `CLAUDE.md` (modificar)

*   **Tipo:** instruções do Claude Code.
*   **Mudanças:** substituir as linhas 5 a 15 (da seção "## documentação
    (`docs/`) — LEIA SEMPRE PRIMEIRO" até o parágrafo de pt-BR, antes de
    "## Tech Stack") por este texto:

    ```markdown
    ## Constituição e documentação — LEIA SEMPRE PRIMEIRO

    @specs/constitution.md

    A precedência entre documentos e o tratamento de divergências estão na D-01. Instruções de ferramenta neste arquivo (bloco do Boost, RTK) são subordinadas à constituição e às convenções (D-01).

    - Normativo: [docs/convencoes_de_desenvolvimento.md](docs/convencoes_de_desenvolvimento.md).
    - Descritivos (estado atual):
      - [docs/arquitetura/](docs/arquitetura/) — visão geral, arquitetura, dicionário de dados, rotas, dark mode;
      - [docs/modulos/](docs/modulos/) — documentação funcional por módulo;
      - [specs/violacoes-conhecidas.md](specs/violacoes-conhecidas.md) — código que viola a constituição; não copie.
    - Histórico de requisitos: [docs/prd/](docs/prd/).

    **SDD por feature (D-05):** antes de mudar comportamento, leia [specs/README.md](specs/README.md) e a pasta da feature. O procedimento está nas convenções §15. As skills do EGM são `egm-brainstorm`, `egm-draft` e `egm-review`, e só o usuário as dispara: no Claude Code, `/egm:egm-brainstorm` etc.; no Codex e no Antigravity, pelo seletor de skills. O agente pede ao usuário quando for a hora de cada uma.

    Arquivos de `specs/` e `docs/` são em pt-BR e só mudam com pedido explícito (D-02).

    **Na dúvida, pare e pergunte:** quando a constituição, as convenções e o código não resolvem um caso, ou quando uma escolha muda materialmente o resultado, pergunte ao usuário antes de seguir (convenções §14).
    ```

*   **Cuidado:** o texto novo não pode conter a tag literal do bloco do Boost.
    O `GuidelineWriter` substitui o primeiro trecho entre a tag de abertura e a
    de fechamento (`vendor/laravel/boost/src/Install/GuidelineWriter.php:56`);
    uma tag a mais no texto novo deslocaria esse trecho.
*   **Não tocar:** a linha 3 (idioma de resposta); a seção "## Tech Stack"
    (linhas 17 a 19), que abre o bloco do Boost; o bloco do Boost; o "Mapa de
    skills" e a "Code Review" (não-meta).
*   **Por quê:** M6.

### Passo 5. `AGENTS.md` (modificar)

*   **Tipo:** instruções do Codex e do Antigravity.
*   **Mudanças:**
    *   substituir as linhas 5 a 26 (seção "## SDD (`specs/`) e documentação
        (`docs/`) — LEIA SEMPRE PRIMEIRO") pelo texto do passo 4, com uma
        única troca: no lugar da linha `@specs/constitution.md`, a frase
        "No início de toda sessão, antes de planejar ou mudar código, leia
        [specs/constitution.md](specs/constitution.md).";
    *   remover a subseção "### Regras do PRD de evolução (resumo)" inteira
        (linhas 28 a 39, até a linha em branco antes da tag do Boost). O
        destino de cada regra:

        | Regra removida | Destino |
        | --- | --- |
        | CRM só no frontend | sai, sem substituto |
        | MUD-01 a MUD-04 | saem, sem substituto |
        | Sufixo de tipo, com exemplos | substituída pela P-12; exemplos na §4a das convenções |
        | Estilo das telas | substituída pela P-13 |
        | Testes: só os afetados; suíte completa só com pedido | substituída pela P-09 e pela §11 das convenções: a suíte completa passa a ser **obrigatória** antes de integrar na `main` |
        | "Na dúvida, pare e pergunte" | fica, no texto novo |

*   **Não tocar:** a linha 3; o bloco do Boost (da tag de abertura, linha 41,
    até a tag de fechamento, linha 488); e as seções depois dele, "## Mapa de
    skills" (491 a 509), "## Code Review" (511 a 525) e "## Instructions"
    (527 a 532). O Mapa do `AGENTS.md` cita `specs/implementation.md` e
    `.agents/skills/`, que não existem; isso fica como no `CLAUDE.md`
    (não-meta e Riscos).
*   **Por quê:** M6 e alternativa I.

### Passo 6. `docs/convencoes_de_desenvolvimento.md` (modificar)

*   **Tipo:** documento normativo operacional.
*   **Mudanças, na ordem do arquivo:**
    *   **Cabeçalho (linhas 1 a 7):**
        *   inserir, como primeira linha do bloco de citação, antes de
            "Público-alvo": "> **Este documento detalha a
            [constituição](../specs/constitution.md) e está abaixo dela
            (D-01).**";
        *   na "Regra número um", acrescentar ao fim, depois de "replique a
            estrutura dele.": "**Exceção:** se o recurso-espelho viola a
            constituição (os casos conhecidos estão em
            [`specs/violacoes-conhecidas.md`](../specs/violacoes-conhecidas.md)),
            escreva o código novo conforme a constituição."
    *   **§0, item 1:** trocar por "Ler [specs/constitution.md](../specs/constitution.md)
        e, se a tarefa muda comportamento, [specs/README.md](../specs/README.md), a
        pasta da feature e a §15 (D-05); depois [docs/arquitetura/project_overview.md](…) e
        [docs/arquitetura/architecture_guide.md](…)." (mantém os links atuais).
    *   **§0, item 4:** trocar "e usá-lo como molde literal." por "e usá-lo
        como molde, com a exceção da Regra número um."
    *   **§0, parágrafo da linha 19:** trocar o parágrafo inteiro por:
        "Divergências entre código e documentos seguem a D-01: o código vale
        sobre os documentos descritivos (`docs/arquitetura/`, `docs/modulos/`),
        e o código que diverge da constituição ou destas convenções é violação.
        Arquivos em `docs/` só são criados ou alterados com pedido explícito
        (D-02)."
    *   **§1:** renomear "## 1. Princípios inegociáveis" para "## 1. Regras
        operacionais". A tabela mantém as colunas #, Regra e Motivo e fica
        assim:

        | # | Regra | Motivo |
        | --- | --- | --- |
        | 1 | **Copie o padrão existente, não crie um novo**, com a exceção da Regra número um. | Consistência entre os controllers e services. |
        | 2 | (sem mudança) | (sem mudança) |
        | 3 | Dependências novas: ver **P-10**. | Constituição. |
        | 4 | Pastas-base novas em `app/` e `resources/js/`: ver **P-10**. **Não crie pastas-base novas em `docs/`.** | Estrutura definida. |
        | 5 | Comandos via Sail: ver **P-10**. | Constituição. |
        | 6 | Testes: ver **P-09**; seleção de testes na §11. | Constituição. |
        | 7 | (sem mudança) | (sem mudança) |
        | 8 | Texto ao usuário e identificadores: ver **P-11**; documentação: ver **D-02**. **Comentários e PHPDoc em pt-BR.** | Padrão do repositório. |
        | 9 | Contexto de tenant: ver **P-01**. | Constituição. |
        | 10 | Se um módulo tiver uma seção própria nestas convenções, ela vence a seção geral, mas nunca vence a constituição. Hoje nenhum módulo tem seção própria; padrão que só existe no código do módulo não é regra. | Precedência (D-01). |
        | 11 | Nomes de classe: ver **P-12** e a §4a. | Constituição. |
        | 12 | Base visual: ver **P-13** e a §10. | Constituição. |

    *   **§3, linhas 70 e 71:** trocar o texto de dinheiro por "Valores
        monetários: ver **P-04**; coluna `integer` (centavos)." e a linha 71
        inteira (de "**Dados de referência entram por migration" até "Regras
        dessas migrations de dados:") por "Dados de referência: ver **P-05**.
        Regras dessas migrations de dados:" (os sub-itens seguintes ficam).
    *   **§4a (nova), "## 4a. Nomes de classe"**, inserida depois da §4:

        | Tipo | Padrão | Pasta | Exemplo |
        | --- | --- | --- | --- |
        | Controller de tenant | `Tenant<Recurso>Controller` | `app/Http/Controllers/` | `TenantClientController` |
        | Controller de ação única | `Tenant<Verbo><Recurso>Controller` | `app/Http/Controllers/` | `TenantConvertQuoteToProposalController` |
        | Controller central ou de autenticação | `<Contexto><Recurso>Controller` | `app/Http/Controllers/`, `Auth/` | `AuthTenantController`, `TenantController` |
        | Form Request | `<Ação><Recurso>Request` | `app/Http/Requests/` | `StoreClientRequest` |
        | Service | `<Recurso>Service` | `app/Services/` | `ClientService` |
        | Action | `<Verbo><Recurso>Action` | `app/Actions/<Modulo>/` | `CreateQuoteAction` |
        | Policy | `<Model>Policy` | `app/Policies/` | `ProposalPolicy` |
        | Enum | `<Nome>Enum` | `app/Enums/` | `RolesEnum` |
        | Job | `<Ação>Job` | `app/Jobs/` | `SeedTenantDatabaseJob` |
        | Command | `<Ação>Command` | `app/Console/Commands/` | — |
        | Rule | `<Nome>Rule` | `app/Rules/` | `CpfRule` |
        | Exception | `<Motivo>Exception` | `app/Exceptions/` | `ContactHasFinancialEntriesException` |
        | Mail | `<Evento>Mail` | `app/Mail/` | — |
        | Middleware | `<Nome>Middleware` | `app/Http/Middleware/` | `RedirectProposalRestrictedUsersMiddleware` |
        | Bootstrapper de tenancy | `<Nome>Bootstrapper` | `app/Tenancy/Bootstrappers/` | `TenantSessionBootstrapper` |
        | Service provider | `<Nome>ServiceProvider` | `app/Providers/` | `TenancyServiceProvider` |
        | Cliente HTTP externo | `<Sistema>Client` | `app/Services/<Sistema>/` | `IbgeLocalityClient` |
        | DTO | `<Nome>Dto` | junto do service | `ItbiInputDto` |
        | Query | `<Nome>Query` | `app/Queries/` | `FinishedProposalsReportQuery` |
        | Builder / Calculator | `<Nome>Builder`, `<Nome>Calculator` | `app/Services/<Modulo>/` | `CostItemsBuilder`, `ItbiCalculator` |
        | Utilitário puro | `<Nome>Helper` | `app/Support/` | `MoneyHelper` |
        | Model | `<Entidade>`, sem sufixo | `app/Models/` | `Proposal` |
        | Factory | `<Model>Factory` | `database/factories/` | — |
        | Seeder | `<Nome>Seeder` | `database/seeders/` | `TenantContactSeeder` |
        | Teste | `<Classe>Test` | `tests/Feature/`, `tests/Unit/` | `ClientServiceTest` |

        Depois da tabela: "Exceção (P-12): classes geradas pelo framework ou
        pelos pacotes mantêm o nome original: as Actions do Fortify em
        `app/Actions/Fortify/`, `Authenticate`, `HandleInertiaRequests`,
        `VerifyCsrfToken`, `Controller`, `AppServiceProvider` e
        `FortifyServiceProvider` e `tests/TestCase.php`."
    *   **§5.1, linha 153:** trocar a frase por "O `Tenant` é parâmetro
        **explícito** (normalmente o último) e o corpo roda dentro de
        `$tenant->run(...)`. A outra forma aceita pela **P-01** (service que
        declara no PHPDoc que exige contexto já inicializado) é exceção, usada
        só por services chamados de dentro de outro `run`."
    *   **§6, linha 257:** trocar por "`authorize()` retorna `true`: a
        autorização é feita pelo middleware `permission:` na rota e, quando o
        acesso depende do registro, por Policy (**P-07**)."
    *   **§7, linha 307 e exemplo:**
        *   trocar o bullet `Log::error('Erro ao <ação> <recurso>: '.$th->getMessage());`
            por "registro do erro conforme a **P-08**: para `QueryException`,
            só a classe e o SQLSTATE; para as demais, a mensagem; sempre com o
            ID do tenant, nunca com o payload nem com o objeto de exceção";
        *   inserir, antes do link do `TenantProductController` (linha 269), a
            linha "**Padrão exigido:**"; a §4 (linha 108, "Padrão real") não
            muda;
        *   no bloco de código da §7 (linhas 271 a 300), inserir
            `use Illuminate\Database\QueryException;` como primeira linha,
            seguida de uma linha em branco, e trocar o `catch` do `store` por:

            ```php
            } catch (\Throwable $th) {
                Log::error('Erro ao criar produto', $th instanceof QueryException
                    ? ['tenant' => tenant('id'), 'exception' => QueryException::class, 'sqlstate' => $th->getCode()]
                    : ['tenant' => tenant('id'), 'message' => $th->getMessage()]);

                return redirect()->back()->with('error', 'Erro ao criar produto!');
            }
            ```

        *   abaixo do bloco de código, a nota: "O `TenantProductController` real ainda
            usa o padrão antigo e está em `specs/violacoes-conhecidas.md`. Não
            altere o controller nesta mudança." (`getCode()` de uma
            `QueryException` devolve o SQLSTATE.)
    *   **§8, linha 324:** trocar por "**Toda rota autenticada que opera recurso
        de módulo tem `->middleware('permission:<modulo>.<recurso>.<acao>')`**
        (**P-07**; as exceções estão lá)."
    *   **§9:**
        *   linha 347: trocar por "**Permissões e módulos:** ver **P-05**." (a
            linha 348 e os sub-itens ficam);
        *   linha 382: trocar por "Policy: quando o acesso depende do registro
            (**P-07**). Hoje existem `DrivePolicy`, `ProposalPolicy` e
            `FeeCalculationPolicy`.";
        *   em todas as ocorrências (linhas 353 e 380), trocar
            `SeedTenantDatabase` por `SeedTenantDatabaseJob`.
    *   **§10:** inserir antes da §10.1 o bloco "**Base visual (P-13):**
        tokens de `resources/css/app.css`; componentes `@/components/ui/*`;
        layouts `TenantLayout`, `TenantSidebar` e `AuthLayout`; `MoneyInput`;
        `@/lib/masks`; composables `usePermission`, `useTenant`,
        `useCepLookup` e `useContactLookup`."
    *   **§11:** inserir depois da tabela de helpers e antes de "Regras:" o bloco "**Seleção de
        testes:** cada tarefa roda só os arquivos de teste afetados. A suíte
        completa (cerca de 9 min) é obrigatória antes de integrar na `main`
        (**P-09**) e roda também quando o usuário pedir."
    *   **§15 (nova), "## 15. SDD por feature"**, inserida depois da §14, com
        estas regras (texto em lista, um item por regra):
        1.  **Quando:** toda mudança que a D-05 cobre cujo primeiro commit tem
            data igual ou posterior à data "Ratificada em" do cabeçalho da
            constituição.
        2.  **Pasta:** `specs/NNN-nome/`. `NNN` é o maior número existente em
            `specs/` mais 1, começando em `001`, atribuído ao criar a pasta, no
            início do `egm-draft`. `nome` é kebab-case, em pt-BR, com até 4
            palavras. Se duas branches pegarem o mesmo número, quem integra por
            último renumera a própria pasta e a linha do `specs/README.md` antes
            do merge; as mensagens de commit antigas ficam como estão.
        3.  **Conteúdo:** `design.md` (a fonte), `reviews/` e os derivados
            `spec.md`, `plan.md` e `tasks.md`. Como os skills gravam em outros
            lugares, o agente:
            *   no `egm-draft`, na pergunta "Where should the design be saved?",
                informa o diretório `specs/NNN-nome/` e, ao final, renomeia o
                arquivo gerado para `design.md`;
            *   ao fim do `egm-brainstorm`, move o `concepts_brief.md` para
                `specs/NNN-nome/`;
            *   ao fim do `egm-draft`, apaga o `design_draft.md` (cópia de
                trabalho) antes do commit `specs/NNN-nome: Rascunho`;
            *   ao fim de cada `egm-review` ou `egm-review-loop`, move os
                relatórios que o skill grava no diretório de trabalho
                (`zoom_out_report.md`, `comprehension_report.md`,
                `critic_report.md`, `intermediate_zoom_out_report.md`,
                `implementation_readiness_report.md`,
                `consolidated_review_report.md`, `goldfish_review_report.md`)
                para `specs/NNN-nome/reviews/`, com o prefixo da rodada
                (`r1-`, `r2-`…) para não apagar os anteriores.

            Os skills do EGM têm `disable-model-invocation`: o agente pede ao
            usuário que rode o comando (no Claude Code, `/egm:egm-draft` etc.)
            e segue o skill quando ele for chamado.
        4.  **Derivação**, feita pelo agente que conduz o `egm-review`, ao fim
            do estágio 9, no commit que marca a feature como Aprovada:
            *   `spec.md` ← Visão geral, O problema, Metas, Não-metas, Jornadas
                críticas e Questões em aberto;
            *   `plan.md` ← Plano técnico, Alternativas, Riscos, Requisitos
                não-funcionais e a seção "Afastamentos de convenção" do design.
                O `design.md` DEVE ter essa seção, dentro do Plano técnico,
                escrita no `egm-draft` e revisada no `egm-review` (com "Nenhum"
                quando não houver);
            *   `tasks.md` ← Plano de implementação detalhado, com cada arquivo
                como um item `- [ ]` e a verificação no fim.

            Os derivados não são editados à mão, com uma exceção: marcar
            `- [x]` no `tasks.md`. Para mudar conteúdo, muda-se o `design.md` e
            regeram-se os três; ao regerar o `tasks.md`, o agente mantém
            marcados só os itens cujo texto não mudou. Em
            divergência entre o `design.md` e um derivado, vale o `design.md`.
        5.  **Status em `specs/README.md`**, mudado pelo agente no commit que
            muda o estado: Rascunho (durante o `egm-draft`), Em revisão
            (durante o `egm-review`), Aprovada, Em implementação, Concluída ou
            Abandonada. A mensagem do commit que muda o status é
            `specs/NNN-nome: <Status>`.
            *   **Aprovada** exige o `egm-review` com todos os estágios (5 a 9),
                no modo interativo ou no consolidado, sem Blocker ou Major
                aberto. "Aberto" é o achado que não foi corrigido nem
                contestado com aceite do usuário; um achado aceito no limite de
                rodadas só deixa de ser aberto se entrar na seção de Riscos do
                `design.md` como risco aceito.
            *   Qualquer mudança no `design.md` depois de Aprovada volta o
                status para Em revisão. As seções alteradas passam por um
                `egm-review-loop` antes de a feature voltar a Aprovada.
                Enquanto estiver Em revisão, NÃO DEVE haver commit de código da
                feature.
            *   **Abandonada** é marcada pelo responsável técnico. É histórico,
                como Concluída, e os afastamentos dela perdem efeito.
        6.  **Afastamentos:** só na seção "Afastamentos de convenção" do
            `design.md` (copiada para o `plan.md`), com a
            seção das convenções e o motivo. Valem se a feature chegou a
            Aprovada sem achado aberto sobre eles. Nunca afastam regra que só
            aponta para um princípio da constituição (ex.: regras 3 a 6, 8, 9,
            11 e 12 da §1).
        7.  **Implementação:** a mensagem de cada commit de código começa com
            `specs/NNN-nome: ` (ex.: `specs/004-webhook-banco: valida assinatura`),
            e o commit marca os itens do `tasks.md` concluídos.
        8.  **Conclusão:** rodar o `egm-drift-report` sobre `specs/NNN-nome/` e
            sobre cada `docs/modulos/<modulo>/` que a feature tocou; corrigir no
            commit `specs/NNN-nome: Concluída`
            as divergências (no código ou em `docs/`); levar para as convenções
            ou para `docs/` o que deve continuar valendo; marcar Concluída. A
            pasta passa a ser histórico.
    *   **§13:** inserir como primeiro parágrafo "As violações dos princípios
        da constituição estão em
        [`specs/violacoes-conhecidas.md`](../specs/violacoes-conhecidas.md).
        Esta seção lista só desvios de estilo." Os itens atuais ficam.
*   **Por quê:** M8 e D-03. O cabeçalho, o §0 e a regra 1 resolvem o problema
    1. A §7 passa a mostrar o padrão de log exigido pela P-08.

### Passo 7. Config e ambiente (modificar e criar)

*   **`.env.example`, linhas 23 a 28:** trocar por

    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=laravel
    DB_USERNAME=sail
    DB_PASSWORD=password
    ```

*   **`config/database.php`, linha 20:** `env('DB_CONNECTION', 'sqlite')` →
    `env('DB_CONNECTION', 'mysql')`. As conexões `sqlite` e `pgsql` continuam
    definidas: fazem parte da config padrão do Laravel, e removê-las está fora
    do escopo.
*   **`config/queue.php`, linhas 106 e 125:** `env('DB_CONNECTION', 'sqlite')`
    → `env('DB_CONNECTION', 'mysql')` (batching e failed jobs).
*   **`config/tenancy.php`, linha 54:** `env('DB_CONNECTION', 'central')` →
    `env('DB_CONNECTION', 'mysql')`. Não existe conexão `central` em
    `config/database.php`.
*   **`database/mysql/grant-tenant-databases.sh` (criar):** o script de init do
    MySQL, no formato de
    `vendor/laravel/sail/database/mysql/create-testing-database.sh`. A pasta
    `database/mysql/` é nova, mas fica dentro de `database/`, que já existe; a
    raiz do repositório não ganha pasta. Conteúdo literal:

    ```bash
    #!/usr/bin/env bash

    if [ -n "$MYSQL_USER" ]; then
    mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
        GRANT ALL PRIVILEGES ON \`tenant%\`.* TO '$MYSQL_USER'@'%';
    EOSQL
    fi
    ```

    O arquivo precisa de permissão de execução (`chmod +x`). `ALL
    PRIVILEGES` sobre `tenant%` é o que o stancl usa para criar, migrar e
    apagar bancos de tenant, inclusive o `tenanttest` que
    `TestCase::refreshTestDatabase` cria (via `TenantRegistry::migrate`) em
    toda execução da suíte Feature.
*   **`compose.yaml`, serviço `mysql`, logo depois da linha 43:** acrescentar
    `- "./database/mysql/grant-tenant-databases.sh:/docker-entrypoint-initdb.d/20-grant-tenant-databases.sh"`.
    O MySQL só roda scripts de init quando o volume de dados está vazio.
*   **`phpunit.xml`:** sem mudança.
*   **Antes do commit:** `vendor/bin/sail bin pint --dirty --format agent`
    (regra 7 da §1 das convenções). Se o Pint ajustar algo, o ajuste entra no
    mesmo commit.
*   **Por quê:** M9 e P-10.

### Passo 8. Verificação (depois do commit)

Cada item tem o resultado esperado.

*   **Estilo PHP:** `vendor/bin/sail bin pint --test config/database.php config/queue.php config/tenancy.php`
    termina sem alterações pendentes.
*   **Fallbacks de banco:** `grep -n "env('DB_CONNECTION'" config/database.php config/queue.php config/tenancy.php`
    deve mostrar `'mysql'` como segundo argumento nas 4 linhas.
*   **Testes no ambiente atual:**
    `vendor/bin/sail artisan test --compact tests/Feature/MoneyColumnsTest.php tests/Feature/ReferenceDataMigrationsTest.php tests/Feature/TenantTest.php`
    deve passar. Os três rodam o bootstrap da suíte, que cria o banco
    `tenanttest`; o `TenantTest` também provisiona tenants via
    `TenantService::store`, que cria bancos `tenant<uuid>`.
*   **Clone novo (M10).** `vendor/bin/sail down -v` apaga o banco local, então o teste roda
    num worktree separado. Só o nome do projeto e as portas diferem do
    `.env.example`:
    1.  `git worktree add ../sistema-pnet-verify HEAD` (depois do commit dos
        passos 2 a 7);
    2.  `cd ../sistema-pnet-verify && cp .env.example .env && cp -r ../sistema-pnet/vendor .`;
    3.  acrescentar ao fim do `.env` do worktree:
        `COMPOSE_PROJECT_NAME=pnet-verify`, `APP_PORT=8081`,
        `VITE_PORT=5174`, `FORWARD_DB_PORT=3307`, `FORWARD_REDIS_PORT=6380`,
        `FORWARD_MAILPIT_PORT=1026`, `FORWARD_MAILPIT_DASHBOARD_PORT=8026`,
        `FORWARD_MINIO_PORT=9001` e `FORWARD_MINIO_CONSOLE_PORT=8901`;
    4.  `vendor/bin/sail up -d --wait` (espera o healthcheck do MySQL, que num
        volume novo roda os scripts de init antes de aceitar conexões) e
        `vendor/bin/sail artisan key:generate`;
    5.  rodar os três testes do item anterior: devem passar sem nenhum grant
        manual;
    6.  `vendor/bin/sail down -v`, `cd ../sistema-pnet` e
        `git worktree remove ../sistema-pnet-verify`.

    Quem executa este item atualiza a Q2 deste design para "confirmada", no
    mesmo PR. Se falhar, a Q2 volta a ficar em aberto e a integração não
    acontece.
*   **Documentos (um comando por meta):**
    *   M6, referências mortas:
        `grep -n "specs/spec.md\|specs/plan.md\|specs/tasks.md\|specs/implementation.md\|prd-sistema-pnet-v2" CLAUDE.md AGENTS.md`
        deve devolver só as linhas do "Mapa de skills" dos dois arquivos
        (`specs/implementation.md`);
    *   M6, import e instrução:
        `grep -n "^@specs/constitution.md" CLAUDE.md` devolve 1 linha, antes
        de "## Tech Stack"; `grep -n "leia \[specs/constitution.md\]" AGENTS.md`
        devolve 1 linha;
    *   M6, regras do PRD: `grep -n "Regras do PRD" AGENTS.md` sai vazio;
    *   problema 1: `grep -n "o código é o estado real\|não é a verdade final" CLAUDE.md AGENTS.md docs/convencoes_de_desenvolvimento.md`
        sai vazio (as frases "estado real da aplicação" da "Code Review" e do
        item 5 da §0 continuam e não são acerto);
    *   M7: `grep -n "e5fad17" specs/constitution.md` devolve a nota de
        substituição e a linha do histórico;
    *   M8: `grep -n "^## 4a. Nomes de classe" docs/convencoes_de_desenvolvimento.md`
        devolve 1 linha; `grep -n "SeedTenantDatabase\b" docs/convencoes_de_desenvolvimento.md | grep -v Job`
        sai vazio;
    *   M1: `grep -c "^### D-\|^#### P-" specs/constitution.md` devolve 18;
        `grep -c "^- \*\*Regra:\*\*.*\(DEVE\|PODE\)" specs/constitution.md`
        devolve 18; `wc -l specs/constitution.md` ≤ 200;
    *   M11: `test -f specs/README.md`; `grep -c "^| Feature | Pasta | Status | Última atualização |" specs/README.md` devolve 1; `grep -n "^## 15. SDD por feature" docs/convencoes_de_desenvolvimento.md` devolve 1 linha;
    *   D-05: `grep -n "Afastamentos\|§15" specs/constitution.md` mostra a D-01 e a D-05.
*   **Registro de violações:** rodar o comando de cada entrada de
    `specs/violacoes-conhecidas.md` e conferir que o resultado bate com a coluna
    "Resultado esperado".
*   **Suíte completa (P-09):** quem integra esta branch na `main` roda
    `vendor/bin/sail artisan test --compact` antes do merge.

--------------------------------------------------------------------------------

*Rascunhado com EGM*
