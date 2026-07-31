<?php

use App\Rules\CpfRule;

it('passes for valid CPFs', function (string $validCpf) {
    $rule = new CpfRule;
    $failed = false;

    $rule->validate('cpf_cnpj', $validCpf, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
})->with([
    '52998224725',
    '529.982.247-25',
    '11144477735',
    '111.444.777-35',
]);

it('fails for invalid CPFs', function (mixed $invalidCpf) {
    $rule = new CpfRule;
    $failedMessage = null;

    $rule->validate('cpf_cnpj', $invalidCpf, function (string $message) use (&$failedMessage) {
        $failedMessage = $message;
    });

    expect($failedMessage)->toBe('O CPF informado é inválido.');
})->with([
    '11111111111',
    '111.111.111-11',
    '000.000.000-00',
    '12345678901',
    '123.456.789-00',
    '12345',
    'invalid_string',
    12345678901,
]);
