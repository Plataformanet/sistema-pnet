# Relatório de revisão Goldfish: SDD por feature

**Status:** corrigido em 2026-11-12 (decisões do autor: regra de transição; design.md + reviews/ por feature; feature Concluída vira histórico; afastamento aprovado pelo egm-review). Procedimento detalhado vai para a nova §15 das convenções.

**Alvo:** `specs/constituicao-design.md`. Só o conteúdo de "SDD por feature": Visão geral, Glossário, M1, M3, M11, Não-metas, CUJ 7, diagrama, D-01 e D-05, Alternativas M e N, Passos 2, 3, 4, 6 e 8.
**Rodada:** 1 (direcionada). **Modo:** REVIEW.
**Decisão já fechada, que não foi reaberta:** a unidade do SDD é a feature, em `specs/NNN-nome/{spec,plan,tasks}.md`. A precedência é constituição > artefatos da feature (spec > plan > tasks) > convenções > documentos descritivos. Só o `plan.md` pode afastar uma convenção.

## Status dos revisores

| Revisor | Persona / padrão | Status | Achados brutos |
| --- | --- | --- | --- |
| `egm-design` | `agents/design_critic.md` / `feature-spec-standard.md` | completed | 0 Blocker, 9 Major, 4 Nit |
| `egm-implementation-readiness` | `agents/implementation_readiness.md` / `implementation-readiness-standard.md` | completed | 1 Blocker, 8 Major, 5 Nit |

Os dois revisores foram executados uma única vez, sem nova tentativa.

Juntei os achados duplicados. Cada achado consolidado leva o ID principal e lista os IDs equivalentes. Quando os revisores deram severidades diferentes, ficou a maior, e a divergência está anotada.

**Total consolidado: 1 Blocker, 10 Major, 7 Nit.**

Conferi no texto do alvo as citações das linhas 377, 378, 381 e 711-722.

---

## 🔴 Blocker

### egm-implementation-readiness-1-1: o commit que ratifica a D-05 viola a própria D-05, e falta uma regra de transição
*   **Equivalente:** egm-design-1-11. O design-critic classificou como Nit. Mantive Blocker porque a M5 bloqueia a integração.
*   **Linhas:** 381, 152-158 (M5), 609-616 (Passo 7), 637-638, 650-655, 769, 1113-1114.
*   **Descrição:**
    *   A D-05 exige `specs/NNN-nome/` antes de toda "correção que muda comportamento". Este mesmo commit muda comportamento em execução: o default de `DB_CONNECTION` em `config/database.php`, `queue.php` e `tenancy.php`, e o script de grant no `compose.yaml`.
    *   Mesmo assim, o commit não tem pasta NNN e deixa o design e os relatórios soltos na raiz de `specs/`.
    *   Nenhuma exceção da D-05 cobre esse caso.
    *   O texto não diz se a D-05 vale só para mudanças iniciadas depois da ratificação. Também não diz o que fazer com trabalho já em andamento.
    *   A M4 ("cumprido ou registrado") fica sem resposta para a D-05.
*   **Recomendação:**
    *   Acrescentar às Exceções da D-05 (linha 381) e ao Passo 3 uma regra de transição fechada: "a D-05 vale para mudanças iniciadas a partir da ratificação 1.0.0".
    *   Excluir dela explicitamente os arquivos da raiz de `specs/`: `constitution.md`, `violacoes-conhecidas.md`, `README.md` e os artefatos deste design.
    *   Outra saída é mover os artefatos desta mudança para `specs/000-constituicao/`.

---

## 🟡 Major

### egm-implementation-readiness-1-2: falta o mapeamento do design EGM para `spec.md`, `plan.md` e `tasks.md`
*   **Equivalente:** egm-design-1-5.
*   **Linhas:** 26-27, 57-61, 202-203, 381, 715, 809.
*   **Descrição:**
    *   O `egm-draft` gera um único documento: Overview, Problem, Goals/Non-Goals, Open Questions, CUJs, Technical Plan, Alternatives, NFR, Detailed Implementation Plan e Verification. Nenhuma skill do plugin gera os três arquivos.
    *   O design não define:
        *   qual seção vai para qual arquivo;
        *   quem faz a divisão (à mão ou por agente) e em que momento (depois do `egm-review` "Aprovada"?);
        *   se a divisão é cópia literal ou reescrita;
        *   para onde vão o design de origem, o `concepts_brief.md` e os relatórios;
        *   qual texto vence se o design e os três arquivos derivados divergirem.
    *   A ambiguidade pesa porque a precedência spec > plan > tasks depende do que está em cada arquivo.
*   **Recomendação:**
    *   Criar uma tabela de seção → arquivo. Proposta:
        *   em `spec.md`: Overview, Problem, Goals, Non-Goals, CUJs e Open Questions;
        *   em `plan.md`: Technical Plan, Alternatives, NFR e Riscos;
        *   em `tasks.md`: Detailed Implementation Plan e Verification.
    *   Definir quem faz a divisão e o momento dela.
    *   Definir o destino do design de origem e dos relatórios, por exemplo `specs/NNN-nome/design.md` e `specs/NNN-nome/revisao/`, ou o descarte deles.
    *   Pôr tudo isso num lugar persistente (ver egm-design-1-6).

### egm-design-1-6: as regras de formato do SDD não chegam a nenhum documento persistente, e a lista de status fica ambígua
*   **Equivalente:** egm-implementation-readiness-1-8.
*   **Linhas:** 57-61, 381 (Detalhe "§0"), 711-722, 869-872.
*   **Descrição:**
    *   O formato da pasta aparece só no glossário do design: NNN com 3 dígitos e nome em kebab-case.
    *   O Detalhe da D-05 aponta para a §0, mas o Passo 6 só acrescenta ali uma instrução de leitura.
    *   "Status aceitos" (linhas 720-721) fica fora do bloco literal do README, no mesmo nível de "Por quê". Quem implementa não sabe se a lista entra no arquivo.
    *   Também não estão definidos:
        *   as transições entre status e quem muda cada um;
        *   qual status libera a implementação;
        *   o formato de "Última atualização";
        *   o formato da célula "Pasta".
*   **Recomendação:**
    *   Pôr no bloco literal do `specs/README.md`, ou numa seção nova das convenções:
        *   o formato da pasta;
        *   a lista de status, com uma frase por status e as transições;
        *   o status que libera o código (por exemplo, "Aprovada" depois do `egm-review`);
        *   o formato das colunas (data AAAA-MM-DD, link relativo);
        *   o mapeamento de egm-implementation-readiness-1-2.
    *   Apontar o Detalhe da D-05 para esse lugar.

### egm-implementation-readiness-1-3: o "Como verificar" da D-05 não verifica a regra
*   **Equivalente:** egm-design-1-3.
*   **Linhas:** 152-158, 381, 1109.
*   **Descrição:**
    *   O `ls specs/*/spec.md ...` só prova que existem pastas completas. Ele não verifica:
        *   que a integração em revisão está ligada a uma pasta;
        *   o "antes da implementação";
        *   que a pasta saiu de um design revisado.
    *   Hoje não há pastas: o `ls` sai com código diferente de zero, e o design não diz se isso é aprovação ou falha.
    *   A M5 bloqueia por "linhas novas ou alteradas", mas a D-05 trata de uma propriedade da integração inteira.
*   **Recomendação:**
    *   Verificar por integração:
        *   o commit ou o PR cita `specs/NNN-nome/` ou a exceção aplicada;
        *   a pasta tem os três arquivos;
        *   no `specs/README.md`, o status está em "Aprovada" ou depois;
        *   o `git log` mostra os artefatos num commit anterior ao primeiro commit de código, ou no mesmo.
    *   Declarar o resultado esperado quando não há nenhuma pasta.
    *   Declarar que, para a D-05, o bloqueio da M5 vale para a integração inteira.

### egm-implementation-readiness-1-4: o gatilho e as exceções da D-05 não formam uma lista fechada (M2)
*   **Equivalente:** egm-design-1-4. Absorve egm-implementation-readiness-1-14.
*   **Linhas:** 26, 137 (M2), 381, 390 (P-09), 769, 809, 869-871.
*   **Descrição:**
    *   O gatilho aparece com formulações diferentes nos vários pontos do texto:
        *   "mudança de evolução do sistema";
        *   "antes de mudar o comportamento";
        *   "se a tarefa muda comportamento";
        *   "Mudanças de evolução do sistema e a pasta `specs/`".
    *   A exceção "correções de bug sem mudança de comportamento esperado" choca com o próprio gatilho "correção que muda comportamento". "Esperado" não é verificável.
    *   Ficam sem classificação:
        *   refatoração;
        *   atualização de dependência (P-10);
        *   mudança só de testes;
        *   mudança de config com efeito em execução;
        *   migration de dados de referência (P-05);
        *   hotfix;
        *   Pint;
        *   emendas (D-04);
        *   o registro de violações.
    *   Não está claro se o `CLAUDE.md` conta como "só documentação".
*   **Recomendação:**
    *   Definir o termo uma vez, na constituição. Exemplo: "muda rota, tela, regra de negócio, esquema ou permissão".
    *   Usar exatamente esse termo na D-05, no `CLAUDE.md`, no `AGENTS.md`, na §0 e no Passo 3.
    *   Reescrever as exceções como lista fechada com critério objetivo, por exemplo:
        *   (a) correção que restaura o comportamento de uma `spec.md` ou de `docs/modulos/`;
        *   (b) só documentação;
        *   (c) só testes;
        *   (d) refatoração sem mudar teste existente;
        *   (e) emendas e registro de violações.
    *   Classificar config, dependência e hotfix.

### egm-design-1-1: a D-05 exige criar e atualizar arquivos em `specs/`, mas a D-02 proíbe isso sem pedido explícito
*   **Linhas:** 194-196, 378 (D-02), 381, 654-655, 720-721.
*   **Descrição:**
    *   A D-02 só permite criar ou alterar arquivos em `specs/` com pedido explícito. A única exceção dela é `violacoes-conhecidas.md`.
    *   Pela letra da D-02, cada uma destas ações exigiria um pedido à parte:
        *   criar a pasta da feature;
        *   marcar uma task;
        *   mudar o status no README.
    *   O próprio design precisou de uma ressalva ad hoc para este caso (linhas 654-655).
*   **Recomendação:** acrescentar à lista fechada da D-02: "os artefatos de `specs/NNN-nome/` e a linha da feature em `specs/README.md` mudam junto com o trabalho da feature, sem pedido à parte". A alternativa é declarar que o pedido da feature autoriza os artefatos dela.

### egm-design-1-2: a D-01 é ambígua sobre a força de `spec.md` e `tasks.md` diante das convenções
*   **Linhas:** 138-141 (M3), 290-291, 304-306, 377.
*   **Descrição:**
    *   Pela ordem, os três artefatos ficam acima das convenções.
    *   Pela cláusula seguinte, só o `plan.md` afasta uma convenção, e só com citação.
    *   Fica sem resposta um caso como este: um `tasks.md` (ou uma `spec.md`) contraria uma convenção sem que o `plan.md` declare o afastamento. Vale a ordem ou a cláusula?
*   **Recomendação:** escrever que os artefatos da feature só prevalecem sobre as convenções no que o `plan.md` afastar com citação; no resto, eles se subordinam às convenções. Outra opção é separar duas ordens: uma interna (spec > plan > tasks) e uma geral.

### egm-implementation-readiness-1-5: o ciclo de vida dos artefatos não está definido (feature Concluída, conflito entre features, saída do drift-report)
*   **Equivalente:** egm-design-1-7.
*   **Linhas:** 138-145, 304-306, 377, 381, 720-721.
*   **Descrição:**
    *   Os artefatos ficam no bloco normativo. Por isso, quando a feature 007 altera o que a 003 especificou, o código passa a "violar" a 003, e não há regra de supersessão entre features.
    *   Não está dito:
        *   se uma feature "Concluída" continua normativa;
        *   se o afastamento de convenção do CUJ 7 vale para sempre.
    *   O `egm-drift-report` aparece sem parâmetros, sem responsável e sem consequência. A skill pede um diretório de designs. Também não se diz se um drift encontrado se resolve corrigindo o código ou atualizando o artefato.
*   **Recomendação:**
    *   Definir que os artefatos são normativos só entre "Aprovada" e "Em implementação" e viram descritivos quando a feature chega a "Concluída". A alternativa é "a feature de número maior prevalece e cita a anterior".
    *   Limitar o afastamento aos arquivos de `tasks.md` enquanto a feature não estiver Concluída.
    *   Definir que o `egm-drift-report` roda em `specs/NNN-nome/` antes de a feature passar a Concluída, e que o drift se resolve no mesmo commit.

### egm-implementation-readiness-1-6: o afastamento de convenção não tem aprovador e permite contornar a constituição por ponteiro
*   **Equivalente:** egm-design-1-8.
*   **Linhas:** 139-141, 284-291, 336-337, 377, 575-582, 736-737, 885-898.
*   **Descrição:**
    *   Basta uma citação no `plan.md`, e qualquer agente pode redigir esse arquivo.
    *   A §1 da constituição diz que o responsável técnico aprova exceções, mas a D-01 não exige essa aprovação para o afastamento.
    *   As novas regras das convenções que só apontam para um P-NN ("ver P-10", "ver P-04") podem ser "afastadas". Na prática, isso afasta a constituição.
    *   Não há ligação definida entre o commit e o `plan.md`, e sem ela a M5 não consegue decidir se uma linha nova é violação.
*   **Recomendação:**
    *   Acrescentar à D-01:
        *   o afastamento cita a seção e o item, e o motivo;
        *   não se afasta texto das convenções que cita um ID da constituição;
        *   o afastamento é aprovado pelo responsável técnico quando a feature passa a "Aprovada" (ou declarar explicitamente que basta a revisão do `plan.md`);
        *   o commit cita a pasta da feature.

### egm-implementation-readiness-1-7: a numeração `NNN` e o nome da pasta não têm procedimento
*   **Linhas:** 26, 57-58.
*   **Descrição:** "sequencial com 3 dígitos, kebab-case" não diz:
    *   quem atribui o número e em que momento;
    *   qual é a base (a tabela do README ou as pastas existentes);
    *   o que fazer quando duas branches pegam o mesmo número;
    *   se números de features abandonadas podem ser reutilizados;
    *   o idioma do nome e se pode ter acento.
*   **Recomendação:** "NNN = maior número da tabela do `specs/README.md` + 1, reservado ao abrir a linha como Rascunho; números de features abandonadas não são reutilizados; o nome fica em pt-BR, kebab-case, sem acento."

### egm-design-1-9: o texto copiado para o `AGENTS.md` cita slash commands que só existem no Claude Code
*   **Equivalente:** egm-implementation-readiness-1-9.
*   **Linhas:** 33-35, 381, 809, 829-833.
*   **Descrição:**
    *   A linha "SDD por feature" usa `/egm:egm-brainstorm` → `/egm:egm-draft` → `/egm:egm-review`.
    *   O Passo 5 copia o texto para o `AGENTS.md` "com uma única troca".
    *   Codex e Antigravity recebem uma obrigação que não conseguem executar, e a D-05 fica dependente de uma ferramenta externa sem plano B.
*   **Recomendação:**
    *   Na D-05, descrever o fluxo em etapas (concepção revisada → design → revisão independente) e citar o EGM como forma padrão.
    *   No Passo 5, prever uma segunda troca explícita para o `AGENTS.md`: "se o fluxo EGM não estiver disponível nesta ferramenta, pare e peça ao usuário o design aprovado".

### egm-design-1-10: Riscos e mitigações não trazem nenhum risco criado pelo SDD
*   **Linhas:** 584-598.
*   **Descrição:** o feature-spec-standard exige riscos declarados. A tabela não cobre estes riscos:
    *   artefatos normativos que envelhecem;
    *   o afastamento usado como válvula de escape;
    *   agentes que não são Claude e não têm o EGM;
    *   o atrito em mudanças pequenas, que leva a classificar tudo como "correção de bug";
    *   o crescimento do contexto em `specs/`.
*   **Recomendação:** acrescentar de três a cinco linhas de risco, cada uma com a sua mitigação.

---

## 🟢 Nit

### egm-implementation-readiness-1-11: o exemplo do CUJ 7 não exige afastamento
*   **Equivalente:** egm-design-1-12.
*   **Linhas:** 286-291. Na §10 das convenções, linhas 417-419.
*   **Descrição:** a §10 já manda usar `DataTable` em listas pequenas carregadas inteiras. Um relatório de no máximo 50 linhas cumpre a convenção sem afastar nada.
*   **Recomendação:** trocar por um caso que contrarie de fato uma regra imperativa que não cite ID da constituição, por exemplo uma Action fora de `app/Actions/<Modulo>/`. No "Então", acrescentar o que o revisor confere.

### egm-implementation-readiness-1-10: os índices repetem o texto da D-05, contra a D-03
*   **Linhas:** 715, 809, 869-872.
*   **Descrição:** a linha 809 repete a estrutura da pasta e o fluxo EGM, quando a D-03 manda citar o ID.
*   **Recomendação:** reduzir a linha 809 a "SDD por feature: ver D-05 e `specs/README.md`". Se a repetição for intencional, declarar a exceção no Passo 4.

### egm-implementation-readiness-1-12: a verificação da M11 é fraca
*   **Linhas:** 1109.
*   **Descrição:** `grep -c "^| Feature | Pasta |"` não confere o link para a constituição nem a lista de status.
*   **Recomendação:** acrescentar:
    *   `grep -c "^| " specs/README.md` com resultado 2;
    *   `grep -c "(constitution.md)"` com resultado 1;
    *   um `grep` por status, se a lista entrar no arquivo.

### egm-implementation-readiness-1-13: a verificação da M1 não confere o conteúdo da D-05 nem a nova ordem da D-01
*   **Linhas:** 1106-1108.
*   **Descrição:** a contagem de 18 títulos passa mesmo sem a ordem com os artefatos da feature e sem a D-05.
*   **Recomendação:** acrescentar dois `grep`, um pela ordem `spec.md` > `plan.md` > `tasks.md` na D-01 e outro por `^### D-05`.

### egm-implementation-readiness-1-14: o escopo da D-05 no Passo 3 é vago
*   **Linhas:** 769.
*   **Descrição:** "a pasta `specs/`" inclui `constitution.md` e `violacoes-conhecidas.md`. Tratado também em egm-implementation-readiness-1-4.
*   **Recomendação:** "Mudanças que alteram comportamento do sistema; as pastas `specs/NNN-nome/` e o `specs/README.md`."

### egm-design-1-13: a Alternativa M faz uma afirmação histórica sem fonte
*   **Linhas:** 568-571.
*   **Descrição:** "O SDD antigo foi abandonado por isso" não cita nenhuma referência.
*   **Recomendação:** citar o commit `e5fad17` ou o `concepts_brief.md`, ou retirar a frase.

### egm-design-1-14: o teto de linhas da M1 ficou apertado depois da D-05
*   **Linhas:** 127-128, 381, 727-729.
*   **Descrição:** as correções pedidas na D-01, na D-02 e na D-05 podem fazer a constituição passar de 200 linhas.
*   **Recomendação:** pôr o formato e o mapeamento no README ou nas convenções e manter na constituição só a regra, o gatilho e as exceções.

---

## Coerência verificada sem achado

*   A ordem de precedência é a mesma no diagrama (297-320), na M3 (138-145), na D-01 (377) e na Alternativa N (575-582).
*   A D-04 classifica a mudança de precedência como MAJOR, sem conflito.
