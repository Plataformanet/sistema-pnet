# PRD — Módulo Calculadora de Emolumentos (com ITBI, Serviços e Orçamento)

> **Documento escrito para o agente que vai implementar o módulo em outro sistema.**
> Stack do destino: **Laravel 13 (PHP ≥ 8.3) + Inertia.js + Vue 3 (`<script setup>`, TypeScript) +
> Tailwind + shadcn-vue**.
> Sistema de origem: "Ágil Documentações" (Laravel 8.83 / PHP 8.1 / Blade + jQuery). A origem serve de
> **especificação de comportamento**, não de código a ser copiado. Os defeitos da origem estão
> catalogados em [`correcoes-modulo-calculadora-emolumentos.md`](correcoes-modulo-calculadora-emolumentos.md)
> e **não devem ser portados** — a seção 12 resume quais são.

---

## 1. Contexto

O módulo estima o custo de registro de um imóvel em cartório (emolumentos), soma o ITBI devido à
prefeitura e os serviços cobrados pela própria empresa, e transforma esse resultado em:

- **linhas de custo de uma proposta** (processo de financiamento/documentação já existente); ou
- **um orçamento** para um cliente, que pode ser impresso em PDF, enviado por e-mail e, depois,
  **convertido em proposta**.

O cálculo dos emolumentos **não é feito localmente**: vem de uma API externa do Registro de Imóveis.
O ITBI **é calculado localmente**, a partir de alíquotas cadastradas por município.

### Fluxo ponta a ponta

```
Escolher UF + município (IBGE)
  └─ Escolher tipo: Registro em geral | Compra e venda c/ alienação fiduciária | Averbação c/ valor econômico
       └─ Informar valores (+ desconto legal opcional, SFH/SFI, primeiro imóvel)
            └─ POST calcular → API externa (emolumentos) + ItbiCalculator (ITBI)
                 └─ Snapshot salvo em fee_calculations  ──┬─ "Vincular à proposta" → fee_estimates + proposal_cost_items
                                                          └─ "Gerar orçamento"     → quotes (+ serviços)
                                                                 ├─ Imprimir PDF / Enviar e-mail
                                                                 ├─ Editar (cliente, serviços, validade)
                                                                 └─ Converter em proposta → proposal_cost_items + fee_estimate vinculado
```

### Escopo

Entra nesta entrega:

1. Calculadora (3 tipos de cálculo) e exibição do resultado.
2. Cadastros de apoio: **municípios de ITBI**, **alíquotas de ITBI** (4 módulos de cálculo) e **serviços cobráveis**.
3. Vincular um cálculo a uma proposta existente.
4. Orçamento completo: criar, listar, ver, editar, excluir, PDF, e-mail, converter em proposta.

### O que NÃO fazer

- **Não copiar arquivos da origem.** Nomes, tabelas e estrutura mudam (seção 3). Use a origem apenas
  para tirar dúvida de comportamento.
- **Não trafegar o resultado do cálculo pelo navegador** (query string, hidden input) para depois
  gravá-lo. A origem faz isso e o valor fica adulterável. O resultado vive no servidor
  (`fee_calculations`) e o front só carrega o **UUID** dele.
- **Não inventar a entidade "proposta".** O destino já deve ter uma entidade equivalente (proposta,
  processo, caso). Descubra qual é antes de começar (seção 2.3). Se não houver, **pare e pergunte**.
- **Não gerar nem enviar senha por e-mail** na conversão de orçamento em proposta (a origem faz isso,
  seção 12).

---

## 2. Convenções obrigatórias

### 2.1 Idioma

| O quê | Idioma | Exemplo |
|---|---|---|
| Classes, arquivos, pastas, métodos, variáveis | **Inglês** | `ItbiCalculator`, `QuoteController`, `resources/js/pages/Quotes/Show.vue` |
| Tabelas, colunas, chaves de enum, nomes de rota | **Inglês** | `itbi_rates.own_funds_rate`, `quotes.show` |
| Labels, mensagens de validação, flash, textos legais, PDF, e-mail | **Português (pt-BR)** | "Orçamento criado com sucesso!" |

Rótulos de enum exibidos ao usuário ficam num método `label()` em pt-BR (ex.: `MaritalStatus::Married->label()` → "Casado(a)").

### 2.2 Laravel 13 / front

- PHP ≥ 8.3: `declare(strict_types=1);`, classes `final` e `readonly` onde couber, backed enums.
- Casts no **método** `casts()` do model (não na propriedade `$casts`).
- Middleware e exceções configurados em `bootstrap/app.php` (não existe `app/Http/Kernel.php`).
- Validação sempre em **FormRequest**; controllers finos; regra de negócio em **Actions**/**Services**.
- Autorização via **Policy** (ou o mecanismo de papéis que o destino já usa — siga o que existir).
- Controllers devolvem `Inertia::render('FeeCalculator/Index', [...])`; após POST/PUT/DELETE,
  `redirect()->route(...)->with('success' | 'error', '...')`. O flash é exposto como prop compartilhada
  em `HandleInertiaRequests::share()` (confira se o destino já faz isso e reutilize o formato).
- Front: formulários com `useForm` do `@inertiajs/vue3`; rotas no front com o helper que o destino já
  usa (Ziggy `route()` ou Wayfinder) — **verifique qual antes de escrever o primeiro componente**.
- Componentes shadcn-vue ficam em `resources/js/components/ui/*` (gerados pelo CLI). Não reescreva
  primitivas que o CLI gera; componha-as.
- PDF: `barryvdh/laravel-dompdf` v3 (`use Barryvdh\DomPDF\Facade\Pdf;`). Instale com a versão que o
  `composer.json` do destino aceitar e **confirme com o responsável antes de adicionar pacote**.
- Antes de usar qualquer API citada aqui, confirme a assinatura na documentação da versão instalada
  (Context7 / docs 13.x). Este PRD descreve comportamento; ele não substitui a doc.

### 2.2.1 Dinheiro em centavos (inteiro)

Mesma convenção do [PRD de Propostas](PRD-proposals-module.md) (seção A2) — os dois módulos
compartilham `proposal_cost_items`, então **tem que ser igual**:

- Toda coluna monetária é `unsignedBigInteger` com o valor em **centavos** (R$ 1.234,56 → `123456`),
  cast `integer`. O nome da coluna não leva sufixo (`price`, `amount`, `fees_total`).
- **Percentuais continuam decimais** (`decimal(7,4)`, cast `decimal:4`).
- O front (`MoneyInput`) exibe a máscara BRL e **envia centavos inteiros**; o backend valida
  `integer|min:0`. Não há conversão de string formatada no backend.
- Somas e totais em inteiro. Multiplicação por percentual (ITBI) → `(int) round($cents * $rate / 100)`,
  arredondando **só no final** da fórmula.
- Exibição por um helper único, compartilhado com o módulo de propostas: `App\Support\Money::format(int $cents)`
  no PHP (PDF, e-mail) e `formatMoney(cents)` no front (`Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })`).
- Valores da API de emolumentos (reais com casas decimais) são convertidos para centavos **na entrada**,
  no `RegistryFeeResult` (4.3): `(int) round($valor * 100)`.

### 2.3 Pré-voo (faça antes de codar)

1. Identifique no destino: a entidade de **proposta** (model, tabela, chave), a de **banco**, o model
   de **usuário/cliente**, e se existe algo como **arquivos da proposta** e **timeline/etapas por banco**.
   A conversão de orçamento (seção 5.9) depende disso.
2. Identifique o mecanismo de **papéis**: na origem, tudo neste módulo é restrito ao perfil
   **Administrador**. Replique com o mecanismo do destino.
3. Veja se já existe componente de **input monetário** (máscara BRL) e helper de formatação de moeda
   no front; reutilize.
4. Rode `php artisan route:list` e anote o padrão de nomes de rota do destino.

---

## 3. Mapeamento origem → destino

| Origem (pt) | Destino (en) | Observação |
|---|---|---|
| `itbi_municipios` / `ItbiMunicipio` | `itbi_municipalities` / `ItbiMunicipality` | ganha `ibge_code` e `state` |
| `itbis` / `ItbiModulo` (módulos 01, 03, 04) | `itbi_rates` / `ItbiRate` | um registro por município |
| `itbi_valor_imovel_descontos` / `ItbiModulo02` | `itbi_brackets` / `ItbiBracket` | N faixas por município (módulo 02) |
| `servicos` / `Servico` | `billable_services` / `BillableService` | |
| — (não existia; ia pela URL) | `fee_calculations` / `FeeCalculation` | snapshot server-side do cálculo |
| `emolumentos` / `Emolumento` | `fee_estimates` / `FeeEstimate` | resultado "congelado" de um orçamento ou proposta |
| `emolumento_pagamento_e_taxa` / `EmolumentoPagamentoTaxa` | `proposal_cost_items` / `ProposalCostItem` | linhas de custo da proposta |
| `orcamentos` / `Orcamento` | `quotes` / `Quote` | |
| `orcamento_servico` / `OrcamentoServico` | `quote_billable_service` (pivot) | valor negociado no pivot |
| `proposta_servico` | `proposal_billable_service` (pivot) | só se o destino não tiver algo equivalente |

| Origem | Destino |
|---|---|
| `CalculadoraEmolumentos*Controller` (4 classes) | `FeeCalculatorController` (1 classe) |
| `EmolumentosPropostaController` | `ProposalFeeEstimateController` |
| `OrcamentoController` | `QuoteController`, `QuotePdfController`, `SendQuoteEmailController` |
| `GeraPropostaController` / `GeraPropostaService` | `ConvertQuoteToProposalController` / `ConvertQuoteToProposal` (action) |
| `CalculadoraEmolumentosService::callAPI` | `RegistryFeeApiClient` |
| `CalculadoraEmolumentosService::calculaITBI` | `ItbiCalculator` |
| `EmolumentoPropostaService`, trecho duplicado de `GeraPropostaService` | `CostItemsBuilder` + `AttachFeeEstimateToProposal` |
| `ItbiMunicipioController`, `ItbiModulo01..04Controller` | `ItbiMunicipalityController`, `ItbiRateController`, `ItbiBracketController` |
| `ServicoController` | `BillableServiceController` |
| `InformacaoDoOrcamentoMail` | `QuoteSummaryMail` |

---

## 4. Integração com a API de emolumentos

### 4.1 Contrato (extraído da origem)

```
POST https://calculadora.registrodeimoveis.org.br/api/calculate
Accept: application/json
Authorization: Bearer <token>
Content-Type: application/json
```

Corpo:

| Campo | Tipo | Origem do valor |
|---|---|---|
| `codigo_municipio` | int | código **IBGE** do município (vem da API do IBGE, seção 6.1) |
| `consulta_id` | int | tipo do cálculo: `1` registro em geral, `2` compra e venda c/ alienação fiduciária, `3` averbação c/ valor econômico |
| `valor_imovel` | int | valor do imóvel/transação — a origem envia **em reais inteiros, truncando centavos** |
| `valor_financiamento` | int | valor total da dívida (tipo 2). Nos tipos 1 e 3 a origem envia `0` |
| `desconto` | string, opcional | código do desconto legal, formato `CODIGO/UF` (seção 5.3). Omitido quando não há desconto |

Resposta de sucesso (formato usado pela origem):

```json
{
  "result": {
    "atos": [
      { "descricao": "Registro de compra e venda", "emolumentos": 1234.56, "...": 0, "subtotal": 1500.00 }
    ],
    "taxas_extras": [ { "descricao": "Prenotação", "valor": 45.10 } ],
    "total": 1545.10,
    "extra_information": "<p>HTML opcional com observações</p>"
  }
}
```

- As **colunas de cada ato são dinâmicas**: além de `descricao` e `subtotal`, a API devolve colunas
  numéricas que variam por UF (emolumentos, fundos, taxas etc.). O front monta o cabeçalho a partir
  das chaves do primeiro ato.
- Erro de negócio: a API responde com `errorMessage` **ou** `message` (string) no corpo.

> ⚠️ **Centavos.** A origem converte `"350.000,99"` em `350000` antes de enviar. Não se sabe se a API
> aceita decimais. **Confirme com o responsável pela integração.** Até confirmar, preserve o
> comportamento (enviar inteiro, arredondando para baixo), mas faça essa conversão **num único lugar**
> (`RegistryFeeRequestData::toPayload()`, a partir dos centavos: `intdiv($cents, 100)`) e com um teste
> que a documente. O ITBI é calculado com o valor **com centavos**.

### 4.2 Configuração

```php
// config/services.php
'registry_fee_calculator' => [
    'url' => env('REGISTRY_FEE_CALCULATOR_URL', 'https://calculadora.registrodeimoveis.org.br/api'),
    'token' => env('REGISTRY_FEE_CALCULATOR_TOKEN'),
    'timeout' => (int) env('REGISTRY_FEE_CALCULATOR_TIMEOUT', 15),
],
```

- O token **nunca** vai para o código. Na origem ele está hardcoded e no histórico do git — peça ao
  responsável um **token novo** para o destino; não reutilize o da origem.
- Adicione as chaves ao `.env.example` sem valor.

### 4.3 `App\Services\RegistryFee\RegistryFeeApiClient`

```php
final readonly class RegistryFeeApiClient
{
    public function calculate(RegistryFeeRequestData $data): RegistryFeeResult
    {
        $response = Http::baseUrl(config('services.registry_fee_calculator.url'))
            ->withToken(config('services.registry_fee_calculator.token'))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(config('services.registry_fee_calculator.timeout'))
            ->retry([200, 1000], throw: false, when: fn (Throwable $e) => $e instanceof ConnectionException)
            ->post('calculate', $data->toPayload());

        // 1) erro de negócio no corpo → RegistryFeeRejectedException(mensagem da API)
        // 2) $response->failed() (401/422/5xx) → RegistryFeeUnavailableException
        // 3) corpo sem 'result.atos' → RegistryFeeUnavailableException
        return RegistryFeeResult::fromArray($response->json('result'));
    }
}
```

Regras:

- **TLS ligado** (a origem usa `verify => false`; não repita).
- Retentar **só** em falha de conexão. Nunca retentar 4xx.
- `errorMessage ?? message` vira a mensagem exibida ao usuário (string, não booleano).
- Exceções em `App\Exceptions\RegistryFee\*`, reportadas com contexto (UF, município, tipo) — **sem o token**.
- `RegistryFeeRequestData` e `RegistryFeeResult` são DTOs `readonly`. `RegistryFeeRequestData` recebe
  `propertyValue`/`financingValue` em **centavos** (`int`). `RegistryFeeResult` expõe `acts` (lista, com
  as colunas numéricas já em centavos), `extraFees` (lista, `amount` em centavos), `total` (`int`,
  centavos) e `extraInformation` (?string). A conversão reais → centavos acontece só em `fromArray()`.
- `api_result` (json) guarda a resposta **bruta** da API, como veio (auditoria); tudo o que é calculado,
  somado ou gravado em coluna usa os centavos do DTO.
- Testes com `Http::fake()` + `Http::preventStrayRequests()`.

---

## 5. Regras de negócio

### 5.1 Estados e tipos de cálculo disponíveis

A origem só oferece 11 UFs (as que a API cobre). Capital pré-selecionada ao escolher a UF.

| UF | Nome | Capital (pré-selecionada) |
|---|---|---|
| AM | Amazonas | Manaus |
| BA | Bahia | Salvador |
| ES | Espírito Santo | Vitória |
| GO | Goiás | Goiânia |
| MG | Minas Gerais | Belo Horizonte |
| MS | Mato Grosso do Sul | Campo Grande |
| PA | Pará | Belém |
| PR | Paraná | Curitiba |
| RJ | Rio de Janeiro | Rio de Janeiro |
| RS | Rio Grande do Sul | Porto Alegre |
| SP | São Paulo | São Paulo |

| Tipo (`CalculationType`) | `consulta_id` | Campos | UFs | Tem ITBI? |
|---|---|---|---|---|
| `GeneralRegistration` — "Registro em geral" | 1 | valor do imóvel, desconto | todas | **sim** (alíquota cheia) |
| `PurchaseWithFiduciaryLien` — "Compra e venda com alienação fiduciária" | 2 | valor do imóvel, valor do financiamento, SFH/SFI, primeiro imóvel*, desconto | todas **exceto PA** | **sim** |
| `EconomicValueAnnotation` — "Averbação com valor econômico" | 3 | valor do imóvel, desconto | **somente RJ** | **não** |

\* "Primeiro imóvel?" (Sim/Não) só aparece quando o município tem alíquota do **módulo 03**. Na origem
isso está amarrado ao **nome** do município ("São Caetano do Sul", "Mauá") na view — no destino,
derive do cadastro (`ItbiRate.module === ItbiModule::FirstPropertyRate`).

Cada tela de cálculo mostra, ao lado do formulário, o bloco "Observações importantes" (textos na seção 13).

### 5.2 Validações do formulário de cálculo

| Campo | Regra |
|---|---|
| `state` | obrigatório, `Rule::enum(SupportedState::class)` |
| `municipality_ibge_code` | obrigatório, inteiro de 7 dígitos |
| `municipality_name` | obrigatório (exibição e mensagens) |
| `type` | obrigatório, `Rule::enum(CalculationType::class)`, e permitido para a UF (5.1) |
| `property_value` | obrigatório, `integer`, `> 0` — **centavos** vindos do `MoneyInput` (ver 2.2.1) |
| `financing_value` | obrigatório no tipo 2, `integer` (centavos), `>= 0` e `<= property_value` |
| `financing_system` | obrigatório no tipo 2, `Rule::enum(FinancingSystem::class)`, default `SFH` |
| `first_property` | obrigatório (boolean) no tipo 2 quando o município é módulo 03 |
| `discount` | opcional, `Rule::enum(FeeDiscount::class)`, e permitido para a UF (5.3) |

Mensagens em pt-BR equivalentes às da origem ("O Valor do imóvel / Transação é obrigatório!",
"O Financiamento é obrigatório!" etc.).

### 5.3 Descontos legais (`FeeDiscount`)

O usuário escolhe no máximo **um**, num diálogo "Defina o desconto a ser utilizado". O valor enviado à
API é `"{code}/{UF}"`. "Remover seleção" **limpa o desconto** (na origem o botão só muda o visual e o
desconto continua sendo enviado — não repita).

| Case | `code` | Título | UFs | Texto legal (exibir no card) |
|---|---|---|---|---|
| `FirstAcquisitionSfh` | `SFH` | 1ª Aquisição SFH | todas | Lei 6.015/73, Art. 290 — redução de 50% dos emolumentos na primeira aquisição residencial financiada pelo SFH. Lei 3.350/99 RJ, Art. 44 — isenção do acréscimo de 20% e das taxas das Leis 489/1981 e 590/1987 na primeira aquisição da casa própria ou com interveniência de Cooperativas Habitacionais. § 3º O notário ou registrador exigirá certidões dos Ofícios de Distribuição competentes. |
| `FirstAcquisitionPublicDeed` | `EP` | 1ª Aquisição - Escritura Pública | **RJ** | Lei 3.350/99 RJ, Art. 44 (mesmo texto acima, sem a Lei 6.015/73). |
| `MinhaCasaMinhaVida` | `PCVA_MCMV` | Minha Casa Minha Vida | todas | Lei 11.977/09, Art. 42, II — redução de 50% para os atos relacionados aos demais empreendimentos do PMCMV. |
| `FarFds` | `FAR_FDS` | FAR e FDS | todas | Lei 11.977/09, Art. 42, I — redução de 75% para os empreendimentos do FAR e do FDS. |
| `PopularHousing` | `HAP` | Habitação Popular | **AM** | Lei 2.751/2002 — redução de metade das custas para habitação popular, da aquisição do terreno à averbação/registro da construção. |

Copie os textos integrais de `resources/views/app/calculadora/_components/modal.blade.php` da origem
para o método `legalText()` do enum (ou para um arquivo de tradução). O desconto é aplicado **pela API**;
o sistema só repassa o código.

### 5.4 ITBI — cadastro

Cada município cadastrado usa **exatamente um** módulo de cálculo (`ItbiModule`):

| Case | Valor | Nome na origem | Municípios de exemplo (origem) | Parâmetros |
|---|---|---|---|---|
| `FinancedCap` | `financed_cap` | módulo 01 | Santo André, São Paulo, Diadema | `own_funds_rate` (%), `financed_rate` (%), `financed_cap_amount` (R$) |
| `ValueBrackets` | `value_brackets` | módulo 02 | São Bernardo do Campo | N faixas: `min_value`, `max_value`, `rate` (%), `discount_amount` (R$) + `full_rate` (%) usada em SFI/registro geral |
| `FirstPropertyRate` | `first_property_rate` | módulo 03 | São Caetano do Sul, Mauá | `own_funds_rate`, `first_property_financed_rate`, `other_property_financed_rate` (%) |
| `Standard` | `standard` | módulo 04 | demais | `own_funds_rate`, `financed_rate` (%) |

Percentuais são guardados como número (ex.: `3` = 3%). Na origem eles estão em colunas `string` — no
destino use `decimal(7,4)`. Os parâmetros em R$ (`financed_cap_amount`, `min_value`, `max_value`,
`discount_amount`) são **centavos** (`unsignedBigInteger`, ver 2.2.1).

> **Mudança deliberada:** a origem fixa 2,5% (`$valorImovel * 2.5 / 100`) para o módulo 02 em SFI e no
> registro geral. No destino esse percentual vira o campo `full_rate` do município (seed com `2.5` para
> São Bernardo do Campo).

### 5.5 ITBI — fórmulas (`App\Services\Itbi\ItbiCalculator`)

Notação: `V` = valor do imóvel, `F` = valor do financiamento, `rp` = `own_funds_rate`,
`rf` = `financed_rate`, `cap` = `financed_cap_amount`. Percentuais divididos por 100.
`V`, `F`, `cap`, `min_value`, `max_value`, `discount_amount` e o resultado estão em **centavos** (`int`).

**Tipo 3 (averbação):** não calcula ITBI. Retorna `null`.

**Tipo 1 (registro em geral)** e **tipo 2 com SFI** — alíquota cheia sobre o valor do imóvel:

| Módulo | ITBI |
|---|---|
| `FinancedCap`, `FirstPropertyRate`, `Standard` | `V × rp` |
| `ValueBrackets` | `V × full_rate` |

**Tipo 2 com SFH:**

| Módulo | ITBI |
|---|---|
| `FinancedCap` | se `F > cap`: `(V − cap) × rp + cap × rf`; senão: `(V − F) × rp + F × rf` |
| `FirstPropertyRate` | `(V − F) × rp + F × (primeiro imóvel ? first_property_financed_rate : other_property_financed_rate)` |
| `Standard` | `(V − F) × rp + F × rf` |
| `ValueBrackets` | faixa com `min_value ≤ V ≤ max_value`: `V × rate − discount_amount` |

Regras:

- Município sem cadastro de ITBI (casado pelo **`ibge_code`**, não pelo nome) nos tipos 1 e 2 →
  erro de validação: "Município não encontrado, por favor verifique em ITBI Municípios se foi
  cadastrado de forma correta!". Na origem o casamento é por nome (`'São Paulo' === 'São Paulo'`), o
  que quebra com grafia/acentos diferentes da API do IBGE.
- `ValueBrackets` sem faixa que contenha `V` → mesmo tratamento de "não cadastrado", com mensagem
  específica ("Valor do imóvel fora das faixas cadastradas para {município}").
- **Limites de faixa inclusivos** (`≤`). Na origem são exclusivos (`>` e `<`): um imóvel de exatamente
  R$ 300.000,00 numa faixa 300.000–400.000 não calculava. O cadastro deve **impedir sobreposição** de faixas.
- Resultado arredondado para **centavo inteiro** (`(int) round($value)`) só no final da fórmula; nunca
  negativo (`max(0, …)`). O `ItbiCalculator` retorna `?int` (centavos).
- Implemente como **strategy por módulo** (`FinancedCapStrategy`, `ValueBracketsStrategy`, …) ou
  `match` sobre o enum — o importante é ser uma classe pura, **sem HTTP e sem request**, recebendo um
  DTO (`ItbiInput`: type, financingSystem, propertyValue, financedValue, firstProperty, municipality) e
  testável por tabela de casos (seção 11).
- Carregue só o município consultado (`ItbiMunicipality::with(['rate', 'brackets'])->firstWhere('ibge_code', ...)`).
  A origem carrega **todas** as linhas das duas tabelas a cada cálculo.

### 5.6 Resultado do cálculo (`fee_calculations`)

Ao calcular, o servidor:

1. valida (5.2);
2. chama a API (4) e o `ItbiCalculator` (5.5);
3. grava um `FeeCalculation` com: usuário, tipo, UF, município, entradas, resposta da API (`json`),
   ITBI, total dos emolumentos;
4. redireciona para `fee-calculator.results.show` com o UUID.

`FeeCalculation` é **imutável** depois de criado e expira (ex.: 7 dias; limpeza por `model:prune` com
`Prunable`). É ele que "Vincular à proposta" e "Gerar orçamento" recebem — **só o UUID**. Isso resolve
dois defeitos da origem: valores adulteráveis no cliente e URL gigante (o JSON inteiro ia na query string).

### 5.7 Tabela de resultado (`FeeResultTable.vue`)

Ordem das linhas (igual à origem):

1. Cabeçalho: chaves do primeiro ato, humanizadas (`snake_case` → "Snake case", primeira maiúscula).
2. Uma linha por ato. Valores numéricos formatados em BRL (`R$ 1.234,56`) por `formatMoney(cents)`,
   strings como texto. O `FeeBreakdown` enviado ao front traz todos os valores em centavos.
3. Linha **SUBTOTAIS**: soma de cada coluna numérica.
4. Linha de **taxas extras**: descrição + valor de cada uma.
5. **Total do cálculo** (`result.total`) — rotulado "TOTAL" quando não há ITBI nem serviços.
6. **ITBI**, quando houver.
7. Uma linha por **serviço** (só em orçamento), com o valor negociado.
8. **TOTAL geral** = `result.total + ITBI + Σ serviços`.

- Na origem, orçamento **sem** serviços não mostra o TOTAL geral (só "Total do cálculo" + ITBI).
  No destino, mostre **sempre** o TOTAL geral quando houver ITBI ou serviços.
- `extra_information` é HTML de terceiro: **sanitize no servidor** (ex.: `Str::of(...)->stripTags` com
  lista de tags permitidas, ou HTMLPurifier se o destino já tiver) antes de salvar, e renderize com
  `v-html` só o valor sanitizado. A origem faz `echo` direto (XSS).
- Totais são calculados **no servidor** (DTO/Resource `FeeBreakdown`), não no template.
- O mesmo componente é reutilizado em: tela de resultado, tela do orçamento; e sua versão Blade
  (`resources/views/pdf/quote.blade.php` e o e-mail) consome o mesmo `FeeBreakdown`.
- Lista de atos vazia → mostrar "Nenhum registro encontrado" (na origem, variável indefinida → erro).

Ações na tela de resultado: **Voltar**, **Vincular à proposta**, **Gerar orçamento**.

### 5.8 Vincular à proposta

- Lista **somente** propostas que ainda não têm `FeeEstimate` (subquery `whereDoesntHave('feeEstimate')`),
  com **busca server-side** (Combobox assíncrono, `limit 20`). A origem carrega todas as propostas e
  filtra em PHP.
- Permite escolher serviços cobráveis, com valor negociado opcional (`ServicePicker`, 7.3).
- Ao confirmar, em **uma transação** (`AttachFeeEstimateToProposal`):
  1. trava a proposta (`lockForUpdate`) e revalida que ela não tem estimativa — se tiver, erro;
  2. cria `FeeEstimate` (proposal_id, dados do `FeeCalculation`, `valid_until` nulo);
  3. gera as linhas com `CostItemsBuilder` (5.10) e grava em `proposal_cost_items`;
  4. anexa os serviços à proposta, se o destino tiver esse vínculo.
- Redireciona para a tela da proposta (aba de pagamentos/custos) com "Emolumento registrado com sucesso!".

> Na origem esse fluxo **falha sempre** com erro de banco (`validade` NOT NULL não preenchida) e,
> quando há serviço, também por gravar `"1.500,00"` numa coluna numérica. Ver doc de correções, itens 3 e 4.

### 5.9 Orçamento (`Quote`)

**Criar** (a partir de um `FeeCalculation`):

| Campo | Regra |
|---|---|
| `name` | obrigatório, `max:191`, normalizado para *Title Case* (`Str::title`) |
| `cpf` | obrigatório, CPF válido (use a regra que o destino já tiver), armazenado só com dígitos |
| `email` | obrigatório, `email`, normalizado para minúsculas |
| `phone` | obrigatório, `max:20` |
| `profession` | obrigatório, `max:191` |
| `marital_status` | obrigatório, `Rule::enum(MaritalStatus::class)` |
| `bank_id` | obrigatório, `exists` na tabela de bancos do destino |
| `valid_until` | obrigatório, `date`, `after_or_equal:today` |
| `services` | `array` opcional; `services.*.id` `exists:billable_services,id`; `services.*.amount` opcional, `integer|min:0` em centavos (vazio = valor de tabela) |
| `fee_calculation_id` | obrigatório, `exists`, do usuário, não expirado |

Em transação (`CreateQuote`): cria `Quote`, cria `FeeEstimate` (quote_id, `valid_until`, snapshot) e
anexa serviços no pivot com o **valor efetivo** (negociado ou de tabela), já normalizado.

**Listar**: paginado (`paginate(20)`), mais novo primeiro, com busca por nome/CPF; colunas nº, cliente,
CPF, validade, status. Status derivado (`QuoteStatus`, calculado, não persistido):

| Status | Condição | Badge |
|---|---|---|
| `Converted` — "Convertido em proposta" | `fee_estimate.proposal_id` preenchido | verde |
| `Expired` — "Expirado" | `valid_until < hoje` e não convertido | cinza/destrutivo |
| `Open` — "Em aberto" | demais | padrão |

**Ver**: dados do cliente, validade, `FeeResultTable` com serviços e ITBI. Alertas (iguais à origem):

- convertido → "Atenção! já foi gerada uma proposta para este orçamento." + link para a proposta;
- expirado e não convertido → "Atenção: Orçamento com data de validade expirado, será necessário gerar um novo!".

Ações: **Imprimir** (PDF), **Enviar por e-mail**, e — só quando `Open` — **Editar** e **Gerar proposta**.

**Editar** (`UpdateQuote`): dados do cliente, serviços (substitui o pivot com `sync`) e **validade**
(na origem a validade editada é descartada em silêncio — ver correções item 12). Não é possível
alterar valores do cálculo: para isso, gera-se outro cálculo/orçamento. Bloqueado se `Converted`.

**Excluir**: soft delete. **Bloqueado se `Converted`** (na origem, excluir um orçamento convertido apaga
em cascata o registro de emolumento vinculado à proposta).

**PDF** (`QuotePdfController`, `Pdf::loadView('pdf.quote', ...)->stream("orcamento-{$id}.pdf")`):
cabeçalho com logo e dados da empresa (vindos de `config('company.*')`, não hardcoded), título
"Informativo do Orçamento", "Orçamento Nº 00012" (5 dígitos com zero à esquerda), bloco
"Administrador ou Analista" com o nome do usuário logado quando ele é admin, bloco "Proponente"
(nome, CPF formatado, telefone, e-mail, profissão), a tabela (5.7), o aviso legal (13) e a linha
"{cidade da empresa}, {dia} de {mês por extenso} de {ano}".

**E-mail** (`SendQuoteEmailController` → `QuoteSummaryMail implements ShouldQueue`, markdown):
assunto "Informação do orçamento para proposta"; "Olá {nome}, estamos enviando o resultado do
orçamento feito conosco."; a tabela; o aviso legal; "Este é um e-mail automático, não é necessário
respondê-lo."; assinatura `config('app.name')`. Anexar o PDF é opcional (decida com o responsável).
Não exponha a mensagem da exceção ao usuário (a origem concatena `$th->getMessage()` no flash).

**Converter em proposta** (`ConvertQuoteToProposal`, rota **POST**):

Pré-condições checadas **no servidor**, com `lockForUpdate` no orçamento: status `Open` (não expirado,
não convertido). Violou → erro, sem efeito colateral. Na origem é um `GET` sem essa checagem, e cada
acesso ao link cria outra proposta.

Em uma transação:

1. Localiza o cliente pelo CPF; se não existir, cria o usuário com papel **Cliente** e **sem senha
   utilizável** (senha aleatória `Str::password()` descartada).
2. Cria a proposta com os defaults da origem: banco do orçamento, status inicial ("Nova proposta"),
   criador = usuário logado, valores zerados, tipo de imóvel padrão. **Adapte aos campos obrigatórios
   da proposta do destino.**
3. Cria o proponente com nome, CPF, e-mail, telefone, profissão e estado civil do orçamento.
4. Anexa os serviços; gera as linhas de custo com `CostItemsBuilder` usando os **valores negociados**.
5. Se o destino tiver etapas por banco / timeline, cria a timeline da proposta (a origem faz isso).
6. Vincula o `FeeEstimate` do orçamento à proposta (`proposal_id`).

**Depois do commit** (`DB::afterCommit` ou jobs com `afterCommit`):

7. Job gera o PDF do orçamento e o anexa aos arquivos da proposta (tipo "Emolumento"), se o destino
   tiver essa estrutura.
8. E-mail ao cliente: se o usuário foi criado agora, **link de definição de senha**
   (`Password::broker()->createToken()` + notificação) em vez da senha em texto; se já existia, o aviso
   de nova proposta. A origem gera a senha a partir do CPF + `rand()` e a manda por e-mail — não repita.

Redireciona para a proposta com "Proposta criada com sucesso!".

### 5.10 Linhas de custo (`CostItemsBuilder`)

Uma única classe gera as linhas a partir de (`FeeCalculation` ou `FeeEstimate`, serviços com valor efetivo).
Substitui o trecho duplicado em `EmolumentoPropostaService` e `GeraPropostaService` da origem.

| Origem da linha | `type` (`CostItemType`) | `description` | `extra_fee_description` | `amount` |
|---|---|---|---|---|
| cada `result.atos[]` | `Emolument` | `ato.descricao` | — | `ato.subtotal` (em centavos, via `RegistryFeeResult`) |
| cada `result.taxas_extras[]` | `ExtraFee` | — | `taxa.descricao` | `taxa.valor` (em centavos) |
| cada serviço | `Service` | `service.name` | — | valor efetivo; copia `service_description` e `generates_receipt` |
| ITBI (tipos 1 e 2) | `Itbi` | "ITBI" | — | ITBI calculado |

- Na origem, taxas extras são gravadas com tipo `Emolumento` e o ITBI entra mesmo quando é zero
  (averbação). No destino: tipo próprio `ExtraFee`; **não** criar linha de ITBI no tipo 3.
- "Total de emolumentos" da proposta = soma de `Emolument` + `ExtraFee` (equivale à soma por tipo
  `Emolumento` da origem).
- O valor de cada linha pode ser **editado depois** na tela da proposta (a origem expõe isso em
  `PagamentosTaxaController@update`). Endpoint: `PATCH proposals/{proposal}/cost-items/{costItem}`
  com `amount` validado, *scoped binding* (a linha tem que ser da proposta) e policy.

### 5.11 Serviços cobráveis (`BillableService`)

CRUD simples: `name` (obrigatório, `max:191`), `description` (obrigatório, texto), `price`
(obrigatório, `integer|min:0`, centavos), `generates_receipt` (boolean, default `false`). Soft delete.
Listagem paginada, ordenada por nome.

### 5.12 Cadastros de ITBI

- **Municípios** (`ItbiMunicipality`): `name`, `state` (`SupportedState`), `ibge_code` (único). O cadastro
  usa o mesmo `StateMunicipalityPicker` da calculadora para preencher nome e código IBGE sem digitação.
- **Alíquota** (`ItbiRate`): 1:1 com o município; o formulário mostra os campos do `module` escolhido
  (5.4) e valida só esses (`Rule::requiredIf`). Unicidade por município (`unique` + índice).
- **Faixas** (`ItbiBracket`): N por município do módulo `ValueBrackets`; formulário com linhas
  dinâmicas (adicionar/remover); validação de `min_value < max_value` e de **não sobreposição**
  (regra customizada `NonOverlappingBrackets` num `after()` do FormRequest).
- Um município tem **um** módulo. Trocar de módulo apaga os parâmetros do módulo anterior (em transação).

---

## 6. Modelagem de dados

Todas as migrations com `down()` reversível, FKs com `constrained()` e comportamento de `onDelete`
explícito, dinheiro em `unsignedBigInteger` (**centavos**, ver 2.2.1), percentuais em `decimal(7,4)`.

### 6.1 Municípios e ITBI

```php
Schema::create('itbi_municipalities', function (Blueprint $table) {
    $table->id();
    $table->string('name', 120);
    $table->char('state', 2);
    $table->unsignedInteger('ibge_code')->unique();
    $table->string('module', 30);                     // ItbiModule
    $table->decimal('full_rate', 7, 4)->nullable();   // ValueBrackets: alíquota cheia (SFI / registro geral)
    $table->timestamps();
    $table->index(['state', 'name']);
});

Schema::create('itbi_rates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('itbi_municipality_id')->unique()->constrained()->cascadeOnDelete();
    $table->decimal('own_funds_rate', 7, 4);
    $table->decimal('financed_rate', 7, 4)->nullable();                 // FinancedCap, Standard
    $table->unsignedBigInteger('financed_cap_amount')->nullable();       // FinancedCap (centavos)
    $table->decimal('first_property_financed_rate', 7, 4)->nullable();  // FirstPropertyRate
    $table->decimal('other_property_financed_rate', 7, 4)->nullable();  // FirstPropertyRate
    $table->timestamps();
});

Schema::create('itbi_brackets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('itbi_municipality_id')->constrained()->cascadeOnDelete();
    $table->unsignedBigInteger('min_value');             // centavos
    $table->unsignedBigInteger('max_value');             // centavos
    $table->decimal('rate', 7, 4);
    $table->unsignedBigInteger('discount_amount')->default(0); // centavos
    $table->timestamps();
    $table->index(['itbi_municipality_id', 'min_value']);
});
```

> O `module` fica no município (e não em cada linha, como o `tipo_modulo` da origem) para garantir
> "um município, um módulo". As colunas `porcentagem_valor_financiado_prefeitura_min/max` da origem
> nunca são usadas no cálculo — **não porte**.

Os códigos IBGE vêm da API pública do IBGE (a mesma que a origem usa no navegador):
`GET https://servicodados.ibge.gov.br/api/v1/localidades/estados/{UF}/municipios` → `[{ id, nome }]`.
No destino, chame-a **pelo backend** (`IbgeLocalityClient`) e cacheie por UF
(`Cache::remember("ibge.municipalities.{$uf}", now()->addDays(30), ...)`), expondo
`GET fee-calculator/states/{state}/municipalities` (JSON) para o picker.

Seed (`ItbiSeeder`, só em ambientes não produtivos, ou como dado inicial se o responsável aprovar):
exporte os valores atuais da origem (`itbis`, `itbi_valor_imovel_descontos`, `itbi_municipios`) e
converta — **não invente alíquotas**. Na base local da origem só há São Paulo (módulo 01, 3%).

### 6.2 Serviços, cálculo, estimativa e custos

```php
Schema::create('billable_services', function (Blueprint $table) {
    $table->id();
    $table->string('name', 191);
    $table->text('description');
    $table->unsignedBigInteger('price');                 // centavos
    $table->boolean('generates_receipt')->default(false);
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('fee_calculations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->unsignedTinyInteger('type');              // CalculationType
    $table->char('state', 2);
    $table->unsignedInteger('municipality_ibge_code');
    $table->string('municipality_name', 120);
    $table->json('input');                            // valores, SFH/SFI, primeiro imóvel, desconto
    $table->json('api_result');                       // result da API (extra_information já sanitizado)
    $table->unsignedBigInteger('fees_total');            // centavos
    $table->unsignedBigInteger('itbi_amount')->nullable(); // centavos
    $table->timestamp('expires_at')->index();
    $table->timestamps();
});

Schema::create('quotes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('created_by')->constrained('users');
    $table->string('name', 191);
    $table->string('cpf', 11)->index();
    $table->string('email', 191);
    $table->string('phone', 20);
    $table->string('profession', 191);
    $table->unsignedTinyInteger('marital_status');    // MaritalStatus
    $table->foreignId('bank_id')->constrained('banks'); // ajuste ao nome da tabela de bancos do destino
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('quote_billable_service', function (Blueprint $table) {
    $table->id();
    $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
    $table->foreignId('billable_service_id')->constrained()->restrictOnDelete();
    $table->unsignedBigInteger('amount');                // valor efetivo (negociado ou de tabela), centavos
    $table->unique(['quote_id', 'billable_service_id']);
});

Schema::create('fee_estimates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('quote_id')->nullable()->unique()->constrained()->cascadeOnDelete();
    $table->foreignId('proposal_id')->nullable()->unique()->constrained('proposals')->nullOnDelete();
    $table->unsignedTinyInteger('type');
    $table->char('state', 2);
    $table->string('municipality_name', 120);
    $table->date('valid_until')->nullable();          // obrigatório só para orçamento (validado na action)
    $table->json('api_result');
    $table->unsignedBigInteger('fees_total');            // centavos
    $table->unsignedBigInteger('itbi_amount')->nullable(); // centavos
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('proposal_cost_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
    $table->string('type', 20);                       // CostItemType
    $table->string('description', 191)->nullable();
    $table->string('extra_fee_description', 191)->nullable();
    $table->text('service_description')->nullable();
    $table->boolean('generates_receipt')->default(false);
    $table->unsignedBigInteger('amount');                // centavos
    $table->timestamps();
    $table->index(['proposal_id', 'type']);
});
```

Troque `proposals`/`banks` pelos nomes reais do destino (pré-voo 2.3). Se o destino já tiver uma
tabela de custos/pagamentos da proposta, **avalie reaproveitá-la** em vez de criar `proposal_cost_items`
e registre a decisão no PR. O [PRD de Propostas](PRD-proposals-module.md) (A0/A4) define essa tabela
como compartilhada e acrescenta colunas de lançamento manual (`cost_type_id`, `notary_id`, `date`,
`notes`, `bill_path`, `proof_path`) e o caso `Manual` em `CostItemType` — se aquele módulo já existir,
reaproveite a tabela dele.

### 6.3 Models

Todos com `$fillable` explícito, relações com tipo de retorno e `casts()`:

| Model | Relações | Casts |
|---|---|---|
| `ItbiMunicipality` | `rate(): HasOne`, `brackets(): HasMany` (ordenadas por `min_value`) | `module` → `ItbiModule`, `state` → `SupportedState`, `full_rate` → `decimal:4` |
| `ItbiRate` | `municipality(): BelongsTo` | taxas `decimal:4`, `financed_cap_amount` `integer` (centavos) |
| `ItbiBracket` | `municipality(): BelongsTo` | valores `integer` (centavos) / `rate` `decimal:4` |
| `BillableService` | — (`SoftDeletes`) | `price` `integer` (centavos), `generates_receipt` `boolean` |
| `FeeCalculation` | `user(): BelongsTo` (`HasUuids`, `Prunable` por `expires_at`) | `type` → `CalculationType`, `input`/`api_result` → `array`, `fees_total`/`itbi_amount` → `integer`, `expires_at` → `datetime` |
| `Quote` | `feeEstimate(): HasOne`, `services(): BelongsToMany` (`withPivot('amount')`), `bank(): BelongsTo`, `creator(): BelongsTo` (`SoftDeletes`) | `marital_status` → `MaritalStatus` |
| `FeeEstimate` | `quote(): BelongsTo`, `proposal(): BelongsTo` | `type` → `CalculationType`, `api_result` → `array`, `fees_total`/`itbi_amount` → `integer`, `valid_until` → `date` |
| `ProposalCostItem` | `proposal(): BelongsTo` | `type` → `CostItemType`, `amount` `integer` (centavos) |

No model de proposta do destino, adicione `feeEstimate(): HasOne` e `costItems(): HasMany`.

`Quote` ganha o accessor `status` (`QuoteStatus`) e o scope `open()`; use `->with('feeEstimate')` na
listagem para não gerar N+1 ao calcular o status.

---

## 7. Enums — `app/Enums/`

| Enum | Tipo | Cases | Métodos |
|---|---|---|---|
| `SupportedState` | `string` | `AM, BA, ES, GO, MG, MS, PA, PR, RJ, RS, SP` | `label()` (nome da UF), `capital()`, `allowedCalculationTypes()` |
| `CalculationType` | `int` | `GeneralRegistration = 1`, `PurchaseWithFiduciaryLien = 2`, `EconomicValueAnnotation = 3` | `label()`, `description()`, `hasItbi()`, `requiresFinancing()` |
| `FinancingSystem` | `string` | `Sfh = 'sfh'`, `Sfi = 'sfi'` | `label()` |
| `FeeDiscount` | `string` | ver 5.3 (valor = `code`) | `label()`, `legalText()`, `allowedStates()`, `apiValue(SupportedState)` → `"SFH/SP"` |
| `ItbiModule` | `string` | `FinancedCap`, `ValueBrackets`, `FirstPropertyRate`, `Standard` | `label()`, `requiredFields()` |
| `CostItemType` | `string` | `Emolument`, `ExtraFee`, `Service`, `Itbi` | `label()` ("Emolumento", "Taxa extra", "Serviço", "ITBI") |
| `MaritalStatus` | `int` | `Single = 1`, `Married = 2`, `Widowed = 3`, `JudiciallySeparated = 4`, `Divorced = 5` | `label()` (Solteiro, Casado, Viúvo, Separado judicialmente, Divorciado) |
| `QuoteStatus` | `string` | `Open`, `Expired`, `Converted` | `label()`, `badgeVariant()` |

Os valores de `MaritalStatus` e `CalculationType` são os mesmos da origem, o que facilita migrar dados.
Exponha ao front via props (`CalculationType::options()` → `[{ value, label }]`), nunca duplique
listas em TypeScript.

---

## 8. Camada de aplicação

```
app/
├── Actions/FeeCalculator/
│   ├── CalculateFees.php                  # valida regras cruzadas, chama API + ITBI, grava FeeCalculation
│   ├── AttachFeeEstimateToProposal.php
│   ├── CreateQuote.php
│   ├── UpdateQuote.php
│   └── ConvertQuoteToProposal.php
├── Services/
│   ├── RegistryFee/RegistryFeeApiClient.php (+ DTOs RegistryFeeRequestData, RegistryFeeResult)
│   ├── Ibge/IbgeLocalityClient.php
│   ├── Itbi/ItbiCalculator.php (+ ItbiInput, estratégias por módulo)
│   └── FeeCalculator/CostItemsBuilder.php, FeeBreakdown.php
├── Exceptions/RegistryFee/RegistryFeeRejectedException.php, RegistryFeeUnavailableException.php
├── Exceptions/Itbi/ItbiNotConfiguredException.php
├── Jobs/AttachQuotePdfToProposal.php
└── Mail/QuoteSummaryMail.php
```

- Actions com um método público (`handle()` ou `__invoke`), injetadas no controller.
- Escritas múltiplas sempre em `DB::transaction()`; efeitos externos (e-mail, PDF, storage) **depois
  do commit**.
- `RegistryFeeRejectedException` vira erro de validação no campo `property_value` (o usuário vê a
  mensagem da API no formulário); `RegistryFeeUnavailableException` vira flash de erro
  "Ocorreu um erro ao processar sua solicitação, servidor da calculadora está fora do ar. Por favor,
  tente novamente mais tarde." e é reportada.
- `ItbiNotConfiguredException` vira erro de validação com a mensagem da seção 5.5.

---

## 9. HTTP

### 9.1 Controllers

| Controller | Métodos | Página Inertia / resposta |
|---|---|---|
| `FeeCalculatorController` | `index`, `create(CalculationType $type)`, `store(CalculateFeesRequest)` | `FeeCalculator/Index`, `FeeCalculator/Calculate`, redirect → resultado |
| `FeeCalculationController` | `show(FeeCalculation)` | `FeeCalculator/Result` |
| `MunicipalityLookupController` | `__invoke(SupportedState)` | JSON `[{ ibge_code, name, has_itbi, itbi_module }]` |
| `ProposalFeeEstimateController` | `create(FeeCalculation)`, `store(AttachFeeEstimateRequest)` | `FeeCalculator/AttachToProposal`, redirect → proposta |
| `ProposalSearchController` | `__invoke(Request)` | JSON (propostas sem estimativa, busca, limite 20) |
| `QuoteController` | `index`, `create(FeeCalculation)`, `store`, `show`, `edit`, `update`, `destroy` | `Quotes/Index`, `Quotes/Create`, `Quotes/Show`, `Quotes/Edit` |
| `QuotePdfController` | `__invoke(Quote)` | PDF stream |
| `SendQuoteEmailController` | `__invoke(Quote)` | redirect back com flash |
| `ConvertQuoteToProposalController` | `__invoke(Quote)` | redirect → proposta |
| `ProposalCostItemController` | `update(Proposal, ProposalCostItem)` | redirect back |
| `ItbiMunicipalityController` | resource sem `show` | `Itbi/Municipalities/*` |
| `ItbiRateController` | `edit`, `update` (1:1 com o município) | `Itbi/Rates/Edit` |
| `ItbiBracketController` | `edit`, `update` (substitui o conjunto de faixas) | `Itbi/Brackets/Edit` |
| `BillableServiceController` | resource sem `show` | `BillableServices/*` |

Um só controller para os 3 tipos de cálculo (na origem são 3 classes quase idênticas).

### 9.2 FormRequests

`CalculateFeesRequest`, `AttachFeeEstimateRequest`, `StoreQuoteRequest`, `UpdateQuoteRequest`,
`UpdateProposalCostItemRequest`, `StoreItbiMunicipalityRequest`, `UpdateItbiMunicipalityRequest`,
`UpdateItbiRateRequest`, `UpdateItbiBracketsRequest`, `StoreBillableServiceRequest`,
`UpdateBillableServiceRequest`.

- Campos monetários chegam do front **em centavos inteiros** (o `MoneyInput` faz a conversão) e são
  validados com `integer|min:0` (ver 2.2.1). Não há parsing de "1.234,56" no backend; nunca grave
  string formatada em coluna numérica.
- Arrays validados item a item (`services.*.id`, `services.*.amount`, `brackets.*.min_value`…).
- Consuma só `$request->validated()` / `$request->safe()`; nunca `$request->all()` ou `except('_token')`.
- `authorize()` delega para a policy.

### 9.3 Autorização

Tudo restrito ao perfil **Administrador** (como na origem). Crie `QuotePolicy`, `FeeCalculationPolicy`
(o dono ou admin vê o cálculo), `ItbiMunicipalityPolicy`, `BillableServicePolicy`, e aplique a regra
de papel com o mecanismo do destino (middleware de rota ou `before()` na policy).
`ProposalCostItem` usa a policy de proposta existente + *scoped binding*.

### 9.4 Rotas (`routes/web.php`)

```php
Route::middleware(['auth', /* papel admin do destino */])->group(function () {
    Route::prefix('fee-calculator')->name('fee-calculator.')->group(function () {
        Route::get('/', [FeeCalculatorController::class, 'index'])->name('index');
        Route::get('states/{state}/municipalities', MunicipalityLookupController::class)->name('municipalities');
        Route::get('calculate/{type}', [FeeCalculatorController::class, 'create'])->name('create');
        Route::post('calculate', [FeeCalculatorController::class, 'store'])->name('store');
        Route::get('results/{feeCalculation}', [FeeCalculationController::class, 'show'])->name('results.show');
        Route::get('results/{feeCalculation}/attach', [ProposalFeeEstimateController::class, 'create'])->name('attach.create');
        Route::post('results/{feeCalculation}/attach', [ProposalFeeEstimateController::class, 'store'])->name('attach.store');
        Route::get('proposals/search', ProposalSearchController::class)->name('proposals.search');
    });

    Route::get('quotes/create/{feeCalculation}', [QuoteController::class, 'create'])->name('quotes.create');
    Route::resource('quotes', QuoteController::class)->except('create');
    Route::get('quotes/{quote}/pdf', QuotePdfController::class)->name('quotes.pdf');
    Route::post('quotes/{quote}/email', SendQuoteEmailController::class)->name('quotes.email');
    Route::post('quotes/{quote}/convert', ConvertQuoteToProposalController::class)->name('quotes.convert');

    Route::patch('proposals/{proposal}/cost-items/{costItem}', [ProposalCostItemController::class, 'update'])
        ->scopeBindings()->name('proposals.cost-items.update');

    Route::prefix('itbi')->name('itbi.')->group(function () {
        Route::resource('municipalities', ItbiMunicipalityController::class)->except('show');
        Route::get('municipalities/{municipality}/rate', [ItbiRateController::class, 'edit'])->name('rates.edit');
        Route::put('municipalities/{municipality}/rate', [ItbiRateController::class, 'update'])->name('rates.update');
        Route::get('municipalities/{municipality}/brackets', [ItbiBracketController::class, 'edit'])->name('brackets.edit');
        Route::put('municipalities/{municipality}/brackets', [ItbiBracketController::class, 'update'])->name('brackets.update');
    });

    Route::resource('billable-services', BillableServiceController::class)->except('show');
});
```

- Enum no parâmetro de rota (`{type}`, `{state}`) com *implicit enum binding*: valor inválido → 404.
- Rotas literais (`quotes/create/...`, `fee-calculator/proposals/search`) declaradas **antes** do
  `resource`/parâmetros que poderiam capturá-las. Rode `php artisan route:list` depois.
- Nada com efeito colateral em `GET` (a origem converte orçamento em proposta via `GET`).
- `throttle` na rota `fee-calculator.store` (a API externa é paga/limitada; ex.: 20/min por usuário).

---

## 10. Frontend (Inertia + Vue 3 + shadcn-vue)

### 10.1 Páginas (`resources/js/pages/`)

Siga a capitalização de pasta que o destino já usa (`pages/` ou `Pages/`).

| Página | Conteúdo | shadcn-vue |
|---|---|---|
| `FeeCalculator/Index.vue` | Texto de apresentação; `StateMunicipalityPicker`; cards dos tipos de cálculo habilitados para a UF escolhida (5.1); mensagem "- Selecione o Estado e o município da comarca -" antes da escolha | `Card`, `Select`, `Combobox` |
| `FeeCalculator/Calculate.vue` | Um formulário para os 3 tipos (campos condicionais por `type`); `MoneyInput`; `RadioGroup` SFH/SFI; `RadioGroup` primeiro imóvel (se módulo 03); botão "Possui desconto?" abre `DiscountDialog` e passa a mostrar o título do desconto escolhido; "Observações importantes" do tipo | `Card`, `Field*`, `RadioGroup`, `Button`, `Dialog` |
| `FeeCalculator/Result.vue` | Cabeçalho "{tipo} ({UF} - {município})", `FeeResultTable`, ações Voltar / Vincular à proposta / Gerar orçamento | `Table`, `Button` |
| `FeeCalculator/AttachToProposal.vue` | Combobox assíncrono de propostas, `ServicePicker`, Gerar | `Combobox`, `Checkbox`, `Dialog` |
| `Quotes/Index.vue` | Tabela paginada, busca, badge de status, ações (ver, editar, excluir com `AlertDialog`), botão "Nova calculadora" | `Table`, `Badge`, `Input`, `AlertDialog`, `Pagination` |
| `Quotes/Create.vue` / `Quotes/Edit.vue` | Dados do cliente, estado civil, banco, validade (`min` = hoje), `ServicePicker`; resumo do cálculo | `Field*`, `Input`, `Select`, date picker do destino |
| `Quotes/Show.vue` | Alertas (convertido/expirado), dados do cliente, `FeeResultTable`, ações Imprimir / Enviar por e-mail / Editar / Gerar proposta (confirmação) | `Alert`, `Table`, `AlertDialog` |
| `Itbi/Municipalities/{Index,Create,Edit}.vue` | CRUD; seleção do módulo | `Table`, `Select` |
| `Itbi/Rates/Edit.vue` | Campos conforme o módulo | `Field*` |
| `Itbi/Brackets/Edit.vue` | Linhas dinâmicas de faixas | `Table`, `Button` |
| `BillableServices/{Index,Create,Edit}.vue` | CRUD | `Table`, `Textarea`, `Switch` |

### 10.2 Componentes (`resources/js/components/fee-calculator/`)

- **`StateMunicipalityPicker.vue`** — `Select` das UFs suportadas (props do servidor) + `Combobox` de
  municípios carregados de `fee-calculator.municipalities` ao trocar a UF; pré-seleciona a capital;
  emite `{ state, ibgeCode, name, itbiModule }`. Um único listener por mudança de UF — a origem
  registra os handlers de clique de novo a cada troca de UF e dispara N requisições.
- **`DiscountDialog.vue`** — `Dialog` com um card por `FeeDiscount` permitido para a UF (título + texto
  legal); clicar seleciona e fecha; "Remover seleção" zera o `v-model`; "Sair" fecha sem mudar.
- **`FeeResultTable.vue`** — recebe o `FeeBreakdown` pronto do servidor (5.7) e só renderiza.
  Tabela com rolagem horizontal no mobile (`overflow-x-auto`).
- **`ServicePicker.vue`** — busca local, "Selecionar todos", `Checkbox` por serviço mostrando o preço
  de tabela, e "Editar valor" abrindo um `Dialog` com `MoneyInput` para o valor negociado. Emite
  `[{ id, amount|null }]`. (Na origem os IDs HTML se repetem dentro do loop; use `useId()`.)
- **`MoneyInput.vue`** — máscara BRL; o `v-model` expõe **centavos inteiros** (`123456` para
  R$ 1.234,56), não a string formatada nem float. É o mesmo componente do módulo de propostas;
  se o destino já tiver um, reutilize.

### 10.3 Estado e navegação

- Nada de chamada AJAX para buscar uma página só para redirecionar (a origem faz isso); use
  `router.visit`/`Link` do Inertia.
- Os parâmetros da tela de cálculo (UF, município, tipo) vão na URL (`?state=SP&ibge=3550308`) para
  permitir "Voltar" e recarregar sem perder a escolha.
- Erros de validação: `form.errors.campo` embaixo de cada campo (`FieldError`); flash global via toast
  (`Sonner`) lendo a prop compartilhada.
- Botões de submit desabilitados com `form.processing` (evita duplo envio de conversão/vinculação).
- Layout responsivo: formulário e "Observações importantes" em duas colunas no desktop, empilhados no mobile.

---

## 11. Testes e critérios de aceite

Use o framework de testes do destino (Pest ou PHPUnit). Banco de teste isolado; `Http::preventStrayRequests()`.

**Unit — `ItbiCalculator`** (dataset por linha, valores conferidos à mão; na tabela os valores estão
em reais para leitura, mas **entrada e asserção são em centavos** — ex.: V=500.000 → `50_000_000`,
esperado 7.500,00 → `750_000`):

| Caso | Entrada | Esperado |
|---|---|---|
| FinancedCap SFH, F abaixo do teto | V=500.000, F=300.000, rp=3%, rf=0,5%, cap=400.000 | 200.000×3% + 300.000×0,5% = 7.500,00 |
| FinancedCap SFH, F acima do teto | V=500.000, F=450.000, mesmos parâmetros | 100.000×3% + 400.000×0,5% = 5.000,00 |
| FirstPropertyRate SFH, primeiro imóvel | V=400.000, F=300.000, rp=2%, sim=0,5%, não=1% | 2.000 + 1.500 = 3.500,00 |
| FirstPropertyRate SFH, não é o primeiro | idem, `first_property=false` | 2.000 + 3.000 = 5.000,00 |
| Standard SFH | V=400.000, F=300.000, rp=3%, rf=0,5% | 3.000 + 1.500 = 4.500,00 |
| Qualquer módulo 01/03/04 em SFI ou registro geral | V=400.000, rp=3% | 12.000,00 |
| ValueBrackets SFH, dentro da faixa | V=250.000, faixa 200.000–300.000, 2%, desconto 1.000 | 4.000,00 |
| ValueBrackets SFH, **no limite** da faixa | V=300.000, mesma faixa | 5.000,00 (na origem: não calculava) |
| ValueBrackets SFI | V=400.000, `full_rate`=2,5% | 10.000,00 |
| Averbação | qualquer | `null` |
| Município sem cadastro | — | `ItbiNotConfiguredException` |

**Unit — `CostItemsBuilder`**: gera 1 linha por ato, 1 por taxa extra (`ExtraFee`), 1 por serviço
com valor negociado quando houver, ITBI só nos tipos 1 e 2.

**Feature (com `Http::fake`)**:

- cálculo com sucesso grava `FeeCalculation` e redireciona para o resultado;
- API responde `errorMessage` → erro no formulário com a mensagem (string);
- API responde 401/500 ou timeout → flash de indisponibilidade, nada gravado;
- payload enviado à API tem `consulta_id`, `codigo_municipio` e `desconto` corretos (`Http::assertSent`);
- averbação fora do RJ e compra no PA → 422;
- vincular: cria estimativa + linhas; segunda tentativa na mesma proposta → erro, sem duplicar;
- orçamento: criar (serviço com `amount = 150000` grava R$ 1.500,00 em centavos; string formatada → 422), editar a validade persiste, excluir convertido é bloqueado;
- API devolve `subtotal: 1500.35` → `proposal_cost_items.amount = 150035` (conversão reais → centavos sem perda);
- converter: cria 1 proposta; **segunda chamada não cria outra**; orçamento expirado → erro;
  cliente com CPF existente reaproveita o usuário (na origem isso dá erro 500);
  e-mail/PDF enfileirados só após o commit (`Queue::fake`, `Mail::fake` + `assertQueued`);
- usuário sem papel admin → 403 em todas as rotas do módulo;
- `extra_information` com `<script>` é salvo sanitizado.

**Critérios de aceite (manual)**: os 3 tipos calculam para SP e RJ; a tabela bate com o total da
API; o desconto escolhido aparece no botão e pode ser removido; o PDF abre e sai com os mesmos totais
da tela; o e-mail chega (Mailpit/Mailtrap); a proposta criada pela conversão mostra as linhas de custo.

---

## 12. Armadilhas da origem que não devem ser portadas

Detalhes, causa e correção em [`correcoes-modulo-calculadora-emolumentos.md`](correcoes-modulo-calculadora-emolumentos.md).

| Na origem | No destino |
|---|---|
| Token da API no código (e no histórico do git) | `config/services.php` + `.env`, token novo |
| `verify => false` | TLS ligado |
| Resultado do cálculo ida e volta pelo navegador | `fee_calculations` + UUID |
| "Vincular à proposta" falha (coluna `validade` NOT NULL) | `valid_until` nullable, obrigatório só no orçamento |
| Valor de serviço gravado como `"1.500,00"` | `MoneyInput` envia centavos inteiros; FormRequest valida `integer` |
| Conversão via `GET`, sem idempotência | `POST` + `lockForUpdate` + checagem de status |
| `$user` indefinido quando o CPF já existe | busca/criação explícita do usuário |
| Senha derivada do CPF enviada por e-mail | link de definição de senha |
| E-mail e PDF dentro da transação | depois do commit, em fila |
| Município casado pelo nome | `ibge_code` |
| Faixas de ITBI com limites exclusivos | limites inclusivos + validação de sobreposição |
| 2,5% fixo no código | `full_rate` cadastrável |
| Todas as linhas de ITBI carregadas a cada cálculo | só o município consultado |
| Todas as propostas carregadas e filtradas em PHP | `whereDoesntHave` + busca paginada |
| `errorMessage \|\| message` → flash "1" | `errorMessage ?? message` |
| Nomes de campo divergentes entre view e FormRequest | um contrato só, testado |
| "Remover seleção" não remove o desconto | `v-model` zerado |
| `extra_information` impresso sem sanitização | sanitizado no servidor |
| Edição do orçamento ignora a validade | `UpdateQuote` persiste `valid_until` |
| Excluir orçamento convertido apaga o vínculo da proposta | exclusão bloqueada |
| Dinheiro em `double`, percentuais em `string` | dinheiro em `unsignedBigInteger` (centavos); percentuais em `decimal(7,4)` |
| 3 controllers de cálculo duplicados, regra de totais na view | 1 controller, `FeeBreakdown` no servidor |

---

## 13. Textos fixos (pt-BR)

Coloque em `lang/pt_BR/fee_calculator.php` (ou no mecanismo de tradução do destino).

- **Apresentação:** "A Calculadora de Emolumentos estima os custos do registro do imóvel de forma
  rápida, eficaz e gratuita. Desta forma não é necessário se deslocar até o cartório para realizar a
  previsão do preço do registro do imóvel. Caso o negócio jurídico envolva mais de um imóvel, deve ser
  realizado um cálculo separado para cada um dos imóveis. O valor definitivo será calculado pelo
  respectivo Registro de Imóveis após o protocolo."
- **Observações comuns:** "Deve ser utilizado no cálculo o maior valor entre o valor declarado pelas
  partes e o da avaliação fiscal para fins do imposto de transmissão (ITBI ou ITCMD). Caso o negócio
  jurídico envolva mais de um imóvel, deve ser realizado um cálculo separado para cada um dos imóveis.
  A Calculadora de Emolumentos tem por objetivo fornecer uma estimativa dos valores previstos em lei
  para o registro pretendido. O valor definitivo será calculado pelo respectivo Registro de Imóveis
  após o protocolo."
- **Registro em geral:** "Utilize essa ferramenta para cálculo de registros de compra e venda,
  promessa de compra e venda, doação, usucapião, inventário, arrematação, dação em pagamento,
  integralização ao capital de sociedade, permuta, entre outros."
- **Compra e venda c/ alienação fiduciária:** "Utilize essa ferramenta para cálculo de compra e venda
  financiada pelo sistema financeiro. Para a avaliação do imóvel, deve ser utilizado no cálculo o maior
  valor entre o valor total de venda declarado pelas partes e o da avaliação fiscal para fins do ITBI.
  No valor do financiamento deve ser informado o valor total da dívida."
- **Averbação com valor econômico:** copie integralmente o bloco "Código de Normas da Corregedoria,
  Art. 1273" de `resources/views/app/calculadora/registro-averbacao.blade.php` da origem.
- **Aviso legal (PDF e e-mail):** "*OS VALORES APRESENTADOS ESTÃO SUSCETÍVEIS A ALTERAÇÕES DEPENDENDO
  DA ATRIBUIÇÃO DA VALORAÇÃO JUNTO À PREFEITURA COMPETENTE" (corrigido de "SUCETIVEIS" / "JUNTO A").
- **Mensagens de sucesso:** "Emolumento registrado com sucesso!", "Orçamento criado com sucesso!",
  "Orçamento atualizado com sucesso!", "Orçamento excluído com sucesso!", "Email enviado com sucesso!",
  "Proposta criada com sucesso!".

---

## 14. Ordem de implementação

1. **Pré-voo** (2.3) e decisões pendentes anotadas no PR: entidade de proposta, papéis, centavos na API.
2. Enums + migrations + models + factories.
3. `IbgeLocalityClient` + `StateMunicipalityPicker` + CRUD de municípios, alíquotas e faixas de ITBI.
4. CRUD de serviços cobráveis.
5. `ItbiCalculator` com os testes da seção 11 (antes de qualquer tela de cálculo).
6. `RegistryFeeApiClient` com `Http::fake`; `CalculateFees`; páginas `Index`/`Calculate`/`Result`; `FeeResultTable`.
7. Vincular à proposta + `CostItemsBuilder` + edição de linha de custo.
8. Orçamento: criar/listar/ver/editar/excluir.
9. PDF e e-mail (em fila).
10. Conversão em proposta.
11. Revisão final: `route:list`, suíte de testes verde, Pint/linters do destino, revisão de responsividade
    das páginas no mobile, e code review do diff.

### Checklist de entrega

- [ ] Nenhum nome de classe, arquivo, tabela ou coluna novo em português.
- [ ] Nenhum segredo no código; `.env.example` atualizado.
- [ ] Nenhuma rota `GET` com efeito colateral.
- [ ] Todo valor monetário gravado como inteiro em centavos (`unsignedBigInteger`), nunca string formatada nem float.
- [ ] Resultado do cálculo nunca volta do navegador — só o UUID.
- [ ] Testes da seção 11 passando.
- [ ] Textos da seção 13 em arquivo de tradução.
