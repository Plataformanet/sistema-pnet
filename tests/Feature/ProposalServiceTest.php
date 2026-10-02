<?php

use App\Enums\BankAccountType;
use App\Enums\MaritalStatus;
use App\Enums\PersonType;
use App\Enums\PropertyCondition;
use App\Enums\ProposalStatus;
use App\Enums\RolesEnum;
use App\Exceptions\LastApplicantException;
use App\Exceptions\PersonAlreadyInProposalException;
use App\Mail\ApplicantWelcomeMail;
use App\Mail\ProposalCreatedMail;
use App\Models\Applicant;
use App\Models\Bank;
use App\Models\Contact;
use App\Models\ContractType;
use App\Models\PropertyType;
use App\Models\Proposal;
use App\Models\Seller;
use App\Models\Stage;
use App\Models\User;
use App\Services\ApplicantService;
use App\Services\BankService;
use App\Services\ProposalQueryService;
use App\Services\ProposalService;
use App\Services\SellerService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            ->and($proposal->analyst_id)->toBeNull()
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

test('findForProposal devolve os proponentes da proposta no formato do formulário de edição', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);

    $applicants = app(ApplicantService::class)->findForProposal((string) $proposal->id, $this->tenant);

    expect($applicants)->toHaveCount(1)
        ->and($applicants[0])->toMatchArray([
            'cpf' => '52998224725',
            'name' => 'Maria Proponente',
            'email' => 'maria@example.com',
            'phone' => '11999990000',
            'marital_status' => MaritalStatus::MARRIED->value,
            'profession' => 'Engenheira',
            'declared_income' => 800_000,
        ])
        ->and($applicants[0]['bank_account']['bank_name'])->toBe('Itaú');
});

test('update do proponente altera contato, dados do proponente e conta bancária', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);
    $applicantId = $this->tenant->run(fn () => $proposal->applicants()->value('applicants.id'));

    app(ApplicantService::class)->update([
        'name' => 'Maria Atualizada',
        'email' => 'maria.nova@example.com',
        'phone' => '11977776666',
        'declared_income' => 900_000,
        'family_income' => 1_000_000,
        'marital_status' => MaritalStatus::SINGLE->value,
        'profession' => 'Fotógrafa',
        'birth_date' => '1982-09-03',
        'declares_income_tax' => true,
        'by_power_of_attorney' => false,
        'bank_account' => ['bank_name' => 'Caixa', 'account_type' => BankAccountType::SAVINGS->value, 'branch' => '1234', 'number' => '98765-4'],
    ], (string) $proposal->id, (string) $applicantId, $this->tenant);

    $this->tenant->run(function () use ($applicantId) {
        $applicant = Applicant::with('contact', 'bankAccount')->find($applicantId);

        expect($applicant->contact->name_corporatereason)->toBe('Maria Atualizada')
            ->and($applicant->contact->email)->toBe('maria.nova@example.com')
            ->and($applicant->contact->cell_phone)->toBe('11977776666')
            ->and($applicant->contact->cpf_cnpj)->toBe('52998224725')
            ->and($applicant->declared_income)->toBe(900_000)
            ->and($applicant->marital_status)->toBe(MaritalStatus::SINGLE)
            ->and($applicant->birth_date->format('Y-m-d'))->toBe('1982-09-03')
            ->and($applicant->bankAccount->bank_name)->toBe('Caixa');
    });
});

test('update do proponente remove a conta bancária enviada em branco', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);
    $applicantId = $this->tenant->run(fn () => $proposal->applicants()->value('applicants.id'));

    app(ApplicantService::class)->update([
        'name' => 'Maria Proponente',
        'email' => 'maria@example.com',
        'phone' => '11999990000',
        'declared_income' => 800_000,
        'marital_status' => MaritalStatus::MARRIED->value,
        'profession' => 'Engenheira',
        'bank_account' => null,
    ], (string) $proposal->id, (string) $applicantId, $this->tenant);

    $this->tenant->run(fn () => expect(Applicant::find($applicantId)->bankAccount()->exists())->toBeFalse());
});

test('update do proponente não aceita proponente de outra proposta', function () {
    $service = app(ProposalService::class);
    $proposal = $service->create(proposalPayload(), $this->creator, $this->tenant);
    $other = $service->create(proposalPayload([
        'applicants' => [array_merge(proposalPayload()['applicants'][0], ['cpf' => '11144477735', 'email' => 'outro@example.com'])],
        'sellers' => [],
    ]), $this->creator, $this->tenant);
    $otherApplicantId = $this->tenant->run(fn () => $other->applicants()->value('applicants.id'));

    app(ApplicantService::class)->update([
        'name' => 'Invasor',
        'email' => 'x@example.com',
        'phone' => '11900000000',
        'declared_income' => 0,
        'marital_status' => MaritalStatus::SINGLE->value,
        'profession' => 'X',
    ], (string) $proposal->id, (string) $otherApplicantId, $this->tenant);
})->throws(ModelNotFoundException::class);

test('addApplicant inclui proponente novo na proposta e envia o e-mail de boas-vindas', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);
    Mail::fake();

    $applicant = app(ProposalService::class)->addApplicant(array_merge(proposalPayload()['applicants'][0], [
        'cpf' => '11144477735',
        'name' => 'Flavio Proponente',
        'email' => 'flavio@example.com',
    ]), (string) $proposal->id, $this->tenant);

    $this->tenant->run(fn () => expect($proposal->applicants()->pluck('applicants.id')->all())->toContain($applicant->id)
        ->and($proposal->applicants()->count())->toBe(2));

    Mail::assertQueued(ApplicantWelcomeMail::class);
});

test('addApplicant reaproveita o proponente existente pelo CPF e avisa da nova proposta', function () {
    $service = app(ProposalService::class);
    $first = $service->create(proposalPayload(), $this->creator, $this->tenant);
    $second = $service->create(proposalPayload([
        'applicants' => [array_merge(proposalPayload()['applicants'][0], ['cpf' => '11144477735', 'email' => 'outro@example.com'])],
        'sellers' => [],
    ]), $this->creator, $this->tenant);
    Mail::fake();

    $applicant = $service->addApplicant(['cpf' => '52998224725', 'existing' => true], (string) $second->id, $this->tenant);

    $this->tenant->run(fn () => expect(Applicant::count())->toBe(2)
        ->and($applicant->id)->toBe($first->applicants()->value('applicants.id')));

    Mail::assertQueued(ProposalCreatedMail::class);
    Mail::assertNotQueued(ApplicantWelcomeMail::class);
});

test('addApplicant bloqueia o proponente que já está na proposta', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);

    app(ProposalService::class)->addApplicant(['cpf' => '52998224725', 'existing' => true], (string) $proposal->id, $this->tenant);
})->throws(PersonAlreadyInProposalException::class, 'Este proponente já está vinculado à proposta.');

test('addSeller inclui o vendedor na proposta e bloqueia o mesmo documento duas vezes', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(['sellers' => []]), $this->creator, $this->tenant);
    $seller = [
        'person_type' => PersonType::COMPANY->value,
        'document' => '11222333000181',
        'name' => 'Construtora LTDA',
        'email' => 'contato@construtora.com',
    ];

    app(ProposalService::class)->addSeller($seller, (string) $proposal->id, $this->creator, $this->tenant);

    $this->tenant->run(fn () => expect($proposal->sellers()->count())->toBe(1));

    expect(fn () => app(ProposalService::class)->addSeller($seller, (string) $proposal->id, $this->creator, $this->tenant))
        ->toThrow(PersonAlreadyInProposalException::class, 'Este vendedor já está vinculado à proposta.');
});

test('findForProposal dos vendedores devolve o formato do formulário de edição', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);

    $sellers = app(SellerService::class)->findForProposal((string) $proposal->id, $this->tenant);

    expect($sellers)->toHaveCount(1)
        ->and($sellers[0])->toMatchArray([
            'person_type' => PersonType::INDIVIDUAL->value,
            'document' => '11144477735',
            'name' => 'João Vendedor',
            'email' => 'joao@example.com',
            'bank_account' => null,
        ]);
});

test('update do vendedor altera contato, dados do vendedor e conta bancária', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);
    $sellerId = $this->tenant->run(fn () => $proposal->sellers()->value('sellers.id'));

    app(SellerService::class)->update([
        'name' => 'João Atualizado',
        'email' => 'joao.novo@example.com',
        'phone' => '11955554444',
        'marital_status' => MaritalStatus::DIVORCED->value,
        'profession' => 'Comerciante',
        'declared_income' => 500_000,
        'bank_account' => ['bank_name' => 'Bradesco', 'account_type' => BankAccountType::CHECKING->value, 'branch' => '0101', 'number' => '1111-1'],
    ], (string) $proposal->id, (string) $sellerId, $this->tenant);

    $this->tenant->run(function () use ($sellerId) {
        $seller = Seller::with('contact', 'bankAccount')->find($sellerId);

        expect($seller->contact->name_corporatereason)->toBe('João Atualizado')
            ->and($seller->contact->email)->toBe('joao.novo@example.com')
            ->and($seller->contact->cpf_cnpj)->toBe('11144477735')
            ->and($seller->marital_status)->toBe(MaritalStatus::DIVORCED)
            ->and($seller->declared_income)->toBe(500_000)
            ->and($seller->bankAccount->bank_name)->toBe('Bradesco');
    });
});

test('update do vendedor não aceita vendedor de outra proposta', function () {
    $service = app(ProposalService::class);
    $proposal = $service->create(proposalPayload(['sellers' => []]), $this->creator, $this->tenant);
    $other = $service->create(proposalPayload(), $this->creator, $this->tenant);
    $otherSellerId = $this->tenant->run(fn () => $other->sellers()->value('sellers.id'));

    app(SellerService::class)->update([
        'name' => 'Invasor',
        'email' => 'x@example.com',
    ], (string) $proposal->id, (string) $otherSellerId, $this->tenant);
})->throws(ModelNotFoundException::class);

test('removeApplicant desvincula o proponente e mantém o cadastro e o usuário dele', function () {
    $service = app(ProposalService::class);
    $proposal = $service->create(proposalPayload(), $this->creator, $this->tenant);
    $added = $service->addApplicant(array_merge(proposalPayload()['applicants'][0], [
        'cpf' => '11144477735',
        'email' => 'flavio@example.com',
    ]), (string) $proposal->id, $this->tenant);

    $service->removeApplicant((string) $proposal->id, (string) $added->id, $this->tenant);

    $this->tenant->run(fn () => expect($proposal->applicants()->pluck('applicants.id')->all())->not->toContain($added->id)
        ->and(Applicant::find($added->id))->not->toBeNull()
        ->and(User::find($added->user_id))->not->toBeNull());
});

test('removeApplicant bloqueia a remoção do único proponente da proposta', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);
    $applicantId = $this->tenant->run(fn () => $proposal->applicants()->value('applicants.id'));

    app(ProposalService::class)->removeApplicant((string) $proposal->id, (string) $applicantId, $this->tenant);
})->throws(LastApplicantException::class);

test('removeSeller desvincula o vendedor e mantém o cadastro dele', function () {
    $proposal = app(ProposalService::class)->create(proposalPayload(), $this->creator, $this->tenant);
    $sellerId = $this->tenant->run(fn () => $proposal->sellers()->value('sellers.id'));

    app(ProposalService::class)->removeSeller((string) $proposal->id, (string) $sellerId, $this->tenant);

    $this->tenant->run(fn () => expect($proposal->sellers()->count())->toBe(0)
        ->and(Seller::find($sellerId))->not->toBeNull());
});

test('removeSeller não aceita vendedor de outra proposta', function () {
    $service = app(ProposalService::class);
    $proposal = $service->create(proposalPayload(['sellers' => []]), $this->creator, $this->tenant);
    $other = $service->create(proposalPayload(), $this->creator, $this->tenant);
    $otherSellerId = $this->tenant->run(fn () => $other->sellers()->value('sellers.id'));

    $service->removeSeller((string) $proposal->id, (string) $otherSellerId, $this->tenant);
})->throws(ModelNotFoundException::class);

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

test('a proposta fica sem analista até a equipe assumir, e o analista pode ser definido e retirado na edição', function () {
    $analyst = userWithRole($this->tenant, RolesEnum::ANALYST);
    $service = app(ProposalService::class);
    $proposal = $service->create(proposalPayload(), $this->creator, $this->tenant);
    $base = array_merge(proposalPayload(), ['partner_ids' => [], 'status' => ProposalStatus::NEW->value]);

    $this->tenant->run(fn () => expect($proposal->fresh()->analyst_id)->toBeNull());

    $service->update(array_merge($base, ['analyst_id' => $analyst->id]), (string) $proposal->id, $this->tenant);
    $this->tenant->run(fn () => expect($proposal->fresh()->analyst_id)->toBe($analyst->id));

    $service->update(array_merge($base, ['analyst_id' => null]), (string) $proposal->id, $this->tenant);
    $this->tenant->run(fn () => expect($proposal->fresh()->analyst_id)->toBeNull());
});

test('a proposta escolhida com analista na criação já nasce com ele', function () {
    $analyst = userWithRole($this->tenant, RolesEnum::ANALYST);

    $proposal = app(ProposalService::class)->create(proposalPayload(['analyst_id' => $analyst->id]), $this->creator, $this->tenant);

    $this->tenant->run(fn () => expect($proposal->fresh()->analyst_id)->toBe($analyst->id));
});
