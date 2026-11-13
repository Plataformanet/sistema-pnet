# Relatório de revisão Goldfish — Etapa 9 (Teste de prontidão para implementação), rodada 1

**Alvo:** `/home/eduar/projects/sistema-pnet/specs/constituicao-design.md` (linhas 581–825, de "## Plano de implementação detalhado" até o fim)

**Status:** corrigido em 2026-11-12. Os 36 achados foram tratados no plano de `constituicao-design.md` (seção "Plano de implementação detalhado"), sem nova rodada de revisão.
**Modo:** REVIEW

## Status dos revisores

| Revisor | Status |
| --- | --- |
| `egm-implementation-readiness` | completed |
| `egm-codebase-details` | completed |
| `egm-style` | completed |

## Resumo

| Severidade | Quantidade |
| --- | --- |
| 🔴 Blocker | 1 |
| 🟡 Major | 19 |
| 🟢 Nit | 16 |

Veredito: **não está pronto.** O plano nomeia todos os arquivos, e quase todas as âncoras de linha batem com o repositório. Mesmo assim:

- uma afirmação sobre o código está errada (`createTenant()`);
- várias decisões de conteúdo ficaram para quem implementar: seções finais do `AGENTS.md`, padrão de log da §7, tabela §4a, campos Por quê e Escopo, e texto residual das convenções;
- o plano de verificação não testa o que diz testar.

Quando revisores diferentes apontaram o mesmo problema, os achados foram fundidos num só. A coluna "Também apontado por" registra quem mais o apontou. Quando os revisores divergiram na severidade, valeu a maior, salvo justificativa indicada.

---

## Achados priorizados (Blocker → Major → Nit)

### 🔴 Blockers

#### codebase-details-1-1 — Helper errado citado como cobertura do provisionamento
* **Lente:** `egm-codebase-details`
* **Escopo afetado:** linhas 771–772, 793–794
* **Descrição:**
  * A linha 794 diz que os três testes exercitam "o provisionamento (`createTenant()`)". Mas `createTenant()` só é definido em `tests/Pest.php:81`, e nenhum teste o chama (confirmado por grep).
  * O `TenantTest` provisiona via `app(TenantService::class)->store(...)` (linhas 55, 83, 96 e 188). Os outros dois testes usam `sharedTenant()`.
  * A linha 772 ("Os testes de provisionamento criam esses bancos") também subestima a necessidade do grant. Todo teste Feature cria o banco `tenanttest` no bootstrap: `tests/TestCase.php:24-36` chama `TenantRegistry::migrate()`, que roda `DROP DATABASE IF EXISTS tenanttest` e `Tenant::create` (`tests/Support/TenantRegistry.php:38-48`).
* **Justificativa e recomendação:** o documento descreve errado o código atual, e o grant de `tenant%` vale para toda a suíte Feature, não só para o provisionamento.
  * Na linha 794, trocar por `TenantService::store` (TenantTest).
  * Na linha 772, dizer que o bootstrap (`TestCase::refreshTestDatabase` / `TenantRegistry::migrate`) cria `tenanttest` em toda execução Feature e que o `TenantTest` cria bancos `tenant<uuid>`.

### 🟡 Majors

#### implementation-readiness-1-1 — Seções finais do `AGENTS.md` sem decisão, e grep de verificação sem `specs/implementation.md`
* **Lente:** `egm-implementation-readiness`
* **Também apontado por:** `egm-codebase-details`, `egm-style`
* **Escopo afetado:** linhas 664–689 (principalmente 674–675 e 687–688) e 810–811
* **Descrição:**
  * Depois do bloco do Boost, que fecha na linha 488, o `AGENTS.md` tem três seções: "Mapa de skills" (491–509), "Code Review" (511–525) e "Instructions" (527–532). A linha 493 cita `specs/implementation.md`, que foi apagado, e `.agents/skills/`, que não existe mais desde o commit d6202b4.
  * O "Não tocar" do passo 5 cobre só a linha 3 e "o bloco a partir da linha 41". Dá para ler isso como "até o fim do arquivo".
  * A M6 (linhas 155–158) isenta só o Mapa do `CLAUDE.md`.
  * O grep da linha 810 omite `specs/implementation.md`. Com isso, a ressalva "fora do Mapa de skills" não se refere a nada, e a referência morta nunca é detectada.
* **Justificativa e recomendação:** quem implementar terá de decidir sozinho, e qualquer escolha pode violar a M6.
  * Dar decisão explícita (manter ou alterar) para cada uma das três seções do `AGENTS.md` e ajustar a M6 de acordo.
  * Incluir `specs/implementation.md` no grep e listar os acertos esperados por arquivo.

#### implementation-readiness-1-2 — Padrão de log da nova §7 não especificado
* **Lente:** `egm-implementation-readiness`
* **Também apontado por:** `egm-codebase-details`, `egm-style`
* **Escopo afetado:** linhas 730–737
* **Descrição:**
  * O plano não traz o código do novo exemplo. Ficam em aberto:
    * um `catch (QueryException $e)` separado ou um `instanceof`;
    * SQLSTATE via `getCode()` ou via `errorInfo[0]`;
    * formato da mensagem e nível do log;
    * o contexto que a P-08 exige (ação, ID do tenant, ID do registro, sem payload).
  * Nada em `app/` usa `QueryException`, `errorInfo` ou `SQLSTATE`, então não há modelo para copiar.
  * O bullet das convenções na linha 307 (`Log::error('Erro ao <ação> <recurso>: '.$th->getMessage());`) não é citado como alvo. Ele vai contradizer o exemplo novo.
* **Justificativa e recomendação:** os agentes vão copiar esse padrão literalmente. Dar o trecho PHP exato do `catch`, com o array de contexto, e o texto novo do bullet da linha 307.

#### implementation-readiness-1-3 — Texto normativo residual nas convenções repete ou contradiz os princípios
* **Lente:** `egm-implementation-readiness`
* **Escopo afetado:** linhas 691–757
* **Descrição:** o passo 6 reescreve só algumas seções. Ficam de fora regras que conflitam com os princípios:
  * §5.1 (convenções:153) exige `$tenant->run` sempre. A P-01 aceita contexto pré-inicializado declarado em PHPDoc.
  * §8 (convenções:324) diz "toda rota tem `->middleware('permission:...')`", o que contradiz as exceções fechadas da P-07.
  * §6 (convenções:257) ignora Policies.
  * §3 (convenções:70-71) e §9 (convenções:347-348) repetem P-04 e P-05 ao pé da letra, contra a D-03.
  * §0 item 4 (convenções:16) mantém "usá-lo como molde literal". O próprio design (linhas 70–71) aponta esse texto como causa do problema 1.
* **Justificativa e recomendação:** pela D-01 esses textos são normativos e passariam a conflitar com a constituição. Listar no passo 6 uma decisão por linha: "trocar por ponteiro P-xx", "reescrever como …" ou "manter (motivo)".

#### implementation-readiness-1-4 — Campos "Por quê" e "Escopo" ficam para quem implementar
* **Lente:** `egm-implementation-readiness`
* **Escopo afetado:** linhas 625–628 (regra nas linhas 336–339)
* **Descrição:** o passo 3 pede "os 6 campos", mas Por quê e Escopo "são escritos na implementação". Escopo é decisão de design: por exemplo, se a P-11 cobre PDFs gerados por bibliotecas, ou se a P-06 cobre as duas pastas de migrations.
* **Justificativa e recomendação:** a constituição é o próprio entregável. Acrescentar Escopo (e de preferência Por quê) à tabela de princípios, ou indicar a frase de origem de cada ID.

#### implementation-readiness-1-5 — Limite de 200 linhas depende de uma regra de quebra de linha não declarada
* **Lente:** `egm-implementation-readiness`
* **Escopo afetado:** linhas 632–636 e 815
* **Descrição:** com um campo por linha, o arquivo fica em cerca de 180 linhas. Com quebra manual em 80 colunas, como neste design, só a D-01 ocupa uns 12 linhas, e o total passa de 200.
* **Justificativa e recomendação:** declarar "um campo por linha, sem quebra manual" ou dar um orçamento de linhas por seção.

#### implementation-readiness-1-6 — Lista de sufixos da §4a incompleta, sem colunas, e usada pela auditoria antes de existir
* **Lente:** `egm-implementation-readiness`
* **Também apontado por:** `egm-codebase-details`, `egm-style`
* **Escopo afetado:** linhas 592–596 e 721–729
* **Descrição:**
  * O passo 1 audita a P-12 contra a §4a, que só é escrita no passo 6.
  * Faltam sufixos que o projeto já usa:
    * `Bootstrapper` (`app/Tenancy/Bootstrappers/TenantSessionBootstrapper.php:9`);
    * `Provider`;
    * a regra para controllers sem o prefixo `Tenant` (`Auth/AuthTenantController.php:16` e `TenantController`).
  * A "tabela" não tem colunas definidas e mistura padrões, sufixos soltos e pasta ("Helper (`app/Support/`)"). "Factory/Seeder" fica ambíguo, e Test/Factory/Seeder ficam fora de `app/`. "A exceção do scaffold" é vaga.
* **Justificativa e recomendação:** do jeito que está, o registro de `TenantSessionBootstrapper` como violação fica a critério de quem implementar, e a M4 falha.
  * Definir as colunas (Tipo | Sufixo/padrão | Pasta | Exemplo), com uma linha por tipo.
  * Incluir Bootstrapper/Provider e a regra dos controllers centrais e de auth.
  * Dizer que o passo 1 usa a lista do passo 6.

#### implementation-readiness-1-7 — Auditoria da P-04 sem escopo definido
* **Lente:** `egm-implementation-readiness`
* **Escopo afetado:** linhas 592–594
* **Descrição:** a verificação da P-04 vale para "migrations novas", mas numa auditoria nada é novo. Também falta critério para separar dinheiro de taxa (por exemplo, ITBI `decimal(7,4)`).
* **Justificativa e recomendação:** dar o comando (`grep -rnE "decimal|float|double" database/migrations`) e a regra de classificação dos acertos.

#### implementation-readiness-1-8 — Verificação em clone novo (M10): conflito de portas, local dos overrides e ref do worktree
* **Lente:** `egm-implementation-readiness`
* **Também apontado por:** `egm-codebase-details`, `egm-style`
* **Escopo afetado:** linhas 795–808
* **Descrição:**
  * O plano só troca `COMPOSE_PROJECT_NAME`, `APP_PORT` e `FORWARD_DB_PORT`. O `compose.yaml` também publica `VITE_PORT` (5173), `FORWARD_REDIS_PORT` (6379), `FORWARD_MAILPIT_PORT` (1025), `FORWARD_MAILPIT_DASHBOARD_PORT` (8025), `FORWARD_MINIO_PORT` (9000) e `FORWARD_MINIO_CONSOLE_PORT` (8900). Com a stack principal no ar, `sail up -d` falha com "port is already allocated".
  * O plano não diz onde os overrides ficam: no `.env` (que deixa de ser o `.env.example` puro) ou em variáveis de shell.
  * `git worktree add` está sem ref, e mudanças não commitadas não aparecem no worktree.
* **Justificativa e recomendação:**
  * Listar todas as variáveis de porta com valores e o local onde são definidas (ou exigir `vendor/bin/sail stop` antes).
  * Dar o comando exato `git worktree add <caminho> HEAD`, a rodar depois do commit dos passos 1–7.
  * Registrar na M10 que só portas e nome do projeto diferem do `.env.example`.

#### implementation-readiness-1-9 — Verificação não cobre as metas centrais (M6, M7, M8) nem os passos 5 e 6
* **Lente:** `egm-implementation-readiness`
* **Também apontado por:** `egm-style`
* **Escopo afetado:** linhas 809–820
* **Descrição:** nada confere:
  * a remoção de "o código é o estado real" e de "não é a verdade final" (problema 1, o núcleo do design);
  * a instrução de leitura no `AGENTS.md` e a remoção de "Regras do PRD";
  * a nota de substituição da M7;
  * a presença da §4a;
  * o sumiço de `SeedTenantDatabase` sem `Job`.

  A checagem "DEVE/NÃO DEVE/PODE na linha da Regra" (813–814) também não tem comando.
* **Justificativa e recomendação:** acrescentar um grep por meta, com resultado esperado. Por exemplo, `grep -n "estado real\|verdade final" CLAUDE.md AGENTS.md docs/convencoes_de_desenvolvimento.md` deve vir vazio, e `grep -n "Regras do PRD" AGENTS.md` também. Para a linha da Regra, usar um grep com contagem igual a 17.

#### codebase-details-1-2 — Outros fallbacks de `DB_CONNECTION` ignorados
* **Lente:** `egm-codebase-details`
* **Escopo afetado:** linhas 777–780 (aplicação da M9/P-10 no passo 7)
* **Descrição:**
  * `config/queue.php:106` e `:125` usam `env('DB_CONNECTION', 'sqlite')` (batching e failed jobs).
  * `config/tenancy.php:54` usa `env('DB_CONNECTION', 'central')`, e não existe conexão `central` em `config/database.php`.
  * O plano só altera `database.php:20`.
* **Justificativa e recomendação:** sem `DB_CONNECTION` definida, filas e tenancy apontam para sqlite ou para uma conexão inexistente, o que quebra a P-10. Incluir essas linhas como Modificar (fallback `'mysql'`) ou declarar e justificar que estão fora de escopo.

#### codebase-details-1-3 — `config:show database.default` não testa a mudança
* **Lente:** `egm-codebase-details`
* **Escopo afetado:** linhas 787–790
* **Descrição:** com `DB_CONNECTION` definida no `.env` (e, depois do passo 7, também no `.env.example`), o comando mostra `mysql` mesmo que `database.php:20` não tenha sido alterado.
* **Justificativa e recomendação:** acrescentar `grep -n "env('DB_CONNECTION', 'mysql')" config/database.php` (e as linhas de queue/tenancy), ou rodar com a variável indefinida.

#### codebase-details-1-4 — Reduzir as regras da §1 a ponteiros perde cobertura
* **Lente:** `egm-codebase-details`
* **Também apontado por:** `egm-implementation-readiness` (N-1), `egm-style`
* **Escopo afetado:** linhas 712–716
* **Descrição:**
  * A regra 4 (convenções:30) também proíbe pastas-base novas em `docs/`, mas a P-10 só cobre `app/` e `resources/js/`.
  * A regra 8 (convenções:34) exige comentários em pt-BR, o que nem a P-11 nem a D-02 cobrem.
  * O mapeamento em listas paralelas ("regras 3, 4, 5… citando P-10, P-10…") é difícil de ler e omite a D-02 na regra 8.
  * Não fica claro se a numeração da tabela (#, Regra, Motivo) é mantida nem qual é o texto exato de cada linha.
* **Justificativa e recomendação:** do jeito que está, esses requisitos somem. Dar os pares explícitos (por exemplo, "8 → P-11 e D-02"), o texto de cada linha da tabela, e manter os requisitos residuais ou ampliar P-10/P-11.

#### style-1-1 — O "passo 8" não existe, e o passo 1 não produz arquivo
* **Lente:** `egm-style`
* **Também apontado por:** `egm-codebase-details` (como Nit)
* **Escopo afetado:** linhas 583–584, 586–588, 784
* **Descrição:**
  * A linha 584 cita "o passo 8 é a verificação", mas a seção "Plano de verificação" não tem número.
  * A auditoria (passo 1) fica sob "Arquivos a criar ou modificar" e "vai no commit" sem alterar nada.
* **Justificativa e recomendação:** numerar a verificação como passo 8 ou reescrever a frase. Por exemplo: "Os passos 2 a 7 vão num único commit; o passo 1 o precede." Ajustar também o título da seção.

#### style-1-2 — A lista de princípios "automatizáveis" não bate com o "Como verificar"
* **Lente:** `egm-style`
* **Também apontado por:** `egm-codebase-details` (como Nit)
* **Escopo afetado:** linhas 590–594
* **Descrição:**
  * A verificação da P-08 é só revisão, então ela não é automatizável.
  * A P-10 tem checagem mecânica (diff do `composer.json` e `DB_CONNECTION`) e hoje está violada (`.env.example:23` e `database.php:20`), mas ficou de fora.
  * Nem todas as "receitas de `grep`" são grep. A da P-07 usa `route:list | jq`, e a do `bootstrap/app.php` sinaliza a violação com saída vazia.
* **Justificativa e recomendação:** montar a lista a partir do conteúdo real do "Como verificar" e trocar "receitas de `grep`" por "comandos de verificação".

#### style-1-3 — O passo 4 restringe a subordinação das instruções de ferramenta
* **Lente:** `egm-style`
* **Escopo afetado:** linhas 645–647
* **Descrição:** a frase a inserir diz "subordinadas à constituição". A D-01 (linha 343) e o diagrama (linha 284) dizem "à constituição e às convenções". O `AGENTS.md` herda o mesmo texto.
* **Justificativa e recomendação:** usar a redação da D-01 ou só apontar para a D-01, como pede a D-03.

#### style-1-4 — A tabela do passo 5 sugere que a regra de testes não muda, mas ela muda
* **Lente:** `egm-style`
* **Escopo afetado:** linhas 684–686
* **Descrição:**
  * "Testes afetados | passa a valer pelas convenções §11" soa como uma transferência 1:1. Hoje a suíte completa roda "só com pedido explícito". Pela P-09 e pela nova §11, ela passa a ser obrigatória antes de integrar na `main`.
  * A linha "Sufixo → P-12" também perde os exemplos.
* **Justificativa e recomendação:** usar "substituída por" e explicitar a mudança na linha de testes.

#### style-1-5 — Pontos de inserção no cabeçalho e na linha 19 das convenções subespecificados
* **Lente:** `egm-style`
* **Também apontado por:** `egm-implementation-readiness` (N-3)
* **Escopo afetado:** linhas 696–708 (e 711, que reaproveita "a mesma exceção")
* **Descrição:**
  * "Acrescentar 'Este documento detalha…'" não diz em que ponto das linhas 1–7.
  * A exceção da "Regra número um" não diz a qual das três frases se prende.
  * A linha 705 cita só a primeira frase do parágrafo da linha 19. A frase "Arquivos em `docs/` só são criados ou alterados mediante pedido explícito" some sem decisão.
* **Justificativa e recomendação:** dar o texto final completo da "Regra número um" e do novo parágrafo da linha 19, e dizer se a frase sobre `docs/` é mantida ou removida (coberta pela D-02).

#### style-1-6 — O exemplo da regra 10 cita seções de módulo que não existem
* **Lente:** `egm-style`
* **Escopo afetado:** linhas 717–720
* **Descrição:** as convenções não têm seção própria de "financeiro" nem de "drive". Os dois só aparecem como exemplos na §14. Além disso, "vence a seção geral, nunca a constituição" é elíptico.
* **Justificativa e recomendação:** retirar o exemplo ou dizer que hoje nenhum módulo tem seção própria. Reescrever como "…vence a seção geral, mas nunca vence a constituição".

#### style-1-7 — O exemplo "ilustrativo" da §7 contradiz a D-01
* **Lente:** `egm-style`
* **Escopo afetado:** linhas 730–737
* **Descrição:** "o exemplo é ilustrativo e não altera o controller real" faz o exemplo divergir de propósito do `TenantProductController`. Pela D-01, exemplos das convenções são descritivos e o código prevalece, e a §7 apresenta o exemplo como "Padrão real".
* **Justificativa e recomendação:**
  * Marcar o exemplo como "padrão exigido (o controller real está no registro de violações)" ou declará-lo normativo.
  * Dizer de forma direta: "Não alterar `app/Http/Controllers/TenantProductController.php`".

### 🟢 Nits

#### implementation-readiness-1-10 — `SeedTenantDatabase` aparece duas vezes
* **Lente:** `egm-implementation-readiness`
* **Também apontado por:** `egm-codebase-details`
* **Escopo afetado:** linha 743
* **Descrição e recomendação:** o nome aparece nas convenções:353 e :380. Dizer "todas as ocorrências". A classe real é `app/Jobs/SeedTenantDatabaseJob.php`.

#### implementation-readiness-1-11 — Esqueleto de `specs/violacoes-conhecidas.md` impreciso
* **Lente:** `egm-implementation-readiness`
* **Também apontado por:** `egm-style`
* **Escopo afetado:** linhas 602–607
* **Descrição e recomendação:** não fica claro se "Verificado em <data>" é o H1 ou um subtítulo, nem qual data usar (a do commit?). "Sem ser emenda" é elíptico. Dar o esqueleto literal, na ordem do arquivo.

#### implementation-readiness-1-12 — "Citar os blocos deste arquivo" no `AGENTS.md` é ambíguo
* **Lente:** `egm-implementation-readiness`
* **Escopo afetado:** linhas 674–675
* **Descrição e recomendação:** o `AGENTS.md` tem o mesmo bloco do Boost com o RTK dentro, então não fica claro o que muda. Dar a frase literal.

#### implementation-readiness-1-13 — Script de grant: justificativa, privilégios e conteúdo literal
* **Lente:** `egm-implementation-readiness`
* **Também apontado por:** `egm-codebase-details`, `egm-style`
* **Escopo afetado:** linhas 765–772
* **Descrição:**
  * "Fica em `database/` para não criar pasta nova na raiz (P-10)" é impreciso: a P-10 só rege `app/` e `resources/js/`, e `database/mysql/` também é pasta nova.
  * "ALL PRIVILEGES" difere da M9 (linha 175), que fala em "criar e apagar bancos".
  * "Quando a variável está definida" é vago.
* **Recomendação:**
  * Corrigir a justificativa.
  * Alinhar a redação dos privilégios.
  * Escrever "`$MYSQL_USER`".
  * Dar o conteúdo literal do script (heredoc, `'$MYSQL_USER'@'%'`, crases escapadas, como em `vendor/laravel/sail/database/mysql/create-testing-database.sh:7-11`) e a linha exata do volume no `compose.yaml` (`./database/mysql/grant-tenant-databases.sh`).

#### implementation-readiness-1-14 — Exclusão pendente de `docs/prd-sistema-pnet-v2.md`
* **Lente:** `egm-implementation-readiness`
* **Escopo afetado:** linhas 583–584
* **Descrição e recomendação:** o git status mostra essa exclusão fora do stage. Dizer se ela vai no mesmo commit, já que o design trata o arquivo como removido (linha 82).

#### implementation-readiness-1-15 — Fronteira de PHP do passo 7
* **Lente:** `egm-implementation-readiness`
* **Escopo afetado:** linha 786
* **Descrição e recomendação:** o `pint --dirty` só atinge `config/database.php`. Dizer que nenhum outro PHP muda, ou ajustar caso a codebase-details-1-2 seja aceita.

#### codebase-details-1-5 — Motivo errado para manter as conexões sqlite e pgsql
* **Lente:** `egm-codebase-details`
* **Escopo afetado:** linhas 779–780
* **Descrição e recomendação:** `config/tenancy.php:73-75` mapeia nomes de driver para classes `*DatabaseManager`. Ele não referencia as conexões `sqlite` e `pgsql`. Dar o motivo correto (config padrão do Laravel, fora de escopo) ou retirar a justificativa. O revisor classificou como Major, mas a severidade foi rebaixada porque a decisão em si é inofensiva e só a justificativa está errada.

#### codebase-details-1-6 — Imagem `laravelsail/php85-composer` não verificada
* **Lente:** `egm-codebase-details`
* **Escopo afetado:** linha 802
* **Descrição e recomendação:** não há referência local a essa tag. Confirmar que ela existe ou definir a alternativa padrão (copiar o `vendor/`).

#### style-1-8 — Placeholders e rótulos inconsistentes entre os passos
* **Lente:** `egm-style`
* **Escopo afetado:** linhas 600–607, 612–615, 630–631, 640–689, 759–782
* **Descrição e recomendação:**
  * Unificar os placeholders de data: hoje aparecem `<data do commit>`, `<data>` e `AAAA-MM-DD`.
  * Usar o mesmo conjunto de campos em todos os passos (Tipo / Conteúdo ou Mudanças / Não tocar / Por quê).
  * Ordenar os arquivos no título do passo 7 na mesma ordem do corpo.

#### style-1-9 — "Entra no passo 2" e "a tabela" são ambíguos
* **Lente:** `egm-style`
* **Escopo afetado:** linhas 595–596
* **Descrição e recomendação:** nomear a tabela de origem ("Conteúdo inicial…") e o arquivo de destino (`specs/violacoes-conhecidas.md`).

#### style-1-10 — Referências vagas no passo 3
* **Lente:** `egm-style`
* **Escopo afetado:** linhas 625, 627, 629
* **Descrição e recomendação:**
  * Trocar "os 6 campos" por "os seis campos da M2".
  * Recolocar o título `### D-NN — Título`.
  * Explicar "sem repetir a P-09": citar a P-09 sem copiar o texto dela.

#### style-1-11 — Critério "Na dúvida, pare e pergunte" vago
* **Lente:** `egm-style`
* **Escopo afetado:** linhas 656–659
* **Descrição e recomendação:** "a decisão" não tem antecedente. Alinhar com a §14: "…quando uma escolha muda materialmente o resultado…".

#### style-1-12 — Hierarquia da lista e escopo do "Não tocar" no passo 4
* **Lente:** `egm-style`
* **Também apontado por:** `egm-codebase-details`
* **Escopo afetado:** linhas 648–653, 660–661
* **Descrição e recomendação:**
  * Aninhar `docs/prd/` como "histórico" dentro da lista.
  * Decidir o destino de "## Tech Stack" (CLAUDE.md:17-19).
  * Avisar que a seção nova não pode conter a tag literal `<laravel-boost-guidelines>`: o `GuidelineWriter` do Boost substitui o conteúdo a partir da primeira ocorrência da tag (`vendor/laravel/boost/src/Install/GuidelineWriter.php:56`).

#### style-1-13 — Números de linha mudam depois da primeira substituição
* **Lente:** `egm-style`
* **Escopo afetado:** linhas 668, 676–677, 687–688
* **Descrição e recomendação:** "linhas 28 a 39" e "a partir da linha 41" só valem antes de substituir as linhas 5–26. Dizer "numeração anterior à mudança" ou ancorar pelo título.

#### style-1-14 — O "Por quê" do passo 6 exagera
* **Lente:** `egm-style`
* **Escopo afetado:** linhas 756–757
* **Descrição e recomendação:** "fecham o problema 1" é informal, e a §7 não "evita" violar a P-08, porque o controller real continua violando. Reescrever: "A §7 passa a mostrar o padrão de log exigido pela P-08".

#### style-1-15 — Prefixos de comando inconsistentes e checagens vagas
* **Lente:** `egm-style`
* **Também apontado por:** `egm-codebase-details`
* **Escopo afetado:** linhas 795–806, 813–814, 818–821
* **Descrição e recomendação:**
  * Usar `vendor/bin/sail` em vez de `sail` puro (linhas 795, 804 e 806), como mandam o CLAUDE.md e a regra 5 da §1.
  * Escolher uma forma padrão de instalar as dependências.
  * Trocar "cada linha" por "cada entrada" e "encontra a violação" pelo "resultado esperado da receita". "Na data da ratificação" é circular.
  * Dizer quem roda a suíte completa.

---

## Afirmações conferidas como corretas (`egm-codebase-details`)

* Âncoras de linha:
  * `CLAUDE.md` 3 e 5–15, com as seções "Mapa de skills" (391) e "Code Review" (411);
  * `AGENTS.md` 5–26, 28–39 e 41;
  * cabeçalho das convenções nas linhas 1–7 e parágrafo da linha 19;
  * numeração das regras da §1, exemplo da §7, linha 382 da §9 e itens da §13.
* Valores de configuração:
  * `.env.example` 23–28;
  * `config/database.php:20`;
  * `config/tenancy.php:66` (`prefix => 'tenant'`);
  * `compose.yaml:37` e `:43`;
  * o script do Sail concede só `testing%`;
  * o `phpunit.xml` não define `DB_CONNECTION`.
* Policies, base visual da §10, os três testes citados, os comandos pint/config e o commit e5fad17 existem como descritos.
