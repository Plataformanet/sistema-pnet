# Relatório da revisão crítica (Goldfish), rodada 1

**Alvo:** `specs/constituicao-design.md` (linhas 1–453, tudo antes de "## Plano de implementação detalhado")
**Etapa:** 7 (Revisão crítica), rodada 1. Modo: REVIEW.

## Status dos revisores

| Revisor | Persona / padrão | Status |
| --- | --- | --- |
| `egm-design` | `design_critic.md` + `feature-spec-standard.md` | completed |
| `egm-structural` | `structural.md` + `feature-spec-standard.md` | completed |
| `egm-style` | `style.md` (sem padrão; julgado em pt-BR) | completed |

## Resumo

| Severidade | Quantidade |
| --- | --- |
| 🔴 Blocker | 1 |
| 🟡 Major | 27 |
| 🟢 Nit | 25 |

Achados que mais de um revisor apontou foram fundidos em um só. O ID fica com o revisor que deu a descrição mais completa, e os outros aparecem em "Também apontado por".

---

## 🔴 Blockers

### design-1-1 — A regra "o código é a verdade" continua nas convenções, que o design torna normativas
- **Linhas:** 35–38, 88–92, 101–109, 113–118, 315–323
- **Descrição:** o problema nº 1 é o motivo de existir deste design, mas o M6 só substitui a regra no `CLAUDE.md` e no `AGENTS.md`. As convenções dizem o mesmo:
  - `docs/convencoes_de_desenvolvimento.md:19`: "A documentação não é a verdade final: o código é.";
  - a "Regra número um" (linha 5), que manda usar o recurso espelho "como molde literal";
  - a regra 1 do §1, "Copie o padrão existente".
  
  Nem o M7 nem os ajustes nas convenções (315–323) removem ou qualificam esses trechos. A única mudança no §0 é citar a constituição como leitura obrigatória.
- **Justificativa e recomendação:** depois da mudança, o documento normativo nº 2 continuaria mandando os agentes tratar o código como verdade e copiar padrões ao pé da letra, inclusive as violações do Apêndice A. É justamente o que os CUJ 1 e 2 querem evitar. No M7 e nas linhas 315–323:
  - remover ou reescrever a linha 19 das convenções para ficar coerente com a D-01;
  - qualificar a "Regra número um" e a regra 1 do §1 com "exceto padrões listados no Apêndice A ou que violem a constituição".

---

## 🟡 Major

### design-1-2 — A P-01, como está escrita, deixa o Apêndice A errado e violações reais de fora, e contradiz a P-05
- **Linhas:** 93–95 (M4), 276 (P-01), 280 (P-05), 297
- **Descrição:**
  - A linha 297 diz que `RoleService` e `UserService` abrem transação manual "sem o `Tenant` explícito". No código, todo método de escrita recebe `Tenant $tenant` e chama `DB::beginTransaction()` dentro de `$tenant->run()` (ex.: `RoleService.php:11–28`, `UserService.php:12–15, 106–109`). A P-01 não proíbe transação manual.
  - Ficam de fora, contra o M4, estas violações de "Service que escreve recebe o `Tenant`":
    - `DriveLogService::record()`;
    - `ContactService::restoreByDocument()`;
    - `TenantPermissionService::apply()` e `applyRoleDefaults()`.
  - A P-05 exige o `TenantPermissionService`, cujo `apply()` roda em um contexto de tenant já inicializado, sem receber `Tenant`. Isso viola a P-01 como está escrita.
- **Justificativa e recomendação:** reescrever a P-01 para aceitar as duas formas: o service recebe `Tenant`, ou documenta que exige contexto já inicializado e só é chamado dentro dele. Depois, refazer as entradas da P-01 no Apêndice A a partir de uma auditoria real.

### design-1-3 — O Apêndice A deixa violações de fora, então o M4 não é cumprido
- **Linhas:** 93–95, 278, 283, 290–303
- **Descrição:**
  - **P-03, registro de falha no disco pós-commit:** só o `DriveService` está listado. Outras exclusões feitas depois do commit não têm try/log:
    - `ProposalTimelineService.php:123` e `:215`;
    - `ProposalCostItemService`;
    - `ProposalDocumentService:159`.
    
    Como o disco `minio` usa `'throw' => true`, uma falha nessas exclusões lança exceção depois do commit, e o controller responde "Erro" mesmo com os dados já gravados.
  - **P-03, jobs:** `TenantReportController.php:65` despacha `BuildFinishedProposalsZipJob` sem `afterCommit()`.
  - **P-08:** há cerca de 183 chamadas a `getMessage()` em 59 arquivos, entre eles Commands e Services, não só Controllers.
- **Justificativa e recomendação:** auditar princípio por princípio, com uma receita de `grep` para cada um, e listar arquivo e símbolo em toda entrada.

### structural-1-1 — Linhas do Apêndice A sem arquivo, com caminho incompleto ou que não são violações
- **Linhas:** 93–95 (M4), 296, 298, 300, 301, 302
- **Também apontado por:** style (M-6), design (Nit 4)
- **Descrição:** o M4 exige "arquivo e descrição" para cada violação.
  - A linha 300 (P-08) dá uma categoria ("Controllers com `Log::error(…)`"), não arquivos.
  - A linha 298 encurta caminhos com "…".
  - A linha 301 (P-02) diz que o código "está conforme": é um risco, não uma violação.
  - A linha 302 (P-11) é uma "exceção registrada". A P-11 só vale para identificadores novos, então os identificadores antigos em pt não são violação.
  - A linha 296 usa "~linha 930", embora o título diga que o conteúdo foi "verificado no código".
- **Justificativa e recomendação:**
  - listar os arquivos, ou o comando que gera a lista;
  - escrever os caminhos completos;
  - mover o risco da P-02 para a seção de riscos, ou criar uma coluna "Tipo" (violação / risco);
  - tirar ou reescrever a entrada da P-11;
  - ancorar cada entrada em um método ou classe, não em um número de linha aproximado.

### design-1-4 — O Apêndice A não tem ciclo de vida, e suas entradas por arquivo não resolvem perguntas por linha
- **Linhas:** 96–100, 251–252, 272, 275, 290–303
- **Descrição:**
  - O bloqueio vale por linha (M5), mas as entradas são por arquivo, então o revisor não sabe quais linhas existentes a entrada cobre.
  - O design não diz quem inclui uma entrada (a D-01 diz só "registra-se") nem quem a retira quando a dívida é paga.
  - Pela D-04, todo commit em `constitution.md` exige pedido explícito do autor e mudança de versão. Quitar qualquer dívida passaria a exigir uma versão PATCH aprovada pelo autor.
- **Justificativa e recomendação:**
  - ancorar cada entrada em um símbolo (`Classe::metodo`) ou em um padrão de `grep`;
  - dizer que mexer no Apêndice A não é emenda, ou que é PATCH com aprovação permanente;
  - definir quem atualiza o apêndice (de preferência, o próprio PR que corrige a violação).

### design-1-5 — "Padrões de módulo" não têm onde morar e conflitam com a separação entre normativo e descritivo
- **Linhas:** 88–92, 213–216, 272
- **Descrição:** o nível 3 da hierarquia são os "padrões de módulo (drive, financeiro)", que são normativos. Só que esses padrões não estão escritos em nenhum documento normativo:
  - `docs/modulos/*` é classificado como descritivo (linha 218);
  - a regra 10 e o §14 das convenções apontam para o código do módulo.
  
  Se o padrão do módulo é o código, a regra "o código vence as convenções" volta pelo nível 3.
- **Justificativa e recomendação:** definir onde ficam os padrões de módulo (por exemplo, uma seção normativa "Padrões do módulo" em cada `docs/modulos/<m>`) ou eliminar o nível 3.

### design-1-6 — As convenções misturam texto normativo e descritivo, mas o design classifica o arquivo inteiro como normativo
- **Linhas:** 21, 88–92, 113–118, 272
- **Descrição:** as convenções trazem fatos do estado atual, e alguns já estão desatualizados:
  - o §9 cita o job `SeedTenantDatabase`, mas no código ele se chama `SeedTenantDatabaseJob`;
  - "hoje: `DrivePolicy`";
  - as contagens do tipo "~25 controllers".
  
  Pela D-01, quando o código diverge de um documento normativo, o código é que está errado. Assim, código correto passaria a contar como violação.
- **Justificativa e recomendação:** escolher uma das saídas:
  - (a) dizer que só as regras imperativas das convenções são normativas, e que exemplos e inventários são descritivos;
  - (b) estender às convenções a checagem de veracidade do M4.

### design-1-7 — O mapa de skills contradiz o M6
- **Linhas:** 106–109, 127–128, 307–312
- **Descrição:** o M6 diz que `CLAUDE.md` e `AGENTS.md` deixam de citar `specs/implementation.md`. Mas as não-metas (127) e a linha 312 deixam o mapa de skills intacto, e o próprio mapa diz "Este mapa é **idêntico** ao do `specs/implementation.md` §2". O mapa também encaminha toda revisão para cinco skills que não existem.
- **Justificativa e recomendação:** permitir uma edição mínima do mapa (tirar a frase sobre `implementation.md` e marcar as skills que faltam), ou retirar essa seção do alcance do M6.

### design-1-8 — Reescrever o `AGENTS.md` derruba regras de proteção ainda em vigor
- **Linhas:** 101–109, 313–314
- **Também apontado por:** structural (Nit 12)
- **Descrição:** a seção que será reescrita contém "Regras do PRD de evolução" (`AGENTS.md:28–39`):
  - "CRM: só frontend… **Não** implemente backend de CRM";
  - as regras MUD-01 a MUD-04;
  - "Na dúvida, pare e pergunte".
  
  A linha 313 manda reescrever a seção "com o mesmo conteúdo" do `CLAUDE.md`, que não tem essas regras.
- **Justificativa e recomendação:** listar cada regra e registrar o que acontece com ela: fica no `AGENTS.md`, vai para as convenções, ou é apagada com um motivo.

### design-1-9 — As diretrizes do Boost e do RTK podem conflitar com a constituição, e nada resolve o conflito
- **Linhas:** 101–105, 221–222, 307–312
- **Descrição:** o bloco do Boost, que o design considera intocável, traz regras que soam normativas:
  - "follow all existing code conventions… check sibling files", que leva o agente a copiar violações do Apêndice A;
  - "Every change must be programmatically tested", que conflita com a exceção da P-09 para mudanças só de documentação.
- **Justificativa e recomendação:** acrescentar à D-01 uma cláusula dizendo que as diretrizes de ferramentas nos arquivos-índice (Boost, RTK) nunca se sobrepõem à constituição nem às convenções, e um lembrete de uma linha junto ao `@import`.

### design-1-10 — O CUJ 1 e o M6 supõem que agentes que não são o Claude vão carregar a constituição
- **Linhas:** 101–105, 161–170, 251
- **Também apontado por:** style (M-9)
- **Descrição:**
  - O `AGENTS.md` tem só um link simples, então nada garante que Codex e Antigravity leiam a constituição, muito menos "antes de qualquer outro documento".
  - Nem o `@import` faz a constituição carregar antes do resto do `CLAUDE.md`.
  - "Não procura arquivos que não existem" é um resultado que ninguém consegue observar.
  - "Cita o ID da violação (`P-03`)" confunde ID de princípio com ID de violação, e a coluna "ID" da linha 251 não diz de que tipo é.
- **Justificativa e recomendação:**
  - manter no `AGENTS.md` uma ordem explícita ("No início de toda sessão, leia `specs/constitution.md`");
  - reescrever o CUJ 1 com um resultado observável;
  - escrever "ID do princípio violado" e chamar a coluna da linha 251 de "Princípio";
  - registrar esse ponto como risco.

### design-1-11 — Q2 está em aberto, o plano já força MySQL no phpunit, e o M9 é vago
- **Linhas:** 119–121, 141–148, 285, 446–451
- **Também apontado por:** style (M-3, M-14)
- **Descrição:**
  - Os testes criam bancos `tenant<uuid>` de verdade. O `create-testing-database.sh` do Sail dá ao usuário `sail` permissão só no banco `testing`, então `CREATE DATABASE tenant…` provavelmente falha. Com isso, todo clone novo quebraria a suíte, e a P-09 junto com a P-10 bloquearia todo PR.
  - O design não dá alternativa para o caso de a Q2 falhar.
  - "Todos os ambientes atuais" (449) não traz evidência, e a produção nem é citada.
  - "Testes afetados" (M9), na prática, é a suíte inteira.
- **Justificativa e recomendação:**
  - resolver a Q2 antes da aprovação, ou nomear a alternativa (credencial root no `phpunit.xml`, um `GRANT` documentado, ou um prefixo `testing_` para os bancos de tenant);
  - conferir o `.env` de produção;
  - reescrever o M9 como "a suíte completa passa em um clone novo a partir do `.env.example`".

### design-1-12 — O limite de ~150 linhas não fecha nem pode ser verificado, e o argumento do custo em tokens não se sustenta
- **Linhas:** 80–84, 251–252, 443–445
- **Também apontado por:** style (M-2)
- **Descrição:**
  - "No máximo cerca de 150 linhas" mistura um teto rígido com um valor aproximado, então não tem critério de aprovação.
  - 17 princípios × 6 campos, mais títulos, já dão cerca de 136 linhas. Com §1, §4 e os textos longos, o total deve passar de 150.
  - O Apêndice A fica fora da contagem, mas está no arquivo importado e é carregado em toda sessão.
  - "Não aumentar demais os tokens fixos" também é vago.
- **Justificativa e recomendação:**
  - fixar um número rígido e a regra de contagem, ou calibrar o limite com um rascunho de teste;
  - mover o Apêndice A para um arquivo separado, que não é importado e é referenciado por link.

### design-1-13 — O isolamento entre tenants (P-01, P-08) não tem como ser verificado
- **Linhas:** 276, 283
- **Descrição:** a P-01 é verificada com `sharedTenant()`, que usa um tenant só e por isso não prova isolamento. A verificação da P-08 cobre apenas logs. O concepts brief (linha 56) já apontava que nenhum módulo tem teste de "o tenant A não lê dados do tenant B", e o design abandona o ponto sem registrá-lo como não-meta.
- **Justificativa e recomendação:** dar um "Como verificar" para acesso entre tenants (pode ser revisão mais um próximo passo declarado) e registrar a falta desse teste como risco conhecido.

### design-1-14 — A verificação da P-02 não pega nomes de disco lidos do banco
- **Linhas:** 277, 301
- **Descrição:** os services resolvem o disco a partir do registro salvo, com `Storage::disk($document->disk)`, em `ProposalDocumentService:142/159`, `ProposalTimelineService:215` e `ReportZipBuilder:58`. A verificação compara só `config('bucket.disk')`.
- **Justificativa e recomendação:** conferir os valores distintos da coluna `disk` contra `tenancy.filesystem.disks`, ou exigir que o disco seja resolvido somente por `config('bucket.disk')`.

### design-1-15 — A aplicação das regras supõe um fluxo de revisão por PR que talvez não exista
- **Linhas:** 172–178, 246–250, 272–275
- **Descrição:** a única forma de aplicar as regras é "revisão do PR", mas o repositório não tem `.github`, template de PR nem CI, e o histórico mostra merges diretos na `main`.
- **Justificativa e recomendação:** descrever o fluxo real e definir o que conta como "PR", ou registrar a lacuna como risco aceito.

### structural-1-2 — Falta a seção "Riscos e mitigações", exigida pelo padrão
- **Linhas:** escopo inteiro (o mais próximo está em 141–157 e 438–452)
- **Também apontado por:** design (16)
- **Descrição:** o padrão exige que modos de falha, implicações de segurança e mitigações apareçam de forma explícita. Estes riscos ficam sem nome e sem mitigação:
  - `@import` ignorado ou quebrado quando o Boost regenera o arquivo;
  - constituição crescendo além do limite;
  - Q2 falhando;
  - aplicação das regras só por revisão humana, sem CI;
  - Apêndice A desatualizado;
  - agentes que não são o Claude ignorando o link;
  - dados pessoais já gravados em `storage/logs`, sem retenção nem expurgo.
- **Justificativa e recomendação:** criar uma H2 "Riscos e mitigações" antes de "Requisitos não-funcionais", com risco, impacto e mitigação para cada item.

### structural-1-3 — Todas as rejeições são atribuídas ao autor, não a uma troca concreta
- **Linhas:** 339–340, 354–356, 363–365, 372–373, 381–382, 392–393, 402–403, 410–412, 419–420, 427, 433–434
- **Também apontado por:** design (17), style (M-13)
- **Descrição:** todo "Motivo da rejeição" começa com "rejeitada pelo autor". Em A, B, C, G e J, o motivo é só preferência ou adiamento ("preferiu recomeçar do zero", "pelo mesmo motivo da B", "Fica para outra tarefa"). O padrão proíbe basear uma rejeição em uma pessoa sem link para o registro da decisão.
- **Justificativa e recomendação:** trocar o rótulo por "Decisão:" e escrever a troca técnica ou de escopo que decidiu cada caso. Várias já estão nos "Contras", como "354 linhas estoura o M1". Quando a decisão foi mesmo do autor, linkar `specs/concepts_brief.md`, seção "Decisões tomadas".

### structural-1-4 — A tabela de "texto-base" dos princípios não tem campos que o M2 exige
- **Linhas:** 85–87, 255–266, 268–288
- **Descrição:** o M2 exige Regra, Por quê, Escopo, Como verificar e Exceções, e o template ainda acrescenta Detalhe. A tabela apresentada como "texto que vai para a constituição" não tem Por quê, Escopo nem Detalhe.
- **Justificativa e recomendação:** acrescentar essas colunas, ou dizer explicitamente que esses campos serão escritos na implementação e a partir de qual fonte.

### structural-1-5 — O template vai pular um nível de título no §2 da constituição
- **Linhas:** 235–243, 258–259
- **Descrição:** o template de princípio usa `#### P-NN`, um H4. O §2 (D-NN) não tem subseções H3, então os títulos iriam direto de H2 para H4.
- **Justificativa e recomendação:** usar H3 para os D-NN, ou dar ao §2 uma subseção H3.

### structural-1-6 — A quantidade de princípios muda de uma seção para outra
- **Linhas:** 83, 205, 361–362
- **Também apontado por:** style (M-1), design (Nit 2)
- **Descrição:**
  - o M1 diz "entre 8 e 17";
  - a Alternativa C fala em "passar de 14";
  - o design define 17 (13 P + 4 D);
  - o concepts brief dizia 8 a 12, e entre 60 e 100 linhas.
  
  Um intervalo cujo teto é igual à contagem atual não limita nada.
- **Justificativa e recomendação:** usar um número só em todo o documento, dizer se os D-NN entram na conta e explicar por que o limite do brief subiu.

### structural-1-7 — O mapeamento das convenções conflita com a D-03 (fonte única)
- **Linhas:** 317–318 (com 274 e 285)
- **Também apontado por:** style (M-12)
- **Descrição:** só as regras 3, 6, 8 e 9 viram ponteiros. As regras 4 (pastas-base) e 5 (Sail) já estão cobertas pela P-10 e ficariam duplicadas.
- **Justificativa e recomendação:** mapear também as regras 4 e 5 para a P-10, ou justificar por que ficam como texto.

### structural-1-8 — O título do CUJ 3 não bate com o cenário
- **Linhas:** 180–186
- **Também apontado por:** style (M-10)
- **Descrição:** o título fala em divergência entre código e documento descritivo, mas o cenário é uma divergência entre a constituição e o `architecture_guide.md`. "Não altera `docs/` sem pedido explícito" é a D-02, não a D-01. Nenhum CUJ cobre o outro ramo do M3 (o código diverge de um documento descritivo).
- **Justificativa e recomendação:** renomear o CUJ 3 e citar D-01 e D-02, ou acrescentar um CUJ para o caso de código contra documento descritivo.

### style-1-1 — A descrição do problema não menciona o `AGENTS.md`, mas o M6 e a Alternativa I dependem dele
- **Linhas:** 32–64 (com 101–109, 313, 414–420)
- **Descrição:** o problema fala em "três lugares" e só nas referências antigas do `CLAUDE.md`. O M6 também tira `specs/plan.md` e `specs/tasks.md`, que só o `AGENTS.md` cita.
- **Justificativa e recomendação:** incluir o `AGENTS.md` nos itens 2 e 3 do problema.

### style-1-2 — As violações da P-07 aparecem de um jeito no problema e de outro no apêndice
- **Linhas:** 60, 282, 299
- **Descrição:** o problema diz que "várias rotas autenticadas não têm `permission:`". O apêndice registra uma única violação da P-07, e ela é outra: uma rota POST protegida por `view`. As rotas sem middleware viraram exceções.
- **Justificativa e recomendação:** reescrever a linha 60, por exemplo: "rotas autenticadas sem `permission:` (tratadas como exceções da P-07) e uma rota de escrita protegida por `view`".

### style-1-3 — O template proíbe nomes de método, mas a tabela usa vários
- **Linhas:** 255–256 (com 276, 278, 283, 286)
- **Descrição:** a regra diz "nomes de método não", mas a tabela usa `$tenant->run()`, `afterCommit()`, `getMessage()` e `messages()`.
- **Justificativa e recomendação:** abrir uma exceção explícita para esses casos, ou reescrever os princípios em termos de classe, middleware e configuração.

### style-1-4 — O vocabulário DEVE / NÃO DEVE / PODE é definido, mas quase não é usado
- **Linhas:** 233–234, 260 (com 272–288)
- **Descrição:** só a P-02 usa DEVE. As outras regras estão no indicativo ("só acontecem", "tem") e soam como descrição do que existe hoje, não como obrigação. Isso pesa num documento cujo ponto central é separar normativo de descritivo.
- **Justificativa e recomendação:** reescrever cada "Regra (resumo)" com as palavras-chave definidas, ou declarar que a tabela é informal e que o texto final vai usá-las.

### style-1-5 — Termos-chave ficam sem definição para quem lê a constituição
- **Linhas:** 249, 275, 284, 285
- **Descrição:**
  - "Fase" (P-09, §4) era definida no `specs/tasks.md`, que foi apagado.
  - "O autor" (P-10, D-04), dentro da constituição, não deixa claro de quem é a aprovação que vale.
- **Justificativa e recomendação:** definir "fase" em uma frase, ou trocá-la por um gatilho verificável (por exemplo, "antes do merge na `main`"). Trocar "autor" por um papel nomeado.

---

## 🟢 Nits

| ID | Linhas | Descrição e recomendação |
| --- | --- | --- |
| structural-1-9 | 110, 143–157 | "M6a" quebra a numeração de M1 a M9; renumerar. As questões resolvidas (Q1, Q3) estão juntas num parágrafo em itálico; usar uma lista no formato de Q2/Q4. Definir V-NN no primeiro uso (hoje só aparece na linha 347). *Também: style N-6, N-8.* |
| structural-1-10 | 7, 141, 436 | A linha horizontal aparece só em dois pontos e sugere uma quebra de escopo que não existe. "Questões em aberto" ficaria melhor depois dos requisitos não-funcionais. |
| structural-1-11 | 290, 305 | "Apêndice A — conteúdo inicial" é ambíguo; usar "Conteúdo inicial do Apêndice A da constituição". O título da linha 305 tem um parêntese longo; levá-lo para o corpo. *Também: style N-18.* |
| structural-1-12 | 235 | A linha passa da largura de quebra usada no resto do documento. |
| design-1-16 | 11, 80 | O Spec Kit espera `.specify/memory/constitution.md`. Dizer que só o formato do Spec Kit é usado, não a ferramenta, e explicar o que é o Spec Kit. *Também: style N-20.* |
| design-1-17 | 45–46 | "As convenções mandam executar todos os testes" não é o que diz a regra 6 ("o teste precisa ser executado"). Corrigir a premissa. |
| design-1-18 | 246–249 | O §4 repete a P-09 ("suíte completa no fechamento de fase") e fere a D-03 dentro da própria constituição. "Hoje" vai envelhecer; usar "na versão 1.0.0". *Também: style N-18.* |
| design-1-19 | 278, 281, 287 | Verificações fracas. P-03: `grep afterCommit` mostra onde a chamada existe, não onde falta; usar `grep -L` e incluir jobs. P-06: "git log em migrations já aplicadas" não sabe o que foi aplicado em produção. P-12: a regra marcaria como erro `Client`/`Dto`/`Calculator` em `app/Services`, e não diz se `Auth/AuthTenantController` atende ao prefixo. |
| design-1-20 | 96–100, 384–393 | "Linha alterada" (M5) não está definida. Uma linha só movida ou reformatada pelo Pint passaria a ter de cumprir a regra. Definir que conta só mudança semântica, e dizer o que acontece quando se acrescenta código dentro de um bloco que já viola a regra. |
| design-1-21 | 283 | A P-08 só proíbe a mensagem de `QueryException`, mas outras exceções também podem carregar CPF ou CNPJ. "Mensagem só fora de `QueryException`; para ela, classe e SQLSTATE" está compactado demais; escrever por extenso. *Também: style N-14.* |
| style-1-6 | 35–38 | "Anula qualquer regra escrita" exagera o `CLAUDE.md:13`, que também manda apontar a divergência e perguntar. Citar também `AGENTS.md:24` e as convenções, linha 19. |
| style-1-7 | 49–50 | "A pasta `specs/` foi apagada", mas a pasta existe. Escrever "o conteúdo anterior de `specs/` foi apagado". |
| style-1-8 | 56, 93 | Regra "falsa" ou "verdadeira": usar "violada" ou "cumprida". |
| style-1-9 | 75 | "Ajustes mínimos" é subjetivo, e a lista inclui reescrita de seções. Retirar "mínimos". |
| style-1-10 | 26–27 | "Recebem por import" vale só para o Claude Code. Codex e Antigravity leem por link. |
| style-1-11 | 149 | "O revisor sugeriu" cita um revisor que não foi apresentado. Nomear a rodada de revisão ou retirar a atribuição. |
| style-1-12 | 170, 175, 185, 192, 150, 410 | IDs às vezes com crase, às vezes sem. Escolher uma forma só. |
| style-1-13 | 276 | P-01: "services chamados fora do HTTP, commands, migrations e testes" permite duas leituras. Quebrar em lista. |
| style-1-14 | 272 | D-01: "registra-se" não diz onde. Escrever "registra-se no Apêndice A". |
| style-1-15 | 274 | D-03: "cada regra normativa mora em um só lugar" colide com as regras operacionais das convenções; usar "cada princípio". O `grep` pelo ID não detecta texto duplicado. |
| style-1-16 | 282 | As exceções da P-07 incluem login, recuperação de senha e o logo público, que não são rotas autenticadas. Retirar essas exceções ou ajustar o escopo da regra. |
| style-1-17 | 337, 364, 397–398, 424, 431 | Hipérbole e jargão: "muda toda semana", "recursos que o MySQL não tem", "mais leve", "draft" (usar "rascunho"), "gate" (usar "etapa obrigatória de revisão"). |
| style-1-18 | 408–411 | Alternativa I: confunde CI com execução em paralelo. "A P-09 exige o teste afetado" não bate com o texto da P-09 ("teste novo ou atualizado"). |
| style-1-19 | 419–420 | "Manter as duas cópias sincronizadas" contradiz a ideia de fonte única. Escrever "ambos citam os mesmos IDs". |
| style-1-20 | 117, 321 | "As 3" e "as três": usar uma forma só. |
