# Relatório de revisão Goldfish — Etapa 9 (Teste de prontidão para implementação), rodada 2

**Status:** corrigido em 2026-11-12. Os 5 Major, os 9 Nit e os 3 achados parciais da rodada 1 foram tratados no plano. O limite de novas rodadas foi atingido; não houve terceira rodada.

**Alvo:** `/home/eduar/projects/sistema-pnet/specs/constituicao-design.md` (linhas 585–1018, de "## Plano de implementação detalhado" até o fim)
**Modo:** REVIEW (reauditoria)
**Rodada anterior:** [implementation_readiness_report.md](implementation_readiness_report.md)

## Status dos revisores

| Revisor | Status |
| --- | --- |
| `egm-implementation-readiness` | completed |
| `egm-codebase-details` | completed |
| `egm-style` | completed |

## Resumo

| Item | Quantidade |
| --- | --- |
| Achados da rodada 1 resolvidos | 33 |
| Achados da rodada 1 parcialmente resolvidos | 3 |
| Achados da rodada 1 não resolvidos | 0 |
| Novos 🔴 Blocker | 0 |
| Novos 🟡 Major | 5 |
| Novos 🟢 Nit | 9 |

Veredito: **não pronto**. O plano melhorou muito: quase toda a rodada 1 foi resolvida e as âncoras de linha batem com o repositório. Restam cinco Majors, todos de correção pontual:

- uma verificação que nunca passa;
- uma âncora que não existe na §7;
- o escopo da P-12 maior que a auditoria;
- o Pint rodando depois do commit;
- a regra de registro de violações sem desconto de exceções e do que o próprio commit corrige.

Quando revisores diferentes apontaram o mesmo problema, os achados foram fundidos num só e valeu a maior severidade. A coluna "Também apontado por" registra quem mais o apontou. O autor da conferência (sintetizador) confirmou diretamente no repositório os achados codebase-details-2-1 e codebase-details-2-2.

---

## Status dos achados da rodada 1

Os números de linha são os atuais do alvo.

| ID | Status | Linhas atuais | Observação |
| --- | --- | --- | --- |
| codebase-details-1-1 | resolvido | 948–952, 969–971 | Cita `TenantService::store` e o bootstrap `TestCase::refreshTestDatabase` / `TenantRegistry::migrate`. |
| implementation-readiness-1-1 | resolvido | 155–159, 760–765, 992–994 | Decisão "não tocar" para as três seções finais do `AGENTS.md`; grep inclui `specs/implementation.md` com acertos esperados (CLAUDE.md:393, AGENTS.md:493). |
| implementation-readiness-1-2 | resolvido | 860–882 | Código exato do `catch`, contexto e novo bullet da linha 307. A âncora do rótulo está errada (ver codebase-details-2-2). |
| implementation-readiness-1-3 | resolvido | 782–792, 812–816, 852–859, 883–893 | Uma decisão por linha residual. A troca da §3 linha 71 ficou ambígua (ver codebase-details-2-6). |
| implementation-readiness-1-4 | resolvido | 680–698 | Tabela Escopo / Por quê para os 17 IDs. |
| implementation-readiness-1-5 | resolvido | 644–646 | "Um campo por linha, sem quebra manual". |
| implementation-readiness-1-6 | resolvido (para `app/`) | 614–616, 817–851 | Colunas definidas, Bootstrapper, Provider e controllers centrais/auth. O escopo `tests/`/`database/` abriu lacuna nova (codebase-details-2-3). |
| implementation-readiness-1-7 | resolvido | 608–613 | Comando e regra dinheiro × taxa. |
| implementation-readiness-1-8 | resolvido | 972–989 | Todas as portas do `compose.yaml`, overrides no `.env` do worktree, `git worktree add … HEAD` após o commit, cópia do `vendor/`. |
| implementation-readiness-1-9 | parcialmente | 990–1009 | Um comando por meta, mas o grep do problema 1 nunca sai vazio (codebase-details-2-1). |
| codebase-details-1-2 | resolvido | 927–931 | `queue.php:106`, `:125` e `tenancy.php:54` incluídos. |
| codebase-details-1-3 | resolvido | 965–966 | Grep testa as linhas-fonte (retorna 4 linhas). |
| codebase-details-1-4 | resolvido | 797–810 | Texto de cada linha da tabela; regras 4 (`docs/`) e 8 (pt-BR, D-02) preservadas. |
| style-1-1 | resolvido | 587–588, 959 | Passo 8 numerado; passo 1 não produz arquivo. |
| style-1-2 | resolvido | 603–605, 617–618 | Lista bate com "Como verificar"; P-08 separada; "comandos". |
| style-1-3 | resolvido | 716 | "à constituição e às convenções (D-01)". |
| style-1-4 | resolvido | 755–757 | "substituída pela"; suíte completa obrigatória antes da `main`. |
| style-1-5 | resolvido | 772–792 | Ponto de inserção, texto completo e frase de `docs/` mantida via D-02. |
| style-1-6 | resolvido | 808 | Nenhum módulo tem seção própria; frase reescrita. |
| style-1-7 | resolvido | 865–882 | "Padrão exigido" e "Não altere o controller". Âncora errada tratada em codebase-details-2-2. |
| implementation-readiness-1-10 | resolvido | 892–893 | Ambas as ocorrências (convenções:353 e :380). |
| implementation-readiness-1-11 | resolvido | 627–638 | Esqueleto literal. |
| implementation-readiness-1-12 | resolvido | 742–746 | Frase literal com substituição única. |
| implementation-readiness-1-13 | resolvido | 932–955 | Script literal, justificativa corrigida, M9 alinhada, linha exata do volume. |
| implementation-readiness-1-14 | resolvido | 598–599 | Exclusão do PRD no mesmo commit. |
| implementation-readiness-1-15 | resolvido | 596–597 | Fronteira de PHP declarada. |
| codebase-details-1-5 | resolvido | 924–926 | Motivo correto. |
| codebase-details-1-6 | resolvido | 977 | Imagem trocada por `cp -r ../sistema-pnet/vendor .`. |
| style-1-8 | resolvido | 592, 634, 649, 674 | `<DATA>` unificado; rótulos consistentes. |
| style-1-9 | resolvido | 604–605, 620–622 | Tabela de origem e arquivo de destino nomeados. |
| style-1-10 | resolvido | 663–669, 675 | `### D-NN — Título`, "os seis da M2", "a suíte segue a P-09". |
| style-1-11 | resolvido | 727 | Alinhado com a §14. |
| style-1-12 | parcialmente | 722–723, 730–735 | Tech Stack decidido e aviso da tag do Boost presente; `docs/prd/` (histórico) ficou sob "Descritivos (estado atual)" (ver style-2-5). |
| style-1-13 | resolvido | 593–594 | Numeração anterior à mudança, com âncora por título. |
| style-1-14 | resolvido | 907–908 | Frase reescrita. |
| style-1-15 | parcialmente | 963–1014 | `vendor/bin/sail` nos comandos, quem roda a suíte definido; resta `sail down -v` puro na linha 972 (ver style-2-5). |

---

## Novos achados (Blocker → Major → Nit)

### 🔴 Blockers

Nenhum.

### 🟡 Majors

#### codebase-details-2-1 — O grep do "problema 1" nunca sai vazio
* **Lente:** `egm-codebase-details`
* **Também apontado por:** `egm-implementation-readiness`, `egm-style`
* **Escopo afetado:** linhas 1000–1001
* **Descrição:** `grep -n "estado real\|verdade final" CLAUDE.md AGENTS.md docs/convencoes_de_desenvolvimento.md` deve "sair vazio", mas depois de uma implementação correta ainda retorna três linhas que o plano manda manter:
  * `CLAUDE.md:417` ("estado real da aplicação", seção "Code Review", "não tocar" na linha 735);
  * `AGENTS.md:517` (mesma frase em "Code Review", "não tocar" nas linhas 760–762);
  * `docs/convencoes_de_desenvolvimento.md:17` (§0 item 5, "Confirmar o estado real da aplicação…", que o passo 6 não altera).
* **Recomendação:** buscar as frases removidas, por exemplo `grep -n "o código é o estado real\|não é a verdade final\|o código é\.\*\*"`, com resultado esperado vazio. Outra opção: listar as três linhas acima como acertos esperados.

#### codebase-details-2-2 — Âncoras da §7 que não existem
* **Lente:** `egm-codebase-details`
* **Escopo afetado:** linhas 865–866 e 878
* **Descrição:**
  * O plano manda trocar o rótulo "Padrão real" da §7 por "Padrão exigido". A §7 não tem rótulo: a linha 269 das convenções é só o link para o controller. O único "Padrão real" está na §4 (linha 108, `Product.php`), que não deve ser tocada. Aplicado literalmente, o implementador pode alterar a §4.
  * O plano manda "acrescentar ao exemplo o import `use Illuminate\Database\QueryException;`", mas o bloco de código da §7 (linhas 271–300) não tem nenhum `use`, então não há ponto de inserção definido.
* **Recomendação:** escrever "inserir, antes do link da linha 269, a linha `Padrão exigido:`" e "acrescentar `use Illuminate\Database\QueryException;` como primeira linha do bloco de código, seguida de linha em branco".

#### codebase-details-2-3 — Escopo da P-12 inclui `tests/` e `database/`, mas a auditoria e a §4a só cobrem `app/`
* **Lente:** `egm-codebase-details`
* **Também apontado por:** `egm-implementation-readiness`, `egm-style` (como Nit)
* **Escopo afetado:** linhas 614–616, 697 e 819–851
* **Descrição:**
  * O Escopo da P-12 (linha 697) cobre classes do projeto em `app/`, `database/` e `tests/`. O passo 1 e o "Como verificar" da P-12 (linha 362) só auditam `app/`.
  * `tests/Support/TenantRegistry.php:21` (`class TenantRegistry`) é classe do projeto, sem sufixo, e nenhuma linha da §4a a cobre.
  * `tests/TestCase.php:11` é scaffold do framework, mas falta na lista de exceções (linhas 847–851).
  * Factories e seeders estão conformes.
* **Justificativa:** pela M4 ("nenhuma violação conhecida fica sem registro"), o implementador teria de decidir sozinho se `TenantRegistry` é violação, regra nova ou fora de escopo.
* **Recomendação:** escolher uma das opções e escrevê-la no plano:
  * estender a auditoria do passo 1 a `database/` e `tests/`, acrescentar `TestCase` às exceções e decidir `TenantRegistry` (linha nova na §4a, como "Suporte de teste | `tests/Support/`", ou entrada em `specs/violacoes-conhecidas.md`);
  * ou estreitar o Escopo da P-12.

#### codebase-details-2-4 — O Pint roda depois do commit e não verifica nada
* **Lente:** `egm-codebase-details`
* **Também apontado por:** `egm-implementation-readiness` (como Nit)
* **Escopo afetado:** linhas 588 e 963–964
* **Descrição:** o passo 8 roda depois do commit (linha 588). `pint --dirty` só olha arquivos modificados ou não rastreados. Depois do commit, os três arquivos de config estão limpos, e o comando passa sem testar nada. O plano também não diz o que fazer se o Pint alterar algo (amend ou commit novo).
* **Recomendação:** mover `vendor/bin/sail bin pint --dirty --format agent` para o fim do passo 7, antes do commit, como exige a regra 7 da §1 das convenções. Outra opção: no passo 8, usar `vendor/bin/sail bin pint --test config/database.php config/queue.php config/tenancy.php` com resultado esperado "sem alterações".

#### style-2-1 — Regra de registro trata todo acerto como violação
* **Lente:** `egm-style`
* **Também apontado por:** `egm-implementation-readiness` (como Nit)
* **Escopo afetado:** linhas 603–605 e 620–622
* **Descrição:** "Todo acerto que não estiver na tabela … entra nesse arquivo no passo 2" não desconta dois casos:
  * P-07: o comando com `jq` (linha 357) lista por definição as exceções fechadas (dashboard, profile). Não há regra para elas nas linhas 608–618.
  * P-10: hoje o comando acha `.env.example:23` (`DB_CONNECTION=sqlite`) e `config/database.php:20`. O passo 7 corrige os dois no mesmo commit. Seguindo a regra ao pé da letra, entram no registro entradas que não deveriam existir no momento do commit.
* **Recomendação:** reescrever como "Todo acerto classificado como violação, descontadas as Exceções do princípio e o que os passos 2–7 corrigem, entra…", e acrescentar uma linha de classificação para a P-07 e para a P-10.

### 🟢 Nits

#### implementation-readiness-2-1 — Acertos de dinheiro da P-04 deixados para o usuário
* **Escopo afetado:** linhas 608–613
* **Descrição e recomendação:** o grep retorna `reported_income` e `family_income` `decimal(10,2)` em `database/migrations/tenant/2026_05_19_171549_create_proponents_table.php:19,22` e no `down()` de `2026_10_01_200001_drop_proponents_table.php:27,30`. `income` não está na lista de palavras-chave, então a regra manda perguntar ao usuário. A tabela foi removida depois. Pré-classificar no plano: "proponents removida em 2026_10_01_200001; sem violação viva, sem entrada".

#### implementation-readiness-2-2 — Destino dos outros arquivos não rastreados de `specs/`
* **Escopo afetado:** linhas 590–599
* **Descrição e recomendação:** `specs/` inteiro está fora do controle de versão, incluindo este design, `concepts_brief.md` e os relatórios de revisão. O plano lista os arquivos dos passos 2–7 e a exclusão do PRD, mas não diz se os demais arquivos de `specs/` entram no commit. Declarar isso nas convenções do plano.

#### codebase-details-2-5 — Falta esperar o MySQL num volume novo
* **Escopo afetado:** linhas 983–985
* **Descrição e recomendação:** num volume novo o MySQL roda os scripts de init e reinicia antes de aceitar conexões. `sail up -d` não espera o healthcheck (`compose.yaml:46-53`), e o passo seguinte pode falhar por conexão recusada, não por grant. Usar `vendor/bin/sail up -d --wait` ou "aguardar o serviço `mysql` ficar healthy".

#### codebase-details-2-6 — Troca da linha 71 da §3 ambígua
* **Também apontado por:** `egm-implementation-readiness`, `egm-style`
* **Escopo afetado:** linhas 812–816
* **Descrição e recomendação:** o plano troca "a primeira frase", mas o texto novo termina em "…todas essas migrations:". Aplicado literalmente, sobram "Assim chegam a qualquer ambiente…" e "Regras dessas migrations de dados:" depois dos dois-pontos, e a frase restante repete o motivo da P-05 (D-03). Escrever "substituir a linha 71 inteira até 'Regras dessas migrations de dados:'".

#### codebase-details-2-7 — Ponto de inserção da §11 contraditório
* **Também apontado por:** `egm-implementation-readiness`, `egm-style`
* **Escopo afetado:** linha 899
* **Descrição e recomendação:** "inserir no fim, antes das 'Regras'" aponta para dois lugares: "Regras:" está em convenções:468, e "Execução" (475–480) é o fim real. Escolher uma âncora, por exemplo "depois da tabela de helpers (linha 466), antes de 'Regras:'".

#### codebase-details-2-8 — Descrição imprecisa do `GuidelineWriter`
* **Escopo afetado:** linhas 731–732
* **Descrição e recomendação:** o plano diz que o `GuidelineWriter` "substitui tudo a partir da primeira ocorrência" da tag. Na verdade, `vendor/laravel/boost/src/Install/GuidelineWriter.php:56,64` substitui o primeiro trecho (não guloso) da tag de abertura até a de fechamento. A conclusão (não pôr a tag literal no texto novo) continua certa. Ajustar a descrição.

#### style-2-2 — Texto anterior ao plano contradiz a tabela Escopo / Por quê
* **Escopo afetado:** linhas 340–343 (fora do trecho auditado) × 677–698
* **Descrição e recomendação:** as linhas 340–343 ainda dizem que "Por quê" e "Escopo" "são escritos na implementação", mas as linhas 677–698 agora os fornecem. Atualizar a frase antiga.

#### style-2-3 — Quem marca a Q2 como "confirmada"
* **Escopo afetado:** linha 989
* **Descrição e recomendação:** "Só depois deste item a Q2 passa a 'confirmada'" não diz se a linha 577 deste design é editada nem por quem, e pela D-02 `specs/` só muda a pedido. Declarar a ação ou reescrever como condição.

#### style-2-4 — Formatação e resíduos
* **Escopo afetado:** linhas 722–723, 868, 914, 938, 972
* **Descrição e recomendação:**
  * As cercas de código nas linhas 868, 914 e 938 começam na coluna 0 dentro de listas aninhadas. O texto recuado que vem depois (878–882 e 948–952) é renderizado como bloco de código. Recuar as cercas até o nível da lista.
  * Linha 723: `docs/prd/` (histórico) ficou sob "Descritivos (estado atual)". Subir "Histórico de requisitos" ao nível de "Descritivos". A linha 722 termina em "." e as irmãs em ";".
  * Linha 972: trocar `sail down -v` por `vendor/bin/sail down -v` (resíduo da style-1-15).

---

## Afirmações conferidas como corretas

* **`CLAUDE.md`:** linhas 3, 5–15, Tech Stack 17–19, bloco do Boost até 389, Mapa 391/393, Code Review 411.
* **`AGENTS.md`:** linhas 3, 5–26, 28–39, 41, 488, 491–509, 511–525, 527–532. `specs/implementation.md` e `.agents/` não existem.
* **Convenções:** linhas 1–7, 16, 17, 19, 23–36, 70–71, 153, 257, 269–300, 307, 324, 347–348, 353, 380, 382, estrutura da §11 e da §13. Todos os arquivos da base visual da §10 existem.
* **§4a:** todas as classes de exemplo e a lista de exceções existem. `Utils` é a única classe sem sufixo em `app/` fora das exceções e já está no registro.
* **Config e ambiente:**
  * `.env.example:23-28`;
  * `config/database.php:20` (sem conexão `central`);
  * `queue.php:106`, `:125`;
  * `tenancy.php:54` e `:66` (prefixo `tenant`);
  * todas as variáveis de porta do `compose.yaml`;
  * `compose.yaml:34`, `:37` e `:43`;
  * o script do Sail (`create-testing-database.sh:7-11`);
  * o `phpunit.xml` não define `DB_CONNECTION`.
* **Testes:** `tests/TestCase.php:24-36`, `tests/Support/TenantRegistry.php:36-48`, `TenantTest` (`TenantService::store`), `tests/Pest.php:31-36` e o pipeline de tenancy (`TenancyServiceProvider.php:30-50`).
* **Comandos e PHP:**
  * `QueryException::getCode()` devolve o SQLSTATE;
  * `GuidelineWriter.php:56`;
  * `vendor/laravel/sail/runtimes/8.5/Dockerfile` existe;
  * os demais greps do passo 8 produzem os resultados declarados, incluindo a contagem de 17 linhas `- **Regra:**`;
  * o commit e5fad17 existe.

---

## Veredito

**Não pronto.** Não há Blockers. Os cinco Majors (codebase-details-2-1 a 2-4 e style-2-1) são correções curtas e localizadas. Depois deles, o plano pode ser executado sem decisões materiais.
