<?php

use App\Http\Requests\StoreTenantRegistrationRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;

/**
 * Monta o payload do cadastro, permitindo sobrescrever campos por teste.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Acme',
        'domain' => 'validacao.localhost',
        'plan_id' => 1,
        'userName' => 'Admin Acme',
        'email' => 'admin@acme.com',
        'password' => 'password',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function validateRegistration(array $overrides = []): ValidatorContract
{
    return Validator::make(
        registrationPayload($overrides),
        (new StoreTenantRegistrationRequest)->rules(),
    );
}

test('rejeita domínio com @ (evita o 404 de user@host no redirect)', function () {
    expect(validateRegistration(['domain' => 'empresa@localhost'])->errors()->has('domain'))->toBeTrue();
});

test('rejeita domínio sem ponto', function () {
    expect(validateRegistration(['domain' => 'empresa'])->errors()->has('domain'))->toBeTrue();
});

test('rejeita domínio com espaço', function () {
    expect(validateRegistration(['domain' => 'nome empresa.localhost'])->errors()->has('domain'))->toBeTrue();
});

test('rejeita domínio central reservado', function () {
    expect(validateRegistration(['domain' => 'localhost'])->errors()->has('domain'))->toBeTrue();
});

test('aceita hostname válido', function () {
    expect(validateRegistration(['domain' => 'validacao.localhost'])->passes())->toBeTrue();
});
