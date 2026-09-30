<?php

use App\Enums\AccountsEnum;
use App\Enums\ContactTypeEnum;
use App\Exceptions\AccountTotalBelowPaidException;
use App\Exceptions\InstallmentAlreadyPaidException;
use App\Exceptions\InstallmentsTotalMismatchException;
use App\Exceptions\PaidInstallmentLockedException;
use App\Http\Requests\StoreAccountPayableRequest;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\FinancialCategory;
use App\Models\Installment;
use App\Services\AccountPayableService;
use App\Services\AccountReceivableService;
use App\Services\CashFlowService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = sharedTenant();

    [$this->contact, $this->category, $this->bankAccount, $this->otherBankAccount] = $this->tenant->run(function () {
        $contact = Contact::create([
            'type' => ContactTypeEnum::SUPPLIER->value,
            'name_corporatereason' => 'Contato Financeiro',
            'cpf_cnpj' => '12345678000190',
            'email' => 'financeiro@teste.com',
            'phone' => '1133334444',
            'cell_phone' => '11999998888',
        ]);

        $category = FinancialCategory::create(['name' => 'Categoria', 'type' => 'despesa']);

        $bankAccount = BankAccount::create([
            'name' => 'Conta Principal',
            'bank' => 'Banco Teste',
            'agency' => '0001',
            'account_number' => '111',
            'account_type' => 'corrente',
            'initial_balance' => 0,
            'current_balance' => 0,
            'main_account' => 1,
        ]);

        $otherBankAccount = BankAccount::create([
            'name' => 'Conta Secundária',
            'bank' => 'Banco Teste',
            'agency' => '0001',
            'account_number' => '222',
            'account_type' => 'corrente',
            'initial_balance' => 0,
            'current_balance' => 0,
            'main_account' => 0,
        ]);

        return [$contact, $category, $bankAccount, $otherBankAccount];
    });
});

/**
 * Lançamento de R$ 900,00 em 3 parcelas de R$ 300,00, no formato que o front envia.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function balancePayload(array $overrides = []): array
{
    return array_merge([
        'financial_category_id' => test()->category->id,
        'bank_account_id' => test()->bankAccount->id,
        'financial_contact_id' => test()->contact->id,
        'description' => 'Lançamento parcelado',
        'total' => 90000,
        'payment_method' => 'pix',
        'payment_condition' => '3',
        'total_installments' => 3,
        'bank_account_out' => 1,
        'value' => 30000,
        'due_date' => '2026-06-12',
        'status' => AccountsEnum::OPEN->value,
        'installments' => [
            ['value' => 30000, 'due_date' => '2026-06-12'],
            ['value' => 30000, 'due_date' => '2026-07-12'],
            ['value' => 30000, 'due_date' => '2026-08-12'],
        ],
    ], $overrides);
}

function balanceOf(int $bankAccountId): int
{
    return (int) test()->tenant->run(fn () => BankAccount::findOrFail($bankAccountId)->current_balance);
}

/**
 * Valida um FormRequest sem rota HTTP. Diferente do helper global formRequest(),
 * configura o redirector, necessário para a validação falhar com
 * ValidationException em vez de erro de URL.
 *
 * @param  class-string<FormRequest>  $class
 * @param  array<string, mixed>  $data
 */
function validateRequest(string $class, array $data): FormRequest
{
    $request = $class::create('/', 'POST', $data);
    $request->setContainer(app())->setRedirector(app(Redirector::class));
    $request->validateResolved();

    return $request;
}

/**
 * @return Collection<int, Installment>
 */
function installmentsOf(AccountPayable|AccountReceivable $account)
{
    return test()->tenant->run(fn () => $account->installments()->orderBy('installment_number')->get());
}

test('dar baixa numa parcela a pagar debita o valor uma única vez', function () {
    $service = app(AccountPayableService::class);
    $account = $service->create(balancePayload(), $this->tenant);
    $installment = installmentsOf($account)->first();

    $service->updateInstallment((string) $installment->id, $this->tenant);

    expect(fn () => $service->updateInstallment((string) $installment->id, $this->tenant))
        ->toThrow(InstallmentAlreadyPaidException::class)
        ->and(balanceOf($this->bankAccount->id))->toBe(-30000);
});

test('dar baixa numa parcela a receber credita o valor na conta', function () {
    $service = app(AccountReceivableService::class);
    $account = $service->create(balancePayload(), $this->tenant);
    $installment = installmentsOf($account)->first();

    $service->updateInstallment((string) $installment->id, $this->tenant);

    $installment = $this->tenant->run(fn () => Installment::findOrFail($installment->id));

    expect(balanceOf($this->bankAccount->id))->toBe(30000)
        ->and($installment->status)->toBe(AccountsEnum::PAID)
        ->and($installment->payment_date)->not->toBeNull();
});

test('o service de contas a pagar não dá baixa em parcela de contas a receber', function () {
    $receivable = app(AccountReceivableService::class)->create(balancePayload(), $this->tenant);
    $installment = installmentsOf($receivable)->first();

    expect(fn () => app(AccountPayableService::class)->updateInstallment((string) $installment->id, $this->tenant))
        ->toThrow(ModelNotFoundException::class)
        ->and(balanceOf($this->bankAccount->id))->toBe(0);
});

test('criar lançamento parcelado já pago movimenta o valor total de todas as parcelas', function () {
    app(AccountPayableService::class)->create(balancePayload(['status' => AccountsEnum::PAID->value]), $this->tenant);
    app(AccountReceivableService::class)->create(balancePayload([
        'status' => AccountsEnum::PAID->value,
        'bank_account_id' => $this->otherBankAccount->id,
    ]), $this->tenant);

    expect(balanceOf($this->bankAccount->id))->toBe(-90000)
        ->and(balanceOf($this->otherBankAccount->id))->toBe(90000);
});

test('parcelas em aberto nascem sem data de pagamento e parcelas pagas usam o vencimento', function () {
    $service = app(AccountPayableService::class);
    $open = $service->create(balancePayload(), $this->tenant);
    $paid = $service->create(balancePayload(['status' => AccountsEnum::PAID->value]), $this->tenant);

    expect(installmentsOf($open)->pluck('payment_date')->filter())->toBeEmpty()
        ->and(installmentsOf($paid)->first()->payment_date->toDateString())->toBe('2026-06-12');
});

test('gerar parcelas no servidor distribui o total sem perder centavos', function () {
    $account = app(AccountPayableService::class)->create(balancePayload([
        'total' => 100000,
        'installments' => [],
    ]), $this->tenant);

    expect(installmentsOf($account)->pluck('value')->all())->toBe([33333, 33333, 33334]);
});

test('excluir lançamento com parcela paga estorna o saldo e remove as parcelas', function () {
    $service = app(AccountPayableService::class);
    $account = $service->create(balancePayload(), $this->tenant);
    $service->updateInstallment((string) installmentsOf($account)->first()->id, $this->tenant);

    $service->delete((string) $account->id, $this->tenant);

    expect(balanceOf($this->bankAccount->id))->toBe(0)
        ->and($this->tenant->run(fn () => Installment::where('installmentable_id', $account->id)
            ->where('installmentable_type', AccountPayable::class)
            ->count()))->toBe(0);
});

test('editar o total preserva as parcelas pagas e redistribui o restante nas abertas', function () {
    $service = app(AccountPayableService::class);
    $account = $service->create(balancePayload(), $this->tenant);
    $paidInstallment = installmentsOf($account)->first();
    $service->updateInstallment((string) $paidInstallment->id, $this->tenant);

    $service->update((string) $account->id, balancePayload(['total' => 100000, 'status' => AccountsEnum::PAID->value]), $this->tenant);

    $installments = installmentsOf($account);

    expect(balanceOf($this->bankAccount->id))->toBe(-30000)
        ->and($installments)->toHaveCount(3)
        ->and($installments->first()->id)->toBe($paidInstallment->id)
        ->and($installments->first()->status)->toBe(AccountsEnum::PAID)
        ->and($installments->slice(1)->pluck('status')->unique()->all())->toBe([AccountsEnum::OPEN])
        ->and($installments->slice(1)->pluck('value')->values()->all())->toBe([35000, 35000])
        ->and($installments->sum('value'))->toBe(100000);
});

test('editar o total sem parcelas pagas divide o valor sem perder centavos', function () {
    $service = app(AccountPayableService::class);
    $account = $service->create(balancePayload(), $this->tenant);

    $service->update((string) $account->id, balancePayload(['total' => 100000]), $this->tenant);

    expect(installmentsOf($account)->pluck('value')->all())->toBe([33333, 33333, 33334]);
});

test('não permite reduzir o total para menos do que já foi pago', function () {
    $service = app(AccountPayableService::class);
    $account = $service->create(balancePayload(), $this->tenant);
    $service->updateInstallment((string) installmentsOf($account)->first()->id, $this->tenant);

    expect(fn () => $service->update((string) $account->id, balancePayload(['total' => 20000]), $this->tenant))
        ->toThrow(AccountTotalBelowPaidException::class)
        ->and(installmentsOf($account))->toHaveCount(3)
        ->and(balanceOf($this->bankAccount->id))->toBe(-30000);
});

test('trocar a conta bancária de um lançamento pago move o valor pago entre as contas', function () {
    $service = app(AccountPayableService::class);
    $account = $service->create(balancePayload(), $this->tenant);
    $service->updateInstallment((string) installmentsOf($account)->first()->id, $this->tenant);

    $service->update((string) $account->id, balancePayload([
        'bank_account_id' => $this->otherBankAccount->id,
        'installments' => [],
    ]), $this->tenant);

    expect(balanceOf($this->bankAccount->id))->toBe(0)
        ->and(balanceOf($this->otherBankAccount->id))->toBe(-30000);
});

test('não permite alterar o valor de uma parcela paga na edição do lançamento', function () {
    $service = app(AccountPayableService::class);
    $account = $service->create(balancePayload(), $this->tenant);
    $installments = installmentsOf($account);
    $service->updateInstallment((string) $installments[0]->id, $this->tenant);

    expect(fn () => $service->update((string) $account->id, balancePayload([
        'installments' => [
            ['installment_id' => $installments[0]->id, 'value' => 10000, 'due_date' => '2026-06-12'],
            ['installment_id' => $installments[1]->id, 'value' => 40000, 'due_date' => '2026-07-12'],
            ['installment_id' => $installments[2]->id, 'value' => 40000, 'due_date' => '2026-08-12'],
        ],
    ]), $this->tenant))->toThrow(PaidInstallmentLockedException::class);

    expect(installmentsOf($account)->first()->value)->toBe(30000);
});

test('não permite que a soma das parcelas editadas fique diferente do total', function () {
    $service = app(AccountPayableService::class);
    $account = $service->create(balancePayload(), $this->tenant);
    $installments = installmentsOf($account);

    expect(fn () => $service->update((string) $account->id, balancePayload([
        'installments' => [
            ['installment_id' => $installments[0]->id, 'value' => 70000, 'due_date' => '2026-06-12'],
            ['installment_id' => $installments[1]->id, 'value' => 30000, 'due_date' => '2026-07-12'],
            ['installment_id' => $installments[2]->id, 'value' => 30000, 'due_date' => '2026-08-12'],
        ],
    ]), $this->tenant))->toThrow(InstallmentsTotalMismatchException::class);

    expect(installmentsOf($account)->pluck('value')->all())->toBe([30000, 30000, 30000]);
});

test('erro no meio da edição de contas a receber desfaz tudo e mantém as parcelas', function () {
    $service = app(AccountReceivableService::class);
    $account = $service->create(balancePayload(), $this->tenant);

    expect(fn () => $service->update((string) $account->id, balancePayload([
        'total' => 100000,
        'financial_category_id' => 999999,
    ]), $this->tenant))->toThrow(QueryException::class);

    expect(installmentsOf($account)->pluck('value')->all())->toBe([30000, 30000, 30000]);
});

test('o cadastro rejeita total negativo, condição inválida e soma de parcelas diferente do total', function (array $overrides, string $field) {
    try {
        validateRequest(StoreAccountPayableRequest::class, balancePayload($overrides));
        $this->fail('A validação deveria ter falhado.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($field);
    }
})->with([
    'total negativo' => [['total' => -90000], 'total'],
    'parcela negativa' => [['installments' => [['value' => -30000, 'due_date' => '2026-06-12']]], 'installments.0.value'],
    'condição inválida' => [['payment_condition' => '0'], 'payment_condition'],
    'status fora do formulário' => [['status' => AccountsEnum::OVERDUE->value], 'status'],
    'soma diferente do total' => [['total' => 150000, 'installments' => [
        ['value' => 70000, 'due_date' => '2026-06-12'],
        ['value' => 70000, 'due_date' => '2026-07-12'],
    ]], 'installments'],
]);

test('filtrar a listagem por categoria não quebra e retorna só a categoria pedida', function () {
    $service = app(AccountPayableService::class);
    $otherCategory = $this->tenant->run(fn () => FinancialCategory::create(['name' => 'Outra', 'type' => 'despesa']));
    $service->create(balancePayload(), $this->tenant);
    $service->create(balancePayload(['financial_category_id' => $otherCategory->id]), $this->tenant);

    $result = $service->findAll(Request::create('/', 'GET', ['categoria_id' => $otherCategory->id]), '2026-06', $this->tenant);

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->financial_category_id)->toBe($otherCategory->id);
});

test('o fluxo de caixa realizado considera a data de pagamento e não o vencimento', function () {
    $this->travelTo('2026-07-20');

    $service = app(AccountPayableService::class);
    $account = $service->create(balancePayload(), $this->tenant);
    $service->updateInstallment((string) installmentsOf($account)->first()->id, $this->tenant);

    $cashFlow = app(CashFlowService::class);
    $request = Request::create('/', 'GET');

    expect($cashFlow->expenses($request, '2026-06', $this->tenant, $this->bankAccount->id))->toBe(0)
        ->and($cashFlow->expenses($request, '2026-07', $this->tenant, $this->bankAccount->id))->toBe(30000);
});
