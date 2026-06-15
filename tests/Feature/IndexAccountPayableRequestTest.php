<?php

use App\Http\Requests\IndexAccountPayableRequest;
use App\Models\BankAccount;
use App\Models\FinancialCategory;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->tenant = sharedTenant();

    [$this->category, $this->bankAccount] = $this->tenant->run(function () {
        $category = FinancialCategory::create([
            'name' => 'Despesa Teste',
            'type' => 'despesa',
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
 * Valida um conjunto de query params contra as regras do IndexAccountPayableRequest,
 * dentro do contexto do tenant (necessário para as regras `exists`).
 *
 * @param  array<string, mixed>  $data
 */
function validatePayableIndex(array $data): Illuminate\Validation\Validator
{
    return test()->tenant->run(function () use ($data) {
        $request = new IndexAccountPayableRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    });
}

test('passes with no query params (all optional)', function () {
    expect(validatePayableIndex([])->passes())->toBeTrue();
});

test('passes with a fully valid set of filters', function () {
    $validator = validatePayableIndex([
        'periodo' => '2026-06',
        'quantidade' => 10,
        'inicio' => '2026-06-01',
        'fim' => '2026-06-30',
        'status' => 'pago',
        'dias' => 7,
        'conta_id' => $this->bankAccount->id,
        'categoria_id' => $this->category->id,
        'search' => 'aluguel',
    ]);

    expect($validator->passes())->toBeTrue();
});

test('fails when periodo is not in the Y-m format', function () {
    $validator = validatePayableIndex(['periodo' => '06-2026']);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('periodo'))->toBeTrue();
});

test('fails when status is not one of the allowed values', function () {
    $validator = validatePayableIndex(['status' => 'cancelado']);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('status'))->toBeTrue();
});

test('accepts every allowed status value', function (string $status) {
    expect(validatePayableIndex(['status' => $status])->passes())->toBeTrue();
})->with(['pago', 'a-vencer', 'vencem-hoje', 'vencidos']);

test('fails when fim is before inicio', function () {
    $validator = validatePayableIndex([
        'inicio' => '2026-06-30',
        'fim' => '2026-06-01',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('fim'))->toBeTrue();
});

test('fails when quantidade is less than 1', function () {
    $validator = validatePayableIndex(['quantidade' => 0]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('quantidade'))->toBeTrue();
});

test('fails when dias is negative', function () {
    $validator = validatePayableIndex(['dias' => -1]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('dias'))->toBeTrue();
});

test('fails when conta_id does not exist', function () {
    $validator = validatePayableIndex(['conta_id' => 999999]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('conta_id'))->toBeTrue();
});

test('fails when categoria_id does not exist', function () {
    $validator = validatePayableIndex(['categoria_id' => 999999]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('categoria_id'))->toBeTrue();
});
