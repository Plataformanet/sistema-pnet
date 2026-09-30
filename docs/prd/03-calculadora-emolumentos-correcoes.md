# Correções do módulo Calculadora de Emolumentos — calculadora, ITBI, vínculo com proposta e orçamento

Code review do módulo como ele está **neste repositório** (Laravel 8.83 / PHP 8.1 / MySQL), cobrindo:
`CalculadoraEmolumentos{,RegistroGeral,RegistroDeCompra,RegistroAverbacao}Controller`,
`EmolumentosPropostaController`, `OrcamentoController`, `GeraPropostaController`,
`CalculadoraEmolumentosService`, `EmolumentoPropostaService`, `OrcamentoService`, `GeraPropostaService`,
os Form Requests `CalculadoraRegistro*Request` / `EmolumentoRequest` / `Orcamento*Request`,
os models `Emolumento`, `EmolumentoPagamentoTaxa`, `Orcamento`, `Servico`, `ItbiModulo`, `ItbiModulo02`,
`ItbiMunicipio`, `Proposta`, as views `resources/views/app/calculadora/**` e `resources/views/app/orcamento/**`
e o JS `public/js/calculadora-emolumentos/**`.

São **27 correções numeradas + nits**, em ordem de severidade. Cada uma tem sintoma, causa, como
localizar e o patch (antes → depois).

> **Este documento só descreve.** Nada foi alterado no código. Para aplicar, use a skill
> `laravel-refactoring-senior` ("aplique as correções do docs/prd/03-calculadora-emolumentos-correcoes.md").
> A reimplementação do módulo em outro sistema está em
> [`03-calculadora-emolumentos.md`](03-calculadora-emolumentos.md), que já nasce sem estes defeitos.

> **Não use números de linha.** Localize pelos trechos citados (`grep`) e pelos nomes dos símbolos.

> **Aplique os patches; não reescreva os arquivos inteiros.** Os trechos "depois" mostram só o que muda.

---

## Como os achados foram verificados

A Laravel Boost MCP não estava disponível nesta sessão; a verificação foi feita diretamente:

| Verificação | Resultado |
|---|---|
| `SHOW CREATE TABLE emolumentos` no MySQL do Sail | `validade date NOT NULL`, sem default |
| `SHOW CREATE TABLE emolumento_pagamento_e_taxa` | `subtotal double(11,2) NOT NULL` |
| `config/database.php` | `'strict' => true` (MySQL rejeita string em coluna numérica e NOT NULL sem valor) |
| `php artisan tinker`: `method_exists(Proposta::class, 'servicos')` | `false` |
| `php artisan tinker`: `'validade'` em `(new Orcamento)->getFillable()` | `false` |
| `vendor/.../Http/Client/PendingRequest.php` (8.83) | `ConnectException` do Guzzle é relançada como `Illuminate\Http\Client\ConnectionException`; não existe `connectTimeout()`; `retry()` lança exceção em resposta não-2xx |
| `git log -S` com o token da API | presente desde o commit `7ec1572` |
| Contagens locais | 4.102 propostas, 1 emolumento, 0 linhas em `emolumento_pagamento_e_taxa`, 1 alíquota de ITBI (São Paulo) |
| `storage/logs/laravel.log` | nenhuma ocorrência dos erros abaixo — os fluxos 3 a 6 **não foram exercitados localmente**; o diagnóstico vem do código + schema, não de reprodução em runtime |

---

## Resumo

O cálculo em si (chamada à API + ITBI) funciona para o caminho feliz, mas **os dois fluxos que gravam
dados estão quebrados**: "Vincular Proposta" falha sempre no INSERT (item 3), e "Gerar Proposta" a
partir de orçamento falha quando o orçamento tem serviço (item 5) ou quando o CPF já existe (item 6).
Somado a isso, o token da API está no código e no histórico do git (item 1) e a conversão em proposta é
um `GET` sem idempotência (item 7). A primeira coisa a fazer é **rotacionar o token**; a segunda,
destravar os fluxos de gravação (3 a 6).

## O que está bom ✅

- Rotas do módulo todas atrás de `acesso:admin` — não há exposição a Cliente/Parceiro.
- Escritas múltiplas já estão em `DB::transaction` (`EmolumentoPropostaService`, `OrcamentoService`, `GeraPropostaService`).
- FormRequests com mensagens em pt-BR e normalização em `prepareForValidation` nos cadastros de ITBI e serviços.
- Falha de rede na API é tratada com log e mensagem amigável (a intenção é boa — o item 10 mostra por que a exceção capturada é a errada).
- FKs com `constrained()` e `onDelete('cascade')` nas tabelas novas.

---

## 🔴 Crítico

### 1. Token da API hardcoded e no histórico do git

- **Sintoma:** qualquer pessoa com acesso ao repositório (ou a um clone antigo) usa a cota da calculadora em nome da empresa.
- **Causa:** o Bearer token está literal no service e foi commitado em `7ec1572`.
- **Como localizar:** `grep -n "withToken" app/Services/CalculadoraEmolumentosService.php`
- **Correção:**
  1. **Pedir ao fornecedor um token novo e revogar o atual.** Remover do código não basta: ele continua no histórico.
  2. Mover para configuração:

```php
// config/services.php — acrescentar
'calculadora_emolumentos' => [
    'url' => env('CALCULADORA_EMOLUMENTOS_URL', 'https://calculadora.registrodeimoveis.org.br/api/calculate'),
    'token' => env('CALCULADORA_EMOLUMENTOS_TOKEN'),
    'timeout' => (int) env('CALCULADORA_EMOLUMENTOS_TIMEOUT', 15),
],
```

```dotenv
# .env.example — acrescentar (sem valor)
CALCULADORA_EMOLUMENTOS_URL=
CALCULADORA_EMOLUMENTOS_TOKEN=
CALCULADORA_EMOLUMENTOS_TIMEOUT=15
```

  3. O patch do service está no item 2. Em produção, rodar `php artisan config:cache` depois de preencher o `.env`.

### 2. TLS desligado na chamada à API (`verify => false`)

- **Sintoma:** a requisição aceita qualquer certificado; um intermediário na rede lê o token e pode forjar o resultado do cálculo.
- **Causa:** `->withOptions(['verify' => false])`.
- **Como localizar:** `grep -rn "'verify' => false" app/`
- **Correção** (Laravel 8: não existe `connectTimeout()`; use a opção do Guzzle):

```php
// antes
return Http::accept('application/json')
    ->withToken('75|...')
    ->withOptions(['verify' => false])
    ->post('https://calculadora.registrodeimoveis.org.br/api/calculate', $data);

// depois
return Http::acceptJson()
    ->withToken(config('services.calculadora_emolumentos.token'))
    ->timeout(config('services.calculadora_emolumentos.timeout'))
    ->withOptions(['connect_timeout' => 5])
    ->post(config('services.calculadora_emolumentos.url'), $data);
```

Se o servidor de produção falhar na verificação, o problema é o bundle de CAs do PHP/cURL do servidor
(`curl.cainfo` / `openssl.cafile`) — corrija lá, não no código. **Não use `retry()` aqui no Laravel 8**:
nesta versão ele lança `RequestException` em qualquer resposta não-2xx, o que quebraria a leitura de
`errorMessage` do item 10.

### 3. "Vincular Proposta" falha sempre — `validade` NOT NULL nunca preenchida

- **Sintoma:** clicar em "Gerar" na tela "Vincular emolumento para a proposta" resulta em erro 500
  (`SQLSTATE[HY000]: General error: 1364 Field 'validade' doesn't have a default value`).
- **Causa:** `emolumentos.validade` é `date NOT NULL` (confirmado no banco). O orçamento envia a validade,
  mas o vínculo direto com proposta não tem esse conceito: o campo está comentado no `EmolumentoRequest`
  e `EmolumentoPropostaService::store` faz `Emolumento::create($data)` sem ele.
- **Como localizar:** `grep -n "validade" app/Http/Requests/EmolumentoRequest.php database/migrations/*create_emolumentos*`
- **Correção:** a validade é do **orçamento**, não do emolumento vinculado a proposta. Torne a coluna
  nullable (o projeto já tem `doctrine/dbal ^3.3`, exigido pelo `->change()` no Laravel 8):

```php
// database/migrations/AAAA_MM_DD_HHMMSS_alter_table_emolumentos_validade_nullable.php
public function up(): void
{
    Schema::table('emolumentos', function (Blueprint $table) {
        $table->date('validade')->nullable()->change();
    });
}

public function down(): void
{
    // Falha se já houver linhas com validade nula (emolumentos vinculados direto à proposta).
    Schema::table('emolumentos', function (Blueprint $table) {
        $table->date('validade')->nullable(false)->change();
    });
}
```

As telas de orçamento continuam exigindo a validade (`OrcamentoCreateRequest` já tem `'validade' => ['required']`).
Remova as linhas comentadas de `validade` do `EmolumentoRequest`.

### 4. Serviços gravados como texto formatado (`"1.500,00"`) em coluna `double`

- **Sintoma:** mesmo com o item 3 corrigido, vincular com **qualquer serviço marcado** falha com
  `Data truncated for column 'subtotal'` (MySQL strict).
- **Causa:** a view manda `servicos[i][valor]` já formatado por `format_coin_real` e `novo_valor` com
  máscara; `EmolumentoPropostaService::store` grava direto em `subtotal`. O `OrcamentoService` faz a
  mesma operação **com** `Utils::format_coin_sql` — aqui ela foi esquecida. Além disso, nome, descrição
  e valor do serviço vêm de hidden inputs, então o valor "de tabela" é adulterável.
- **Como localizar:** `grep -n "novo_valor" app/Services/EmolumentoPropostaService.php`
- **Correção:** buscar o serviço no banco pelo id e normalizar só o valor negociado:

```php
// antes
if(isset($data['servicos'])){
    foreach ($data['servicos'] as $servico) {
        if (isset($servico['id'])) {
            array_push($arrayDeEmolumentos, ['tipo_emolumento' => 'Servico', 'descricao' => $servico['nome'], 'descricao_servico' => $servico['descricao_servico'], 'gera_recibo' => $servico['gera_recibo'], 'subtotal' => $servico['novo_valor'] !== null ? $servico['novo_valor'] : $servico['valor']]);
        }
    }
}

// depois
$selecionados = collect($data['servicos'] ?? [])->filter(fn ($servico) => isset($servico['id']));
$servicos = Servico::whereIn('id', $selecionados->pluck('id'))->get()->keyBy('id');

foreach ($selecionados as $selecionado) {
    $servico = $servicos->get($selecionado['id']);

    if (!$servico) {
        continue;
    }

    $arrayDeEmolumentos[] = [
        'tipo_emolumento' => 'Servico',
        'descricao' => $servico->nome,
        'descricao_servico' => $servico->descricao_servico,
        'gera_recibo' => $servico->gera_recibo,
        'subtotal' => $selecionado['novo_valor'] !== null
            ? Utils::format_coin_sql($selecionado['novo_valor'])
            : $servico->valor,
    ];
}
```

(`novo_valor` vazio chega como `null` por causa do `ConvertEmptyStringsToNull` do Kernel.) Com isso, os
hidden inputs `servicos[i][nome|descricao_servico|gera_recibo|valor]` da view
`gera-emolumento-proposta.blade.php` deixam de ser necessários. As regras de validação estão no item 11.

### 5. "Gerar Proposta" quebra quando o orçamento tem serviço — `Proposta::servicos()` não existe

- **Sintoma:** em um orçamento com pelo menos um serviço, "Gerar Proposta" dá erro 500
  (`BadMethodCallException: Call to undefined method ... Builder::servicos()`) e nada é gravado.
- **Causa:** `GeraPropostaService::store` chama `$proposta->servicos()->attach(...)`, mas o model
  `Proposta` só tem `rules()` e `feedback()` — confirmado com `method_exists` no tinker. A tabela pivô
  `proposta_servico` existe (migration `2025_04_01_104719`), só falta a relação.
- **Como localizar:** `grep -n "servicos()" app/Services/GeraPropostaService.php app/Models/Proposta.php`
- **Correção:**

```php
// app/Models/Proposta.php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

public function servicos(): BelongsToMany
{
    return $this->belongsToMany(Servico::class, 'proposta_servico', 'proposta_id', 'servico_id');
}
```

`proposta_servico` não tem `timestamps`, então **não** use `->withTimestamps()`. Troque também o loop
de `attach` por uma chamada só: `$proposta->servicos()->attach(collect($data['servicos'])->pluck('id'));`

### 6. "Gerar Proposta" com CPF já cadastrado: `$user` indefinido

- **Sintoma:** se já existe proponente com o CPF do orçamento, erro 500 (`Undefined variable $user`).
- **Causa:** `$user` só é criado dentro de `if (!$existeProponente)`, mas é usado incondicionalmente em
  `'user_id' => $user->id`. Além disso, um usuário com o mesmo e-mail e outro CPF faz o `User::create`
  estourar a unique de `users.email`.
- **Como localizar:** `grep -n "existeProponente\|\$user->id" app/Services/GeraPropostaService.php`
- **Correção:** resolver o usuário explicitamente (por CPF no `info_users`, depois por e-mail) e só criar se não houver:

```php
// antes
$existeProponente = Proponente::where('cpf', $data['cpf'])->exists();

if (!$existeProponente) {
    $user = User::create([...]);
    InfoUser::create([...]);
}

// depois
$user = optional(InfoUser::where('cpf_cnpj', $data['cpf'])->first())->user
    ?? User::where('email', $data['email'])->first();

$usuarioCriado = $user === null;

if ($usuarioCriado) {
    $password = Str::random(12);   // ver item 8

    $user = User::create([
        'name' => $data['nome'],
        'email' => $data['email'],
        'password' => Hash::make($password),
    ]);

    InfoUser::create([
        'user_id' => $user->id,
        // ... demais campos iguais aos atuais
    ]);
}
```

E no final troque `if (!$existeProponente)` por `if ($usuarioCriado)` na escolha do e-mail.

### 7. Conversão orçamento → proposta via `GET`, sem idempotência

- **Sintoma:** duplo clique, "voltar" + recarregar, pré-carregamento de link pelo navegador ou abrir o
  link em outra aba **criam outra proposta** para o mesmo orçamento. Orçamento expirado também converte,
  bastando acessar a URL.
- **Causa:** rota `GET /gera-proposta/{id}`; a única trava é a view esconder o botão
  (`!$proposta_id && !$data_validade`). O `disabled` que `public/js/utils/utils.js` põe no `<a>` não
  impede a navegação.
- **Como localizar:** `grep -n "gera-proposta" routes/web.php resources/views/app/calculadora/_components/resultado-calculo.blade.php`
- **Correção:**

```php
// routes/web.php — antes
Route::middleware('acesso:admin')->get('/gera-proposta/{id}', 'App\Http\Controllers\GeraPropostaController')->name('gera-proposta');
// depois
Route::middleware('acesso:admin')->post('/gera-proposta/{id}', 'App\Http\Controllers\GeraPropostaController')->name('gera-proposta');
```

```blade
{{-- resultado-calculo.blade.php — antes --}}
<a href="{{ route('gera-proposta', ['id' => ...]) }}" @class('btn btn__calcular px-5 me-2') id="btn-gerar-proposta">Gerar Proposta</a>
{{-- depois --}}
<form action="{{ route('gera-proposta', ['id' => $orcamento_id]) }}" method="post" class="d-inline"
      onsubmit="this.querySelector('button').disabled = true">
    @csrf
    <button type="submit" class="btn btn__calcular px-5 me-2">Gerar Proposta</button>
</form>
```

E a checagem no servidor, **no início** da transação de `GeraPropostaService::store`, com trava de linha:

```php
$emolumento = Emolumento::where('orcamento_id', $data['id'])->lockForUpdate()->firstOrFail();

if ($emolumento->proposta_id !== null) {
    throw new OrcamentoNaoConversivelException('Já foi gerada uma proposta para este orçamento.');
}

if ($emolumento->validade !== null && $emolumento->validade < now()->toDateString()) {
    throw new OrcamentoNaoConversivelException('Orçamento com data de validade expirada, gere um novo.');
}
```

`OrcamentoNaoConversivelException` em `app/Exceptions/` (padrão dos módulos novos); o
`GeraPropostaController` captura e devolve `redirect()->back()->with('msg_erro', $e->getMessage())`.
No fim do service, reutilize esse `$emolumento` em vez de buscá-lo de novo.

### 8. Senha previsível enviada por e-mail; e-mail e PDF dentro da transação

- **Sintoma:** a senha do cliente criado é `{primeiros 3 dígitos do CPF}#{10..100}{segundo atual}` —
  quem conhece o CPF tem ~5.500 combinações para testar. E, se o SMTP falhar, a proposta inteira é
  desfeita (o `catch` relança como `Exception`), mas o PDF já foi escrito no disco (arquivo órfão).
- **Causa:** `$password = $cpf[0] . '#' . $rand . date('s');` com `rand()`; `Mail::send` e `Storage::put`
  dentro do `DB::transaction`.
- **Como localizar:** `grep -n "rand(\|Mail::to\|Storage::put" app/Services/GeraPropostaService.php`
- **Correção mínima:**
  - senha com `Str::random(12)` (item 6);
  - efeitos externos depois do commit, sem derrubar a proposta já criada:

```php
// dentro da transação, no lugar dos try/catch de e-mail
DB::afterCommit(function () use ($data, $password, $usuarioCriado) {
    try {
        $usuarioCriado
            ? Mail::to($data['email'])->send(new PropostaCadastroMail($data['nome'], $data['email'], $password))
            : Mail::to($data['email'])->send(new PropostaCadastroDeUsuarioCadastradoMail($data['nome']));
    } catch (\Throwable $th) {
        report($th);
    }
});
```

  O mesmo vale para o `Storage::put` do PDF: grave o `Arquivo` dentro da transação e escreva o arquivo no `afterCommit`.
- **Correção ideal (próxima iteração):** não enviar senha. Criar o usuário com senha aleatória descartada e
  mandar link de definição de senha (`Password::broker()->createToken($user)`), o que exige ajustar o
  template de `PropostaCadastroMail`.

---

## 🟠 Alto

### 9. O resultado do cálculo passa pelo navegador e é gravado sem revalidação

- **Sintoma:** os valores dos emolumentos gravados na proposta/orçamento podem ser editados pelo
  usuário (query string → hidden input → banco). Cálculos com muitos atos geram URLs enormes, com risco
  de `414 URI Too Long` em proxies.
- **Causa:** `resultado-calculo.blade.php` monta `route('gerar-emolumento-proposta', ['resultado_calculo' => $resultado_calculo['result'], ...])`
  e `route('cria-orcamento', ...)`; o controller faz `json_encode($request->get('resultado_calculo'))` para
  um hidden input; o `store` faz `json_decode` e grava. O check `=== "null"` é o único controle.
- **Como localizar:** `grep -rn "resultado_calculo' =>\|resultado_emolumento" resources/views/app/calculadora app/Http/Controllers`
- **Correção:** guardar o resultado no servidor e trafegar só uma chave. Neste projeto (sessão em
  `file`), a sessão resolve sem migration:

```php
// nos 3 métodos calculo(), depois de obter $response e $itbi
$chave = (string) Str::uuid();
session()->put("calculos.{$chave}", [
    'resultado' => $response->json('result'),
    'itbi' => $itbi ?? null,
]);
// e passar 'calculo_id' => $chave para a view
```

```blade
{{-- resultado-calculo.blade.php --}}
<a href="{{ route('gerar-emolumento-proposta', ['calculo' => $calculo_id]) }}" ...>Vincular Proposta</a>
<a href="{{ route('cria-orcamento', ['calculo' => $calculo_id]) }}" ...>Gerar Orçamento</a>
```

```php
// EmolumentosPropostaController@store / OrcamentoController@store
$calculo = session()->get("calculos.{$request->validated('calculo')}");

if (!$calculo) {
    return redirect()->route('calculadora-de-emolumentos')
        ->with('msg_erro', 'O cálculo expirou, por favor gere outro!');
}

$data['resultado_emolumento'] = json_encode($calculo['resultado']);
$data['resultado_itbi'] = $calculo['itbi'] ?? 0;
```

Os hidden inputs `resultado_emolumento`/`resultado_itbi` saem das views; os Form Requests trocam
`resultado_emolumento` por `'calculo' => ['required', 'uuid']`. (Se a sessão de produção for `cookie`,
use uma tabela `calculos` em vez da sessão — o JSON passa de 4 KB.)

### 10. Tratamento de erro da API: exceção errada, `failed()` ignorado e mensagem "1"

- **Sintoma:** (a) com a API fora do ar, aparece "Ocorreu um erro interno no servidor" em vez de
  "servidor da calculadora está fora do ar"; (b) na averbação, um erro da API aparece como a mensagem
  **"1"**; (c) resposta 401/500 sem `errorMessage` renderiza a página com "Nenhum registro encontrado"
  em vez de avisar o erro.
- **Causa:** (a) os controllers capturam `GuzzleHttp\Exception\ConnectException`, mas o Laravel 8 a
  relança como `Illuminate\Http\Client\ConnectionException` (confirmado em `PendingRequest.php`) — o
  `catch` específico nunca dispara; (b) `$response->json('errorMessage') || $response->json('message')`
  é uma expressão booleana; (c) nenhum controller olha `$response->failed()`.
- **Como localizar:** `grep -n "ConnectException\|errorMessage" app/Http/Controllers/CalculadoraEmolumentos*.php`
- **Correção** (nos 3 controllers; ver item 15 para unificá-los):

```php
// antes
use GuzzleHttp\Exception\ConnectException;
...
} catch(ConnectException $e){
...
if ($response->json('errorMessage') || $response->json('message')) {
    return redirect()->back()->with('msg_erro', $response->json('errorMessage') || $response->json('message'));
}

// depois
use Illuminate\Http\Client\ConnectionException;
...
} catch (ConnectionException $e) {
...
$mensagemApi = $response->json('errorMessage') ?? $response->json('message');

if ($mensagemApi) {
    return redirect()->back()->withInput()->with('msg_erro', $mensagemApi);
}

if ($response->failed() || !$response->json('result.atos')) {
    Log::error('Calculadora de emolumentos respondeu erro', ['status' => $response->status()]);

    return redirect()->back()->withInput()->with('msg_erro', 'Ocorreu um erro ao processar sua solicitação, servidor da calculadora está fora do ar. Por favor, tente novamente mais tarde.');
}
```

Ainda nos controllers de compra e registro geral, chame `calculaITBI()` **depois** das checagens da API (hoje é calculado antes e descartado quando a API falha).

### 11. Campos da view e do Form Request não conversam

- **Sintoma:** o `required_if` do ITBI nunca dispara; os serviços chegam ao service sem validação alguma.
- **Causa:** `EmolumentoRequest` e `OrcamentoCreateRequest` validam `verifica_tipo_orcamento`, mas as
  views enviam `verifica_registro_compra`; o `EmolumentoRequest` valida `servico` (singular), a view envia
  `servicos`; e o controller passa `$request->except('_token')` (tudo o que veio do formulário) para o service.
- **Como localizar:** `grep -rn "verifica_tipo_orcamento\|verifica_registro_compra\|except('_token')" app resources/views`
- **Correção:**

```php
// EmolumentoRequest::rules() — depois
return [
    'proposta_id' => ['required', 'integer', 'exists:propostas,id'],
    'calculo' => ['required', 'uuid'],                       // item 9
    'servicos' => ['nullable', 'array'],
    'servicos.*.id' => ['nullable', 'integer', 'exists:servicos,id'],
    'servicos.*.novo_valor' => ['nullable', 'string'],
];
```

```php
// EmolumentosPropostaController@store — antes
$result = $this->emolumentoPropostaService->store($request->except('_token'));
// depois
$result = $this->emolumentoPropostaService->store($request->validated());
```

Em `OrcamentoCreateRequest`, troque `'servicos' => ['nullable']` pelas mesmas três regras de `servicos`
e remova `verifica_tipo_orcamento` (com o item 9, a presença do ITBI vem do cálculo guardado, não de um hidden).

### 12. `calculaITBI`: varredura completa, casamento por nome e faixas com limite exclusivo

- **Sintoma:** (a) cada cálculo carrega **todas** as alíquotas e **todas** as faixas do banco; (b) um
  município cuja grafia no cadastro difere da API do IBGE ("Sao Paulo", espaço extra) dá "Município não
  encontrado"; (c) imóvel com valor exatamente igual ao limite de uma faixa (ex.: R$ 300.000,00 numa
  faixa 300.000–400.000) não calcula ITBI em São Bernardo; (d) a alíquota de 2,5% de São Bernardo em
  SFI/registro geral está no código.
- **Causa:** `ItbiModulo::with(...)->get()` + `foreach` comparando `$data['municipio']['nome'] === $request->get('nome_do_municipio')`;
  `$valorImovel > min && $valorImovel < max`; `$valorImovel * 2.5 / 100`. O `for` externo com
  `array_chunk($arr, count($arr))` percorre a mesma lista duas vezes sem efeito.
- **Como localizar:** `grep -n "function calculaITBI" -A 140 app/Services/CalculadoraEmolumentosService.php`
- **Correção** (incremental, sem mudar o schema):

```php
// antes
$modulo = ItbiModulo::with('municipio:id,nome')->get()->toArray();
$modulo02 = ItbiModulo02::with('municipio:id,nome')->get()->toArray();

// depois — só o município consultado
$municipio = ItbiMunicipio::where('nome', $request->get('nome_do_municipio'))->first();

if (!$municipio) {
    return false;
}

$modulo = ItbiModulo::where('itbi_municipio_id', $municipio->id)->get()->toArray();
$modulo02 = ItbiModulo02::where('itbi_municipio_id', $municipio->id)->get()->toArray();
```

```php
// faixas — antes
for ($i = 0; $i < count($arrayValoresImoveis); $i++) {
    foreach (array_chunk($arrayValoresImoveis, count($arrayValoresImoveis)) as $data) {
        ...
        if ($valorImovel > $data[$i]['valor_imovel_min'] && $valorImovel < $data[$i]['valor_imovel_max']) {

// depois
$faixa = collect($arrayValoresImoveis)->first(
    fn ($faixa) => $valorImovel >= $faixa['valor_imovel_min'] && $valorImovel <= $faixa['valor_imovel_max']
);

if ($faixa) {
    $itbi = $valorImovel * $faixa['porcentagem'] / 100 - $faixa['desconto'];
}
```

Com o limite inclusivo, valide no `StoreItbiModulo02Request`/`UpdateItbiModulo02Request` que as faixas
de um município não se sobrepõem. **Próximo passo estrutural** (não incremental): adicionar
`codigo_ibge` em `itbi_municipios` e casar por ele (a view já envia `codigo_municipio`), e mover o 2,5%
para uma coluna do cadastro. O PRD descreve esse desenho.

### 13. Tela "Vincular Proposta" carrega todas as propostas e filtra em PHP

- **Sintoma:** a página fica mais lenta a cada proposta nova (4.102 na base local) e o `<select>` fica inutilizável.
- **Causa:** `Proposta::select('id')->get()` + `Emolumento::select('proposta_id')->get()` e depois
  `filter(... !$proposta_id->contains(...))` — O(n·m) em memória.
- **Como localizar:** `grep -n "contains('proposta_id'" app/Http/Controllers/EmolumentosPropostaController.php`
- **Correção:**

```php
// antes
$propostas = Proposta::select('id')->orderBy('id', 'desc')->get();
$proposta_id = Emolumento::select('proposta_id')->get();
$propostas = $propostas->collect()->filter(function ($proposta) use ($proposta_id) {
    return !$proposta_id->contains('proposta_id', $proposta->id);
});

// depois
$propostas = Proposta::select('id')
    ->whereNotIn('id', Emolumento::select('proposta_id')->whereNotNull('proposta_id'))
    ->orderByDesc('id')
    ->get();
```

`whereNotNull` é obrigatório: `NOT IN` com um `NULL` na subquery não devolve nada — e os emolumentos de
orçamento ainda não convertidos têm `proposta_id` nulo. `emolumentos.proposta_id` já tem índice (FK).
Para volume maior, troque o `<select>` por busca AJAX paginada.

### 14. Editar orçamento descarta a nova validade

- **Sintoma:** o usuário altera a validade, recebe "Orçamento atualizado com sucesso!" e a data continua a antiga.
- **Causa:** `validade` não está no `$fillable` de `Orcamento` (confirmado no tinker) — ela mora em
  `emolumentos` — e o update do emolumento está comentado em `OrcamentoService::update`.
- **Como localizar:** `grep -n "emolumento()->update" app/Services/OrcamentoService.php`
- **Correção:**

```php
// OrcamentoService::update — depois de $orcamento->update($data);
$orcamento->emolumento()->update(['validade' => $data['validade']]);
```

E, em `OrcamentoUpdateRequest`, `'validade' => ['required', 'date', 'after_or_equal:today']`.
Bloqueie a edição de orçamento já convertido (mesma checagem do item 7).

### 15. Excluir orçamento convertido apaga o emolumento vinculado à proposta

- **Sintoma:** depois de gerar a proposta, excluir o orçamento some com o registro `emolumentos` daquela proposta.
- **Causa:** `Orcamento` não usa `SoftDeletes` e `emolumentos.orcamento_id` tem `ON DELETE CASCADE`.
- **Como localizar:** `grep -n "function delete" -A 3 app/Services/OrcamentoService.php`
- **Correção:**

```php
public function delete(string $id): bool
{
    $orcamento = Orcamento::with('emolumento:id,orcamento_id,proposta_id')->findOrFail($id);

    if (optional($orcamento->emolumento)->proposta_id) {
        return false;   // controller responde "Orçamento já convertido em proposta não pode ser excluído"
    }

    return $orcamento->delete();
}
```

Ajuste a mensagem de erro do `OrcamentoController@destroy` e trate `status` no `public/js/orcamento/modal-delete.js`.

### 16. JS: "Remover seleção" não remove o desconto e handlers se acumulam

- **Sintoma:** (a) o usuário remove o desconto, o botão volta a "Possui desconto?", mas o cálculo sai
  **com** o desconto; (b) depois de trocar a UF N vezes, um clique em um tipo de cálculo dispara N
  requisições.
- **Causa:** (a) em `modal-calculadora-emolumentos.js`, o handler de `.remover__selecao` não zera
  `#desconto` (e é registrado uma vez por card, dentro do `.each`); (b) em
  `lista-estados-e-municipios.js`, os `.on('click')` de `#registro-*` e o `.on('change')` de
  `#municipio` estão dentro do callback do `$.getJSON`, que roda a cada troca de UF. Cada clique ainda
  faz um `GET` AJAX da página inteira só para, em seguida, redirecionar.
- **Como localizar:** `grep -n "remover__selecao\|\.on(" public/js/calculadora-emolumentos/*.js`
- **Correção:**

```js
// modal-calculadora-emolumentos.js — fora do .each, uma vez só
$('.remover__selecao').on('click', function () {
    $('#desconto').val('');
    $('.bloco__modal--calculadora-emolumentos').removeClass('bloco__modal--calculadora-emolumentos-selected');
    $('.modal').modal('hide');
    $('.btn-desconto').text('Possui desconto?').css({ background: 'none', color: '#265E9F' });
});
```

```js
// lista-estados-e-municipios.js — registrar fora do callback e navegar direto
const tipos = { 'registro-geral': 1, 'registro-de-compra': 2, 'registro-averbacao': 3 };

$('#registro-geral, #registro-de-compra, #registro-averbacao').on('click', function (e) {
    e.preventDefault();
    const params = new URLSearchParams({
        nome_do_estado: $('#estado option:selected').text(),
        codigo_estado: $('#estado').val(),
        consulta_id: tipos[this.id],
        nome_do_municipio: $('#municipio option:selected').text(),
        codigo_municipio: $('#municipio').val(),
    });
    window.location.href = `${url}/v1/calculadora-de-emolumentos/${this.id}?${params}`;
});
```

`URLSearchParams` também resolve a falta de encoding dos nomes (hoje concatenados crus na URL).
Apague `form-calculadora-emolumentos.js`: não é carregado em lugar nenhum e aponta para `#form_calcula_desconto`, que não existe.

### 17. `extra_information` da API impresso sem sanitização (XSS)

- **Sintoma:** qualquer HTML/JS que a API devolva nesse campo roda no navegador do administrador (e vai para o e-mail/PDF).
- **Causa:** `tabela.blade.php` faz `echo $resultado_calculo['result']['extra_information'];`.
- **Como localizar:** `grep -n "extra_information" resources/views/app/calculadora/_components/tabela.blade.php`
- **Correção:**

```blade
{{-- antes --}}
@php
    echo $resultado_calculo['result']['extra_information'];
@endphp
{{-- depois --}}
{!! strip_tags($resultado_calculo['result']['extra_information'], '<p><br><strong><b><em><i><ul><ol><li>') !!}
```

`strip_tags` com lista permitida ainda deixa atributos (`onclick`) nas tags liberadas; o ideal é
sanitizar com uma biblioteca (HTMLPurifier) — decisão de dependência, confirme com o responsável
(`composer.json` espelha produção).

---

## 🟡 Médio

### 18. Três controllers de cálculo quase idênticos; `formatarNumero` trunca centavos

`CalculadoraEmolumentosRegistro{Geral,DeCompra,Averbacao}Controller` repetem o mesmo `try/catch`, a
mesma montagem de array da view e o mesmo tratamento de erro — o item 10 teve de ser aplicado três
vezes. Unifique em um controller com o tipo vindo do `consulta_id` (ou um enum `TipoCalculoEnum`
`1/2/3`, no padrão de `app/Enums/`). `CalculadoraEmolumentosService::callAPI` também duplica o array
`$data` só para incluir `desconto` — use `array_filter` ou `when`. Registre no código que
`Utils::formatarNumero` faz `(int)` e **descarta os centavos** enviados à API (intencional? confirme com o
fornecedor), enquanto o ITBI usa o valor com centavos. `use App\Models\ItbiModulo01` importa uma classe que não existe.

### 19. Regra de "explodir o resultado em linhas de custo" duplicada

O mesmo bloco (atos → `Emolumento`, taxas extras → `Emolumento`, serviços → `Servico`, ITBI → `Itbi`)
existe em `EmolumentoPropostaService::store` e em `GeraPropostaService::store`, e já divergiu (um usa
`novo_valor`, o outro `pivot.valor`). Extraia um `MontaLinhasDeCustoService::montar(array $resultado, iterable $servicos, float $itbi): array`
e use `EmolumentoPagamentoTaxa::insert()` com `created_at/updated_at` em vez de um `create()` por linha.

### 20. `updateValorPagamento` sem checagem de existência

`EmolumentoPropostaService::updateValorPagamento` faz `find()` e usa o resultado direto
(`->subtotal` em `null` → erro); o `if ($pagamentosTaxa)` depois do `save()` é sempre verdadeiro.
Use `findOrFail`, valide `valor_pagamento` num FormRequest e confira que a linha pertence à proposta
da rota. O `PagamentosTaxaController@update` que chama esse método tem código morto depois dos
`return` (o bloco `if ($pagamentosTaxa === null)` nunca executa).

### 21. `OrcamentoService`: listagem inteira em memória e `count(null)`

- `findAll()` percorre com `chunk(500)` só para juntar tudo numa coleção — é um `get()` com passos a
  mais. Use `paginate(20)` e `{{ $orcamentos->links() }}` na view.
- `findById()` devolve `toArray()`; views e PDF dependem de índices de array (`$orcamento['emolumento']['validade']`),
  o que perde casts e relações. Devolva o model.
- `update()` faz `count($data['servicos'])`; se não houver serviços cadastrados, `servicos` não vem no
  request e `count(null)` lança `TypeError` no PHP 8. Use `$data['servicos'] ?? []`.

### 22. `attach($orcamento->id, ['servico_id' => ...])` funciona por acidente

`$orcamento->servicos()->attach($orcamento->id, ['servico_id' => $servico['id'], 'valor' => ...])`
passa o **id do orçamento** como id do serviço; só dá certo porque o array de atributos sobrescreve a
chave. Escreva o que se quer dizer:

```php
$orcamento->servicos()->sync(
    collect($data['servicos'] ?? [])
        ->filter(fn ($servico) => isset($servico['id']))
        ->mapWithKeys(fn ($servico) => [
            $servico['id'] => ['valor' => Utils::format_coin_sql($servico['novo_valor'] ?? $servico['valor'])],
        ])
        ->all()
);
```

(`sync` também substitui o par `detach()` + loop de `attach` do `update`.)

### 23. Tipos de coluna

Dinheiro em `double(11,2)` (`subtotal`, `resultado_itbi`, `valor`, `valor_financiado_prefeitura`,
`desconto`) — use `decimal(12,2)`. Percentuais de ITBI em `string` — use `decimal(7,4)` (a validação
`numeric` já garante o formato). `orcamentos.banco_id` é `integer` sem FK. `tipo_emolumento` é string
livre e `getTotalEmolumento` compara com `'emolumento'` minúsculo, o que só funciona pela collation
`_ci` — crie um enum (`TipoEmolumentoEnum`) e compare com ele. Mudanças de tipo em tabela com dados
exigem migration nova (nunca editar as existentes) e janela de manutenção.

### 24. `tabela.blade.php`: regra de negócio na view

Totais somados em `@php` dentro do template, blocos inteiros duplicados para `pdf_email` true/false,
`$keyTable` indefinido se `atos` vier vazio (erro na view), e orçamento **sem serviços** não exibe o
TOTAL geral (só "Total do cálculo" + ITBI). Calcule os totais num service (ou num objeto
`ResumoCalculo`) e passe pronto para a view; trate `atos` vazio antes do `foreach`.

### 25. "Primeiro imóvel?" amarrado ao nome do município

`registro-compra.blade.php` mostra o campo só para `'São Caetano do Sul'` e `'Mauá'`, mas quem define a
regra é o cadastro do ITBI (módulo 03). Um município novo no módulo 03 calcula sem a pergunta — e
`$request->get('primeiro_imovel') == 0` trata ausência (`null`) como "Não". Passe para a view um
booleano vindo do cadastro (`ItbiModulo::where('tipo_modulo', 'modulo_03')->whereHas(...)`) e torne o
campo `required_if` nesse caso.

### 26. Nomes de rota globais

`calculo`, `calculo-registro-compra`, `calculo-averbacao`, `registro-geral`, `registro-de-compra`,
`gera-emolumento`, `gera-proposta`, `orcamento` — fáceis de colidir. Prefixe (`calculadora.calculo`,
`orcamentos.gerar-proposta`...) e atualize as views e os JS que montam URLs à mão
(`lista-estados-e-municipios.js` usa `${url}/v1/...` em vez do nome da rota).

### 27. E-mail do orçamento síncrono e com mensagem de exceção para o usuário

`enviarOrcamentoPorEmail` envia no request e concatena `$th->getMessage()` no flash (vaza detalhes de
SMTP). Faça `report($th)` e mostre uma mensagem genérica. Quando houver worker de fila (hoje
`QUEUE_CONNECTION=sync`), `InformacaoDoOrcamentoMail implements ShouldQueue`.

---

## 🟢 Baixo / Nits

IDs HTML repetidos dentro de loops (`id="checkR"`, `id="servicos"`, `id="selecione-opcao"`, e
"Editar Orçamento" com `id="btn-gerar-proposta"`); `<a href="http://">` para abrir modal (use
`<button type="button">`); `old("servicos.$key.valor")` onde deveria ser `novo_valor` em
`gera-emolumento-proposta.blade.php`; bloco `session('msg_success')` nessa mesma view nunca é acionado
(o controller redireciona com `msg`) e referencia `$proposta` fora do loop; radio SFH com
`? 'checked' : 'checked'` (sempre marcado); regra `'desconto' => ['']` nos `CalculadoraRegistro*Request`
(use `nullable|string`); `validated('resultado_emolumento') === "null"` como validação; `$itbi = $itbi = ...`;
classes mortas `OrcamentoProponente` (enum `OrcamentoProponenteEnum` inexistente), `OrcamentoProposta`
e `ItbiValorImovelDesconto` (sem tabela/uso); views vazias `servicos/create.blade.php` (o cadastro é
feito pelo formulário do `index`, mas `GET /servicos/create` continua respondendo página em branco — use
`->except(['create', 'show'])` no `Route::resource`) e `itbi_modulo_0{1..4}/show.blade.php`; `show()` vazio
nos controllers de ITBI registrado por `Route::resource`; typos
"Exigencia" (`ProtocoloRegistroEnum`), "cívil", "SUCETIVEIS" e "JUNTO A PREFEITURA" (PDF e e-mail),
`control-lable`.

---

## Próximos passos (em ordem)

1. **Rotacionar o token da API** e aplicar os itens 1 e 2 (config + TLS). É o único item com risco
   fora do sistema.
2. **Destravar a gravação:** itens 3 (migration `validade` nullable), 4 (normalizar serviços), 5
   (relação `Proposta::servicos`) e 6 (`$user`). Sem eles, "Vincular Proposta" e "Gerar Proposta" não funcionam.
3. **Blindar a conversão:** itens 7 (POST + trava + checagem) e 8 (senha, efeitos após commit).
4. **Integridade e UX do cálculo:** itens 9 (resultado no servidor), 10 (erros da API), 11 (contrato
   view ↔ request) e 16 (desconto que não sai).
5. Depois: 12 e 13 (ITBI e listagem), 14 e 15 (orçamento), e os médios conforme houver tempo.

## Verificação

Depois de aplicar, rodar:

```bash
php artisan route:list | grep -E "calculadora|orcamento|gera-proposta|emolumento"
./vendor/bin/sail artisan migrate
php artisan test
```

## Roteiro de teste manual

1. Calculadora → SP / São Paulo → Registro de compra → valores com centavos → SFH → **com** desconto
   → Calcular: tabela, ITBI e TOTAL aparecem.
2. Abrir o desconto, "Remover seleção", Calcular de novo: o total muda (item 16).
3. Trocar a UF 3 vezes e clicar num tipo: **uma** navegação só (DevTools → Network).
4. Com a API inacessível (URL errada no `.env`): mensagem "servidor da calculadora está fora do ar" (item 10).
5. "Vincular Proposta" com 2 serviços, um com valor editado: redireciona para a proposta; aba de
   pagamentos mostra atos, taxas, serviços (valor editado) e ITBI (itens 3, 4, 9).
6. "Gerar Orçamento" com serviços → editar a validade → a nova data aparece (item 14).
7. "Gerar Proposta" para um CPF **já cadastrado** e com serviços: 1 proposta criada, sem erro (itens 5, 6).
8. Repetir o POST de "Gerar Proposta" (reenviar o formulário): mensagem "Já foi gerada uma proposta"
   e nenhuma proposta nova (item 7).
9. Tentar excluir o orçamento convertido: bloqueado (item 15).
