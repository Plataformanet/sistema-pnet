# Relatório de revisão Goldfish, rodada 4: SDD por feature

**Status:** encerrado em 2026-11-12 por decisão do autor (churn). Verificação da D-05 e regra de transição simplificadas para revisão e data, sem receitas git (resolve IR-4-1, design-4-2 e IR-4-2); design-4-3 corrigido (relatórios com prefixo de rodada, consolidated, brief e draft). Os 8 Nits ficam como trade-off aceito.

**Alvo:** `specs/constituicao-design.md`. Só o conteúdo de "SDD por feature": Visão geral ("raiz do SDD"), Glossário ("SDD por feature"), M1, M3, M11, Não-metas, CUJ 7, diagrama, D-01, D-02, D-05, Alternativas M e N, Riscos, Passos 2, 3, 4, 6 (§0, item 1, e §15, linhas 1002-1077) e 8.
**Rodada:** 4 (direcionada). **Modo:** REVIEW.
**Relatórios anteriores:** `specs/sdd_review_report.md`, `specs/sdd_review_report_r2.md` e `specs/sdd_review_report_r3.md`.
**Decisões fechadas, que não foram reabertas:**
*   a unidade do SDD é a feature;
*   a precedência é constituição > artefatos da feature não concluída (o `design.md` é a fonte; nos derivados, spec > plan > tasks) > convenções > descritivos;
*   só a seção "Afastamentos de convenção" do design, copiada para o `plan.md`, afasta uma convenção, e a aprovação vem do `egm-review` (estágios 5 a 9) chegar a Aprovada;
*   a transição isenta a ratificação e as mudanças cujo primeiro commit é anterior a ela;
*   cada pasta tem `design.md`, `reviews/` e os derivados;
*   Concluída e Abandonada são histórico;
*   os commits de status usam a mensagem `specs/NNN-nome: <Status>`.

## Status dos revisores

| Revisor | Persona / padrão | Status | Achados novos brutos |
| --- | --- | --- | --- |
| `egm-design` | `agents/design_critic.md` / `feature-spec-standard.md` | completed | 0 Blocker, 3 Major, 4 Nit |
| `egm-implementation-readiness` | `agents/implementation_readiness.md` / `implementation-readiness-standard.md` | completed | 0 Blocker, 3 Major, 7 Nit |

Os dois revisores rodaram uma vez cada, e nenhum falhou. O `egm-design` avisou que não recebeu o `egm-review-loop/SKILL.md` (ele não estava no GoldfishContext), por isso não conferiu as saídas do loop.

Juntei os achados duplicados. Quando os revisores deram severidades diferentes, ficou a maior, e a divergência está anotada. Conferi no alvo o texto das linhas 380-384 e 1002-1077 e o `AGENTS.md:9`. Conferi também a semântica dos comandos git criticados:
*   em `git log`, o limite `-n` é aplicado antes do `--reverse`. Por isso `git log --reverse -1` devolve o commit mais novo, e não o mais antigo;
*   o `-1` combinado com `--grep` ou com `--diff-filter` conta só os commits que passaram pelo filtro.

**Total consolidado de achados novos: 0 Blocker, 4 Major, 8 Nit.**

---

## Resolução dos Major da rodada 3

| Achado (equivalentes) | Resolução | Linhas atuais | Justificativa |
| --- | --- | --- | --- |
| egm-implementation-readiness-3-1 (egm-design-3-m1): verificação de ordem da D-05 | Parcialmente | 384 | `A` agora se ancora na mensagem `specs/NNN-nome: Aprovada`. `B` exclui `specs/` pelo pathspec. A ordem é decidida por ancestralidade (`merge-base --is-ancestor`), e um `A` ou `B` vazio falha. Mas o `-1` pega a aprovação mais nova, e uma feature reaprovada é reprovada. Ver egm-implementation-readiness-4-1. |
| egm-implementation-readiness-3-2 (egm-design-3-m2): comando do commit de ratificação | Parcialmente | 1004-1006, 384, 777 | O `--diff-filter=A` não se move mais com as emendas. Mas o comando pode devolver dois hashes, e "anterior/posterior" continua sem definição em 384 e 777. Ver egm-design-4-2. |
| egm-implementation-readiness-3-3 (egm-design-3-m4): preset do `egm-review` e "aberto" | Resolvido | 1028-1029, 1050-1055 | Aprovada exige os estágios 5 a 9, no modo interativo ou no consolidado, o que bate com os presets 0 e 1 do SKILL.md. "Aberto" está definido. A derivação acontece no commit que marca Aprovada. Resta o Nit egm-implementation-readiness-4-n3 (reaprovação). |
| egm-implementation-readiness-3-4: alcance do `egm-drift-report` | Resolvido | 605, 1072-1077 | O risco e o item 8 agora dizem os dois diretórios, e a contradição sumiu. Restam o local de saída e o ruído dos derivados (Nit egm-design-4-n4). |
| egm-design-3-1 (egm-design-3-m3): caminhos de saída dos skills | Parcialmente | 1012-1027 | O destino `specs/NNN-nome/`, o nome `design.md` e a mudança dos relatórios para `reviews/` entraram. Mas faltam o `consolidated_review_report.md` e o sufixo de rodada, e nada diz o que acontece com o `design_draft.md` e o `concepts_brief.md`. Ver egm-design-4-3. |
| egm-design-3-2 (egm-design-3-m5): código enquanto a feature está Em revisão | Resolvido | 1056-1060, 1063-1066 | O texto agora diz "Enquanto estiver Em revisão, NÃO DEVE haver commit de código da feature". A verificação da D-05 ainda não detecta isso (Nit egm-design-4-n3). |

**Resumo:** 3 resolvidos e 3 parcialmente resolvidos. Os resíduos dos parciais viraram os Major egm-implementation-readiness-4-1, egm-design-4-2 e egm-design-4-3.

---

## 🟡 Major (novos)

### egm-implementation-readiness-4-1: a verificação da D-05 pega a aprovação mais recente e reprova uma feature reaprovada
*   **Equivalente:** egm-design-4-1. Os dois revisores classificaram como Major.
*   **Linhas:** 384 (coluna "Como verificar"), junto com 1056-1058.
*   **Texto:** "`A` = commit de mensagem `specs/NNN-nome: Aprovada` (`git log -1 --format=%H --grep "^specs/NNN-nome: Aprovada"`) … `git merge-base --is-ancestor A B` sai com código 0."
*   **Descrição:**
    *   `git log -1` devolve o commit mais novo que casa com o `--grep`.
    *   O item 5 da §15 cria de propósito o caso de várias aprovações: Aprovada (A1), commits de código (B…), mudança no design, Em revisão, nova Aprovada (A2).
    *   Nesse caso, `A` = A2. Como A2 descende de B, o `--is-ancestor` sai com 1, e uma feature feita corretamente é reprovada.
    *   Trocar só por `--reverse -1` não resolve, porque o git aplica o `-1` antes de inverter a ordem.
*   **Recomendação:**
    *   Usar a primeira aprovação: `A=$(git log --reverse --format=%H --grep "^specs/NNN-nome: Aprovada" | head -1)`.
    *   Escrever `B` da mesma forma (`… | head -1`), em vez de "primeira linha".
    *   Dizer de forma explícita que um `A` ou `B` vazio reprova.

### egm-design-4-2: o comando da ratificação pode devolver dois hashes, e "anterior" continua indefinido
*   **Equivalente:** o Nit egm-implementation-readiness-4-n1 cobre a parte de "anterior/posterior". Ficou Major, a severidade maior, porque a ambiguidade do comando desloca o corte da transição, que é uma decisão fechada.
*   **Linhas:** 1004-1006; também 384 (exceção (a)) e 777 (Escopo do Passo 3).
*   **Texto:** "(`git log --diff-filter=A --format=%H -- specs/constitution.md`)".
*   **Descrição:**
    *   A constituição anterior quase certamente ficava no mesmo caminho. O `AGENTS.md:9` ainda aponta para `specs/constitution.md`, e o commit `e5fad17` removeu a pasta `specs/` antiga. Não rodei o git: isso foi inferido do texto do repositório.
    *   Se for assim, o histórico tem duas adições de `specs/constitution.md`, e o comando imprime as duas, da mais nova para a mais velha. "O commit de ratificação" fica ambíguo.
    *   Se alguém pegar o hash mais antigo, a leitura natural de "o commit que criou", o corte vai para a criação da constituição antiga. Tudo o que veio depois disso passa a cair na D-05 retroativamente, o que contradiz a isenção de transição.
    *   As linhas 384 e 777 dizem "anterior/posterior" sem dizer se é por data ou por ancestralidade. Uma branch criada antes da ratificação, que não é ancestral dela, não é "anterior" nem "posterior" por ancestralidade.
*   **Recomendação:**
    *   Usar `git log --diff-filter=A -1 --format=%H -- specs/constitution.md`. Ele devolve a adição mais nova, que é a ratificação. As emendas são modificações e não mudam o resultado.
    *   Definir "posterior" como `git merge-base --is-ancestor R X`, em que `R` é o commit de ratificação e `X` é o primeiro commit da mudança. Tudo o que não é posterior é isento.
    *   Usar o mesmo texto e o mesmo comando nas linhas 384, 777 e 1004-1006.

### egm-design-4-3: os arquivos de rascunho e de revisão ficam soltos, os registros da revisão se sobrescrevem e a verificação da D-05 pode falhar
*   **Equivalente:** egm-implementation-readiness-4-3 e o Nit egm-implementation-readiness-4-n2 (parte dos relatórios). Os dois revisores classificaram como Major. É o resíduo de egm-design-3-1.
*   **Linhas:** 1012-1027 (item 3) e 1045-1058 (item 5), contra 384 (o `B` e o pathspec `-- . ':!specs'`).
*   **Descrição:**
    *   **O relatório consolidado não é movido.** O item 5 permite o modo consolidado, em que o `egm-review` grava `consolidated_review_report.md` (SKILL.md, linhas 167-170 e 310). Esse arquivo não está na lista de 1019-1022.
    *   **As revisões seguintes sobrescrevem as anteriores.** Os nomes são fixos e não têm sufixo de rodada. Uma revisão nova, depois de uma mudança no design, apaga em `reviews/` a prova de que a feature chegou a Aprovada "sem Blocker ou Major aberto" e de que houve o "aceite do usuário" das linhas 1052-1053. A regra vira inverificável.
    *   **Os arquivos de rascunho não têm destino.** O `egm-draft` grava `design_draft.md` e lê `./concepts_brief.md` no diretório de trabalho (SKILL.md, linhas 26-28 e 146-149). A §15 não diz se eles são movidos, apagados ou mantidos. O commit Rascunho "durante o `egm-draft`" acontece quando `specs/NNN-nome/` ainda está vazia, e o git não versiona pasta vazia. O agente não sabe o que esse commit contém.
    *   **A verificação da D-05 erra.** O `B` trata como código qualquer arquivo commitado fora de `specs/`. Se um commit Rascunho ou Em revisão levar `design_draft.md`, `concepts_brief.md` ou `*_report.md` na raiz, que é uma escolha natural, `B` fica antes de `A` e a feature é reprovada. Além disso, um `concepts_brief.md` velho na raiz é lido pelo próximo `egm-draft`.
*   **Recomendação:** acrescentar ao item 3:
    *   `consolidated_review_report.md` na lista de arquivos movidos;
    *   o número da rodada no nome dos relatórios em `reviews/` (ex.: `critic_report-r2.md`), valendo também para as saídas do `egm-review-loop`;
    *   o aceite do usuário de achados contestados ou aceitos registrado em `reviews/`;
    *   "`concepts_brief.md` vai para `specs/NNN-nome/`; `design_draft.md` é apagado depois de gravar o `design.md`; nenhum arquivo de trabalho do EGM é commitado fora de `specs/NNN-nome/`; os commits de status tocam só `specs/`";
    *   o que o commit Rascunho contém (ex.: o `design.md` parcial já em `specs/NNN-nome/` e a linha no README).

### egm-implementation-readiness-4-2: renumerar a pasta quebra o prefixo dos commits de que a D-05 depende
*   **Equivalente:** nenhum. Só o implementation-readiness levantou. É Major porque deixa o agente com uma escolha material não decidida.
*   **Linhas:** 1009-1011 ("quem integra por último renumera a própria pasta antes do merge"), 604 (Riscos), 384, 1048-1049 e 1069-1070.
*   **Descrição:**
    *   Quando a renumeração acontece, logo antes do merge, a branch já tem os commits Rascunho, Em revisão, Aprovada e todos os commits de código com o prefixo `specs/<NNN antigo>-nome: `.
    *   A verificação da D-05 procura `^specs/<NNN novo>-nome: `. Ela não acha `A` nem `B`, e a integração é reprovada.
    *   O agente precisa escolher sozinho entre reescrever as mensagens com rebase (numa branch talvez já publicada), verificar com o nome antigo ou criar um commit de renumeração. Também não se diz em que commit muda a coluna Pasta do README.
*   **Recomendação:** fixar uma das opções no item 2 e na D-05. Por exemplo: "A verificação da D-05 usa o nome com que a feature foi aprovada; o commit `specs/NNN-nome: renumerada de <NNN antigo>` move a pasta e atualiza a linha do README." A outra opção é reescrever as mensagens com `git rebase -i` antes do merge, dizendo isso de forma explícita.

---

## 🟢 Nit (novos)

### egm-design-4-n1: o item 8 manda editar `docs/` sem cobertura da D-02
*   **Linhas:** 1075-1076, contra 381 (a exceção (b) da D-02 cobre só `specs/NNN-nome/` e a linha do README).
*   **Recomendação:** estender a D-02 (b) aos `docs/modulos/` que a feature tocou, no commit `specs/NNN-nome: Concluída`, ou dizer "com pedido explícito" no item 8.

### egm-implementation-readiness-4-n3: as regras de reaprovação conflitam com as da primeira aprovação
*   **Equivalente:** egm-design-4-n2.
*   **Linhas:** 1028-1029, 1050 e 1057-1058.
*   **Descrição:**
    *   Lida ao pé da letra, a linha 1050 exige os estágios 5 a 9 também na reaprovação, mas a linha 1058 pede só um `egm-review-loop`.
    *   A derivação "ao fim do estágio 9" não acontece no loop, porque ele não tem estágio 9.
    *   Os revisores e os padrões do loop continuam sem definição.
*   **Recomendação:**
    *   Dizer que a linha 1050 vale para a primeira aprovação.
    *   Ancorar a derivação só em "no commit que marca Aprovada".
    *   Nomear os revisores do loop: `egm-design` e `egm-structural` com `feature-spec-standard`, e `egm-implementation-readiness` com `implementation-readiness-standard`.

### egm-design-4-n3: a proibição de código enquanto a feature está Em revisão não entra na verificação da D-05
*   **Linhas:** 1059-1060, contra 384.
*   **Recomendação:** acrescentar à verificação que nenhum commit do conjunto de `B` fica entre um commit `specs/NNN-nome: Em revisão` e o próximo `specs/NNN-nome: Aprovada`.

### egm-design-4-n4: a entrada e a saída do `egm-drift-report` estão incompletas
*   **Equivalente:** parte de egm-implementation-readiness-4-n2.
*   **Linhas:** 605 e 1072-1073.
*   **Descrição:**
    *   Rodado sobre `specs/NNN-nome/`, o skill analisa também os derivados e `reviews/` (SKILL.md, linhas 23-24).
    *   O `{YYYY-MM-DD}_DRIFT_REPORT.md` fica no diretório de trabalho.
*   **Recomendação:** tratar o `design.md` como o único documento da pasta e salvar o relatório em `specs/NNN-nome/reviews/`.

### egm-implementation-readiness-4-n4: a forma de chamar os skills no Antigravity
*   **Linhas:** 817.
*   **Descrição:** o texto diz "no Codex e no Antigravity, pelo seletor de skills". O `egm-draft/SKILL.md` (linhas 159-160 e 319-321) diz que a forma `/egm:egm-...` vale "in Claude Code and agy CLI", e que o seletor de skills é o do Codex. A linha 817 também lista só três skills (resíduo do Nit egm-design-3-n4).
*   **Recomendação:** separar a forma de chamar por ferramenta e listar os cinco skills.

### egm-implementation-readiness-4-n5: em que ref a verificação da D-05 roda
*   **Linhas:** 384.
*   **Descrição:** com squash merge, os commits prefixados não existem na `main`.
*   **Recomendação:** dizer "na branch da feature, antes do merge", ou proibir o squash.

### egm-implementation-readiness-4-n6: quem marca Em implementação, e quando
*   **Linhas:** 1045-1049.
*   **Recomendação:** dizer que o primeiro commit de código, ou um commit de status depois de Aprovada, marca Em implementação.

### egm-implementation-readiness-4-n7: aprovação e código no mesmo commit passam
*   **Linhas:** 384.
*   **Descrição:** se o commit Aprovada também tiver código, então `A` == `B`, e o `--is-ancestor` sai com 0, porque um commit é ancestral de si mesmo.
*   **Recomendação:** opcionalmente, exigir `A` != `B`, ou exigir que os commits de status toquem só `specs/` (veja egm-design-4-3).

**Não reabertos:**
*   a M3 omite o `design.md` (Nit egm-design-2-14, não aplicado);
*   a força normativa de Rascunho e Em revisão (2ª metade do Nit egm-design-3-n5).

Nenhum dos dois cria contradição nova.

**Comandos conferidos e corretos:**
*   no Passo 8: `grep -c "^### D-\|^#### P-"`, `grep -c "^- \*\*Regra:\*\*.*\(DEVE\|PODE\)"`, `grep -c "^| Feature | Pasta | Status | Última atualização |"`, `grep -n "^## 15. SDD por feature"` e `grep -n "Afastamentos\|§15"`;
*   o pathspec `-- . ':!specs'` do `B`, que mantém os commits que tocam `specs/` e código ao mesmo tempo.

---

## Veredito

**Não limpa.** Há 4 Major novos e nenhum Blocker. Nenhum reabre as decisões fechadas. Todos tratam de como cumprir essas decisões na prática:

1.  o `-1` do comando de `A` na D-05 (linha 384): trocar por `--reverse … | head -1`;
2.  o comando da ratificação e a definição de "posterior" (linhas 384, 777 e 1004-1006): acrescentar `-1` e definir por ancestralidade;
3.  o destino dos arquivos de trabalho do EGM e o sufixo de rodada em `reviews/` (§15, item 3);
4.  a renumeração da pasta contra o prefixo dos commits (§15, item 2, e D-05).

Os itens 1 e 2 se resolvem com um comando e uma frase cada. Os itens 3 e 4 pedem duas ou três frases na §15.
