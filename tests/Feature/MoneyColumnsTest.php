<?php

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

/**
 * Tipo SQL completo de uma coluna (ex.: `bigint unsigned`) na conexão atual.
 */
function columnSqlType(string $table, string $column): string
{
    $definition = collect(Schema::getColumns($table))->firstWhere('name', $column);

    return $definition['type'];
}

/**
 * @param  class-string<StoreServiceRequest|StoreProductRequest|StoreEmployeeRequest>  $class
 * @param  array<string, mixed>  $data
 */
function moneyRequestValidator(string $class, array $data): Illuminate\Validation\Validator
{
    $request = $class::create('/', 'POST', $data);
    $request->setContainer(app());

    return Validator::make($data, $request->rules(), $request->messages());
}

it('guarda todo dinheiro do tenant em unsignedBigInteger, menos os saldos das contas', function (string $table, string $column) {
    $type = $this->tenant->run(fn () => columnSqlType($table, $column));

    expect($type)->toBe('bigint unsigned');
})->with([
    ['products', 'cost_value'],
    ['products', 'sell_value'],
    ['services', 'cost_value'],
    ['services', 'sell_value'],
    ['services', 'fees'],
    ['employees', 'salary'],
    ['account_payables', 'total'],
    ['account_receivables', 'total'],
    ['installments', 'value'],
]);

it('mantém os totais de pagar e receber anuláveis', function (string $table) {
    $nullable = $this->tenant->run(
        fn () => collect(Schema::getColumns($table))->firstWhere('name', 'total')['nullable']
    );

    expect($nullable)->toBeTrue();
})->with(['account_payables', 'account_receivables']);

it('mantém os saldos de conta bancária com sinal e aceita saldo negativo (V-10)', function () {
    $this->tenant->run(function () {
        expect(columnSqlType('bank_accounts', 'initial_balance'))->toBe('bigint')
            ->and(columnSqlType('bank_accounts', 'current_balance'))->toBe('bigint');

        DB::transaction(function () {
            $account = BankAccount::create([
                'name' => 'Conta no vermelho',
                'bank' => 'Banco Teste',
                'agency' => '0001',
                'account_number' => '999',
                'account_type' => 'corrente',
                'initial_balance' => 0,
                'current_balance' => -5000,
                'main_account' => 0,
            ]);

            expect($account->fresh()->current_balance)->toBe(-5000);
        });
    });
});

it('rejeita valor negativo ou fracionado nos catálogos com mensagem em pt-BR', function (string $class, array $base, string $field, string $message) {
    $negative = moneyRequestValidator($class, array_merge($base, [$field => -1]));
    $fractional = moneyRequestValidator($class, array_merge($base, [$field => 10.5]));

    expect($negative->errors()->first($field))->toBe($message)
        ->and($fractional->fails())->toBeTrue()
        ->and($fractional->errors()->has($field))->toBeTrue();
})->with([
    'serviço: venda' => [StoreServiceRequest::class, ['cost_value' => 0, 'fees' => 0], 'sell_value', 'O valor de venda não pode ser inferior a zero.'],
    'serviço: custo' => [StoreServiceRequest::class, ['sell_value' => 0, 'fees' => 0], 'cost_value', 'O custo não pode ser inferior a zero.'],
    'serviço: taxas' => [StoreServiceRequest::class, ['cost_value' => 0, 'sell_value' => 0], 'fees', 'A taxa/comissão não pode ser inferior a zero.'],
    'produto: venda' => [StoreProductRequest::class, ['cost_value' => 0], 'sell_value', 'O valor de venda não pode ser inferior a zero.'],
    'produto: custo' => [StoreProductRequest::class, ['sell_value' => 0], 'cost_value', 'O custo não pode ser inferior a zero.'],
]);

it('aceita valor zero e inteiro nos catálogos', function () {
    $validator = moneyRequestValidator(StoreServiceRequest::class, [
        'cost_value' => 0, 'sell_value' => 15000, 'fees' => 0,
    ]);

    expect($validator->errors()->keys())->not->toContain('cost_value', 'sell_value', 'fees');
});

it('rejeita salário negativo ou fracionado', function () {
    $negative = moneyRequestValidator(StoreEmployeeRequest::class, ['salary' => -100]);
    $fractional = moneyRequestValidator(StoreEmployeeRequest::class, ['salary' => 1500.5]);
    $valid = moneyRequestValidator(StoreEmployeeRequest::class, ['salary' => 350000]);

    expect($negative->errors()->first('salary'))->toBe('O salário não pode ser inferior a zero.')
        ->and($fractional->errors()->first('salary'))->toBe('O salário deve ser um valor inteiro em centavos.')
        ->and($valid->errors()->has('salary'))->toBeFalse();
});
