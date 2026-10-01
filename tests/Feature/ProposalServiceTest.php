<?php

use App\Enums\MaritalStatus;
use App\Enums\PersonType;
use App\Enums\PropertyCondition;
use App\Enums\ProposalStatus;
use App\Enums\RolesEnum;
use App\Mail\ApplicantWelcomeMail;
use App\Mail\ProposalCreatedMail;
use App\Models\Applicant;
use App\Models\Bank;
use App\Models\Contact;
use App\Models\ContractType;
use App\Models\PropertyType;
use App\Models\Proposal;
use App\Models\Stage;
use App\Models\User;
use App\Services\ApplicantService;
use App\Services\BankService;
use App\Services\ProposalQueryService;
use App\Services\ProposalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->tenant = sharedTenant();
    Mail::fake();

    [$this->creator, $this->bank, $this->contractType] = $this->tenant->run(fn () => [
        User::factory()->create(),
        Bank::factory()->create(),
        ContractType::factory()->create(),
    ]);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function proposalPayload(array $overrides = []): array
{
    return array_merge([
        'creator_id' => test()->creator->id,
        'bank_id' => test()->bank->id,
        'contract_type_id' => test()->contractType->id,
        'amortization_table' => 'sac',
        'payment_term' => 360,
        'property_condition' => PropertyCondition::USED->value,
        'purchase_value' => 50_000_000,
        'down_payment_value' => 10_000_000,
        'applicants' => [[
            'cpf' => '52998224725',
            'name' => 'Maria Proponente',
            'email' => 'maria@example.com',
            'phone' => '11999990000',
            'declared_income' => 800_000,
            'marital_status' => MaritalStatus::MARRIED->value,
            'profession' => 'Engenheira',
            'bank_account' => [
                'bank_name' => 'Itaú',
                'account_type' => 1,
                'branch' => '0001',
                'number' => '12345-6',
            ],
        ]],
        'sellers' => [[
            'person_type' => PersonType::INDIVIDUAL->value,
            'document' => '11144477735',
            'name' => 'João Vendedor',
            'email' => 'joao@example.com',
        ]],
    ], $overrides);
}

test('create grava a proposta como nova com proponente, vendedor e timeline', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);

    $this->tenant->run(function () use ($proposal) {
        $proposal->refresh()->load('applicants.contact', 'applicants.bankAccount', 'sellers.contact', 'stages');

        expect($proposal->status)->toBe(ProposalStatus::NEW)
            ->and($proposal->analyst_id)->toBe($this->creator->id)
            ->and($proposal->purchase_value)->toBe(50_000_000)
            ->and($proposal->applicants)->toHaveCount(1)
            ->and($proposal->applicants->first()->contact->cpf_cnpj)->toBe('52998224725')
            ->and($proposal->applicants->first()->bankAccount->bank_name)->toBe('Itaú')
            ->and($proposal->sellers->first()->contact->name_corporatereason)->toBe('João Vendedor')
            ->and($proposal->stages)->toHaveCount(Stage::count())
            ->and($proposal->stages->first()->is_current)->toBeTrue()
            ->and($proposal->stages->whereNotNull('started_at'))->toBeEmpty();
    });
});

test('proponente novo vira usuário com cargo Cliente e recebe link de definição de senha', function () {
    app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);

    $this->tenant->run(function () {
        $user = Applicant::with('user')->firstWhere('user_id', '!=', null)->user;

        expect($user->email)->toBe('maria@example.com')
            ->and($user->hasRole(RolesEnum::CLIENT->label()))->toBeTrue();
    });

    Mail::assertQueued(ApplicantWelcomeMail::class, fn (ApplicantWelcomeMail $mail) => str_contains($mail->setPasswordUrl, 'token='));
    Mail::assertNotQueued(ProposalCreatedMail::class);
});

test('proponente existente é reaproveitado pelo CPF e recebe aviso de nova proposta', function () {
    $service = app(ProposalService::class);
    $first = $service->create(proposalPayload(), $this->creator, $this->tenant);

    Mail::fake();

    $second = $service->create(proposalPayload([
        'applicants' => [['cpf' => '529.982.247-25', 'existing' => true]],
        'sellers' => [],
    ]), $this->creator, $this->tenant);

    $this->tenant->run(function () use ($first, $second) {
        expect(Applicant::count())->toBe(1)
            ->and(User::where('email', 'maria@example.com')->count())->toBe(1)
            ->and($second->applicants()->first()->id)->toBe($first->applicants()->first()->id);
    });

    Mail::assertQueued(ProposalCreatedMail::class);
    Mail::assertNotQueued(ApplicantWelcomeMail::class);
});

test('findOrCreateByCpf reaproveita o contato já cadastrado em outro papel', function () {
    $contact = $this->tenant->run(fn () => Contact::factory()->create(['cpf_cnpj' => '52998224725']));

    $applicant = app(ApplicantService::class)->findOrCreateByCpf([
        'cpf' => '52998224725',
        'name' => 'Outro Nome',
        'email' => 'novo@example.com',
        'phone' => '11988887777',
    ], $this->tenant);

    expect($applicant->contact_id)->toBe($contact->id)
        ->and($applicant->user->wasRecentlyCreated)->toBeTrue();
});

test('create grava parceiros e imóvel', function () {
    [$partner, $propertyType] = $this->tenant->run(fn () => [User::factory()->create(), PropertyType::factory()->create()]);

    $proposal = app(ProposalService::class)->create(proposalPayload([
        'partner_ids' => [$partner->id],
        'property' => ['property_type_id' => $propertyType->id, 'address' => 'Rua A', 'number' => '10'],
    ]), $this->creator, $this->tenant);

    $this->tenant->run(function () use ($proposal, $partner) {
        expect($proposal->partners()->pluck('users.id')->all())->toBe([$partner->id])
            ->and($proposal->property()->first()->address)->toBe('Rua A');
    });
});

test('update aplica o status manual e limpa motivos que não se aplicam', function () {
    $proposal = createProposal($this->tenant, ['bank_id' => $this->bank->id, 'contract_type_id' => $this->contractType->id]);
    $service = app(ProposalService::class);
    $base = array_merge(proposalPayload(), ['partner_ids' => []]);

    $service->update(array_merge($base, ['status' => ProposalStatus::CANCELED->value, 'cancellation_reason' => 'Desistência']), (string) $proposal->id, $this->tenant);

    $this->tenant->run(fn () => expect($proposal->fresh()->cancellation_reason)->toBe('Desistência'));

    $service->update(array_merge($base, [
        'status' => ProposalStatus::AWAITING_PROPERTY->value,
        'expected_delivery_month' => 6,
        'expected_delivery_year' => 2027,
    ]), (string) $proposal->id, $this->tenant);

    $this->tenant->run(function () use ($proposal) {
        $fresh = $proposal->fresh();

        expect($fresh->status)->toBe(ProposalStatus::AWAITING_PROPERTY)
            ->and($fresh->cancellation_reason)->toBeNull()
            ->and($fresh->expected_delivery_month)->toBe(6);
    });
});

test('update não altera o status de proposta finalizada', function () {
    $proposal = createProposal($this->tenant, [
        'status' => ProposalStatus::FINISHED,
        'finished_at' => now(),
        'bank_id' => $this->bank->id,
        'contract_type_id' => $this->contractType->id,
    ]);

    app(ProposalService::class)->update(
        array_merge(proposalPayload(), ['status' => ProposalStatus::IN_PROGRESS->value]),
        (string) $proposal->id,
        $this->tenant,
    );

    $this->tenant->run(function () use ($proposal) {
        expect($proposal->fresh()->status)->toBe(ProposalStatus::FINISHED)
            ->and($proposal->fresh()->finished_at)->not->toBeNull();
    });
});

test('excluir a proposta é soft delete e preserva os usuários dos proponentes', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);

    app(ProposalService::class)->delete((string) $proposal->id, $this->tenant);

    $this->tenant->run(function () use ($proposal) {
        expect(Proposal::find($proposal->id))->toBeNull()
            ->and(Proposal::withTrashed()->find($proposal->id))->not->toBeNull()
            ->and(User::where('email', 'maria@example.com')->exists())->toBeTrue();
    });
});

test('excluir um catálogo em uso não quebra a proposta', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);

    $this->tenant->run(function () use ($proposal) {
        $this->bank->delete();
        $this->contractType->delete();

        $fresh = Proposal::with('bank', 'contractType')->findOrFail($proposal->id);

        expect($fresh->bank->name)->toBe($this->bank->name)
            ->and($fresh->contractType->id)->toBe($this->contractType->id);
    });
});

test('a coluna "Em uso" dos bancos conta as propostas', function () {
    app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);

    $row = collect(app(BankService::class)->findAll(['search' => $this->bank->name], $this->tenant)->items())
        ->firstWhere('id', $this->bank->id);

    expect($row['in_use_count'])->toBe(1);
});

test('o lookup por CPF devolve só nome e CPF para cargos externos e tudo para a equipe', function () {
    app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);
    $partner = userWithRole($this->tenant, RolesEnum::PARTNER);
    $admin = userWithRole($this->tenant, RolesEnum::ADMIN);

    $restricted = app(ApplicantService::class)->lookup('529.982.247-25', $partner, $this->tenant);
    $full = app(ApplicantService::class)->lookup('52998224725', $admin, $this->tenant);

    expect($restricted)->toBe(['existing' => true, 'name' => 'Maria Proponente', 'cpf' => '52998224725'])
        ->and($full['email'])->toBe('maria@example.com')
        ->and($full['declared_income'])->toBe(800_000)
        ->and($full['bank_account']['bank_name'])->toBe('Itaú');
});

test('a tela da proposta não carrega conta bancária nem renda das partes', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);

    $display = app(ProposalQueryService::class)->findForDisplay((string) $proposal->id, $this->tenant)->toArray();

    expect($display['applicants'][0])->not->toHaveKeys(['bank_account', 'declared_income', 'family_income', 'income_tax_notes'])
        ->and($display['applicants'][0]['contact']['name_corporatereason'])->toBe('Maria Proponente')
        ->and($display['sellers'][0])->not->toHaveKeys(['bank_account', 'declared_income'])
        ->and($display['sellers'][0]['contact']['name_corporatereason'])->toBe('João Vendedor');
});

test('a busca por CPF acha o proponente cujo contato veio formatado do módulo de Cadastros', function () {
    $this->tenant->run(fn () => Contact::factory()->create(['cpf_cnpj' => '529.982.247-25', 'name_corporatereason' => 'Maria Proponente']));
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);
    $admin = userWithRole($this->tenant, RolesEnum::ADMIN);

    $ids = collect(app(ProposalQueryService::class)->paginate(['search' => '52998224725'], $admin, $this->tenant)->items())->pluck('id');

    expect($ids->all())->toBe([$proposal->id]);
});

test('o PDF de lista das propostas tem um limite de linhas', function () {
    $admin = userWithRole($this->tenant, RolesEnum::ADMIN);

    $sql = $this->tenant->run(function () use ($admin) {
        DB::enableQueryLog();

        app(ProposalQueryService::class)->forPrint([], $admin, $this->tenant);

        return collect(DB::getQueryLog())->pluck('query')->first(fn (string $query) => str_starts_with($query, 'select * from `proposals`'));
    });

    expect($sql)->toContain('limit '.ProposalQueryService::PRINT_LIMIT);
});
