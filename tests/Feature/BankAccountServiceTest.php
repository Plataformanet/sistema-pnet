<?php

use App\Enums\AccountsEnum;
use App\Enums\ContactTypeEnum;
use App\Exceptions\BankAccountHasFinancialEntriesException;
use App\Http\Requests\StoreAccountPayableRequest;
use App\Http\Requests\StoreAccountReceivableRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\FinancialCategory;
use App\Models\FinancialContact;
use App\Models\Installment;
use App\Services\AccountPayableService;
use App\Services\BankAccountService;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bankAccountPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Conta Corrente',
        'bank' => 'Banco Teste',
        'agency' => '0001',
        'account_number' => '12345',
        'account_type' => 'corrente',
        'initial_balance' => 150000,
        'active' => true,
        'main_account' => true,
    ], $overrides);
}

test('create define o saldo atual igual ao saldo inicial e ignora saldo atual enviado', function () {
    $bankAccount = app(BankAccountService::class)->create(
        bankAccountPayload(['current_balance' => 999999]),
        $this->tenant,
    );

    expect($bankAccount->initial_balance)->toBe(150000)
        ->and($bankAccount->current_balance)->toBe(150000);
});

test('update não altera o saldo inicial nem o saldo atual', function () {
    $service = app(BankAccountService::class);
    $bankAccount = $service->create(bankAccountPayload(), $this->tenant);

    $service->update((string) $bankAccount->id, bankAccountPayload([
        'name' => 'Conta Renomeada',
        'initial_balance' => 1,
        'current_balance' => 1,
    ]), $this->tenant);

    $fresh = $this->tenant->run(fn () => BankAccount::findOrFail($bankAccount->id));

    expect($fresh->name)->toBe('Conta Renomeada')
        ->and($fresh->initial_balance)->toBe(150000)
        ->and($fresh->current_balance)->toBe(150000);
});

test('a edição da conta bancária descarta o saldo inicial enviado pelo formulário', function () {
    $request = formRequest(UpdateBankAccountRequest::class, bankAccountPayload(['initial_balance' => 1]));

    expect($request->validated())->not->toHaveKey('initial_balance')
        ->and($request->validated())->not->toHaveKey('current_balance');
});

test('os saldos suportam valores acima do limite de integer', function () {
    $bankAccount = app(BankAccountService::class)->create(
        bankAccountPayload(['initial_balance' => 5_000_000_000]),
        $this->tenant,
    );

    $fresh = $this->tenant->run(fn () => BankAccount::findOrFail($bankAccount->id));

    expect($fresh->current_balance)->toBe(5_000_000_000);
});

/**
 * Cria uma conta a pagar de R$ 600,00 em 2 parcelas na conta bancária informada.
 */
function payableOnBankAccount(BankAccount $bankAccount): AccountPayable
{
    [$contact, $category] = test()->tenant->run(fn () => [
        Contact::create([
            'type' => ContactTypeEnum::SUPPLIER->value,
            'name_corporatereason' => 'Fornecedor',
            'cpf_cnpj' => '12345678000190',
            'email' => 'fornecedor@teste.com',
            'phone' => '1133334444',
            'cell_phone' => '11999998888',
        ]),
        FinancialCategory::create(['name' => 'Despesa', 'type' => 'despesa']),
    ]);

    return app(AccountPayableService::class)->create([
        'financial_category_id' => $category->id,
        'bank_account_id' => $bankAccount->id,
        'financial_contact_id' => $contact->id,
        'description' => 'Aluguel',
        'total' => 60000,
        'payment_method' => 'pix',
        'payment_condition' => '2',
        'total_installments' => 2,
        'value' => 30000,
        'due_date' => '2026-06-12',
        'status' => AccountsEnum::OPEN->value,
        'installments' => [],
    ], test()->tenant);
}

test('excluir a conta bancária com lançamentos é bloqueado e nada é alterado (RN-FIN-02)', function () {
    $bankAccount = app(BankAccountService::class)->create(bankAccountPayload(), $this->tenant);
    $account = payableOnBankAccount($bankAccount);

    expect(fn () => app(BankAccountService::class)->delete((string) $bankAccount->id, $this->tenant))
        ->toThrow(BankAccountHasFinancialEntriesException::class, 'existem lançamentos financeiros vinculados');

    $this->tenant->run(function () use ($bankAccount, $account) {
        expect(BankAccount::find($bankAccount->id))->not->toBeNull()
            ->and(AccountPayable::find($account->id))->not->toBeNull()
            ->and(Installment::where('installmentable_type', AccountPayable::class)
                ->where('installmentable_id', $account->id)
                ->count())->toBeGreaterThan(0);
    });
});

test('excluir a conta bancária sem lançamentos faz soft delete', function () {
    $bankAccount = app(BankAccountService::class)->create(bankAccountPayload(), $this->tenant);

    app(BankAccountService::class)->delete((string) $bankAccount->id, $this->tenant);

    $this->tenant->run(fn () => expect(BankAccount::find($bankAccount->id))->toBeNull()
        ->and(BankAccount::withTrashed()->find($bankAccount->id))->not->toBeNull());
});

test('lançamentos já excluídos não bloqueiam a exclusão da conta bancária', function () {
    $bankAccount = app(BankAccountService::class)->create(bankAccountPayload(), $this->tenant);
    $account = payableOnBankAccount($bankAccount);

    $this->tenant->run(fn () => $account->delete());

    app(BankAccountService::class)->delete((string) $bankAccount->id, $this->tenant);

    $this->tenant->run(fn () => expect(BankAccount::find($bankAccount->id))->toBeNull());
});

test('lançamentos a receber também bloqueiam a exclusão da conta bancária', function () {
    $bankAccount = app(BankAccountService::class)->create(bankAccountPayload(), $this->tenant);

    $this->tenant->run(fn () => AccountReceivable::create([
        'financial_category_id' => FinancialCategory::create(['name' => 'Receita', 'type' => 'receita'])->id,
        'bank_account_id' => $bankAccount->id,
        'financial_contact_id' => FinancialContact::create([
            'contact_id' => Contact::factory()->create()->id,
            'type' => ContactTypeEnum::CLIENT->value,
        ])->id,
        'description' => 'Honorários',
        'total' => 10000,
        'payment_method' => 'pix',
        'payment_condition' => '1',
        'total_installments' => 1,
    ]));

    expect(fn () => app(BankAccountService::class)->delete((string) $bankAccount->id, $this->tenant))
        ->toThrow(BankAccountHasFinancialEntriesException::class);
});

test('os requests de contas a pagar e a receber não pedem mais bank_account_out', function () {
    expect((new StoreAccountPayableRequest)->rules())->not->toHaveKey('bank_account_out')
        ->and((new StoreAccountReceivableRequest)->rules())->not->toHaveKey('bank_account_out');
});

test('as tabelas de contas não têm mais a coluna bank_account_out', function () {
    $this->tenant->run(function () {
        expect(Schema::hasColumn('account_payables', 'bank_account_out'))->toBeFalse()
            ->and(Schema::hasColumn('account_receivables', 'bank_account_out'))->toBeFalse();
    });
});
