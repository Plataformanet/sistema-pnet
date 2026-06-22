<?php

use App\Enums\AccountsEnum;
use App\Enums\ContactTypeEnum;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\FinancialCategory;
use App\Services\BillingFlowService;

beforeEach(function () {
    $this->tenant = sharedTenant();

    [$this->contact, $this->category, $this->mainAccount, $this->secondAccount] = $this->tenant->run(function () {
        $contact = Contact::create([
            'type'                 => ContactTypeEnum::SUPPLIER->value,
            'name_corporatereason' => 'Contato Faturamento',
            'cpf_cnpj'             => '12345678000190',
            'email'                => 'faturamento@teste.com',
            'phone'                => '1133334444',
            'cell_phone'           => '11999998888',
        ]);

        $category = FinancialCategory::create([
            'name' => 'Categoria Faturamento',
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

test('calculateBilling consolida receitas menos despesas pagas por conta, mes e ano', function () {
    // Conta principal - Marco/2025: 100000 receber - 40000 pagar = 60000
    makeReceivable(AccountsEnum::PAID->value, 100000, '2025-03-10', $this->mainAccount->id);
    makePayable(AccountsEnum::PAID->value, 40000, '2025-03-15', $this->mainAccount->id);
    // Conta principal - Marco/2026: 200000 receber
    makeReceivable(AccountsEnum::PAID->value, 200000, '2026-03-10', $this->mainAccount->id);
    // Conta secundaria - Julho/2025: 50000 receber
    makeReceivable(AccountsEnum::PAID->value, 50000, '2025-07-01', $this->secondAccount->id);
    // Parcela em aberto nao deve entrar
    makeReceivable(AccountsEnum::OPEN->value, 999000, '2026-03-10', $this->mainAccount->id);

    $result = app(BillingFlowService::class)->calculateBilling(2025, 2026, $this->tenant);

    $main = $result['data_by_account'][$this->mainAccount->id]['invoicing'];
    expect($main[2025]['months'][3])->toBe(60000)
        ->and($main[2025]['annual_total'])->toBe(60000)
        ->and($main[2026]['months'][3])->toBe(200000)
        ->and($main[2026]['annual_total'])->toBe(200000)
        // variacao 2026 vs 2025: (200000 - 60000) / 60000 * 100
        ->and(round($main[2026]['percentage_variation'], 2))->toBe(233.33);

    $second = $result['data_by_account'][$this->secondAccount->id]['invoicing'];
    expect($second[2025]['months'][7])->toBe(50000)
        ->and($second[2025]['annual_total'])->toBe(50000);
});

test('calculateBilling monta a comparacao mensal consolidada de todas as contas', function () {
    makeReceivable(AccountsEnum::PAID->value, 100000, '2025-03-10', $this->mainAccount->id);
    makePayable(AccountsEnum::PAID->value, 40000, '2025-03-15', $this->mainAccount->id);
    makeReceivable(AccountsEnum::PAID->value, 200000, '2026-03-10', $this->mainAccount->id);
    makeReceivable(AccountsEnum::PAID->value, 50000, '2025-07-01', $this->secondAccount->id);

    $result = app(BillingFlowService::class)->calculateBilling(2025, 2026, $this->tenant);

    $march = $result['monthly_comparison']['March'];
    expect($march['totals_by_year'][2025])->toBe(60000)
        ->and($march['totals_by_year'][2026])->toBe(200000)
        ->and($march['total_period'])->toBe(260000);

    $july = $result['monthly_comparison']['July'];
    expect($july['totals_by_year'][2025])->toBe(50000)
        ->and($july['totals_by_year'][2026])->toBe(0);
});

test('calculateBilling gera o resumo geral consolidado', function () {
    makeReceivable(AccountsEnum::PAID->value, 100000, '2025-03-10', $this->mainAccount->id);
    makePayable(AccountsEnum::PAID->value, 40000, '2025-03-15', $this->mainAccount->id);
    makeReceivable(AccountsEnum::PAID->value, 200000, '2026-03-10', $this->mainAccount->id);
    makeReceivable(AccountsEnum::PAID->value, 50000, '2025-07-01', $this->secondAccount->id);

    $summary = app(BillingFlowService::class)->calculateBilling(2025, 2026, $this->tenant)['general_summary'];

    // total = main(60000 + 200000) + second(50000) = 310000
    expect($summary['total_active_accounts'])->toBe(2)
        ->and($summary['total_period_billing'])->toBe(310000)
        // 2025 consolidado = 60000 + 50000 = 110000 ; 2026 = 200000
        ->and($summary['best_year']['year'])->toBe(2026)
        ->and($summary['best_year']['value'])->toBe(200000)
        ->and($summary['worst_year']['year'])->toBe(2025)
        ->and($summary['worst_year']['value'])->toBe(110000);
});

test('calculateBilling filtra por conta bancaria quando informada', function () {
    makeReceivable(AccountsEnum::PAID->value, 100000, '2025-03-10', $this->mainAccount->id);
    makeReceivable(AccountsEnum::PAID->value, 50000, '2025-07-01', $this->secondAccount->id);

    $result = app(BillingFlowService::class)
        ->calculateBilling(2025, 2026, $this->tenant, $this->mainAccount->id);

    expect($result['data_by_account'])->toHaveCount(1)
        ->and($result['data_by_account'])->toHaveKey($this->mainAccount->id)
        // comparacao mensal nao deve conter a parcela da conta secundaria
        ->and($result['monthly_comparison']['July']['totals_by_year'][2025])->toBe(0)
        ->and($result['monthly_comparison']['March']['totals_by_year'][2025])->toBe(100000);
});

test('formatForChart transforma a comparacao mensal em labels e datasets', function () {
    makeReceivable(AccountsEnum::PAID->value, 100000, '2025-03-10', $this->mainAccount->id);
    makeReceivable(AccountsEnum::PAID->value, 200000, '2026-03-10', $this->mainAccount->id);

    $service = app(BillingFlowService::class);
    $data    = $service->calculateBilling(2025, 2026, $this->tenant);
    $chart   = $service->formatForChart($data);

    expect($chart['labels'])->toHaveCount(12)
        ->and($chart['labels'][0])->toBe('January')
        ->and($chart['datasets'])->toHaveCount(2)
        ->and($chart['datasets'][0]['label'])->toBe(2025)
        ->and($chart['datasets'][1]['label'])->toBe(2026);

    // March e o terceiro mes (indice 2)
    expect($chart['datasets'][0]['data'][2])->toBe(100000.0)
        ->and($chart['datasets'][1]['data'][2])->toBe(200000.0);
});
