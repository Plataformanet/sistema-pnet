<?php

use App\Enums\AccountsEnum;
use App\Enums\ContactTypeEnum;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\FinancialCategory;
use App\Services\AccountPayableService;
use App\Services\AccountReceivableService;
use App\Services\CashFlowService;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->tenant = sharedTenant();

    [$this->contact, $this->category, $this->mainAccount, $this->secondAccount] = $this->tenant->run(function () {
        $contact = Contact::create([
            'type'                 => ContactTypeEnum::SUPPLIER->value,
            'name_corporatereason' => 'Contato Fluxo',
            'cpf_cnpj'             => '12345678000190',
            'email'                => 'fluxo@teste.com',
            'phone'                => '1133334444',
            'cell_phone'           => '11999998888',
        ]);

        $category = FinancialCategory::create([
            'name' => 'Categoria Fluxo',
            'type' => 'despesa',
        ]);

        $mainAccount = BankAccount::create([
            'name'            => 'Conta Principal',
            'bank'            => 'Banco Teste',
            'agency'          => '0001',
            'account_number'  => '111111',
            'account_type'    => 'corrente',
            'initial_balance' => 0,
            'current_balance' => 0,
            'main_account'    => 1,
        ]);

        $secondAccount = BankAccount::create([
            'name'            => 'Conta Secundaria',
            'bank'            => 'Banco Teste',
            'agency'          => '0002',
            'account_number'  => '222222',
            'account_type'    => 'corrente',
            'initial_balance' => 0,
            'current_balance' => 0,
            'main_account'    => 0,
        ]);

        return [$contact, $category, $mainAccount, $secondAccount];
    });
});

function cashFlowRequest(array $query = []): Request
{
    return Request::create('/tenant/finance/cash-flow', 'GET', $query);
}

function makePayable(string $status, int $value, string $dueDate, int $bankAccountId): void
{
    app(AccountPayableService::class)->create([
        'financial_category_id' => test()->category->id,
        'bank_account_id'       => $bankAccountId,
        'financial_contact_id'  => test()->contact->id,
        'description'           => 'Pagar',
        'total'                 => $value,
        'payment_method'        => 'pix',
        'payment_condition'     => 'a-vista',
        'total_installments'    => 1,
        'bank_account_out'      => 1,
        'value'                 => $value,
        'due_date'              => $dueDate,
        'status'                => $status,
        'installments'          => [
            ['value' => $value, 'due_date' => $dueDate],
        ],
    ], test()->tenant);
}

function makeReceivable(string $status, int $value, string $dueDate, int $bankAccountId): void
{
    app(AccountReceivableService::class)->create([
        'financial_category_id' => test()->category->id,
        'bank_account_id'       => $bankAccountId,
        'financial_contact_id'  => test()->contact->id,
        'description'           => 'Receber',
        'total'                 => $value,
        'payment_method'        => 'pix',
        'payment_condition'     => 'a-vista',
        'total_installments'    => 1,
        'bank_account_out'      => 1,
        'value'                 => $value,
        'due_date'              => $dueDate,
        'status'                => $status,
        'installments'          => [
            ['value' => $value, 'due_date' => $dueDate],
        ],
    ], test()->tenant);
}

test('expenses soma apenas parcelas pagas no periodo da conta filtrada', function () {
    makePayable(AccountsEnum::PAID->value, 50000, '2026-06-12', $this->mainAccount->id);
    makePayable(AccountsEnum::OPEN->value, 70000, '2026-06-12', $this->mainAccount->id); // em aberto, nao conta
    makePayable(AccountsEnum::PAID->value, 30000, '2026-06-12', $this->secondAccount->id); // outra conta, nao conta

    $expenses = app(CashFlowService::class)
        ->expenses(cashFlowRequest(), '2026-06', $this->tenant, $this->mainAccount->id);

    expect($expenses)->toBe(50000);
});

test('revenues soma apenas parcelas pagas no periodo', function () {
    makeReceivable(AccountsEnum::PAID->value, 80000, '2026-06-10', $this->mainAccount->id);
    makeReceivable(AccountsEnum::OPEN->value, 90000, '2026-06-10', $this->mainAccount->id); // em aberto, nao conta

    $revenues = app(CashFlowService::class)
        ->revenues(cashFlowRequest(), '2026-06', $this->tenant, $this->mainAccount->id);

    expect($revenues)->toBe(80000);
});

test('calculateAccounts inclui parcelas em aberto e vencidas e exclui as pagas', function () {
    makePayable(AccountsEnum::OPEN->value, 20000, '2026-06-15', $this->mainAccount->id);
    makeReceivable(AccountsEnum::OVERDUE->value, 40000, '2026-06-05', $this->secondAccount->id);
    makePayable(AccountsEnum::PAID->value, 99000, '2026-06-12', $this->mainAccount->id); // paga, nao entra na projecao

    $installments = app(CashFlowService::class)
        ->calculateAccounts(cashFlowRequest(), '2026-06', $this->tenant);

    expect($installments)->toHaveCount(2)
        ->and($installments->pluck('status')->map(fn($status) => $status->value)->all())
        ->toEqualCanonicalizing([AccountsEnum::OPEN->value, AccountsEnum::OVERDUE->value]);
});

test('calculateAccounts filtra por conta bancaria quando informada', function () {
    makePayable(AccountsEnum::OPEN->value, 20000, '2026-06-15', $this->mainAccount->id);
    makeReceivable(AccountsEnum::OVERDUE->value, 40000, '2026-06-05', $this->secondAccount->id);

    $installments = app(CashFlowService::class)
        ->calculateAccounts(cashFlowRequest(), '2026-06', $this->tenant, $this->secondAccount->id);

    expect($installments)->toHaveCount(1)
        ->and($installments->first()->value)->toBe(40000);
});

test('findAll inclui lancamentos vencidos na listagem', function () {
    makePayable(AccountsEnum::OVERDUE->value, 35000, '2026-06-08', $this->mainAccount->id);

    $paginated = app(CashFlowService::class)
        ->findAll(cashFlowRequest(), '2026-06', $this->tenant, $this->mainAccount->id);

    expect($paginated->total())->toBe(1);
});
