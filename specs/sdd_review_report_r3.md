# Relatório de revisão Goldfish, rodada 3: SDD por feature

**Status:** os 6 Major corrigidos em 2026-11-12, junto com os Nits de texto (derivados, marcas `[x]`, prefixo `specs/NNN-nome: `, `disable-model-invocation`, quem marca Abandonada, design.md na visão geral, commit da derivação).

**Alvo:** `specs/constituicao-design.md`. Só o conteúdo de "SDD por feature": Visão geral, Glossário, M1, M3, M11, Não-metas, CUJ 7, diagrama, D-01, D-02, D-05, Alternativas M e N, Riscos (linhas 601-604), Passos 2, 3, 4, 6 (§0 e §15, linhas 1001-1051) e 8.
**Rodada:** 3 (direcionada). **Modo:** REVIEW.
**Relatórios anteriores:** `specs/sdd_review_report.md` e `specs/sdd_review_report_r2.md`.
**Decisões fechadas, que não foram reabertas:**
*   a unidade do SDD é a feature;
*   a precedência é constituição > artefatos da feature não concluída (o `design.md` é a fonte; nos derivados, spec > plan > tasks) > convenções > descritivos;
*   só a seção "Afastamentos de convenção" do design, copiada para o `plan.md`, afasta uma convenção, e a aprovação vem do `egm-review` chegar a Aprovada;
*   a transição isenta a ratificação e as mudanças cujo primeiro commit é anterior a ela;
*   cada pasta tem `design.md`, `reviews/` e os derivados;
*   Concluída e Abandonada são histórico.

## Status dos revisores

| Revisor | Persona / padrão | Status | Achados novos brutos |
| --- | --- | --- | --- |
| `egm-design` | `agents/design_critic.md` / `feature-spec-standard.md` | completed | 0 Blocker, 5 Major, 6 Nit |
| `egm-implementation-readiness` | `agents/implementation_readiness.md` / `implementation-readiness-standard.md` | completed | 0 Blocker, 4 Major, 5 Nit |

Os dois revisores rodaram uma vez cada, e nenhum falhou. Desta vez os dois abriram os `SKILL.md` do `egm-review`, do `egm-review-loop`, do `egm-draft` e do `egm-drift-report`. O "estágio 9" e as entradas do `egm-drift-report` foram conferidos.

Juntei os achados duplicados. Quando os revisores deram severidades diferentes, ficou a maior, e a divergência está anotada. Conferi no alvo o texto das linhas 383, 604 e 1001-1051, e conferi no `egm-draft/SKILL.md` (linhas 26-28 e 277-311) e no `egm-drift-report/SKILL.md` (linhas 21-47) as afirmações dos revisores sobre caminhos de saída e entradas.

**Total consolidado de achados novos: 0 Blocker, 6 Major, 7 Nit.**

---

## Resolução dos Major da rodada 2

| Achado (equivalentes) | Resolução | Linhas atuais | Justificativa |
| --- | --- | --- | --- |
| egm-design-2-1 (egm-implementation-readiness-2-3): fonte dos afastamentos e lugar do `design.md` na precedência | Resolvido | 379, 288-291, 1018-1022, 1030, 1039-1041 | O `design.md` DEVE ter a seção "Afastamentos de convenção" no Plano técnico. Ela é revisada no `egm-review` e copiada para o `plan.md`, e a D-01 e a §15 dizem "em divergência, vale o `design.md`". |
| egm-implementation-readiness-2-1 (egm-design-2-2, 1ª metade): editar o `tasks.md` | Resolvido | 1026-1029, 1045-1047 | Marcar `- [x]` é a única edição manual permitida, e a regeneração preserva a marca. Restam os Nits egm-design-3-n1 e egm-design-3-n2. |
| egm-implementation-readiness-2-2 (egm-design-2-2, 2ª metade): mudança no `design.md` depois de Aprovada | Parcialmente | 1035-1037 | A feature volta para Em revisão e passa por um `egm-review-loop`. Mas nada impede commits de código enquanto ela está Em revisão, e um afastamento novo pode ser usado antes de revisado. Ver egm-design-3-2. |
| egm-design-2-4 (egm-implementation-readiness-2-6): "Como verificar" da D-05 | Parcialmente | 383 | A brecha do caso vazio saiu e a verificação de ordem entrou, mas os comandos não isolam os commits certos. Ver egm-implementation-readiness-3-1. Continua sem dizer que, para a D-05, a M5 vale para a integração inteira (Nit). |
| egm-implementation-readiness-2-5 (egm-design-2-5): exceção (a) sem critério | Parcialmente | 383, 776, 1003-1005 | O critério "primeiro commit anterior/posterior ao commit de ratificação" é o mesmo nos três lugares. Mas o comando que acha o commit de ratificação devolve o commit errado depois da primeira emenda. Ver egm-implementation-readiness-3-2. |
| egm-design-2-6 (egm-implementation-readiness-2-8): estado terminal de abandono | Resolvido | 379, 724, 1034-1038 | Abandonada existe e é histórico, e os afastamentos dela perdem efeito. Como a pasta fica, a regra "maior + 1" não reutiliza o número. Resta o Nit egm-design-3-n5. |

**Resumo:** 3 resolvidos e 3 parcialmente resolvidos. Os resíduos dos 3 parciais viraram os Major egm-design-3-2, egm-implementation-readiness-3-1 e egm-implementation-readiness-3-2.

---

## 🟡 Major (novos)

### egm-implementation-readiness-3-1: a verificação de ordem da D-05 não funciona como está escrita
*   **Equivalente:** egm-design-3-m1. Os dois revisores classificaram como Major.
*   **Linhas:** 383, coluna "Como verificar", item (2); 1045-1046.
*   **Texto:** "o commit que pôs a feature em Aprovada (`git log -S "Aprovada" --format=%H -- specs/README.md`) é anterior ao primeiro commit de código que cita a pasta (`git log --reverse --grep "specs/NNN-nome" --format=%H`)".
*   **Descrição:**
    *   O `specs/README.md` é compartilhado por todas as features. O `-S "Aprovada"` devolve todo commit que muda a contagem da palavra no arquivo:
        *   o commit que cria o README, porque a legenda de status (linha 724) contém "Aprovada";
        *   a aprovação de qualquer outra feature;
        *   toda saída de Aprovada para Em implementação ou Em revisão.
        Ele não acha o commit em que uma feature entra em Aprovada enquanto outra sai, porque a contagem não muda. Devolve vários hashes, do mais novo para o mais velho, e "o commit" fica indefinido. Se o revisor pegar o mais antigo, a ratificação, a verificação sempre passa.
    *   O `--grep` acha qualquer commit cuja mensagem cite a pasta, inclusive os de rascunho, revisão, derivação e status no padrão da linha 1046. Esses vêm antes de Aprovada, e a verificação reprova toda feature feita corretamente. "Commit de código" não está definido.
    *   "É anterior" não diz se é por data ou por ancestralidade. Com branches, as duas dão respostas diferentes.
*   **Recomendação:**
    *   Definir "commit de código" como commit que toca arquivos fora de `specs/` e `docs/`, e usar essa definição na verificação (1), na (2) e no item 7 da §15.
    *   Commit de aprovação, ancorado na linha da feature: `A=$(git log --reverse --format=%H -G '^\|.*NNN-nome.*\| Aprovada \|' -- specs/README.md | head -1)`.
    *   Primeiro commit de código: `C=$(git log --reverse --format=%H --grep 'specs/NNN-nome' -- . ':!specs' ':!docs' | head -1)`.
    *   Condição: `git merge-base --is-ancestor "$A" "$C"`.

### egm-implementation-readiness-3-2: o comando que acha o commit de ratificação devolve a última emenda
*   **Equivalente:** egm-design-3-m2. Os dois revisores classificaram como Major.
*   **Linhas:** 1003-1005; dependem dele 383 (exceção (a)) e 776 (Escopo do Passo 3).
*   **Texto:** "(`git log --format=%H -1 -- specs/constitution.md` na versão 1.0.0)".
*   **Descrição:**
    *   `git log -1 -- <arquivo>` devolve o commit mais recente que tocou o arquivo.
    *   A D-04 faz toda emenda tocar o `constitution.md`. Depois da primeira emenda, o comando devolve a emenda.
    *   Toda mudança iniciada entre a 1.0.0 e essa emenda passa a parecer "anterior" e entra indevidamente na exceção (a).
    *   "na versão 1.0.0" não é executável, e a regra fica inverificável a partir da primeira emenda.
*   **Recomendação:**
    *   Usar `git log --diff-filter=A --format=%H -- specs/constitution.md`, que devolve o commit que criou o arquivo.
    *   Definir "anterior/posterior" por ancestralidade (`git merge-base --is-ancestor`).
    *   Usar o mesmo texto nas linhas 383, 776 e 1003-1005.

### egm-design-3-1: a §15 supõe caminhos de saída que o `egm-draft` e o `egm-review` não usam
*   **Equivalente:** egm-design-3-m3 e o Nit egm-implementation-readiness-3-n2. O implementation-readiness tratou só a parte dos relatórios, como Nit. Mantive Major porque, conferido no `egm-draft/SKILL.md`, o comportamento padrão do skill põe o design fora da pasta e com outro nome.
*   **Linhas:** 58-59, 383, 1006-1013, 1032.
*   **Descrição:**
    *   O `egm-draft` escreve em `design_draft.md`, no diretório de trabalho (SKILL.md, linhas 26-28). No fim, grava `<DESIGN_DIR>/<FEATURE>.md`, com `docs/designs/` como padrão (linhas 277-311). O arquivo não se chama `design.md`, e o destino padrão cria uma pasta-base nova em `docs/`, que a regra 4 da nova §1 proíbe.
    *   O `egm-draft` procura o `concepts_brief.md` no diretório de trabalho, e não na pasta da feature.
    *   O `egm-review` grava os relatórios (`zoom_out_report.md`, `critic_report.md`, `implementation_readiness_report.md`, `goldfish_review_report.md`) no diretório de trabalho, com nomes fixos. Uma segunda rodada sobrescreve a primeira, e nada leva esses arquivos para `reviews/`.
    *   O status Rascunho entra no README "durante o `egm-draft`", quando o `design.md` ainda não existe na pasta.
    *   Um agente que siga os padrões dos skills não sabe se renomeia o arquivo, para onde move os relatórios, nem o que faz com o `design_draft.md`.
*   **Recomendação:** dizer nos itens 2 e 3 da §15:
    *   no gate "Where should the design be saved?" do `egm-draft`, informar `specs/NNN-nome/` e o nome de arquivo `design.md`;
    *   o `design_draft.md` é apagado antes do commit;
    *   o `concepts_brief.md` vai para `specs/NNN-nome/` (ou é descartado; isso fecha o Nit egm-implementation-readiness-2-11);
    *   ao fim de cada rodada, os relatórios vão para `specs/NNN-nome/reviews/`, com a rodada no nome (ex.: `critic_report-r2.md`).

### egm-implementation-readiness-3-3: "Aprovada" e o momento da derivação dependem de um modo do `egm-review` que o texto não fixa
*   **Equivalente:** egm-design-3-m4. Os dois revisores classificaram como Major. Supera o Nit egm-implementation-readiness-2-12, agora conferido no SKILL.md.
*   **Linhas:** 1014-1015 ("ao fim do estágio 9"), 1021, 1033-1034 ("`egm-review` concluído sem Blocker ou Major aberto"), 1037, 1041-1042, 291.
*   **Descrição:**
    *   O estágio 9 existe (Implementation Readiness), mas não em todo preset:
        *   o preset 2, "Feature spec only", para depois do estágio 8. A revisão termina, a feature pode ir a Aprovada pelo item 5, e o `plan.md` e o `tasks.md` nunca são derivados. O CUJ 7 fica sem base;
        *   o preset 3, "Implementation plan only", roda só o estágio 9, sobre o Plano de implementação detalhado. A seção "Afastamentos de convenção" está no Plano técnico e chega a Aprovada sem revisão, o que quebra a decisão fechada.
    *   "Aberto" não está definido nos termos do skill. O `egm-review` encerra o limite de re-auditoria registrando "residual findings as accepted trade-offs", e o `egm-review-loop` trata como "settled" o achado contestado ou aceito como risco. O agente não sabe se um Major aceito no limite bloqueia Aprovada ou o afastamento.
*   **Recomendação:**
    *   Nos itens 4 e 5 da §15, exigir um preset com os estágios 5 a 9 (Interactive com todas as rubricas, ou Consolidated report).
    *   Definir "aberto" como "nem corrigido nem contestado". Um Blocker ou Major aceito como trade-off só libera Aprovada com o aceite do usuário registrado em `reviews/`. Nenhum achado sobre os afastamentos pode ser aceito: só corrigido ou contestado.
    *   Trocar "ao fim do estágio 9" por "no commit que marca Aprovada em `specs/README.md`". Isso fecha também o Nit egm-implementation-readiness-3-n3.

### egm-design-3-2: a feature pode receber commits de código enquanto está Em revisão depois de Aprovada
*   **Equivalente:** egm-design-3-m5 e o resíduo apontado pelo implementation-readiness para egm-implementation-readiness-2-2. Os dois revisores trataram como Major.
*   **Linhas:** 1035-1037 contra 383 e 1041-1042.
*   **Texto:** "Qualquer mudança no `design.md` depois de Aprovada volta o status para Em revisão: as seções alteradas passam por um `egm-review-loop` antes de a feature voltar a Aprovada."
*   **Descrição:**
    *   A D-05 só exige Aprovada antes do primeiro commit de código, e a verificação (2) só olha essa primeira aprovação.
    *   O item 6 valida os afastamentos se a feature "chegou a Aprovada", no passado. Um afastamento acrescentado depois da aprovação pode ser usado no código antes da nova revisão.
    *   O `egm-review-loop` exige revisores e padrões, e a §15 não diz quais usar (menor: o skill pergunta ao usuário).
*   **Recomendação:** acrescentar ao item 5:
    *   "Enquanto a feature estiver Em revisão depois de Aprovada, não há commit de código; só valem os afastamentos da última versão Aprovada."
    *   Os revisores do loop: `egm-design` e `egm-structural` com `feature-spec-standard` para seções acima do Plano de implementação; `egm-implementation-readiness` com `implementation-readiness-standard` para o Plano.
    *   Estender a verificação (2) da D-05: nenhum commit de código fica entre um commit que tira a feature de Aprovada e o que a devolve.

### egm-implementation-readiness-3-4: o risco e a conclusão atribuem ao `egm-drift-report` uma conferência de `docs/` que ele só faz se receber `docs/`
*   **Equivalente:** o design-critic confirmou que "confere os documentos contra o código" (383) é verdadeiro e manteve a lacuna como o Nit egm-design-2-10, não reaberto. Mantive Major porque a afirmação da linha 604 sobre o skill não confere com o `egm-drift-report/SKILL.md` e cria uma contradição com o item 8.
*   **Linhas:** 383, 604, 1048-1051.
*   **Descrição:**
    *   O skill recebe um diretório de documentos de design ou READMEs, analisa cada arquivo dele contra o código e acrescenta o resultado a `{YYYY-MM-DD}_DRIFT_REPORT.md` (SKILL.md, linhas 21-47). Sem diretório, ele pergunta ao usuário.
    *   A linha 604 diz que o relatório mitiga "`docs/` e o código divergem sem aviso", mas o item 8 não manda passar `docs/`. Se o agente passar `specs/NNN-nome/`, o skill analisa também `spec.md`, `plan.md`, `tasks.md` e `reviews/` como se fossem designs, e `docs/` fica de fora.
    *   O item 8 manda corrigir divergências "no código ou em `docs/`", mas o relatório lista divergências entre o design e o código.
    *   Ficam abertos: qual diretório passar, onde salvar o relatório, e se uma divergência em que o código está certo é corrigida no `design.md`, o que pelo item 5 devolveria a feature a Em revisão.
*   **Recomendação:**
    *   Rodar o skill em `specs/NNN-nome/`, tratando o `design.md` como o único documento (derivados e `reviews/` ignorados), e de novo em cada `docs/modulos/<módulo>/` que a feature tocou.
    *   Salvar os dois relatórios em `specs/NNN-nome/reviews/`.
    *   Quando o código estiver certo, atualizar `docs/` ou as convenções e registrar a decisão no relatório, sem editar o `design.md` (a pasta vira histórico).
    *   Isso fecha também o Nit egm-design-2-10.

---

## 🟢 Nit (novos)

### egm-design-3-n1: o risco ainda diz "nunca" editar os derivados
*   **Linhas:** 602 contra 1026-1027.
*   **Recomendação:** "não são editados à mão, exceto a marcação `[x]` do `tasks.md` (§15)".

### egm-design-3-n2: a regeneração mantém marcados itens cujo conteúdo mudou
*   **Equivalente:** egm-implementation-readiness-3-n3 (marcas obsoletas).
*   **Linhas:** 1028-1029.
*   **Descrição:** "mantém marcados os itens do mesmo arquivo" preserva o `[x]` de um item reescrito depois de Aprovada.
*   **Recomendação:** preservar o `[x]` só quando o texto do item não mudou.

### egm-design-3-n3: a citação da pasta aparece com e sem barra
*   **Linhas:** 383, item (1), contra 1046.
*   **Descrição:** a verificação pede `specs/NNN-nome/`, e o exemplo é `specs/004-webhook-banco: ...`. Um grep literal com a barra não acha o exemplo.
*   **Recomendação:** padronizar sem a barra, como no `--grep` da verificação (2).

### egm-design-3-n4: o agente não aciona os skills do EGM sozinho
*   **Equivalente:** egm-implementation-readiness-3-n1.
*   **Linhas:** 816, 1014, 1037, 1048.
*   **Descrição:** todos os skills do EGM têm `disable-model-invocation: true`, então no Claude Code só o usuário os inicia. A §15 atribui ao "agente" o `egm-review`, o `egm-review-loop` e o `egm-drift-report`, e a linha 816 lista só brainstorm, draft e review.
*   **Recomendação:** listar os cinco skills na linha 816 e dizer que o agente pede ao usuário para rodar o comando.

### egm-design-3-n5: quem marca Abandonada, e a força de Rascunho e Em revisão
*   **Linhas:** 379, 1034-1035.
*   **Descrição:** não se diz quem marca Abandonada nem quando. A D-01 dá força normativa a toda feature "não concluída", inclusive Rascunho e Em revisão, que ainda não foram revisados.
*   **Recomendação:** Abandonada só por pedido do usuário; limitar a força normativa a Aprovada e Em implementação.

### egm-implementation-readiness-3-n4: a Visão geral e o diagrama omitem o `design.md`
*   **Equivalente:** egm-design-3-n6.
*   **Linhas:** 26-27, 306-308.
*   **Descrição:** os dois listam só `spec.md`, `plan.md` e `tasks.md` e dizem "o plan pode afastar", enquanto o Glossário (57-61) e a D-01 põem o `design.md` como fonte e lugar dos afastamentos. A M3 tem a mesma omissão (Nit egm-design-2-14, não aplicado).
*   **Recomendação:** acrescentar o `design.md` nos dois lugares e trocar "o plan pode afastar" por "a seção Afastamentos do design".

### egm-implementation-readiness-3-n5: "no mesmo commit" do item 4 não diz qual commit
*   **Linhas:** 1015.
*   **Recomendação:** "no commit que marca Aprovada em `specs/README.md`". Fica resolvido junto com egm-implementation-readiness-3-3.

**Resíduo sem id novo:** a D-05 ainda não declara que a M5 vale para a integração inteira (parte de egm-design-2-4). Impacto baixo.

---

## Veredito

**Não limpa.** Há 6 Major novos e nenhum Blocker. Nenhum reabre as decisões fechadas. Todos tratam de como cumprir essas decisões na prática, e quatro vêm da conferência dos `SKILL.md`, que as rodadas anteriores não fizeram:

1.  os comandos da verificação de ordem da D-05 (linha 383);
2.  o comando do commit de ratificação (linhas 1003-1005);
3.  os caminhos de saída do `egm-draft` e do `egm-review` (§15, itens 2 e 3);
4.  o preset do `egm-review` e a definição de "aberto" (§15, itens 4 a 6);
5.  commits de código enquanto a feature está Em revisão depois de Aprovada (§15, item 5);
6.  a entrada, a saída e o alcance do `egm-drift-report` (linhas 604 e 1048-1051).

Os itens 1, 2 e 5 se resolvem com comandos git e uma frase cada. Os itens 3, 4 e 6 pedem uma ou duas frases por item da §15.
