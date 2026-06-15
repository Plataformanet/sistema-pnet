<?php

use App\Http\Requests\IndexAccountReceivableRequest;
use App\Models\BankAccount;
use App\Models\FinancialCategory;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->tenant = sharedTenant();

    [$this->category, $this->bankAccount] = $this->tenant->run(function () {
        $category = FinancialCategory::create([
            'name' => 'Receita Teste',
            'type' => 'receita',
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

        return [$category, $bankAccount];
    });
});

/**
 * Valida um conjunto de query params contra as regras do IndexAccountReceivableRequest,
 * dentro do contexto do tenant (necessário para as regras `exists`).
 *
 * @param  array<string, mixed>  $data
 */
function validateReceivableIndex(array $data): Illuminate\Validation\Validator
{
    return test()->tenant->run(function () use ($data) {
        $request = new IndexAccountReceivableRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    });
}

test('passes with no query params (all optional)', function () {
    expect(validateReceivableIndex([])->passes())->toBeTrue();
});

test('passes with a fully valid set of filters', function () {
    $validator = validateReceivableIndex([
        'periodo' => '2026-06',
        'quantidade' => 10,
        'inicio' => '2026-06-01',
        'fim' => '2026-06-30',
        'status' => 'pago',
        'dias' => 7,
        'conta_id' => $this->bankAccount->id,
        'categoria_id' => $this->category->id,
        'search' => 'mensalidade',
    ]);

    expect($validator->passes())->toBeTrue();
});

test('fails when periodo is not in the Y-m format', function () {
    $validator = validateReceivableIndex(['periodo' => '06-2026']);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('periodo'))->toBeTrue();
});

test('fails when status is not one of the allowed values', function () {
    $validator = validateReceivableIndex(['status' => 'cancelado']);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('status'))->toBeTrue();
});

test('accepts every allowed status value', function (string $status) {
    expect(validateReceivableIndex(['status' => $status])->passes())->toBeTrue();
})->with(['pago', 'a-vencer', 'vencem-hoje', 'vencidos']);

test('fails when fim is before inicio', function () {
    $validator = validateReceivableIndex([
        'inicio' => '2026-06-30',
        'fim' => '2026-06-01',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('fim'))->toBeTrue();
});

test('fails when quantidade is less than 1', function () {
    $validator = validateReceivableIndex(['quantidade' => 0]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('quantidade'))->toBeTrue();
});

test('fails when dias is negative', function () {
    $validator = validateReceivableIndex(['dias' => -1]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('dias'))->toBeTrue();
});

test('fails when conta_id does not exist', function () {
    $validator = validateReceivableIndex(['conta_id' => 999999]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('conta_id'))->toBeTrue();
});

test('fails when categoria_id does not exist', function () {
    $validator = validateReceivableIndex(['categoria_id' => 999999]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('categoria_id'))->toBeTrue();
});
