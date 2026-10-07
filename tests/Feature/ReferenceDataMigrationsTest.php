<?php

use App\Enums\ReceiptType;
use App\Models\Bank;
use App\Models\ContractType;
use App\Models\CostType;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Stage;
use App\Models\TenantSetting;

test('central migrations create the plans and include every core module in the basic plan', function () {
    expect(Plan::pluck('slug')->all())->toEqualCanonicalizing(['basic', 'standard', 'premium'])
        ->and(Plan::where('slug', 'basic')->firstOrFail()->includedModules()->pluck('modules.id')->all())
        ->toEqualCanonicalizing(Module::where('is_core', true)->pluck('id')->all());
});

test('plan prices are stored in cents', function () {
    expect(Plan::pluck('price', 'slug')->all())->toEqual([
        'basic' => 2999,
        'standard' => 5999,
        'premium' => 9999,
    ]);
});

test('tenant migrations create the documents catalogs', function () {
    $tenant = sharedTenant();

    $tenant->run(function () {
        expect(Bank::pluck('name')->all())->toContain('Banco do Brasil', 'Caixa')
            ->and(ContractType::findOrFail(4)->requires_financing)->toBeFalse()
            ->and(ContractType::find(7))->toBeNull()
            ->and(CostType::where('name', 'Motoboy')->firstOrFail()->receipt_type)->toBe(ReceiptType::COURIER)
            ->and(Stage::orderBy('order')->pluck('name')->all())->toHaveCount(8)
            ->and(Stage::where('order', 7)->firstOrFail()->shows_registry_protocol)->toBeTrue();
    });
});

test('tenant migrations create the default settings', function () {
    $tenant = sharedTenant();

    $tenant->run(function () {
        expect(TenantSetting::where('key', 'app.timezone')->value('value'))->toBe('America/Sao_Paulo')
            ->and(TenantSetting::where('key', 'drive.max_file_size_mb')->count())->toBe(1);
    });
});
