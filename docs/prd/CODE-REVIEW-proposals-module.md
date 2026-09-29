# Code Review — Módulo de Propostas (sistema atual)


## Resumo
O módulo funciona no caminho feliz, mas a **autorização está quebrada**: os middlewares de perfil não bloqueiam nada e a checagem de parceiro nos controllers usa `isset(...) == id`, liberando edição/visualização de propostas a qualquer usuário logado. Há também senhas previsíveis enviadas por e-mail, documentos pessoais no disco público e perda de itens na aba de pagamentos por causa do `merge` de coleções Eloquent. Primeiro corrija autorização e exposição de dados; depois, a integridade (mass assignment, exclusão de usuários) e só então performance.
*Limitação: o MCP laravel-boost não conectou; não houve `EXPLAIN`/contagem de linhas. Itens de performance estão como “a confirmar”.*

## O que está bom ✅
- `PropostaService::insert/update` já usam `DB::transaction`.
- `Proposta::proponentes()` com eager load na listagem evita N+1 dos nomes.
- `GeraPropostaService`/`EmolumentoPropostaService` usam promoted properties e array tipado; `EmolumentoRequest` já é Form Request.
- Soft delete em propostas e etapas preserva histórico.

## 🔴 Crítico
**C1. Middlewares de perfil não bloqueiam ninguém** — `app/Http/Middleware/Admin.php:24-27` (idem `Parceiro`, `Vendedor`, `Cliente`)
- Problema: os dois caminhos fazem `return $next($request)`. Rotas como `arquivos.*`, `gera-proposta`, `gera-emolumento`, `imprimir-*` não têm outra checagem.
- Impacto: um cliente logado pode enviar/apagar documentos de qualquer proposta e imprimir o informativo (CPF, renda) de qualquer uma.
- Correção:
```php
// antes
if (Auth::user()->infoUser->acesso_id === 1) { return $next($request); }
return $next($request);
// depois (middleware único parametrizado: ->middleware('role:1,3,5'))
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    abort_unless(in_array((string) $request->user()->infoUser->acesso_id, $roles, true), 403);
    return $next($request);
}
```
Obs.: `middleware('admin','parceiro','vendedor')` encadeia os três (AND), não é OR — com middleware real ninguém passaria; por isso o parametrizado.

**C2. Checagem de parceiro compara booleano com id** — `app/Http/Controllers/PropostaController.php:245` e `:365`
- `isset($user_id_parceiro->user_id) == Auth::user()->id` vira `true == 42` → `true` sempre que a proposta tem parceiro.
- Correção: Policy.
```php
// ProposalPolicy / PropostaPolicy
public function update(User $user, Proposta $proposta): bool
{
    return $user->infoUser->acesso_id === 1
        || $proposta->criador_id === $user->id
        || ParceiroProposta::where('proposta_id', $proposta->id)->where('user_id', $user->id)->exists();
}
// controller
$this->authorize('update', $proposta);
```

**C3. Endpoints sem autorização/ownership (IDOR)** — `PropostaController::imprimirInformativo/imprimirAcompanhamento/imprimirRecibo*` (`:694-889`), `ArquivoController` inteiro, `PagamentosTaxaController::update` (`:126`), `GeraPropostaController`, `EmolumentosPropostaController`.
- Correção: `$this->authorize('view', $proposta)` / `manageFinancial` em cada ação; `update` do cliente só se ele for proponente da proposta.

**C4. Senha previsível enviada em texto por e-mail** — `app/Services/PropostaService.php:88,111` (também `GeraPropostaService`, `ProponenteService::insert`)
- `id . '#' . rand(10,100) . date('s')` → ~5 mil combinações, sabendo o id da proposta.
- Correção: criar o usuário com `Str::password()` descartável e enviar link de redefinição (`Password::broker()->createToken($user)`), nunca a senha.

**C5. Documentos pessoais no disco `public`** — `app/Repositories/ArquivoRepository.php:28-29` e `PagamentosTaxaRepository`
- RG/CNH, IRPF, CTPS ficam acessíveis a qualquer um com a URL (`/storage/arquivos/propostas/proposta-0X/...`), e o caminho é previsível.
- Correção: `->store("proposals/{$id}", 'local')` + rota de download com `authorize` e `Storage::download()`; migrar os arquivos existentes.

**C6. Excluir proposta apaga os usuários dos proponentes** — `app/Services/PropostaService.php:194-196`
- Um proponente reaproveitado em outra proposta perde o acesso.
- Correção: remover `deleteAllById`; no máximo desvincular ou excluir o usuário só se ele não tiver outras propostas.

**C7. Mass assignment com `$request->all()` + `id` no `$fillable`** — `PropostaRepository.php:81`, `TimelineRepository.php:54`, `EmpreendimentoPropostaRepository.php:45`, `app/Models/Proposta.php:18`
- Dá para enviar `id`, `criador_id`, `concluido`, `etapa_atual`, `proposta_id` no POST.
- Correção: remover `id/created_at/updated_at` do `$fillable` e usar `$request->validated()` via Form Request.

## 🟠 Alto
**H1. Itens somem na aba Pagamentos e no recibo** — `PropostaController.php:361` e `:836`
- `Eloquent\Collection::merge()` indexa por chave primária (confirmado em `vendor/.../Eloquent/Collection.php`); `EmolumentoPagamentoTaxa#5` e `PagamentosTaxa#5` colidem e um sobrescreve o outro.
- Correção imediata: `$emolumentoPagamentoTaxa->toBase()->merge($pagamentosTaxa)` (Support\Collection concatena). Definitiva: tabela única (PRD A4).

**H2. Listagem do perfil Vendedor quebra** — `app/Repositories/PropostaRepository.php:353`
- `where('propostas.vendedor_id', $id)`: a coluna não existe (não há migration que a crie) → SQL error.
- Correção: `->whereExists(fn ($q) => $q->from('proposta_vendedores')->whereColumn('proposta_id','propostas.id')->where('vendedor_id', $id))`.

**H3. Validação do update nunca roda** — `PropostaController.php:432`
- `if (!$proponente)`: `findByPropostaId` retorna Collection (sempre truthy). Além disso `$proposta === null` é checado **depois** de `$proposta->criador_id` (`:420-426`).
- Correção: Form Request `UpdatePropostaRequest` e route model binding (`Proposta $proposta`).

**H4. Upload de comprovante pelo cliente é código morto** — `PagamentosTaxaController.php:136-159`
- Os dois ramos do `if/else` retornam; `pagamentosTaxaService->update()` (`:159`) nunca é alcançado. E `$pagamentosTaxa->valor !== $valorPagamento` compara string com float (sempre `true`).
- Correção: separar em duas ações (`updateAmount`, `uploadProof`) com Form Requests próprios.

**H5. E-mails síncronos dentro da transação** — `PropostaService.php:111,117`, `GeraPropostaService`
- SMTP lento segura o lock; falha de e-mail faz rollback da proposta.
- Correção: `Mail::to(...)->queue(...)` + `DB::afterCommit(...)` (ou `ShouldQueueAfterCommit`).

**H6. Alerta de prazo vai para e-mail fixo e sem nome da etapa** — `app/Console/Commands/AlertaEtapaDaTimelineScheduler.php:69,86`
- Destinatário hardcoded `eduardo@plataformanet.com.br`; `$p->etapa_nome` não existe (alias é `nome_etapa`).
- Correção: usar `$emailCriador`/`$emailUser` e `$p->nome_etapa`.

**H7. Listagem carrega todas as propostas em memória** — `PropostaRepository.php:298-360` *(a confirmar volume com EXPLAIN)*
- `chunk()` + `merge()` junta tudo; o JOIN com `timelines` multiplica linhas por nº de etapas e depois `unique('id')` em PHP; DataTables faz paginação no cliente.
- Correção: `paginate()` no servidor, filtrar `timelines.etapa_atual = 1` no JOIN (não no `when`), índice em `timelines(proposta_id, etapa_atual)`.

**H8. Dinheiro em `double`** — `create_propostas_table` (`valor_entrada`, `valor_financiamento`, `valor_despesas`, `valor_prestacao_pretendida`, `valor_taxa_doc_a_financiar`, `renda_info`), `pagamentos_taxas.valor`, `emolumento_pagamento_e_taxa.subtotal`, `emolumentos.resultado_itbi`
- Correção: migration `->decimal(..., 12, 2)->change()`.

## 🟡 Médio
- **M1.** `ProponenteService::insertAll` (`:85-123`) funciona por acidente: na 1ª iteração cria **todos** os proponentes novos; se o último da sessão já existir, `$user = []` e ninguém recebe o e-mail de cadastro. `ProponenteRepository::insertAll` usa `$proponente` possivelmente indefinido. → Iterar uma vez, criando/anexando cada proponente individualmente.
- **M2.** Wizard de proponentes/vendedores guardado na sessão (driver `file`) via 6 rotas AJAX e confiando em `arrayObjetos` vindo do cliente. → Estado no front, enviado junto no `store`.
- **M3.** `ArquivoRepository::documentsSalesman` (`:217`) retorna na 1ª iteração e compara por posição → resultado errado. → `collect($required)->diff($documents->pluck('tipo_doc'))->isEmpty()`.
- **M4.** `edit()` `PropostaController.php:336`: `isset(...)` passa **bool** como `$empreendimento_id`; funciona por coincidência.
- **M5.** `Arquivo::rules` `max:300000` (300 MB) vs mensagem “3MB”.
- **M6.** `data_fin` gravado com `now()` na criação (`PropostaRepository.php:65`) e nunca atualizado; a data real vem da última timeline. → Setar ao finalizar.
- **M7.** `TimelineRepository::nextStep` usa `id >` em vez da `ordem` da etapa.
- **M8.** Números mágicos: status 1–6, acesso 1–6, contrato 4, `tabela_id` 0 → enums.
- **M9.** `findJoinById` retorna Collection e os controllers fazem `foreach` para pegar o item; usar `first()`/route model binding.
- **M10.** 7 métodos `news/inProgress/awaiting/...` e 6 `countProposal*` idênticos; `switch` sem `default` retorna `null`. → Um método com `ProposalStatus` e um `groupBy` para contadores.
- **M11.** Sem FK/índice em `parceiro_propostas.user_id`, `proponente_propostas.proponente_id`, `proposta_vendedores.vendedor_id` (FK removida), `recibos.*`, `propostas.criador_id/analista_id/contrato_id` *(confirmar índices com Database Schema)*.
- **M12.** `GeraPropostaService::getTotalEmolumento` filtra `'emolumento'` minúsculo contra valor `'Emolumento'` — só funciona por collation case-insensitive.
- **M13.** `PropostaPolicy` e `StatusPropostaController` vazios — remover ou implementar.

## 🟢 Baixo / Nit
Controller com 19 dependências no construtor (dividir por responsabilidade); `dd()`/código comentado espalhado; `rules()/feedback()` nos Models em vez de Form Requests; `Utils` com métodos estáticos e de instância misturados; SQL em `@php` na view (`tabela_lista_propostas.blade.php:7-8` instancia repositórios); typo `utiil_ir`; `ini_set('max_execution_time')` em controller (mover PDF grande para Job).

## Próximos passos (prioridade)
1. C1 + C2 + C3: middleware de papel real + `PropostaPolicy` aplicada em todas as ações (inclui PDFs e arquivos).
2. C4 + C5: link de definição de senha; mover documentos para disco privado com download autorizado.
3. C6 + C7 + H1 + H2: parar de excluir usuários, remover mass assignment, corrigir `merge` e filtro de vendedor.
4. H5–H8: e-mails em fila após commit, alerta de prazo, paginação server-side, `decimal` para dinheiro.

