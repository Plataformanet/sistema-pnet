# Constituição do Sistema PNET (`specs/constitution.md`)

* **Autor:** Eduardo Sousa
* **Data:** 2026-11-11

## Visão geral

O objetivo é criar o arquivo `specs/constitution.md` no formato do Spec Kit: uma lista curta de princípios de engenharia e documentação que o PNET não negocia, mais as regras de governança desse próprio documento. A constituição passa a ter a maior precedência do repositório, acima do `CLAUDE.md` e de `docs/convencoes_de_desenvolvimento.md`. O `CLAUDE.md` vira um índice que aponta para ela, e as convenções passam a ser o detalhamento operacional.

O documento serve a dois públicos: os agentes de IA que geram código no repositório e as pessoas do time que revisam os PRs. Só entra na constituição uma regra que justifique bloquear um PR. Nomes de pasta, exemplos de código, anatomia de feature e o mapa de skills continuam fora dela.

## Análise

Seis revisores independentes leram os documentos e o código: design, detalhes de código, escopo, compreensão, prontidão para implementação e estrutura. O veredito foi escrever a constituição, mas curta: de 8 a 12 princípios, cerca de 60 a 100 linhas, cada um com uma linha "Como verificar".

### 1. Conflito de precedência (bloqueador)

O `CLAUDE.md:13` e as convenções dizem que, quando o código diverge da documentação, quem vale é o código. Se a constituição fica no topo e essa regra continua igual, o código que viola um princípio (por exemplo, dinheiro gravado em decimal) também passa a valer, e a constituição deixa de ter efeito.

A solução é separar os documentos em dois tipos:

- **Normativos** (a constituição e as convenções): definem o estado permitido. Se o código diverge deles, o defeito está no código.
- **Descritivos** (`docs/arquitetura`, `docs/modulos`): descrevem o estado atual. Se divergem do código, quem vale é o código e o documento é atualizado.

Proposta de texto:

> "O código descreve o **estado atual**; a constituição define o **estado permitido**. Divergência entre código e `docs/` → atualizar o documento. Divergência entre código e constituição → é violação: não replique, aponte ao usuário e registre como dívida; a constituição só muda por emenda."

A regra 10 das convenções ("padrões locais prevalecem") também precisa mudar para "padrões de módulo prevalecem sobre as convenções, nunca sobre a constituição". A ordem fica assim: Constituição > convenções > padrões do módulo > documentos descritivos.

### 2. Metade dos princípios candidatos está falsa no código atual (bloqueador)

Seis dos doze candidatos não são verdadeiros hoje do jeito que foram escritos. Ratificá-los assim cria uma constituição violada desde o primeiro dia.

| Princípio candidato | O que o código mostra | Ajuste proposto |
| --- | --- | --- |
| Todo acesso a dado de tenant passa por `$tenant->run()` | No HTTP, quem inicializa o tenant é o middleware de domínio. Na fila, é o `QueueTenancyBootstrapper`. Alguns services (`ContactService`, `DriveLogService`) já esperam ser chamados dentro de um `run`. `RoleService` e `UserService` usam `DB::beginTransaction` manual. | Exigir um "contexto de tenant inicializado": middleware no HTTP, bootstrapper na fila e `run()` em services, commands, migrations e testes. A transação, quando existir, fica dentro do contexto. |
| Arquivo físico só é apagado depois do commit | `CompanySettingService.php:75-109` e `UserProfileService.php:22-36` apagam o arquivo dentro da transação. O Drive e as Propostas seguem o padrão correto. | Generalizar para "efeitos irreversíveis (arquivo, e-mail, job) só depois do commit da transação de banco". Antes de ratificar, corrigir os dois services ou listá-los como violação conhecida. |
| Dados de referência só por migration, sempre com query builder | Três migrations de tenant usam os models `Permission`/`Role` e services da aplicação. | Usar query builder no banco central e o `TenantPermissionService` nas permissões do tenant, sempre filtradas pelo plano. |
| Toda rota tem `permission:` | Login, logo pública, dashboard, perfil, avatar e o `/cadastro` central não têm. O upload de comprovante (POST) exige só `view`. | Exigir em "toda rota autenticada que opera recurso de módulo", com as exceções numa lista fechada por categoria. |
| MySQL fixo | Na configuração, o default é `sqlite`, o `.env.example` também usa `sqlite` e o `phpunit.xml` não fixa a conexão. Os docs dizem "MySQL/PostgreSQL". | Mudar o default, o `.env.example` e o `phpunit.xml` para MySQL no mesmo commit da constituição. |
| Identificadores em inglês | Enums persistidos usam valores em português (`'despesa'`, `'receita'`). Também há nomes como `geraPDF` e `$periodo`. | Exigir inglês nos identificadores novos. Os valores já persistidos ficam como estão até uma migration específica mudá-los. |

### 3. Riscos de configuração que o texto atual não cobre

- **Isolamento de arquivos:** o `config/tenancy.php` aplica o sufixo de tenant só nos discos `local`, `public` e `minio`. Se `BUCKET_DISK` for `s3`, o isolamento quebra sem nenhum aviso. Além disso, alguns services leem o nome do disco a partir do banco. O princípio precisa exigir que todo disco usado para arquivo de tenant esteja em `tenancy.filesystem.disks`.
- **Idioma:** o `config/app.php` usa o locale `en` e não existe pasta `lang/`, então as mensagens de validação e de autenticação do framework saem em inglês. Isso contradiz a regra de texto ao usuário em pt-BR.
- **Logs e LGPD:** o padrão `Log::error(...->getMessage())` pode gravar CPF/CNPJ ou renda no log.

### 4. Princípios que faltam

- **Migrations central × tenant:** a pasta define o escopo, o deploy roda `migrate` antes de `tenants:migrate` e uma migration de dados já aplicada nunca é editada.
- **Fila:** trabalho disparado em contexto de tenant leva o tenant junto, e e-mails usam `afterCommit()`.
- **Frontend:** o `usePermission` só esconde elementos da tela. Quem autoriza é sempre o backend.
- **LGPD/PII:** dado pessoal nunca cruza tenants, nunca vai para log e é apagado junto com o tenant.
- **Teste de vazamento entre tenants:** nenhum módulo tem hoje um teste que prove que o tenant A não lê dados do tenant B.

### 5. Quem garante que a regra é cumprida

O repositório não tem CI (`.github/`) nem hooks. Hoje a única barreira é a revisão, e o gate de revisão do `CLAUDE.md` aponta para skills que não existem mais. Os revisores propõem três medidas:

- Um `tests/Arch/ConstitutionTest.php`, com uma entrada própria de testsuite no `phpunit.xml`, que verifique:
  - nenhuma coluna de dinheiro em `decimal`;
  - toda rota de tenant com `permission:`, com uma allowlist de exceções;
  - nenhum seeder criando permissão;
  - `bucket.disk` registrado no tenancy.
- Um apêndice "Violações conhecidas", para que o bloqueio valha só para código novo.
- Um `@specs/constitution.md` no topo do `CLAUDE.md`, fora do bloco do Boost, para que os agentes carreguem a constituição de verdade.

### 6. Como evitar uma quarta cópia das mesmas regras

Hoje as mesmas regras aparecem nas convenções §1, no `architecture_guide` §4–6 e no `CLAUDE.md`. Cada regra deve morar em um lugar só: a constituição define a regra com um ID (`P-01`), e os outros documentos citam esse ID e guardam só a mecânica. Toda emenda MINOR ou MAJOR atualiza os três documentos no mesmo PR.

## Metas × Não-metas

* **Metas (escopo):**
  * `specs/constitution.md` em pt-BR, com cabeçalho de versão (1.0.0), data de ratificação e data da última emenda.
  * Uma seção sobre precedência e a natureza dos documentos (normativos × descritivos).
  * Os princípios de engenharia `P-NN`, cada um com Regra, Por quê, Escopo, Como verificar e Exceções (lista fechada).
  * Os princípios de documentação `D-NN`: precedência, pt-BR, alteração só com pedido explícito, regras de emenda.
  * Governança com semver (MAJOR remove ou redefine um princípio ou muda a precedência; MINOR adiciona um princípio; PATCH ajusta o texto sem mudar o sentido) e histórico de alterações.
  * Um apêndice de violações conhecidas.
* **Não-metas (fora do escopo):**
  * O mapa de skills e o fluxo de agentes.
  * Nomes de pasta, exemplos de código e a anatomia de feature (ficam nas convenções).
  * A política de quais testes rodar e quanto tempo leva (detalhe operacional, vai para as convenções).
  * Uma lista de Policies pelo nome (a regra entra como critério).

## Alternativas consideradas

* **Ampliar a §1 das convenções ("Princípios inegociáveis") em vez de criar a constituição.** É a opção mais barata e funciona quase igual. O autor a descartou porque escolheu o formato Spec Kit em `specs/`, como raiz normativa acima das convenções.
* **Constituição com todo o conteúdo das convenções.** O autor a descartou: o documento envelheceria a cada mudança de pasta ou de padrão de código.
* **Manter "o código manda" como regra geral.** Descartada porque anula qualquer princípio normativo (ver a Análise, item 1).
* **Banco neutro de dialeto, ou PostgreSQL.** O autor descartou as duas e fixou MySQL.
* **Rodar a suíte completa a cada mudança.** O autor descartou: a suíte leva cerca de 9 minutos, então roda só nos arquivos afetados e, completa, no fechamento de fase.
* **Levar o mapa de skills para a constituição.** O autor descartou: o mapa é fluxo de agentes e está quebrado hoje.
* **Versão com data e lista de mudanças, sem semver.** Sugerida pelo revisor de escopo como suficiente para um time pequeno. O autor manteve o semver.

## Decisões tomadas e próximos passos

**Decidido pelo autor (rodadas 1 e 2):**

1. **Local e formato:** o arquivo fica em `specs/constitution.md`, no formato Spec Kit. Todos os artefatos gerados ficam em `specs/`.
2. **Precedência normativo × descritivo:**
   * A ordem é constituição > convenções > padrões de módulo > documentos descritivos.
   * Se o código diverge da constituição ou das convenções, a violação é do código: não replicar, apontar ao usuário e registrar como dívida.
   * Se o código diverge de `docs/arquitetura` ou `docs/modulos`, o documento é que está desatualizado e precisa ser corrigido.
3. **Escopo:** entram só princípios inegociáveis, com nomes concretos de classe, middleware, comando e pasta (`TenantPermissionService`, `permission:`, `QueueTenancyBootstrapper`, `database/migrations/{central,tenant}`), para que cada regra possa ser verificada. Ficam de fora blocos de código e nomes de método.
4. **Violações atuais:** a constituição é ratificada com um apêndice "Violações conhecidas" e o bloqueio vale só para código novo. O apêndice lista:
   * `CompanySettingService` e `UserProfileService`, que apagam arquivo dentro da transação;
   * as três migrations de tenant que usam models;
   * as rotas sem `permission:`, além das categorias de exceção;
   * os enums com valores em pt;
   * `RoleService` e `UserService`, que abrem transação manual;
   * o disco `s3` fora do tenancy, como risco.
5. **Princípios adicionados:**
   * migrations central × tenant;
   * fila e efeitos irreversíveis só depois do commit;
   * autorização só no backend (o front apenas esconde elementos);
   * LGPD/PII.
6. **Testes:** toda mudança de comportamento tem teste novo ou atualizado, executado e passando. A suíte completa roda no fechamento de fase. A regra de rodar só os testes afetados fica nas convenções.
7. **Banco:** MySQL em todos os ambientes, testes incluídos.
8. **pt-BR:** a regra vale para o texto escrito pelo projeto (`messages()`, flash, telas, docs). As mensagens do framework e o locale ficam fora.
9. **Cumprimento:** por enquanto, quem bloqueia é a revisão do PR, e a suíte completa é rodada por quem fecha a fase. CI e `tests/Arch/ConstitutionTest.php` ficam registrados como próximo passo.
10. **Governança:** versão inicial 1.0.0, semver (MAJOR também cobre mudança de precedência; PATCH nunca muda o sentido) e histórico completo no fim do documento.
11. **Fora do escopo:** o mapa de skills.

**Ajustes que entram no mesmo conjunto de mudanças (pedidos explicitamente pelo autor):**

* **`CLAUDE.md`:**
  * a regra normativo × descritivo no lugar da linha 13;
  * `@specs/constitution.md` perto do topo, fora do bloco do Boost;
  * remoção das referências a `specs/spec.md`, `specs/implementation.md` e `docs/prd-sistema-pnet-v2.md`.
* **`docs/convencoes_de_desenvolvimento.md`:**
  * a §1 vira "Regras operacionais", com ponteiros para os IDs dos princípios, e a regra 10 passa a dizer "nunca sobre a constituição";
  * a §9 passa a refletir as três Policies;
  * a regra de testes é alinhada.
* **Configuração MySQL:** `.env.example`, `phpunit.xml` (`DB_CONNECTION=mysql`) e o default de `config/database.php`.

**Não entra agora:** `architecture_guide.md` (que ainda diz "MySQL/PostgreSQL" e repete as regras), a correção do mapa de skills no `CLAUDE.md`, CI e arch tests.
