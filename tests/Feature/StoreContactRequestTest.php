<?php

use App\Http\Requests\StoreContactRequest;
use Illuminate\Support\Facades\Validator;

it('validates CPF when type is PF', function () {
    $request = new StoreContactRequest;

    $invalidData = [
        'type' => 'PF',
        'name_corporatereason' => 'Test User',
        'cpf_cnpj' => '111.111.111-11',
        'email' => 'test@example.com',
        'phone' => '1133334444',
        'cell_phone' => '11999998888',
        'zip_code' => '01001-000',
        'street' => 'Rua A',
        'number' => '123',
        'neighborhood' => 'Centro',
        'city' => 'São Paulo',
        'state' => 'SP',
    ];

    $validator = Validator::make($invalidData, $request->rules());
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cpf_cnpj'))->toBeTrue();

    $validData = array_merge($invalidData, ['cpf_cnpj' => '529.982.247-25']);
    $validatorValid = Validator::make($validData, $request->rules());
    expect($validatorValid->fails())->toBeFalse();
});

it('does not trigger CpfRule when type is PJ', function () {
    $request = new StoreContactRequest;

    $pjData = [
        'type' => 'PJ',
        'name_corporatereason' => 'Empresa Teste LTDA',
        'cpf_cnpj' => '12.345.678/0001-90',
        'email' => 'empresa@example.com',
        'phone' => '1133334444',
        'cell_phone' => '11999998888',
        'zip_code' => '01001-000',
        'street' => 'Rua B',
        'number' => '456',
        'neighborhood' => 'Centro',
        'city' => 'São Paulo',
        'state' => 'SP',
    ];

    $validator = Validator::make($pjData, $request->rules());
    expect($validator->fails())->toBeFalse();
});
