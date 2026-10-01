<?php

use App\Enums\CalculationType;
use App\Enums\FinancingSystem;
use App\Enums\ItbiModule;
use App\Exceptions\Itbi\ItbiNotConfiguredException;
use App\Models\ItbiBracket;
use App\Models\ItbiMunicipality;
use App\Models\ItbiRate;
use App\Services\Itbi\ItbiCalculator;
use App\Services\Itbi\ItbiInput;

/**
 * Município em memória (sem banco) com as alíquotas/faixas informadas.
 *
 * @param  array<string, mixed>  $rate
 * @param  array<int, array<string, mixed>>  $brackets
 */
function itbiMunicipality(ItbiModule $module, array $rate = [], array $brackets = [], ?string $fullRate = null): ItbiMunicipality
{
    $municipality = new ItbiMunicipality(['name' => 'São Bernardo do Campo', 'module' => $module, 'full_rate' => $fullRate]);
    $municipality->setRelation('rate', $rate === [] ? null : new ItbiRate($rate));
    $municipality->setRelation('brackets', collect($brackets)->map(fn (array $bracket) => new ItbiBracket($bracket)));

    return $municipality;
}

function itbi(ItbiMunicipality $municipality, CalculationType $type, int $value, int $financed = 0, FinancingSystem $system = FinancingSystem::SFH, bool $first = false): ?int
{
    return (new ItbiCalculator)->calculate(new ItbiInput($type, $value, $municipality, $system, $financed, $first));
}

$purchase = CalculationType::PURCHASE_WITH_FIDUCIARY_LIEN;

test('módulo 01 SFH com financiamento abaixo do teto', function () use ($purchase) {
    $municipality = itbiMunicipality(ItbiModule::FINANCED_CAP, ['own_funds_rate' => 3, 'financed_rate' => 0.5, 'financed_cap_amount' => 40_000_000]);

    expect(itbi($municipality, $purchase, 50_000_000, 30_000_000))->toBe(750_000);
});

test('módulo 01 SFH com financiamento acima do teto', function () use ($purchase) {
    $municipality = itbiMunicipality(ItbiModule::FINANCED_CAP, ['own_funds_rate' => 3, 'financed_rate' => 0.5, 'financed_cap_amount' => 40_000_000]);

    expect(itbi($municipality, $purchase, 50_000_000, 45_000_000))->toBe(500_000);
});

test('módulo 03 SFH primeiro imóvel e demais', function () use ($purchase) {
    $municipality = itbiMunicipality(ItbiModule::FIRST_PROPERTY_RATE, [
        'own_funds_rate' => 2,
        'first_property_financed_rate' => 0.5,
        'other_property_financed_rate' => 1,
    ]);

    expect(itbi($municipality, $purchase, 40_000_000, 30_000_000, first: true))->toBe(350_000)
        ->and(itbi($municipality, $purchase, 40_000_000, 30_000_000, first: false))->toBe(500_000);
});

test('módulo 04 SFH', function () use ($purchase) {
    $municipality = itbiMunicipality(ItbiModule::STANDARD, ['own_funds_rate' => 3, 'financed_rate' => 0.5]);

    expect(itbi($municipality, $purchase, 40_000_000, 30_000_000))->toBe(450_000);
});

test('módulos 01, 03 e 04 usam a alíquota cheia em SFI e no registro em geral', function (ItbiModule $module) use ($purchase) {
    $municipality = itbiMunicipality($module, [
        'own_funds_rate' => 3,
        'financed_rate' => 0.5,
        'financed_cap_amount' => 40_000_000,
        'first_property_financed_rate' => 0.5,
        'other_property_financed_rate' => 1,
    ]);

    expect(itbi($municipality, $purchase, 40_000_000, 30_000_000, FinancingSystem::SFI))->toBe(1_200_000)
        ->and(itbi($municipality, CalculationType::GENERAL_REGISTRATION, 40_000_000))->toBe(1_200_000);
})->with([ItbiModule::FINANCED_CAP, ItbiModule::FIRST_PROPERTY_RATE, ItbiModule::STANDARD]);

test('módulo 02 SFH dentro da faixa e no limite da faixa', function () use ($purchase) {
    $municipality = itbiMunicipality(ItbiModule::VALUE_BRACKETS, brackets: [
        ['min_value' => 20_000_000, 'max_value' => 30_000_000, 'rate' => 2, 'discount_amount' => 100_000],
    ], fullRate: '2.5');

    expect(itbi($municipality, $purchase, 25_000_000, 10_000_000))->toBe(400_000)
        ->and(itbi($municipality, $purchase, 30_000_000, 10_000_000))->toBe(500_000);
});

test('módulo 02 em SFI usa a alíquota cheia do município', function () use ($purchase) {
    $municipality = itbiMunicipality(ItbiModule::VALUE_BRACKETS, fullRate: '2.5');

    expect(itbi($municipality, $purchase, 40_000_000, 30_000_000, FinancingSystem::SFI))->toBe(1_000_000);
});

test('módulo 02 com valor fora das faixas não calcula', function () use ($purchase) {
    $municipality = itbiMunicipality(ItbiModule::VALUE_BRACKETS, brackets: [
        ['min_value' => 20_000_000, 'max_value' => 30_000_000, 'rate' => 2, 'discount_amount' => 0],
    ], fullRate: '2.5');

    itbi($municipality, $purchase, 50_000_000, 10_000_000);
})->throws(ItbiNotConfiguredException::class);

test('averbação não tem ITBI', function () {
    $municipality = itbiMunicipality(ItbiModule::STANDARD, ['own_funds_rate' => 3, 'financed_rate' => 0.5]);

    expect(itbi($municipality, CalculationType::ECONOMIC_VALUE_ANNOTATION, 40_000_000))->toBeNull();
});

test('município sem cadastro de ITBI é rejeitado', function () {
    (new ItbiCalculator)->calculate(new ItbiInput(CalculationType::GENERAL_REGISTRATION, 40_000_000, null));
})->throws(ItbiNotConfiguredException::class);

test('o resultado nunca é negativo', function () use ($purchase) {
    $municipality = itbiMunicipality(ItbiModule::VALUE_BRACKETS, brackets: [
        ['min_value' => 0, 'max_value' => 10_000_000, 'rate' => 1, 'discount_amount' => 500_000],
    ], fullRate: '2.5');

    expect(itbi($municipality, $purchase, 1_000_000, 500_000))->toBe(0);
});
