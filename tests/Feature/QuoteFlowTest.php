<?php

use App\Actions\FeeCalculator\AttachFeeEstimateToProposalAction;
use App\Actions\FeeCalculator\ConvertQuoteToProposalAction;
use App\Actions\FeeCalculator\CreateQuoteAction;
use App\Actions\FeeCalculator\UpdateQuoteAction;
use App\Enums\CalculationTypeEnum;
use App\Enums\CostItemTypeEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\SupportedStateEnum;
use App\Exceptions\ProposalAlreadyHasFeeEstimateException;
use App\Exceptions\QuoteNotOpenException;
use App\Http\Requests\StoreQuoteRequest;
use App\Jobs\AttachQuotePdfToProposalJob;
use App\Mail\ApplicantWelcomeMail;
use App\Mail\ProposalCreatedMail;
use App\Mail\QuoteSummaryMail;
use App\Models\Applicant;
use App\Models\Bank;
use App\Models\BillableService;
use App\Models\FeeCalculation;
use App\Models\FeeEstimate;
use App\Models\Proposal;
use App\Models\Quote;
use App\Models\Stage;
use App\Models\User;
use App\Services\ApplicantService;
use App\Services\QuoteService;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = sharedTenant();
    Mail::fake();
    Queue::fake();

    [$this->user, $this->bank, $this->service, $this->calculation] = $this->tenant->run(function () {
        $user = User::factory()->create();

        return [
            $user,
            Bank::factory()->create(),
            BillableService::factory()->create(['name' => 'Assessoria', 'price' => 200000, 'generates_receipt' => true]),
            FeeCalculation::create([
                'user_id' => $user->id,
                'type' => CalculationTypeEnum::GENERAL_REGISTRATION,
                'state' => SupportedStateEnum::SP,
                'municipality_ibge_code' => 3550308,
                'municipality_name' => 'São Paulo',
                'input' => ['property_value' => 40_000_000],
                'api_result' => [
                    'atos' => [['descricao' => 'Registro', 'emolumentos' => 1000.00, 'subtotal' => 1500.35]],
                    'taxas_extras' => [['descricao' => 'Prenotação', 'valor' => 45.10]],
                    'total' => 1545.45,
                    'extra_information' => null,
                ],
                'fees_total' => 154545,
                'itbi_amount' => 1_200_000,
                'expires_at' => now()->addDays(7),
            ]),
        ];
    });
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function quoteData(array $overrides = []): array
{
    return array_merge([
        'name' => 'maria   da silva',
        'cpf' => '529.982.247-25',
        'email' => 'Maria@Example.com',
        'phone' => '11999990000',
        'profession' => 'Engenheira',
        'marital_status' => MaritalStatusEnum::SINGLE->value,
        'bank_id' => test()->bank->id,
        'valid_until' => now()->addDays(10)->toDateString(),
        'services' => [['id' => test()->service->id, 'amount' => 150000]],
    ], $overrides);
}

function createTestQuote(array $overrides = []): Quote
{
    return app(CreateQuoteAction::class)->handle(quoteData($overrides), test()->calculation, test()->user, test()->tenant);
}

test('vincular à proposta cria a estimativa sem validade, as linhas de custo e o serviço', function () {
    $proposal = createProposal($this->tenant);

    app(AttachFeeEstimateToProposalAction::class)->handle($this->calculation, (string) $proposal->id, [['id' => $this->service->id]], $this->tenant);

    $this->tenant->run(function () use ($proposal) {
        $estimate = $proposal->feeEstimate()->firstOrFail();
        $items = $proposal->costItems()->get();

        expect($estimate->valid_until)->toBeNull()
            ->and($items->pluck('type')->all())->toEqualCanonicalizing([CostItemTypeEnum::EMOLUMENT, CostItemTypeEnum::EXTRA_FEE, CostItemTypeEnum::SERVICE, CostItemTypeEnum::ITBI])
            ->and($items->firstWhere('type', CostItemTypeEnum::SERVICE)->amount)->toBe(200000)
            ->and($items->firstWhere('type', CostItemTypeEnum::EMOLUMENT)->amount)->toBe(150035)
            ->and($proposal->feesTotal())->toBe(154545);
    });
});

test('a segunda vinculação na mesma proposta é bloqueada sem duplicar linhas', function () {
    $proposal = createProposal($this->tenant);
    $action = app(AttachFeeEstimateToProposalAction::class);

    $action->handle($this->calculation, (string) $proposal->id, [], $this->tenant);

    expect(fn () => $action->handle($this->calculation, (string) $proposal->id, [], $this->tenant))
        ->toThrow(ProposalAlreadyHasFeeEstimateException::class);

    $this->tenant->run(fn () => expect($proposal->costItems()->count())->toBe(3));
});

test('propostas com emolumento saem da seleção do vínculo', function () {
    $with = createProposal($this->tenant);
    $without = createProposal($this->tenant);
    app(AttachFeeEstimateToProposalAction::class)->handle($this->calculation, (string) $with->id, [], $this->tenant);

    $this->tenant->run(function () use ($with, $without) {
        $ids = Proposal::withoutFeeEstimate()->pluck('id');

        expect($ids)->toContain($without->id)->not->toContain($with->id);
    });
});

test('criar orçamento normaliza o cliente e grava o valor negociado do serviço em centavos', function () {
    $quote = createTestQuote();

    $this->tenant->run(function () use ($quote) {
        $fresh = Quote::with('feeEstimate', 'services')->findOrFail($quote->id);

        expect($fresh->name)->toBe('Maria Da Silva')
            ->and($fresh->cpf)->toBe('52998224725')
            ->and($fresh->email)->toBe('maria@example.com')
            ->and($fresh->services->first()->pivot->amount)->toBe(150000)
            ->and($fresh->feeEstimate->valid_until->toDateString())->toBe(now()->addDays(10)->toDateString())
            ->and($fresh->status())->toBe(QuoteStatusEnum::OPEN);
    });
});

test('valor de serviço formatado como texto é rejeitado e validade passada também', function () {
    $request = StoreQuoteRequest::create('/', 'POST', quoteData([
        'services' => [['id' => $this->service->id, 'amount' => '1.500,00']],
        'valid_until' => now()->subDay()->toDateString(),
    ]));
    $request->setContainer(app())->setRedirector(app(Redirector::class));

    try {
        $request->validateResolved();
        $errors = [];
    } catch (ValidationException $exception) {
        $errors = $exception->errors();
    }

    expect($errors)->toHaveKeys(['services.0.amount', 'valid_until']);
});

test('editar o orçamento persiste a nova validade', function () {
    $quote = createTestQuote();
    $newDate = now()->addDays(30)->toDateString();

    app(UpdateQuoteAction::class)->handle((string) $quote->id, quoteData(['valid_until' => $newDate, 'services' => []]), $this->tenant);

    $this->tenant->run(function () use ($quote, $newDate) {
        $fresh = Quote::with('feeEstimate', 'services')->findOrFail($quote->id);

        expect($fresh->feeEstimate->valid_until->toDateString())->toBe($newDate)
            ->and($fresh->services)->toBeEmpty();
    });
});

test('orçamento expirado tem status expirado', function () {
    $quote = createTestQuote();
    $this->tenant->run(fn () => $quote->feeEstimate()->update(['valid_until' => now()->subDay()->toDateString()]));

    $this->tenant->run(fn () => expect($quote->fresh()->status())->toBe(QuoteStatusEnum::EXPIRED));
});

test('converter cria uma proposta com proponente, linhas de custo e estimativa vinculada', function () {
    $quote = createTestQuote();

    $proposal = app(ConvertQuoteToProposalAction::class)->handle((string) $quote->id, $this->user, $this->tenant);

    $this->tenant->run(function () use ($proposal, $quote) {
        $proposal->refresh();
        $items = $proposal->costItems()->get();

        expect($proposal->bank_id)->toBe($this->bank->id)
            ->and($proposal->creator_id)->toBe($this->user->id)
            ->and($proposal->applicants()->first()->contact->cpf_cnpj)->toBe('52998224725')
            ->and($proposal->stages()->count())->toBeGreaterThan(0)
            ->and($items->firstWhere('type', CostItemTypeEnum::SERVICE)->amount)->toBe(150000)
            ->and(FeeEstimate::where('quote_id', $quote->id)->value('proposal_id'))->toBe($proposal->id)
            ->and($quote->fresh()->status())->toBe(QuoteStatusEnum::CONVERTED);
    });

    Queue::assertPushed(AttachQuotePdfToProposalJob::class, fn (AttachQuotePdfToProposalJob $job) => $job->afterCommit === true && $job->proposalId === $proposal->id);
    Mail::assertQueued(ApplicantWelcomeMail::class, fn (ApplicantWelcomeMail $mail) => $mail->afterCommit === true);
});

test('converter instancia a timeline do banco do orçamento', function () {
    $ids = $this->tenant->run(fn () => [
        Stage::factory()->forBank($this->bank)->create(['order' => 1])->id,
        Stage::factory()->forBank($this->bank)->create(['order' => 2])->id,
    ]);
    $quote = createTestQuote();

    $proposal = app(ConvertQuoteToProposalAction::class)->handle((string) $quote->id, $this->user, $this->tenant);

    $this->tenant->run(fn () => expect($proposal->stages()->pluck('stage_id')->all())->toBe($ids));
});

test('a segunda conversão não cria outra proposta', function () {
    $quote = createTestQuote();
    $action = app(ConvertQuoteToProposalAction::class);
    $action->handle((string) $quote->id, $this->user, $this->tenant);

    expect(fn () => $action->handle((string) $quote->id, $this->user, $this->tenant))->toThrow(QuoteNotOpenException::class);

    $this->tenant->run(fn () => expect(Proposal::count())->toBe(1));
});

test('orçamento expirado não pode ser convertido', function () {
    $quote = createTestQuote();
    $this->tenant->run(fn () => $quote->feeEstimate()->update(['valid_until' => now()->subDay()->toDateString()]));

    app(ConvertQuoteToProposalAction::class)->handle((string) $quote->id, $this->user, $this->tenant);
})->throws(QuoteNotOpenException::class);

test('cliente com CPF já cadastrado é reaproveitado na conversão', function () {
    $existing = app(ApplicantService::class)->findOrCreateByCpf([
        'cpf' => '52998224725',
        'name' => 'Maria da Silva',
        'email' => 'maria@example.com',
        'phone' => '11999990000',
    ], $this->tenant);
    $quote = createTestQuote();

    $proposal = app(ConvertQuoteToProposalAction::class)->handle((string) $quote->id, $this->user, $this->tenant);

    $this->tenant->run(function () use ($existing, $proposal) {
        expect(Applicant::count())->toBe(1)
            ->and($proposal->applicants()->first()->id)->toBe($existing->id);
    });

    Mail::assertQueued(ProposalCreatedMail::class);
});

test('orçamento convertido não pode ser editado nem excluído', function () {
    $quote = createTestQuote();
    app(ConvertQuoteToProposalAction::class)->handle((string) $quote->id, $this->user, $this->tenant);

    expect(fn () => app(UpdateQuoteAction::class)->handle((string) $quote->id, quoteData(), $this->tenant))->toThrow(QuoteNotOpenException::class)
        ->and(fn () => app(QuoteService::class)->delete((string) $quote->id, $this->tenant))->toThrow(QuoteNotOpenException::class);

    $this->tenant->run(fn () => expect(FeeEstimate::where('quote_id', $quote->id)->value('proposal_id'))->not->toBeNull());
});

test('o e-mail do orçamento leva o PDF anexado, gerado só no envio', function () {
    $quote = createTestQuote();
    $mail = new QuoteSummaryMail($quote->id, 'Maria Da Silva', $quote->number, '10/10/2026', app(QuoteService::class)->breakdown(
        app(QuoteService::class)->findForDisplay((string) $quote->id, $this->tenant)
    )->toArray());

    $attachment = $mail->attachments()[0];
    $contents = $attachment->attachWith(fn () => null, fn (Closure $data) => $data());

    expect($mail->afterCommit)->toBeTrue()
        ->and($attachment->as)->toBe('orcamento-'.$quote->number.'.pdf')
        ->and($attachment->mime)->toBe('application/pdf')
        ->and($contents)->toStartWith('%PDF');
});

test('excluir orçamento em aberto é soft delete', function () {
    $quote = createTestQuote();

    app(QuoteService::class)->delete((string) $quote->id, $this->tenant);

    $this->tenant->run(function () use ($quote) {
        expect(Quote::find($quote->id))->toBeNull()
            ->and(Quote::withTrashed()->find($quote->id))->not->toBeNull();
    });
});
