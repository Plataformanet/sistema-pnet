# Revisão Zoom Out intermediária (estágio 8): `specs/constituicao-design.md`

* **Data:** 2026-11-11
* **Revisor:** egm-zoom-out, em contexto isolado
* **Base de comparação:** `specs/zoom_out_report.md` (estágio 5)

**Veredito: aprovar com mudanças.** Não há Blocker. Há 2 achados Major e 6 Nit.

O objetivo continua claro, e a D-01 ataca a causa do problema. As revisões deixaram o design mais simples:

* a precedência caiu para 3 níveis;
* o registro de violações saiu do documento versionado;
* as listas da P-12 e da P-13 foram para as convenções.

Este ainda é o design mais simples para o objetivo.

## Situação dos achados do estágio 5

| # anterior | Situação |
| --- | --- |
| 1. Dívida em dois lugares e apêndice dentro do import | **Resolvido.** `specs/violacoes-conhecidas.md` fica fora do import, e a §13 aponta para ele. |
| 2. Mudança no apêndice vira emenda | **Resolvido.** Exceção na D-02 e CUJ 6. |
| 3. Padrão de log da P-08 como desvio local | **Não resolvido.** Ver o Major 2. |
| 4. Mapa de skills | **Aceito como risco.** O CUJ 1 já não promete demais. Resta o Nit 1. |
| 5. Cópia da P-12 e da P-13 | **Resolvido.** |
| 6. Q2 / usuário do banco | **Decidido, mas com risco novo.** Ver o Major 1. |
| 7, 9, 10, 11, 12 (Nits) | **Resolvidos.** |
| 8. Escopo de 17 princípios | **Mantido, agora com justificativa.** Aceitável. |

---

## Major

### 1. `DB_USERNAME=root` provavelmente quebra a inicialização do MySQL num clone novo, e a verificação não detecta isso

* **Onde:** M9 e M10 (169-173), Riscos (520), passo 7 (738-746) e verificação (765-766).
* **O que acontece:**
  * O `compose.yaml:37` passa `MYSQL_USER: "${DB_USERNAME}"`. Com `root`, o entrypoint da imagem `mysql` recusa a configuração na primeira inicialização do volume: "MYSQL_USER and MYSQL_PASSWORD are for configuring a regular user and cannot be used for the root user". Isso acontece exatamente no cenário de clone novo que a M10 quer garantir.
  * O ambiente do autor usa `sail`, e o volume já existe. Por isso a verificação por `SHOW GRANTS` passa sem testar o caso real.
* **Contradição com a justificativa da linha 744:** se o ambiente atual cria bancos de tenant com o usuário `sail`, existe um grant manual que o design não registra.
* **Recomendação:**
  * testar com um volume novo (`sail down -v && sail up -d` mais os 3 testes);
  * se a falha se confirmar, manter `sail` e registrar o grant necessário, ou tirar `MYSQL_USER` e `MYSQL_PASSWORD` do `compose.yaml`;
  * a Q2 só pode ser marcada como resolvida depois disso.

> **Conferência feita pelo Elephant (2026-11-11):** no container atual, o usuário `sail` tem `GRANT … CREATE, DROP … ON *.* TO sail@'%' WITH GRANT OPTION`, além do `testing%` do script do Sail. É um grant manual, que não está registrado no repositório. A justificativa do passo 7 está errada, e o achado procede.

### 2. A P-08 continua dependendo de um desvio em cada `catch`, e o próprio framework a viola sem registro

* **Onde:** P-08 (345), registro (374), não-metas (188-189), NFR (531-534) e passo 6 §7 (705-714).
* **O que acontece:**
  * Todo controller novo copia o `if` "é `QueryException`?". Quando o helper central chegar, esses desvios vão ser reescritos.
  * O plano traz um trecho de código (707-711), o que é detalhe de implementação demais para este nível.
  * O handler padrão do Laravel continua gravando em `storage/logs` a mensagem completa de toda `QueryException` não capturada, com os binds. O `bootstrap/app.php` não tem `report` para ela, e esse caminho não está no registro, contra a M4.
  * O `DriveService.php:110` loga `['exception' => $th]`, que também serializa a mensagem, e a receita da linha 374 não encontra esse caso.
* **Recomendação:** escolher uma das saídas.
  * **(a)** Um único `$exceptions->report(fn (QueryException $e) => …)` em `bootstrap/app.php`, que registra só a classe e o SQLSTATE. A §7 deixa de ter o desvio. É o mecanismo padrão do framework, não um helper novo.
  * **(b)** Limitar a P-08 às chamadas `Log::` escritas pelo projeto e registrar o handler padrão e o `['exception' => $th]` em `specs/violacoes-conhecidas.md`, com receita própria.

  Nas duas, o trecho de código sai do design.

---

## Nit

### 1. O problema 3 promete mais do que o design entrega

* **Onde:** 83 e 179-181.
* **O que acontece:** as skills ausentes estão no problema, mas são não-meta. A "Code Review" do `CLAUDE.md` continua obrigando a usar uma skill que não existe.
* **Recomendação:** tirar o item do problema 3, ou acrescentar uma linha dizendo que as skills `*-senior` não existem por enquanto.

### 2. O registro de violações não tem efeito normativo, mas o texto sugere que tem

* **Onde:** 221-222, 522 e 674-676.
* **O que acontece:** quem protege as linhas antigas é a M5, não o registro.
* **Recomendação:** descrever o registro como um aviso para o agente não copiar a violação, e corrigir a CUJ 2, o risco "bloqueia algo já corrigido" e a redundância na regra 1.

### 3. A CUJ 1 e a D-01 não combinam

* **Onde:** 210-213 e 334-335.
* **O que acontece:** a CUJ 1 não diz se o agente registra a violação em `specs/violacoes-conhecidas.md` ou só a cita na resposta.

### 4. A entrada da P-08 não segue o formato da M4

* **Onde:** 374.
* **O que acontece:** a entrada não traz o símbolo. A receita pega arquivos que têm `getMessage()` e `Log::`, mas não necessariamente na mesma linha.
* **Recomendação:** permitir na M4 uma entrada agregada com receita, ou ajustar a receita.

### 5. A alternativa L não discute o registro na §13 das convenções

* **Onde:** 504-510.
* **Recomendação:** acrescentar uma frase sobre por que a §13 foi descartada: as convenções são normativas e mudariam a cada registro.

### 6. O filtro da P-07 no registro não pode ser executado como está

* **Onde:** 373.
* **Recomendação:** dar o filtro concreto (ex.: `jq`) ou declarar a verificação como manual.

---

## O que está bom e deve ficar

* **Precedência:** os 3 níveis e a subordinação dos índices na D-01.
* **Registro de violações:** fora do documento versionado e do import, com a CUJ 6.
* **P-12 e P-13:** abstratas, com as listas só nas convenções.
* **Novas seções:** o glossário e a CUJ 5.
* **`AGENTS.md`:** a tabela de destino das regras removidas.
* **Afirmações conferidas no código:**
  * os 11 Mailables têm `afterCommit()`;
  * existem três Policies;
  * `Utils.php` existe;
  * `DriveService:930` usa `Log::info`;
  * o `s3` está comentado;
  * o prefixo `tenant` está em `config/tenancy.php`.

---

# Reauditoria (estágio 8, rodada 2)

**Veredito: aprovar.** Os dois Major anteriores e os 6 Nit foram resolvidos. Não há Blocker nem Major novo.

As correções acrescentaram só duas coisas: um script de init de 4 linhas, no formato do `create-testing-database.sh` do Sail, e um volume no `compose.yaml`. As duas seguem o padrão do próprio Sail e não criam pasta nova.

Conferido no código:

* o prefixo `tenant` está em `config/tenancy.php:66`;
* o gerenciador de banco é o `MySQLDatabaseManager` comum;
* o `phpunit.xml` só fixa `DB_DATABASE=testing`.

## Nits novos e o que foi feito com eles

1. **A verificação da M10 no worktree não diz como montar o ambiente.** Um worktree novo não tem `vendor/`, e o `compose.yaml` constrói a imagem e monta scripts a partir de `vendor/`. O `.env.example` também vem sem `APP_KEY`. Além disso, a Q2 aparecia como resolvida antes da M10 rodar.
   * **Aplicado:** passos de instalação do `vendor/` e de `key:generate`. A Q2 passou a "decidida, confirmada só depois da M10".
2. **O handler padrão aparecia ao mesmo tempo como exceção e como violação da P-08.** Faltava também o `report` para `QueryException` entre os próximos passos da §4.
   * **Aplicado:** a P-08 voltou a proibir dado pessoal em qualquer log, sem exceção. O handler ficou como violação registrada, e o `report` entrou como próximo passo na §4.
3. **Duas receitas de busca deixavam casos de fora.**
   * A receita da P-08 não pegava chamadas de `Log::` quebradas em várias linhas (`TenantDriveController`, `DriveService`). **Aplicado:** a receita passou a ser `grep -A3`.
   * A receita do handler procurava `report`, sem dizer o que o resultado significa. **Aplicado:** agora procura `QueryException`, com a anotação "saída vazia = violação presente".
4. **O documento citava um "Blocker da revisão" sem contexto.**
   * **Aplicado:** passou a citar "problema 1".

O limite de uma reauditoria por estágio foi atingido.
