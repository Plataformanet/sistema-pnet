<?php

use App\Enums\AccountsEnum;
use App\Enums\TypeContactEnum;
use App\Http\Requests\UpdateAccountReceivableRequest;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\FinancialCategory;
use App\Models\FinancialSubcategory;
use App\Services\AccountReceivableService;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->tenant = sharedTenant();

    [$this->contact, $this->category, $this->subcategory, $this->bankAccount] = $this->tenant->run(function () {
        $contact = Contact::create([
            'type' => TypeContactEnum::CLIENT->value,
            'name_corporatereason' => 'Cliente Teste',
            'cpf_cnpj' => '12345678000190',
            'email' => 'cliente@teste.com',
            'phone' => '1133334444',
            'cell_phone' => '11999998888',
        ]);

        $category = FinancialCategory::create([
            'name' => 'Receita Teste',
            'type' => 'receita',
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

function receivablePayload(array $overrides = []): array
{
    return array_merge([
        'financial_category_id' => test()->category->id,
        'financial_subcategory_id' => test()->subcategory->id,
        'bank_account_id' => test()->bankAccount->id,
        'financial_contact_id' => test()->contact->id,
        'description' => 'Conta a receber de teste',
        'total' => 100000,
        'payment_method' => 'pix',
        'payment_condition' => '2',
        'total_installments' => 2,
        'bank_account_out' => 1,
        'value' => 50000,
        'due_date' => '2026-06-12',
        'status' => AccountsEnum::OPEN->value,
        'installments' => [
            ['value' => 50000, 'due_date' => '2026-06-12'],
            ['value' => 50000, 'due_date' => '2026-07-12'],
        ],
    ], $overrides);
}

test('creates a single installment for a-vista when the front sends no installments', function () {
    $account = app(AccountReceivableService::class)->create(receivablePayload([
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
    $account = app(AccountReceivableService::class)->create(receivablePayload([
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
    $payload = receivablePayload([
        'installments' => [
            ['installment_id' => 10, 'value' => 60000, 'due_date' => '2026-06-12'],
            ['installment_id' => 11, 'value' => 40000, 'due_date' => '2026-07-12'],
        ],
    ]);

    $validated = Validator::make($payload, (new UpdateAccountReceivableRequest)->rules())->validate();

    expect($validated['installments'][0])->toHaveKey('installment_id')
        ->and($validated['installments'][0]['installment_id'])->toBe(10)
        ->and($validated['installments'][1]['installment_id'])->toBe(11);
});

test('update edits each installment by installment_id when total is unchanged', function () {
    $service = app(AccountReceivableService::class);

    $account = $service->create(receivablePayload(), $this->tenant);

    $installments = $this->tenant->run(
        fn () => $account->installments()->orderBy('installment_number')->get()
    );

    $service->update($account->id, receivablePayload([
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
    $service = app(AccountReceivableService::class);

    $account = $service->create(receivablePayload(), $this->tenant);

    $service->update($account->id, receivablePayload([
        'installments' => [
            ['value' => 70000, 'due_date' => '2026-06-15'],
        ],
    ]), $this->tenant);

    $this->tenant->run(function () use ($account) {
        expect($account->installments()->count())->toBe(2);
    });
});
