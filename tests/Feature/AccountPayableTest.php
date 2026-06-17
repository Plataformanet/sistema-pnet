<?php

use App\Enums\AccountsEnum;
use App\Enums\TypeContactEnum;
use App\Http\Requests\UpdateAccountPayableRequest;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\FinancialCategory;
use App\Models\FinancialContact;
use App\Models\FinancialSubcategory;
use App\Services\AccountPayableService;
use App\Services\AccountReceivableService;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->tenant = sharedTenant();

    [$this->contact, $this->category, $this->subcategory, $this->bankAccount] = $this->tenant->run(function () {
        $contact = Contact::create([
            'type' => TypeContactEnum::SUPPLIER->value,
            'name_corporatereason' => 'Fornecedor Teste',
            'cpf_cnpj' => '12345678000190',
            'email' => 'fornecedor@teste.com',
            'phone' => '1133334444',
            'cell_phone' => '11999998888',
        ]);

        $category = FinancialCategory::create([
            'name' => 'Despesa Teste',
            'type' => 'despesa',
        ]);

        $subcategory = FinancialSubcategory::create([
            'financial_category_id' => $category->id,
            'name' => 'Subcategoria Teste',
        ]);

        $bankAccount = BankAccount::create([
            'name' => 'Conta Principal',
            'bank' => 'Banco Teste',
            'agency' => '0001',
            'account_number' => '123456',
            'account_type' => 'corrente',
            'initial_balance' => 0,
            'current_balance' => 0,
            'main_account' => 1,
        ]);

        return [$contact, $category, $subcategory, $bankAccount];
    });
});

function payablePayload(array $overrides = []): array
{
    return array_merge([
        'financial_category_id' => test()->category->id,
        'financial_subcategory_id' => test()->subcategory->id,
        'bank_account_id' => test()->bankAccount->id,
        'financial_contact_id' => test()->contact->id, // id de contacts, resolvido para financial_contacts pelo service
        'description' => 'Conta de teste',
        'total' => 50000,
        'payment_method' => 'pix',
        'payment_condition' => 'a-vista',
        'total_installments' => 1,
        'bank_account_out' => 1,
        'value' => 50000,
        'due_date' => '2026-06-12',
        'status' => AccountsEnum::OPEN->value,
        'installments' => [
            ['value' => 50000, 'due_date' => '2026-06-12'],
        ],
    ], $overrides);
}

test('resolves the contact into a financial_contact when creating an account payable', function () {
    $account = app(AccountPayableService::class)->create(payablePayload(), $this->tenant);

    $this->tenant->run(function () use ($account) {
        $financialContact = FinancialContact::where('contact_id', $this->contact->id)->first();

        expect($financialContact)->not->toBeNull()
            ->and($financialContact->type)->toBe(TypeContactEnum::SUPPLIER->value)
            ->and($account->financial_contact_id)->toBe($financialContact->id);

        $this->assertDatabaseHas('account_payables', [
            'id' => $account->id,
            'financial_contact_id' => $financialContact->id,
        ]);
    });
});

test('a contact can hold separate financial_contacts as supplier and client', function () {
    app(AccountPayableService::class)->create(payablePayload(), $this->tenant);
    app(AccountReceivableService::class)->create(payablePayload(), $this->tenant);

    $this->tenant->run(function () {
        $types = FinancialContact::where('contact_id', $this->contact->id)
            ->orderBy('type')
            ->pluck('type')
            ->all();

        expect($types)->toBe([
            TypeContactEnum::CLIENT->value,
            TypeContactEnum::SUPPLIER->value,
        ])->and($this->contact->financialContacts()->count())->toBe(2);
    });
});

test('reuses the same financial_contact for the same contact', function () {
    $service = app(AccountPayableService::class);

    $service->create(payablePayload(), $this->tenant);
    $service->create(payablePayload(['description' => 'Segunda conta']), $this->tenant);

    $this->tenant->run(function () {
        expect(FinancialContact::where('contact_id', $this->contact->id)->count())->toBe(1);
        $this->assertDatabaseCount('account_payables', 2);
    });
});

test('generates one installment per period for parcelado', function () {
    $account = app(AccountPayableService::class)->create(payablePayload([
        'payment_condition' => '3',
        'total_installments' => 3,
        'total' => 90000,
        'installments' => [
            ['value' => 30000, 'due_date' => '2026-06-12'],
            ['value' => 30000, 'due_date' => '2026-07-12'],
            ['value' => 30000, 'due_date' => '2026-08-12'],
        ],
    ]), $this->tenant);

    $this->tenant->run(function () use ($account) {
        expect($account->installments()->count())->toBe(3);
    });
});

test('creates a single installment for a-vista when the front sends no installments', function () {
    $account = app(AccountPayableService::class)->create(payablePayload([
        'payment_condition' => 'a-vista',
        'total_installments' => 1,
        'total' => 50000,
        'value' => 50000,
        'installments' => [],
    ]), $this->tenant);

    $this->tenant->run(function () use ($account) {
        expect($account->installments()->count())->toBe(1);

        $this->assertDatabaseHas('installments', [
            'installmentable_id' => $account->id,
            'installment_number' => 1,
            'value' => 50000,
            'due_date' => '2026-06-12',
        ]);
    });
});

test('creates a single installment for one parcela when the front sends no installments', function () {
    $account = app(AccountPayableService::class)->create(payablePayload([
        'payment_condition' => '1',
        'total_installments' => 1,
        'total' => 50000,
        'value' => 50000,
        'installments' => [],
    ]), $this->tenant);

    $this->tenant->run(function () use ($account) {
        expect($account->installments()->count())->toBe(1);

        $this->assertDatabaseHas('installments', [
            'installmentable_id' => $account->id,
            'installment_number' => 1,
            'value' => 50000,
            'due_date' => '2026-06-12',
        ]);
    });
});

test('validated() keeps installment_id for each installment', function () {
    $payload = payablePayload([
        'total' => 100000,
        'payment_condition' => '2',
        'total_installments' => 2,
        'installments' => [
            ['installment_id' => 10, 'value' => 60000, 'due_date' => '2026-06-12'],
            ['installment_id' => 11, 'value' => 40000, 'due_date' => '2026-07-12'],
        ],
    ]);

    $validated = Validator::make($payload, (new UpdateAccountPayableRequest)->rules())->validate();

    expect($validated['installments'][0])->toHaveKey('installment_id')
        ->and($validated['installments'][0]['installment_id'])->toBe(10)
        ->and($validated['installments'][1]['installment_id'])->toBe(11);
});

test('update edits each installment by installment_id when total is unchanged', function () {
    $service = app(AccountPayableService::class);

    $account = $service->create(payablePayload([
        'total' => 100000,
        'payment_condition' => '2',
        'total_installments' => 2,
        'installments' => [
            ['value' => 50000, 'due_date' => '2026-06-12'],
            ['value' => 50000, 'due_date' => '2026-07-12'],
        ],
    ]), $this->tenant);

    $installments = $this->tenant->run(
        fn () => $account->installments()->orderBy('installment_number')->get()
    );

    $service->update($account->id, payablePayload([
        'total' => 100000,
        'payment_condition' => '2',
        'total_installments' => 2,
        'installments' => [
            ['installment_id' => $installments[0]->id, 'value' => 70000, 'due_date' => '2026-06-15'],
            ['installment_id' => $installments[1]->id, 'value' => 30000, 'due_date' => '2026-07-20'],
        ],
    ]), $this->tenant);

    $this->tenant->run(function () use ($installments) {
        $this->assertDatabaseHas('installments', [
            'id' => $installments[0]->id,
            'value' => 70000,
            'due_date' => '2026-06-15',
        ]);

        $this->assertDatabaseHas('installments', [
            'id' => $installments[1]->id,
            'value' => 30000,
            'due_date' => '2026-07-20',
        ]);
    });
});

test('update does not crash when an installment is missing installment_id', function () {
    $service = app(AccountPayableService::class);

    $account = $service->create(payablePayload([
        'total' => 100000,
        'payment_condition' => '2',
        'total_installments' => 2,
        'installments' => [
            ['value' => 50000, 'due_date' => '2026-06-12'],
            ['value' => 50000, 'due_date' => '2026-07-12'],
        ],
    ]), $this->tenant);

    $service->update($account->id, payablePayload([
        'total' => 100000,
        'payment_condition' => '2',
        'total_installments' => 2,
        'installments' => [
            ['value' => 70000, 'due_date' => '2026-06-15'],
        ],
    ]), $this->tenant);

    $this->tenant->run(function () use ($account) {
        expect($account->installments()->count())->toBe(2);
    });
});
