# Teste de Compreensão (Estágio 6): `specs/constituicao-design.md`

* **Data:** 2026-11-11
* **Revisor:** egm-comprehension, em contexto isolado. Leu só o documento.
* **Faixas:** especificação, linhas 1 a 453; plano de implementação, linhas 454 a 645.

## O que isto propõe (What this proposes)

O documento propõe criar `specs/constitution.md`, no formato Spec Kit e em pt-BR, como o documento de maior precedência do repositório. São 17 princípios que justificam bloquear um PR: D-01 a D-04 tratam de documentação e P-01 a P-13, de engenharia. Cada princípio tem Regra, Por quê, Escopo, Como verificar e Exceções. O arquivo também tem um apêndice de violações já existentes e um histórico versionado com semver.

A hierarquia fica assim: constituição > convenções > padrões de módulo > documentos descritivos. Se o código diverge de um documento normativo, isso é violação. Se diverge de um documento descritivo, quem é atualizado é o documento. O bloqueio vale só para linhas novas ou alteradas pelo PR.

O mesmo commit faz três ajustes:

* o `CLAUDE.md` e o `AGENTS.md` viram índices que apontam para a constituição e perdem as referências a arquivos que não existem mais;
* as convenções passam a citar os IDs dos princípios em vez de repetir as regras;
* o MySQL fica fixado em `.env.example`, `phpunit.xml` e `config/database.php`.

## Como o sistema existente funciona, segundo este documento (How the existing system works, per this document)

Hoje as regras estão espalhadas em três lugares: na §1 das convenções, no `architecture_guide.md` e no `CLAUDE.md`. Esses lugares divergem entre si (número de Policies, regra de testes, banco) e não há ordem de precedência entre eles. A regra "o código é o estado real" (`CLAUDE.md:13`) faz o código prevalecer sobre qualquer convenção.

O `CLAUDE.md` e o `AGENTS.md` apontam para arquivos apagados: a pasta `specs/` foi removida no commit `e5fad17`, junto com uma constituição anterior. Várias regras "inegociáveis" já são violadas no código:

* arquivo apagado dentro de uma transação;
* migrations de tenant que usam models;
* rotas sem `permission:`.

O sistema é um Laravel multi-tenant: stancl/tenancy com um banco por tenant, disks de bucket e Sail com MySQL. A config, porém, ainda tem `sqlite` como padrão.

## Achados

### 1. Major: a regra "sem nomes de método, nem nos apêndices" contradiz o texto-base dos princípios

* **Linhas:** 255-256 e 494, contra 276-288 e 296-300.
* **O problema:** o plano manda copiar o texto da tabela "Princípios" (linha 476). Esse texto, porém, traz nomes de método, e o documento não diz qual das duas instruções prevalece. Os nomes que aparecem:
  * `$tenant->run()` (P-01);
  * `afterCommit()` (P-03);
  * `messages()` (P-11);
  * `usePermission` (P-07);
  * `config('bucket.disk')` (P-02);
  * `sharedTenant()` (P-01);
  * `getMessage()` e `beginTransaction` (Apêndice A).

### 2. Major: as rotas sem `permission:` não estão registradas, o que contradiz a M4

* **Linhas:** 60, contra 93-95 e 299.
* **O problema:** o problema 4 diz que "várias rotas autenticadas não têm `permission:`". O Apêndice A, porém, só tem uma linha para a P-07, e ela trata de outra coisa: uma rota de escrita protegida por `view`. O documento não diz se essas rotas caem nas exceções da P-07 ou se são violações sem registro.

### 3. Major: a verificação do Apêndice A cita checagens que o Plano técnico não define

* **Linhas:** 634-640.
* **O problema:** os 4 comandos listados cobrem só as linhas da P-01, P-05, P-07 e parte da P-03. As linhas da P-02, P-08, P-11, P-12 e as duas primeiras da P-03 ficam sem checagem. Além disso, o texto não diz qual é o "filtro de permissão" do `route:list`.

### 4. Major: o campo "Detalhe" não tem origem definida

* **Linhas:** 263-265, 474-476 e 480-482.
* **O problema:**
  * a tabela "Princípios" não tem coluna "Detalhe", e o valor só está definido para a P-08;
  * para D-01 a D-04, talvez nem exista uma seção correspondente nas convenções;
  * a M2 lista 5 campos, mas o modelo de princípio tem 6.

### 5. Major: "fechamento de fase" não é definido

* **Linhas:** 248-249, 284, 411, 484 e 641.
* **O problema:** a P-09 depende desse conceito, mas o documento não diz o que é uma fase, quem a fecha nem como se sabe que ela terminou.

### 6. Major: "padrões locais de módulo" não são definidos

* **Linhas:** 88, 213-216 e 272.
* **O problema:** o termo é o terceiro elo da cadeia de precedência, mas o documento não diz onde esses padrões estão escritos nem como diferenciá-los de `docs/modulos/`.

### 7. Major: o conteúdo removido do `AGENTS.md` não é explicado

* **Linhas:** 532-537.
* **O problema:** o documento não diz o que são o "PRD de evolução", o CRM só no front e MUD-01 a MUD-04. Sem isso, não dá para avaliar se perder essas regras é seguro.

### 8. Major: a saída prevista para a Q2 não tem lugar no plano

* **Linhas:** 624-627.
* **O problema:**
  * uma falha de permissão de `CREATE DATABASE` não se encaixa no formato do Apêndice A, que registra violações de princípio;
  * o README de setup não está na lista de arquivos do commit;
  * não fica claro se a P-10 continua valendo caso os testes não rodem num clone novo.

### 9. Nit: limite de princípios inconsistente

* **Linhas:** 362, contra 83.
* **O problema:** a alternativa C fala em "passar de 14 princípios", mas a M1 permite até 17.

### 10. Nit: a linha da P-11 no Apêndice A diz "(exceção registrada)"

* **Linhas:** 302, contra 286.
* **O problema:** a exceção da P-11 cobre valores de enum já persistidos, mas não identificadores em pt num controller. Fica ambíguo se esse caso é violação ou exceção.

### 11. Nit: a linha da P-08 no Apêndice A não lista arquivos

* **Linha:** 300.
* **O problema:** a M4 exige o arquivo e a descrição. Basta a lista de arquivos ou o comando que a gera.

### 12. Nit: "recurso-espelho" sem definição

* **Linha:** 591.

### 13. Nit: a CUJ 1 fala em "ID da violação (`P-03`)"

* **Linhas:** 169-170.
* **O problema:** o correto seria "ID do princípio".

### 14. Nit: termos externos ou de projeto sem link ou definição curta

Spec Kit, stancl/`TenantDeleted`, Laravel Boost/`GuidelineWriter`, `boost.json`, `sharedTenant()`/`createTenant()` e "o revisor" da Q4.

### 15. Nit: a CUJ 3 cita a D-01 para um caso que a D-01 não descreve literalmente

* **Linhas:** 182-186.
* **O problema:** é uma divergência entre um documento descritivo e a constituição, e não entre código e documento.

## Veredito

As duas paráfrases foram possíveis: o documento se sustenta e não há bloqueador. Os achados Major (1 a 8) são lacunas de autossuficiência e contradições internas que quem implementa ou revisa encontraria sem conseguir resolver só com o documento.
