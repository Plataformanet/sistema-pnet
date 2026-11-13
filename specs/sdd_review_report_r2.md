# Relatório de revisão Goldfish, rodada 2: SDD por feature

**Status:** os 6 Major corrigidos em 2026-11-12 (seção "Afastamentos de convenção" no design.md; exceção de marcar checkbox no tasks.md; volta a Em revisão após mudança pós-Aprovada; verificação da D-05 por git log; exceção de transição pelo primeiro commit; status Abandonada). Nits da rodada não aplicados.

**Alvo:** `specs/constituicao-design.md`. Só o conteúdo de "SDD por feature": Visão geral, Glossário, M1, M3, M11, Não-metas, CUJ 7, diagrama, D-01, D-02, D-05, Alternativas M e N, Riscos (linhas 601-604), Passos 2, 3, 4, 6 (§0 e §15) e 8.
**Rodada:** 2 (direcionada). **Modo:** REVIEW.
**Relatório anterior:** `specs/sdd_review_report.md`.
**Decisões fechadas, que não foram reabertas:**
*   a unidade do SDD é a feature;
*   a precedência é constituição > artefatos da feature não concluída (spec > plan > tasks) > convenções > descritivos;
*   só o `plan.md` afasta uma convenção, na seção "Afastamentos", e a aprovação vem do `egm-review` chegar a Aprovada;
*   a regra de transição isenta a ratificação da 1.0.0;
*   cada pasta tem `design.md` (a fonte), `reviews/` e os derivados `spec.md`, `plan.md` e `tasks.md`;
*   a feature Concluída vira histórico.

## Status dos revisores

| Revisor | Persona / padrão | Status | Achados novos brutos |
| --- | --- | --- | --- |
| `egm-design` | `agents/design_critic.md` / `feature-spec-standard.md` | completed | 0 Blocker, 4 Major, 10 Nit |
| `egm-implementation-readiness` | `agents/implementation_readiness.md` / `implementation-readiness-standard.md` | completed | 0 Blocker, 4 Major, 9 Nit |

Os dois revisores rodaram uma vez cada, e nenhum falhou. Nenhum deles abriu os `SKILL.md` do `egm-review` e do `egm-drift-report`, porque esses arquivos não estavam no contexto fornecido. Por isso ficaram sem conferência o "estágio 9" e as entradas do `egm-drift-report`.

Juntei os achados duplicados. Quando os revisores deram severidades diferentes, ficou a maior, e a divergência está anotada. Conferi no alvo o texto das linhas 378, 379, 382, 719-726 e 1000-1038.

**Total consolidado de achados novos: 0 Blocker, 6 Major, 12 Nit.**

---

## Resolução dos achados da rodada 1

| Achado (equivalentes) | Sev. R1 | Resolução | Linhas atuais | Justificativa |
| --- | --- | --- | --- | --- |
| egm-implementation-readiness-1-1 (egm-design-1-11): regra de transição | Blocker | Resolvido | 382 Exceções (a), 775, 1002-1003 | A ratificação da 1.0.0 está isenta. A cláusula nova "mudanças iniciadas antes dela" não tem critério verificável: ver egm-implementation-readiness-2-5. |
| egm-implementation-readiness-1-2 (egm-design-1-5): mapeamento do design para os derivados | Major | Parcialmente | 57-61, 1009-1022, 601 | A tabela seção → arquivo existe, e estão definidos quem deriva, quando deriva e o destino do `design.md` e de `reviews/`. Ficam abertos: a origem da seção "Afastamentos" (egm-design-2-1), o destino do `concepts_brief.md` e as seções do template sem destino (Additional Resources, Future Improvements). |
| egm-design-1-6 (egm-implementation-readiness-1-8): formato e status persistentes | Major | Parcialmente | 723, 1004-1008, 1023-1026 | A lista de status está no bloco literal, e a §15 define quem muda o status e qual status libera o código. Faltam o gatilho de "Em implementação", o estado de abandono (egm-design-2-6) e o formato das células "Pasta" e "Última atualização". |
| egm-implementation-readiness-1-3 (egm-design-1-3): "Como verificar" da D-05 | Major | Parcialmente | 382 | Passou a conferir a citação no commit e o status no README. Não confere a ordem "Aprovada antes do primeiro commit de código". A cláusula do caso vazio abre uma brecha: ver egm-design-2-4. |
| egm-implementation-readiness-1-4 (egm-design-1-4): gatilho e exceções fechados | Major | Parcialmente | 382, 775 vs. 26, 57, 815, 875-876 | As exceções formam uma lista fechada. O gatilho ainda aparece com outras palavras fora da D-05, e não se diz se o parêntese "(rota, tela, …)" é exaustivo. A atualização de dependência (P-10) continua sem classificação. Ver o Nit egm-design-2-7. |
| egm-design-1-1: D-02 contra D-05 | Major | Resolvido | 379 Exceções (b) | O pedido da feature conta como pedido explícito. |
| egm-design-1-2: força de spec e tasks diante das convenções | Major | Resolvido | 378 | "Sem essa citação, vale a convenção." |
| egm-implementation-readiness-1-5 (egm-design-1-7): ciclo de vida | Major | Parcialmente | 378, 1035-1038, 603 | Concluída vira histórico, e os afastamentos ficam limitados ao código da feature. Faltam o estado terminal de abandono (egm-design-2-6), o desempate entre features abertas e as entradas e evidências do drift-report (Nits). Os revisores divergiram: o design-critic marcou "parcialmente" e o implementation-readiness marcou "resolvido com nits". |
| egm-implementation-readiness-1-6 (egm-design-1-8): aprovador do afastamento e contorno por ponteiro | Major | Parcialmente | 378, 1027-1034 | A aprovação via Aprovada está definida, a regra que só aponta para um princípio não pode ser afastada, e o commit cita a pasta. Mas o texto revisado (o `design.md`) não tem lugar para os afastamentos (egm-design-2-1), e um afastamento incluído depois de Aprovada escapa da revisão (egm-implementation-readiness-2-3). |
| egm-implementation-readiness-1-7: numeração NNN | Major | Parcialmente | 1004-1008, 602 | Estão definidos a base, o momento, o idioma e a colisão. Faltam o acento no nome, a reutilização de números e a citação no commit depois de renumerar (Nit). |
| egm-design-1-9 (egm-implementation-readiness-1-9): slash commands no `AGENTS.md` | Major | Parcialmente | 815, 382 | O texto ficou neutro em relação à ferramenta ("pelo seletor de skills"). Continua sem plano B quando o EGM não está instalado em Codex ou Antigravity, embora a D-05 torne o fluxo obrigatório. |
| egm-design-1-10: riscos do SDD | Major | Parcialmente | 601-604 | Entraram 4 riscos. Faltam o afastamento como válvula de escape, os agentes sem EGM e o crescimento do contexto. A mitigação da linha 604 é a própria exceção que causa o risco. |
| egm-implementation-readiness-1-11 (egm-design-1-12): exemplo do CUJ 7 | Nit | Resolvido | 284-292; convenções §11, linha 442 | O exemplo agora contraria de fato a §11 ("testar o Service diretamente, não a rota HTTP"). |
| egm-implementation-readiness-1-10: índices repetem a D-05 | Nit | Resolvido | 815 | Virou ponteiro. |
| egm-implementation-readiness-1-12: verificação da M11 | Nit | Parcialmente | 1154 | Entraram `test -f`, o grep do cabeçalho e o grep da §15. Não confere o link para `constitution.md` nem a linha de status. |
| egm-implementation-readiness-1-13: verificação da M1 | Nit | Parcialmente | 1151, 1155 | Há grep para a D-05 e "Afastamentos". Falta o grep da ordem `spec.md` > `plan.md` > `tasks.md` na D-01. |
| egm-implementation-readiness-1-14: escopo da D-05 no Passo 3 | Nit | Não resolvido | 775 | Ainda diz "a pasta `specs/`", que inclui `constitution.md` e `violacoes-conhecidas.md`. O impacto é baixo, porque a exceção (c) neutraliza o problema. |
| egm-design-1-13: afirmação histórica na Alternativa M | Nit | Resolvido | 572-573 | Agora cita o commit `e5fad17`. |
| egm-design-1-14: teto de linhas da M1 | Nit | Resolvido | 733-735, 1000-1038, 1153 | O procedimento foi para a §15, e o `wc -l` continua conferido. |

**Resumo:** 7 resolvidos, 11 parcialmente, 1 não resolvido. O Blocker e 2 dos 10 Major da rodada 1 foram resolvidos. Dos Major restantes, 8 estão parcialmente resolvidos, e as lacunas que sobraram viram os Major novos abaixo.

---

## 🟡 Major (novos)

### egm-design-2-1: a seção "Afastamentos" não tem fonte revisada, e o `design.md` fica fora da precedência
*   **Equivalente:** egm-implementation-readiness-2-3. Os dois revisores classificaram como Major.
*   **Linhas:** 378, 1009-1022, 1027-1029, 288-290 (CUJ 7), 601.
*   **Descrição:**
    *   O `plan.md` recebe "uma seção 'Afastamentos' (vazia se não houver)" (1016-1017), mas nenhuma seção do `design.md` alimenta essa seção. O template do `egm-draft` também não tem uma.
    *   As linhas 1021-1022 proíbem editar os derivados à mão.
    *   O item 6 (1027-1029) só aceita o afastamento se a feature "chegou a Aprovada sem achado aberto sobre eles". Como a derivação acontece depois da revisão, o `egm-review` nunca vê esse texto.
    *   O autor não sabe onde escrever o afastamento sem quebrar a regra "não editar à mão" ou a regra de aprovação.
    *   A D-01 ordena spec > plan > tasks, mas não põe nessa ordem o `design.md`, que é "a fonte". Se o `design.md` e os derivados divergirem (o risco da linha 601), nada diz qual dos dois vale até a regeneração.
*   **Recomendação:**
    *   Exigir no `design.md` uma subseção "Afastamentos" dentro do Plano técnico, copiada literalmente para o `plan.md` e auditada pelo `egm-review`. Dizer isso nos itens 4 e 6 da §15 e no CUJ 7.
    *   Acrescentar à §15: "se o `design.md` e os derivados divergirem, vale o `design.md`, e os derivados são regerados antes de seguir".

### egm-implementation-readiness-2-1: a §15 se contradiz sobre editar o `tasks.md`
*   **Equivalente:** egm-design-2-2, primeira metade.
*   **Linhas:** 1021-1022 contra 1032-1034.
*   **Descrição:**
    *   A regra é "Os derivados não são editados à mão".
    *   O item 7, porém, manda que cada commit de código "marca os itens do `tasks.md` concluídos", o que é edição manual de um derivado.
    *   Qualquer regeneração depois de uma mudança no `design.md` apaga essas marcas.
    *   O agente tem de escolher qual das duas regras quebra.
*   **Recomendação:** declarar a marcação `[x]` como a única edição manual permitida no `tasks.md` e definir que a regeneração preserva o `[x]` dos itens cujo texto não mudou. A alternativa é registrar o progresso fora do `tasks.md`.

### egm-implementation-readiness-2-2: mudar o `design.md` depois de Aprovada não tem regra, e um afastamento pode escapar da revisão
*   **Equivalente:** egm-design-2-2, segunda metade.
*   **Linhas:** 1021-1029, 288-290.
*   **Descrição:**
    *   O `design.md` pode ser alterado e os derivados regerados a qualquer momento.
    *   Um afastamento, ou uma mudança de escopo, incluído depois de Aprovada continua cumprindo "chegou a Aprovada", e nenhuma regra devolve a feature à revisão.
    *   Isso anula a decisão fechada de que o afastamento é aprovado pelo `egm-review`.
*   **Recomendação:** acrescentar ao item 5 da §15: "qualquer mudança no `design.md` depois de Aprovada volta o status para Em revisão e exige uma nova rodada do `egm-review` antes do próximo commit de código; só contam os afastamentos da versão revisada". Se for o caso, listar as mudanças isentas, como as tipográficas.

### egm-design-2-4: o "Como verificar" da D-05 tem uma brecha e não confere a ordem
*   **Equivalente:** egm-implementation-readiness-2-6. O implementation-readiness classificou como Nit. Mantive Major porque a regra fica inverificável no seu ponto central.
*   **Linhas:** 382.
*   **Descrição:**
    *   "Sem nenhuma feature, `ls -d specs/[0-9][0-9][0-9]-*/` sai vazio, o que é aceito" pode ser lido como aprovação de qualquer integração feita enquanto não existe pasta. Essa é justamente a situação da primeira mudança de comportamento depois da ratificação.
    *   Literalmente, a frase é falsa: sem correspondência, o `ls -d` escreve um erro em stderr e sai com código 2.
    *   O núcleo da regra, "Aprovada antes do primeiro commit de código", não é conferido. Na hora da revisão, um status "Concluída" no README não diz nada sobre a ordem.
    *   Também não se declara que, para a D-05, o bloqueio da M5 vale para a integração inteira.
*   **Recomendação:**
    *   Trocar a cláusula por: "integração sem mudança coberta pela D-05 não cita pasta; caso contrário, a pasta tem de existir".
    *   Usar `find specs -maxdepth 1 -type d -name '[0-9][0-9][0-9]-*'`, que sai com código 0.
    *   Acrescentar a verificação de ordem: no `git log`, o commit que marca Aprovada em `specs/README.md` é ancestral do primeiro commit que cita a pasta.
    *   Declarar que, para a D-05, a M5 vale para a integração inteira.

### egm-implementation-readiness-2-5: a exceção (a) da D-05, "mudanças iniciadas antes dela", não é verificável
*   **Equivalente:** egm-design-2-5. O design-critic classificou como Nit. Mantive Major porque qualquer integração pode alegar a exceção, e o revisor não tem como decidir.
*   **Linhas:** 382 Exceções (a), 775, 1002-1003.
*   **Descrição:**
    *   "Iniciada" não está definido: pode ser o primeiro commit, a criação da branch ou o pedido do usuário.
    *   A linha Escopo do Passo 3 (775) usa outro critério: "integrada na `main` depois da ratificação".
*   **Recomendação:** definir com o git, por exemplo "mudanças cujo primeiro commit é anterior ao commit de ratificação (`git merge-base --is-ancestor`)". A alternativa é isentar só a própria ratificação. Usar o mesmo texto nas linhas 382, 775 e 1002.

### egm-design-2-6: falta um estado terminal para feature abandonada
*   **Equivalente:** egm-implementation-readiness-2-8, parte. O implementation-readiness classificou como Nit. Mantive Major porque a D-01 cria aqui uma contradição em aberto.
*   **Linhas:** 378, 723, 1023-1026.
*   **Descrição:**
    *   Os status vão só de Rascunho a Concluída.
    *   A D-01 torna normativos, acima das convenções, os artefatos de toda "feature não concluída".
    *   Uma feature abandonada, ou um Rascunho nunca terminado, fica normativa para sempre e pode transformar código existente em "violação".
    *   Também não se diz se o número de uma feature abandonada pode ser reutilizado. Com a regra "maior existente + 1", ele pode.
*   **Recomendação:**
    *   Acrescentar o status "Descartada": a pasta vira descritiva, o número nunca é reutilizado, e a §15 define quem marca o status.
    *   Limitar a força normativa da D-01 a Aprovada e Em implementação, ou declarar que Descartada sai da ordem.

---

## 🟢 Nit (novos)

### egm-design-2-7: o gatilho ainda aparece com palavras diferentes
*   **Equivalente:** egm-implementation-readiness-2-4. Resíduo de egm-implementation-readiness-1-4.
*   **Linhas:** 26, 57, 382, 775, 815, 875-876.
*   **Descrição:** cada trecho usa uma formulação:
    *   linha 26: "mudança de evolução do sistema";
    *   linhas 57, 815 e 875: "altera" ou "mudar comportamento";
    *   linhas 382 e 775: "comportamento observável (…)".

    Não se diz se o parêntese é exaustivo. Permissões, e-mails, PDFs, jobs e dependências (P-10) não estão classificados.
*   **Recomendação:** definir "comportamento observável" uma única vez na D-05, como lista fechada, e usar "(D-05)" nos outros pontos.

### egm-design-2-8: a exceção (b) da D-05 entra em tensão com a D-01 e o CUJ 5
*   **Equivalente:** egm-implementation-readiness-2-9.
*   **Linhas:** 382 contra 378 e 269-275.
*   **Descrição:**
    *   "Restaura o comportamento já descrito em `docs/`" trata a divergência com um documento descritivo como bug do código.
    *   A D-01 manda atualizar o documento nesse caso.
    *   Qualquer mudança pode se apresentar como "restaurar `docs/`".
*   **Recomendação:** restringir a exceção a "correção pedida pelo usuário como bug, que restaura comportamento descrito num `spec.md` ou em `docs/modulos/`, com teste que reproduz o bug".

### egm-implementation-readiness-2-10: renumerar a pasta quebra a citação nos commits, e o acento no nome não está definido
*   **Equivalente:** egm-design-2-9.
*   **Linhas:** 1004-1008 contra 1032-1033 e 382.
*   **Descrição:**
    *   "Quem integra por último renumera" a pasta, mas os commits feitos antes citam o `NNN` antigo, e a verificação da D-05 passa a apontar para a pasta errada.
    *   "nome … em pt-BR" não diz se o caminho pode ter acento.
*   **Recomendação:** exigir rebase com reword ao renumerar, ou a citação da pasta final na mensagem de merge ou do PR. Acrescentar "sem acento".

### egm-design-2-10: o `egm-drift-report` não tem entradas, saída nem evidência
*   **Linhas:** 382, 603, 1035-1038.
*   **Descrição:**
    *   Não se diz qual diretório o relatório recebe, onde ele é salvo, nem o que são "os documentos".
    *   A mitigação da linha 603 não deixa nenhuma evidência que o revisor possa conferir.
*   **Recomendação:** salvar o relatório em `specs/NNN-nome/reviews/` e fazer a verificação da D-05 exigir esse arquivo quando o status for Concluída.

### egm-implementation-readiness-2-11: o `concepts_brief.md` não tem destino
*   **Equivalente:** egm-design-2-11.
*   **Linhas:** 382, 1004-1011.
*   **Descrição:** o `egm-brainstorm` roda antes de a pasta existir, porque a pasta só é criada "no início do `egm-draft`". O item 3 da §15 não cita o brief.
*   **Recomendação:** movê-lo para `specs/NNN-nome/` ao criar a pasta, ou declarar que ele é descartado.

### egm-design-2-12: falta desempate entre duas features abertas
*   **Linhas:** 378.
*   **Descrição:** duas features não concluídas podem se contradizer, por exemplo uma Em implementação e outra Aprovada, e a precedência não diz qual vale.
*   **Recomendação:** "a feature de número maior cita a anterior e prevalece", ou exigir que a feature posterior emende o `design.md` da anterior.

### egm-implementation-readiness-2-12: o momento da derivação depende do "estágio 9" e não cobre a revisão reprovada
*   **Equivalente:** egm-design-2-13.
*   **Linhas:** 1012-1019.
*   **Descrição:**
    *   "Ao fim do estágio 9" depende da numeração interna do `egm-review`, que este documento não define e que nenhum revisor conseguiu conferir.
    *   O mapeamento usa nomes de seção em pt-BR, enquanto o template do `egm-draft` tem títulos em inglês.
    *   Não se diz se a derivação acontece quando a revisão termina com um Blocker ou Major aberto.
*   **Recomendação:**
    *   Trocar por "ao marcar Aprovada".
    *   Dar o equivalente em inglês de cada seção (Overview, The Problem, Goals, …).

### egm-implementation-readiness-2-13: o modelo de status está incompleto
*   **Linhas:** 723, 725, 1023-1026.
*   **Descrição:** não estão definidos:
    *   o evento que leva a "Em implementação" (o primeiro commit de código?);
    *   a volta de status;
    *   qual commit grava "Rascunho";
    *   o formato das células "Pasta" (link relativo?) e "Última atualização" (AAAA-MM-DD?).
*   **Recomendação:** definir esses pontos no item 5 da §15 ou no bloco do README.

### egm-implementation-readiness-2-14: a lista de exemplos do item 6 da §15 contradiz o critério "só aponta"
*   **Linhas:** 1030-1031 contra as regras 4, 6 e 8 da §1 das convenções.
*   **Descrição:** essas regras têm conteúdo próprio além do ponteiro, por exemplo "Não crie pastas-base novas em `docs/`" e "Comentários e PHPDoc em pt-BR". A lista as dá como inteiramente inafastáveis.
*   **Recomendação:** escrever "nunca afastam a parte de uma regra que aponta para um princípio", ou corrigir a lista.

### egm-implementation-readiness-2-15: o bloco literal do README usa recuo de 4 espaços, sem cerca
*   **Linhas:** 719-726, 1154.
*   **Descrição:** os outros blocos literais usam cerca de três crases. Se quem implementa mantiver o recuo, o README é renderizado como código, e o `grep -c "^| Feature | Pasta |"` da M11 devolve 0.
*   **Recomendação:**
    *   Usar a cerca.
    *   Acrescentar à M11 o grep de `(constitution.md)` e o de `^Status: Rascunho`. Isso também fecha o resíduo de egm-implementation-readiness-1-12.

### egm-design-2-14: a M3 não acompanha a D-01
*   **Linhas:** 138-141 contra 378.
*   **Descrição:** a M3 apresenta a ordem sem a qualificação "não concluída" e sem a regra do histórico.
*   **Recomendação:** alinhar o texto da M3 com a D-01.

### egm-design-2-15: a comparação "como no Spec Kit" não tem fonte
*   **Linhas:** 582.
*   **Descrição:** a afirmação vai contra a Alternatives Rule do `feature-spec-standard.md`.
*   **Recomendação:** citar a documentação do Spec Kit ou retirar a comparação.

---

## Veredito

**Não limpa.** Há 6 Major novos e nenhum Blocker. Todos estão na D-01 e na D-05 (linhas 378 e 382) e na §15 do Passo 6 (linhas 1000-1038), e cada um se corrige com uma ou duas frases:

1.  a fonte da seção "Afastamentos" e a posição do `design.md` na precedência;
2.  a marcação no `tasks.md` contra a proibição de editar os derivados;
3.  a mudança no `design.md` depois de Aprovada;
4.  a brecha e a falta de verificação de ordem no "Como verificar" da D-05;
5.  a exceção "iniciadas antes" sem critério;
6.  a falta de um estado para feature abandonada.

Nenhum dos 6 reabre as decisões fechadas: todos detalham a forma de cumpri-las.
