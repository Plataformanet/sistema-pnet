# Revisão Zoom Out (Estágio 5): `specs/constituicao-design.md`

* **Data:** 2026-11-11
* **Revisor:** egm-zoom-out, em contexto isolado
* **Lista de leitura de base:** `CLAUDE.md`, `AGENTS.md`, `docs/convencoes_de_desenvolvimento.md`, `docs/arquitetura/architecture_guide.md`, `specs/concepts_brief.md`

**Veredito: aprovar com mudanças.** Não há bloqueador. Há 6 achados Major e 6 Nit.

O contexto é suficiente. O objetivo e o motivo estão claros nas linhas 9 a 76. O problema de raiz é a regra "o código é o estado real" sem precedência entre documentos, e a D-01 resolve isso diretamente. A estrutura geral é enxuta: um documento normativo, ponteiros por ID e um único commit.

---

## Major

### 1. A dívida fica registrada em dois lugares, e o apêndice ignora o próprio limite de tokens

* **Onde:** linhas 73-74, 251-252, 290-303, 443-445 e 588-590.
* **O que acontece:** o Apêndice A e a §13 das convenções ("Divergências conhecidas (não replique cegamente)") servem para a mesma coisa: avisar o agente de que um código existente não deve ser copiado. A §13 já existe e cumpre esse papel. O plano ainda acrescenta à §13 um item da P-08 que repete o Apêndice A (linha 588). A D-03 (fonte única) é violada já na primeira versão.
* **Custo de contexto:** a linha 443 justifica o limite de cerca de 150 linhas com o custo de tokens por sessão. Mas o apêndice não entra na conta e é importado do mesmo jeito em toda sessão, pelo `@specs/constitution.md`.
* **Recomendação:** manter um único registro de dívida. A opção preferida é a §13 das convenções; a alternativa é um arquivo descritivo separado, como `specs/violacoes-conhecidas.md`. A constituição fica só com o ponteiro para esse registro.

### 2. A governança trata qualquer mudança no apêndice como emenda

* **Onde:** linhas 93-95, 273, 275 e 624-627.
* **O que acontece:** pela D-02 e pela D-04, qualquer alteração em `specs/constitution.md` exige pedido explícito, subida de versão e uma linha no Apêndice B. Assim, corrigir uma violação, ou registrar uma nova (o que a M4 exige), vira emenda. O design não diz se isso é PATCH, nem se pode ser feito sem pedido explícito.
* **Consequência:** com esse atrito, o apêndice tende a ficar desatualizado, e a M4 deixa de valer na primeira correção.
* **Recomendação:** tirar o apêndice do documento versionado (resolve junto com o item 1). Outra saída é declarar na D-04 que alterar o Apêndice A não é emenda.

### 3. O padrão de log da P-08 é um desvio local repetido em 143 lugares

* **Onde:** linhas 283 e 563-569.
* **O que acontece:** o novo padrão da §7 coloca um desvio "se é `QueryException`, então…" dentro de cada `catch`. Hoje existem 143 chamadas `Log::*(...getMessage())` em 50 controllers. Cada controller novo copiaria essa lógica, e a revisão teria que conferir o desvio linha por linha. Não existe tratamento central: nenhuma ocorrência de `QueryException`, `errorInfo` ou `sqlstate` em `app/`, `bootstrap/` e `config/`.
* **Recomendação:** centralizar o tratamento, por um destes caminhos:
  * um helper em `app/Support/`, no padrão de `MoneyHelper` e `DocumentMaskHelper` (por exemplo, `LogContextHelper::fromThrowable($th)`);
  * ou uma personalização do report de exceções em `bootstrap/app.php`.

  Com isso, a convenção vira uma linha, a P-08 pode ser verificada por `grep` e o exemplo de código sai do design.

### 4. A não-meta do mapa de skills impede que a CUJ 1 e o problema 3 sejam cumpridos

* **Onde:** linhas 48-55, 127-128, 166-167, 514-520 e 629-630.
* **O que acontece:** a CUJ 1 promete que o agente "não procura arquivos que não existem". Mas o `CLAUDE.md` continua mandando carregar cinco skills `*-senior` que não existem, e a seção "Code Review" obriga a usar a `laravel-code-review-senior` em toda revisão. Em `.claude/skills/` só existem as 6 skills do Boost. A própria verificação (linha 629) já abre exceção para o "Mapa de skills".
* **Recomendação:** escolher uma destas saídas:
  * acrescentar uma linha acima do "Mapa de skills" e da "Code Review": "as skills `*-senior` ainda não existem; ignore até a tarefa X". É um ajuste mínimo e não recria nada;
  * ou reduzir o texto da CUJ 1 e do problema 3 ao que o design de fato resolve.

### 5. P-12 e P-13 são copiadas nas convenções no mesmo PR

* **Onde:** linhas 287-288 e 570-579.
* **O que acontece:**
  * A P-12 lista os 20 sufixos, e o plano coloca a mesma lista nas convenções §4 e §5. A tabela também fica em seções que não combinam: §4 é Models e §5 é Services, mas a tabela traz Job, Mail e Middleware.
  * A P-13 lista tokens, `components/ui/*` e layouts, e o plano repete essa base na §10.

  Nos dois casos é a cópia que a D-03 proíbe.
* **Recomendação:** a constituição fica com a regra abstrata. Exemplo para a P-12: "toda classe do projeto termina com o seu tipo; models sem sufixo; a lista está nas convenções §X". A enumeração mora só nas convenções, numa seção própria.

### 6. O repositório já responde a Q2, e é provável que a resposta seja "falha"

* **Onde:** linhas 143-148, 597-600 e 624-627.
* **O que o código mostra:** `compose.yaml:43` monta o `create-testing-database.sh` do Sail, que só faz `GRANT ALL PRIVILEGES ON \`testing%\`.* TO '$MYSQL_USER'@'%'`. Mas os bancos de tenant usam o prefixo `tenant` (`config/tenancy.php:66`).
* **Consequência:** com `DB_USERNAME=sail`, um clone novo provavelmente não consegue rodar `CREATE DATABASE tenant…` em `createTenant()` nem em `sharedTenant()`.
* **Problemas no plano de verificação:**
  * a checagem proposta é cara: um clone num diretório temporário exige `composer install` e a stack do Sail;
  * o plano manda registrar uma falha de setup no Apêndice A, que é o lugar errado, porque o apêndice é para violações de princípio.
* **Recomendação:** decidir agora, antes de implementar.
  * Opções: usar `DB_USERNAME=root` no `.env.example` (o compose já define `MYSQL_ROOT_PASSWORD=${DB_PASSWORD}` e `MYSQL_ROOT_HOST=%`) ou documentar um grant adicional.
  * Para confirmar o diagnóstico, basta rodar `sail mysql -e "SHOW GRANTS"`.
  * Em qualquer opção, tirar do plano o desvio para o Apêndice A.

---

## Nit

### 7. O problema 2 só é resolvido em parte

* **Onde:** linhas 39-47 e 129-131.
* **O que acontece:** as seções 4 a 6 do `architecture_guide.md` continuam repetindo as regras. Pela D-01, isso é aceitável, porque o guia passa a ser descritivo.
* **Outra deriva no mesmo documento:** `architecture_guide.md:116` cita `App\Support\Money`, mas a classe é `app/Support/MoneyHelper.php`.
* **Recomendação:** registrar as duas divergências na não-meta.

### 8. O escopo cresceu além da recomendação do brief

* **Onde:** linhas 83-84 e 272-275.
* **O que acontece:** a M1 diz "entre 8 e 17", e o limite superior coincide com o resultado. São quatro princípios só de governança (D-01 a D-04), com semver e lápides, para um único autor.
* **Sugestão:** juntar a D-02 com a D-01 e a D-03 com a D-04.

### 9. A linha da P-02 no Apêndice A é risco, não violação

* **Onde:** linha 301.
* **Recomendação:** descomentar `'s3'` em `config/tenancy.php:119` no mesmo commit, ou tirar a linha.

### 10. Referências que vão envelhecer rápido

* **Onde:** linhas 296 e 300.
* **O que acontece:**
  * "DriveService (~linha 930)" depende do número da linha;
  * "Controllers com `Log::error`…" não cita um arquivo, como a M4 exige (são 50 arquivos).
* **Recomendação:** citar um padrão verificável por `grep` em vez de número de linha.

### 11. A D-01 não diz a que estão subordinadas as regras dos índices

* **Onde:** linhas 221-222.
* **O que acontece:** o `CLAUDE.md` e o `AGENTS.md` trazem regras, como o bloco do Boost e a seção "Code Review".
* **Recomendação:** acrescentar à D-01: "regras de ferramenta nos índices são subordinadas à constituição".

### 12. A conferência do Apêndice A é parcial

* **Onde:** linhas 634-640.
* **O que acontece:** só 4 das 10 linhas têm uma checagem que dá para executar de novo.
* **Recomendação:** listar a checagem de cada linha, ou dizer que as demais são conferidas à mão.

---

## O que está bom e deve ficar

* **Precedência e bloqueio:** a separação normativo × descritivo e a fronteira de bloqueio por linhas novas ou alteradas.
* **Fonte única:** a citação por ID (D-03) e a ausência de exemplos de código na constituição.
* **Verificação:** adiar a CI e o `tests/Arch/ConstitutionTest.php` está bem justificado, porque os testes que já existem (`MoneyColumnsTest`, `DocumentsRoutesTest`) servem como verificação.
* **Fatos:** as afirmações conferem com o código (as três Policies, o `.env.example` em sqlite, o `phpunit.xml` sem `DB_CONNECTION`).
