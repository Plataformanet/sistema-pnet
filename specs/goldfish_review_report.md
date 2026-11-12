# Relatório de resolução dos achados (Goldfish)

**Status:** resolvido (interactive breakdown, 2026-11-11). 7 ESCALATE decididos pelo autor; 27 PROPOSE FIX aceitos; 1 CONTEST aceito; 12 DECLINE. Decisões: E1 mysql + root no .env.example, sem DB_CONNECTION no phpunit.xml; E2 arquivo próprio; E3 regras do PRD saem; "na dúvida, pare e pergunte" revertido pelo autor depois e mantido nos dois índices; E4 suíte antes do merge na main; E5 desvio inline na §7; E6 API do framework permitida; E7 nível de módulo eliminado.

**Alvo:** `specs/constituicao-design.md` (arquivo inteiro: especificação nas linhas 1–453, plano nas linhas 454–645)
**Origem:** estágio 5 (`zoom_out_report.md`, Z-1 a Z-12), estágio 6 (`comprehension_report.md`, C-1 a C-15) e estágio 7 (`critic_report.md`, IDs `design-1-*`, `structural-1-*` e `style-1-*`).
**Data:** 2026-11-11

Achados repetidos entre estágios foram fundidos. Cada item lista todos os achados que o originaram.

Classificação de cada item:

* **PROPOSE FIX**: o achado procede, e a correção vai para o design se você aprovar.
* **ESCALATE**: depende de um fato que só você sabe, ou de uma escolha entre opções defensáveis. Não mexo no design sem a sua resposta.
* **CONTEST**: o achado não procede, e a evidência citada pode ser conferida.
* **DECLINE**: Nit registrado e não corrigido.

Placar: 7 ESCALATE, 27 PROPOSE FIX, 1 CONTEST e 12 DECLINE.

---

## ESCALATE: precisam da sua decisão ou de um fato que só você conhece

### [E1] Q2: os testes num clone novo não conseguem criar os bancos de tenant (Blocker na prática)
* **Origem:** Z-6, C-8, design-1-11
* **Escopo:** linhas 143-148 (Q2), 446-451 (rollout), 597-600 e 624-627 (verificação)
* **Fato conferido no código:** o `create-testing-database.sh` do Sail só dá `GRANT ALL ON testing%.*` ao usuário `sail`, e o tenancy cria bancos com o prefixo `tenant` (`config/tenancy.php:66`). Com o `phpunit.xml` fixado em `mysql` e `DB_USERNAME=sail`, `createTenant()` e `sharedTenant()` falham.
* **Opções:**
  1. Usar `DB_USERNAME=root` no `.env.example` (o compose já define `MYSQL_ROOT_PASSWORD` e `MYSQL_ROOT_HOST=%`). É a mudança mais simples, e só afeta desenvolvimento.
  2. No `phpunit.xml`, prefixar os bancos de tenant de teste com `testing_` (`TENANCY_DATABASE_PREFIX` ou equivalente). O usuário `sail` continua sem privilégio extra, mas exige ler a config do stancl.
  3. Documentar um `GRANT` adicional no setup. Exige um passo manual em cada clone.
* **Fato que só você sabe:** qual `DB_CONNECTION` e qual usuário a produção usa ("todos os ambientes atuais", linha 449).
* **Em qualquer opção:** tirar do plano o "registrar no Apêndice A" e reescrever a M9 como "a suíte afetada passa num clone novo a partir do `.env.example`".

### [E2] O Apêndice A sai da constituição para um arquivo próprio?
* **Origem:** Z-1, Z-2, Z-10, Z-12, C-3, design-1-4, design-1-12, structural-1-1
* **Escopo:** M4 (93-95), §5 da estrutura (251-252), tabela do apêndice (290-303), NFR (443-445) e verificação (634-640)
* **Problema:** três revisões independentes dizem que o apêndice dentro da constituição não funciona:
  * pela D-04, cada correção ou novo registro vira emenda versionada, então o apêndice tende a ficar desatualizado;
  * ele é importado em toda sessão, fora do limite de linhas;
  * duplica a §13 das convenções, o que fere a D-03.
* **Isso muda uma decisão sua:** "apêndice de dívida" foi escolhido dentro da constituição.
* **Proposta:** criar o arquivo `specs/violacoes-conhecidas.md`, descritivo, sem versão e fora do `@import`:
  * cada entrada traz princípio, símbolo (`Classe::metodo`, `arquivo:símbolo`) e a receita de `grep` que a reencontra;
  * entra ou sai pelo próprio PR que cria ou corrige a violação, sem ser emenda;
  * a constituição guarda só um ponteiro;
  * a §13 das convenções aponta para ele.

  A opção alternativa é manter o apêndice dentro da constituição e declarar na D-04 que mudar o Apêndice A não é emenda.

### [E3] Destino das regras do `AGENTS.md` (CRM, MUD-01 a MUD-04, "na dúvida, pare e pergunte")
* **Origem:** design-1-8, C-7
* **Escopo:** 532-537
* **Fatos que só você sabe:**
  * A proibição "não implemente backend de CRM" ainda vale? Hoje existem as rotas `tenant.crm.kanban` e `tenant.crm.list`, e o `CrmPermissionsTest`.
  * As regras MUD-01 a MUD-04 (CRM → proposta, proposta → financeiro, orçamento → funil, remoção do Billable Service) já foram implementadas, foram abandonadas ou continuam pendentes?
* **Proposta (quando você responder):** listar no plano cada regra com o destino dela: fica no `AGENTS.md` e no `CLAUDE.md`, vai para as convenções, ou sai com o motivo. A regra "Na dúvida, pare e pergunte" vai para a seção nova nos dois índices.

### [E4] O que é uma "fase" (P-09) agora que `specs/tasks.md` não existe mais
* **Origem:** C-5, style-1-5, design-1-18
* **Escopo:** 248-249, 284, 411, 484 e 641
* **Fato que só você sabe:** as fases R0 a R7 continuam valendo?
* **Opções:**
  1. Trocar "fechamento de fase" por um gatilho que dá para verificar: "antes de integrar uma branch na `main`".
  2. Definir "fase" numa frase e dizer quem a fecha.

### [E5] Padrão de log da §7: desvio em cada `catch` ou centralizado
* **Origem:** Z-3, design-1-21
* **Escopo:** 283 (P-08) e 563-569 (§7)
* **Fato:** são 143 chamadas `Log::*(...getMessage())` em 50 controllers (cerca de 183 em 59 arquivos, contando commands e services). O desvio "se for `QueryException`…" seria copiado em todo `catch` novo.
* **Opções:**
  1. Manter o que já foi decidido: desvio inline na §7.
  2. Criar `app/Support/LogContextHelper` (no padrão de `MoneyHelper`), com um teste. A §7 vira uma linha, e a P-08 passa a ser verificada por `grep` de `getMessage()` em `Log::`. O custo é que entra código de produção neste conjunto de mudanças.

### [E6] Nomes de método na constituição
* **Origem:** C-1, style-1-3
* **Escopo:** 255-256, 494 e a tabela de princípios (276-288)
* **Contradição:** você decidiu "sem nomes de método" e reforçou isso com "só o arquivo", no caso do `geraPDF`. Mas o texto-base usa `$tenant->run()`, `afterCommit()`, `messages()`, `config('bucket.disk')` e `sharedTenant()`.
* **Opções:**
  1. Permitir nomes de método e de função quando a regra depende deles para ser verificada (API do framework ou do pacote), e manter a proibição de citar métodos do projeto como exemplo.
  2. Reescrever os princípios sem nenhum nome de método, por exemplo "dentro do contexto aberto pelo `Tenant`".

### [E7] O terceiro nível da precedência ("padrões de módulo")
* **Origem:** C-6, design-1-5
* **Escopo:** 88-92, 213-216 e 272
* **Problema:** esses padrões não estão escritos em nenhum documento normativo. Se "padrão de módulo" for o código do módulo, a regra "o código vence" volta pelo nível 3.
* **Isso muda uma decisão sua:** a ordem de 4 níveis vem do brief.
* **Opções:**
  1. Eliminar o nível. O padrão de um módulo só conta se estiver escrito numa seção das convenções, e a seção específica vence a geral.
  2. Criar uma seção normativa "Padrões do módulo" em cada `docs/modulos/<m>/`.

---

## PROPOSE FIX: correções propostas

### [F1] 🔴 Blocker: as convenções continuam mandando tratar o código como verdade e copiá-lo como molde
* **Origem:** design-1-1
* **Escopo:** M7 (113-118), ajustes nas convenções (315-323) e passo 4 do plano (543-591)
* **Justificativa:** procede. As convenções dizem, na linha 19, "A documentação não é a verdade final: o código é". A "Regra número um" e a regra 1 da §1 mandam copiar o recurso-espelho "como molde literal". Isso replica as violações registradas.
* **Edição proposta:** acrescentar à M7 e ao passo 4:
  * substituir a linha 19 das convenções por um ponteiro para a D-01;
  * na "Regra número um" e na regra 1 da §1, acrescentar "exceto o que viola a constituição ou está em `specs/violacoes-conhecidas.md`: nesses pontos, escreva o código novo conforme a constituição".

### [F2] Entradas erradas e faltantes no registro de violações
* **Origem:** design-1-2, design-1-3, C-10, C-11, structural-1-1
* **Escopo:** tabela do apêndice (290-303)
* **Justificativa:** conferi no código. `RoleService` e `UserService` recebem `Tenant $tenant`, e a violação é só a transação manual, que a P-01 nem proíbe. A linha da P-11 também não é violação, porque a regra vale para identificadores novos.
* **Edição proposta:**
  * remover a linha da P-01 (`RoleService`/`UserService`) e a da P-11;
  * acrescentar as violações da P-03:
    * `ProposalTimelineService` e `ProposalDocumentService`: exclusão depois do commit sem try/log, sabendo que o disco `minio` lança exceção (`throw => true`);
    * `TenantReportController`: despacha `BuildFinishedProposalsZipJob` sem `afterCommit()`;
  * na P-08, trocar a categoria pela receita `grep -rln "getMessage()" app | xargs grep -l "Log::"` (são cerca de 59 arquivos);
  * acrescentar no plano o passo "auditoria por receita de `grep`, princípio por princípio", que gera as entradas na data da ratificação.

### [F3] P-01 incompatível com services que exigem contexto já inicializado
* **Origem:** design-1-2
* **Escopo:** 276 (P-01) e 280 (P-05)
* **Justificativa:** procede. `TenantPermissionService::apply()`, `DriveLogService` e `ContactService::restoreByDocument` rodam dentro de um contexto já aberto e não recebem `Tenant`, e a própria P-05 exige o `TenantPermissionService`.
* **Edição proposta:** a P-01 passa a dizer: "Service que escreve **recebe o `Tenant`** ou **declara no PHPDoc que exige contexto de tenant já inicializado** e só é chamado dentro dele."

### [F4] P-12 e P-13 copiadas nas convenções (fere a D-03)
* **Origem:** Z-5
* **Escopo:** P-12 e P-13 (287-288); passo 4, §4/§5 e §10 (570-579)
* **Edição proposta:**
  * a P-12 fica com a regra abstrata ("toda classe criada para o projeto termina com o seu tipo; models sem sufixo; controllers de tenant com o prefixo `Tenant`; scaffold do framework isento; a lista de tipos está nas convenções");
  * a P-13 fica com "nenhuma mudança visual sem pedido; telas novas usam a base visual existente, listada nas convenções §10";
  * as enumerações moram só nas convenções, e a tabela de sufixos vai para uma §4a nova, "Nomes de classe", fora de Models e Services.

### [F5] Campos do modelo de princípio sem origem definida
* **Origem:** C-4, structural-1-4
* **Escopo:** M2 (85-87), modelo (255-266) e tabela (268-288)
* **Edição proposta:**
  * a M2 passa a listar 6 campos;
  * a tabela ganha a coluna "Detalhe (convenções)", com a seção de cada princípio, ou "—" para D-01 a D-04;
  * nota: "Por quê" e "Escopo" são escritos na implementação a partir de "O problema" e da tabela "O que o código mostra" do brief.

### [F6] Contradição entre a M6 e o mapa de skills, e o CUJ 1 inalcançável
* **Origem:** Z-4, design-1-7, design-1-10, C-13
* **Escopo:** M6 (101-109), CUJ 1 (161-170) e não-metas (127-128)
* **Edição proposta:**
  * a M6 exclui explicitamente a seção "Mapa de skills" e a seção "Code Review" (não-meta);
  * o CUJ 1 passa a ter um resultado observável: "o agente cita `P-NN` ao recusar replicar uma violação", e deixa de dizer "não procura arquivos que não existem";
  * a coluna "ID" vira "Princípio";
  * acrescentar um risco: "o mapa de skills continua apontando para skills inexistentes até a tarefa própria".

### [F7] Regras de ferramenta (Boost, RTK) sem subordinação
* **Origem:** Z-11, design-1-9
* **Escopo:** D-01 (272) e diagrama (221-222)
* **Edição proposta:** acrescentar à D-01: "Instruções de ferramenta nos índices (bloco do Boost, RTK) são subordinadas à constituição e às convenções. Em conflito, valem estas."

### [F8] O `AGENTS.md` não garante a leitura da constituição
* **Origem:** design-1-10, style-1-10
* **Escopo:** M6 (101-105) e passo 3 (523-537)
* **Edição proposta:** o `AGENTS.md` traz a ordem explícita "No início de toda sessão, antes de planejar ou mudar código, leia `specs/constitution.md`". A Visão geral passa a dizer "o Claude Code a recebe por import; Codex e Antigravity, por instrução de leitura".

### [F9] As convenções misturam regra e inventário
* **Origem:** design-1-6
* **Escopo:** D-01 (272) e M7
* **Edição proposta:** acrescentar à D-01: "Nas convenções, só as regras escritas no imperativo são normativas. Exemplos, contagens e inventários são descritivos." O plano também corrige `SeedTenantDatabase` → `SeedTenantDatabaseJob` na §9.

### [F10] Limite de tamanho sem critério
* **Origem:** Z-8, C-9, design-1-12, structural-1-6
* **Escopo:** M1 (80-84), Alternativa C (361-362) e NFR (443-445)
* **Edição proposta:**
  * a M1 passa a "exatamente 17 princípios (4 D + 13 P); `wc -l specs/constitution.md` ≤ 200, contando tudo o que o import carrega";
  * explicar que o limite subiu em relação ao brief (8-12 e 60-100) por causa das 5 inclusões decididas no draft;
  * a Alternativa C deixa de citar "14".

### [F11] Isolamento entre tenants não verificável
* **Origem:** design-1-13
* **Escopo:** P-01 (276), P-08 (283)
* **Edição proposta:** "Como verificar" da P-01: revisão, mais o próximo passo declarado "teste de que o tenant A não lê dados do tenant B, por módulo". Isso também vai para a seção de riscos.

### [F12] A P-02 não verifica o nome de disco gravado no banco
* **Origem:** design-1-14, Z-9
* **Escopo:** P-02 (277) e linha da P-02 no apêndice (301)
* **Edição proposta:**
  * a regra passa a "todo disco usado para arquivo de tenant, inclusive o nome gravado em coluna `disk`, está em `tenancy.filesystem.disks`";
  * a verificação inclui um `SELECT DISTINCT disk` nas tabelas com essa coluna;
  * a linha do apêndice (risco do `s3`) vai para "Riscos e mitigações".

### [F13] Definir "PR" e "linha alterada"
* **Origem:** design-1-15, design-1-20
* **Escopo:** D-01 e M5 (96-100)
* **Edição proposta:**
  * "PR" passa a ser "qualquer conjunto de commits integrado à `main`, por PR ou por merge direto";
  * "linha alterada" passa a ser "linha com mudança de comportamento"; reformatação do Pint e movimentação de linha não contam;
  * código novo dentro de um bloco que já viola a regra cumpre a regra só nas linhas novas;
  * registrar como risco aceito: não há CI, então o cumprimento depende de revisão.

### [F14] Falta a seção "Riscos e mitigações"
* **Origem:** structural-1-2, design-1-16 (concept)
* **Escopo:** nova seção H2 antes de "Requisitos não-funcionais"
* **Edição proposta:** criar a seção com uma tabela de risco, impacto e mitigação para estes itens:
  * `@import` perdido;
  * constituição crescendo além do limite;
  * Q2;
  * cumprimento sem CI;
  * registro de violações desatualizado;
  * agentes que não são o Claude ignorando o link;
  * dados pessoais já gravados em `storage/logs`;
  * mapa de skills;
  * disco `s3`;
  * isolamento sem teste.

### [F15] Rejeições atribuídas ao autor sem a troca concreta
* **Origem:** structural-1-3
* **Escopo:** "Motivo da rejeição" das alternativas A a K (339-434)
* **Edição proposta:** trocar o rótulo por "**Decisão:**" e escrever a troca técnica, por exemplo "B: 354 linhas, o que estoura a M1, e dependência de 3 arquivos apagados". Linkar `specs/concepts_brief.md#decisões-tomadas-e-próximos-passos` quando a decisão foi uma escolha do autor.

### [F16] Salto de nível de título no §2 da constituição
* **Origem:** structural-1-5
* **Escopo:** modelo (258-259)
* **Edição proposta:** usar `### D-NN` direto sob o §2 e `#### P-NN` sob as subseções 3.x.

### [F17] Regras 4 e 5 das convenções duplicam a P-10
* **Origem:** structural-1-7
* **Escopo:** passo 4, §1 (550-556)
* **Edição proposta:** as regras 4 e 5 também viram ponteiro para a P-10.

### [F18] CUJ 3 com título e cenário diferentes, e falta o caso "código × documento descritivo"
* **Origem:** structural-1-8, C-15
* **Escopo:** 180-186
* **Edição proposta:**
  * renomear o CUJ 3 para "Documento descritivo diverge da constituição", citando D-01 e D-02;
  * acrescentar o CUJ 5, "Código diverge de documento descritivo": o agente aponta a divergência e só atualiza o documento com pedido.

### [F19] O problema não menciona o `AGENTS.md`
* **Origem:** style-1-1
* **Escopo:** 32-64
* **Edição proposta:** incluir o `AGENTS.md` nos itens 2 e 3 do problema. Ele cita `specs/plan.md`, `specs/tasks.md` e "`specs/` manda".

### [F20] Rotas sem `permission:` descritas de forma diferente no problema e no apêndice
* **Origem:** C-2, style-1-2, style-1-16
* **Escopo:** 60 e a P-07 (282)
* **Edição proposta:**
  * a linha 60 passa a dizer: "13 rotas autenticadas sem `permission:` (todas dentro das exceções da P-07, conferido por `route:list --json`) e uma rota de escrita protegida por `view`";
  * as exceções da P-07 ficam só com as rotas autenticadas (perfil próprio, dashboard, logout). Login, recuperação de senha e logo pública não são autenticadas e saem da lista.

### [F21] DEVE / NÃO DEVE quase não é usado
* **Origem:** style-1-4
* **Escopo:** 233-234 e tabela
* **Edição proposta:** acrescentar uma nota acima da tabela: "resumo informal; no texto final da constituição, toda Regra usa DEVE / NÃO DEVE / PODE". Na verificação, entra um `grep` que confirma DEVE ou NÃO DEVE em cada bloco.

### [F22] "Autor" sem papel definido na constituição
* **Origem:** style-1-5
* **Escopo:** P-10 (285) e D-04
* **Edição proposta:** usar "o responsável técnico do repositório", definido na §1.

### [F23] Termos sem definição
* **Origem:** C-12, C-14, design-1-16 (Spec Kit)
* **Escopo:** Visão geral
* **Edição proposta:** acrescentar um glossário curto: Spec Kit (só o formato; não usamos a ferramenta nem o caminho `.specify/`), Laravel Boost e o bloco de guidelines, stancl/tenancy, `sharedTenant()`/`createTenant()`, recurso-espelho e V-NN.

### [F24] Verificações fracas da P-03 e da P-12
* **Origem:** design-1-19
* **Escopo:** 278 e 287
* **Edição proposta:**
  * P-03: `grep -L afterCommit app/Mail/*.php`, mais a revisão de `::dispatch(` dentro de transação;
  * P-12: a comparação passa a ser por sufixo de tipo, não pelo nome da pasta (`Client`, `Dto`, `Builder` e `Calculator` são tipos válidos em `app/Services`).

### [F25] A §4 da constituição repete a P-09
* **Origem:** design-1-18
* **Escopo:** 246-249
* **Edição proposta:** a §4 só aponta para a P-09, e "hoje" vira "na versão 1.0.0".

### [F26] Pequenas correções de texto
* **Origem:** structural-1-9, style-1-7, style-1-9, style-1-11, style-1-14, design-1-17, Z-7
* **Escopo:** várias linhas
* **Edição proposta:**
  * renumerar a M6a como M7, ajustando as seguintes;
  * "o conteúdo anterior de `specs/` foi apagado";
  * retirar "mínimos";
  * "o revisor do brainstorm sugeriu";
  * D-01: "registra-se em `specs/violacoes-conhecidas.md`";
  * premissa da linha 45: "a regra 6 exige executar o teste" no lugar de "todos os testes";
  * nas não-metas, registrar também o `App\Support\Money` desatualizado no `architecture_guide.md:116`.

### [F27] Questões resolvidas em itálico
* **Origem:** structural-1-9
* **Escopo:** 155-157
* **Edição proposta:** passar para uma lista no formato de Q2/Q4, com "Resolvida:".

---

## CONTEST

### [X1] "O padrão proíbe rejeição baseada em pessoa" no caso das alternativas F, H e K
* **Origem:** parte de structural-1-3
* **Justificativa:** em F, H e K a troca técnica já está escrita no próprio "Motivo". F diz que contradiz a §13 e é impossível para migrations aplicadas; H, que leva 9 minutos e não há CI; K, que a forma alternativa não diz se a mudança quebra o que já valia. Para essas três, a [F15] só troca o rótulo.

---

## DECLINE: Nits registrados e não corrigidos

| ID | Motivo |
| --- | --- |
| structural-1-10 | A posição das linhas horizontais segue o template do EGM. |
| structural-1-11 | Já fica resolvido, porque o apêndice sai do documento ([E2]). |
| structural-1-12 | Largura de linha não afeta a leitura. |
| design-1-21 | A P-08 já proíbe qualquer dado pessoal em log; o caso da `QueryException` é só o padrão da §7. |
| style-1-6 | O exagero sobre o `CLAUDE.md:13` é contexto do problema; [F19] e [F26] já ajustam o entorno. |
| style-1-8 | "Falsa ou verdadeira" vira "violada ou cumprida" junto com [F26], sem item próprio. |
| style-1-12 | Crases em IDs: padronizado na escrita final da constituição. |
| style-1-13 | A quebra da P-01 em lista entra junto com [F3]. |
| style-1-15 | "Cada princípio" no lugar de "cada regra normativa" entra junto com [F9]. |
| style-1-17 | Hipérbole e jargão nas alternativas: revisado junto com [F15]. |
| style-1-18, style-1-19 | Revisados junto com [F15] (alternativas H e I). |
| style-1-20 | "As 3" ou "as três": padronizado na escrita final. |
