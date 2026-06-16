<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\BankAccount;
use App\Services\CashFlowService;
use App\Services\FinancialCategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantCashFlowController extends Controller
{
    public function __construct(
        protected CashFlowService $cashFlowService,
        protected FinancialCategoryService $financialCategoryService
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $type = 'cashFlow';

        $period = $request->input('period', now()->format('Y-m'));

        $accountId = $request->query('account_id');

        // "all" => todas as contas combinadas (sem filtro de banco).
        // Conta específica => filtra por ela. Primeiro acesso (sem parâmetro) => conta principal.
        if ($accountId === 'all') {
            $bankAccount = null;
        } elseif ($accountId !== null) {
            $bankAccount = BankAccount::select('id', 'name', 'bank', 'current_balance')->where('id', $accountId)->first();
        } else {
            $bankAccount = BankAccount::select('id', 'name', 'bank', 'current_balance')->where('main_account', 1)->first();
        }

        $expenses = $this->cashFlowService->expenses($request, $period, tenant(), $bankAccount?->id);
        $revenues = $this->cashFlowService->revenues($request, $period, tenant(), $bankAccount?->id);
        $totalPeriod = $revenues - $expenses;

        $financialCategories = $this->financialCategoryService->findAll(tenant());

        $accounts = $this->cashFlowService->findAll($request, $period, tenant(), $bankAccount?->id);

        $bankAccounts = BankAccount::select('id', 'name', 'bank', 'current_balance', 'main_account')->get();

        // Uma única consulta carrega todas as parcelas em aberto/vencidas do período
        // (todas as contas), evitando uma query por conta bancária (N+1).
        $openInstallments = $this->cashFlowService->calculateAccounts($request, $period, tenant());

        $payablesByAccount = [];
        $receivablesByAccount = [];

        foreach ($openInstallments as $installment) {
            $installmentBankAccountId = $installment->installmentable?->bank_account_id;

            if ($installmentBankAccountId === null) {
                continue;
            }

            if ($installment->installmentable_type === AccountPayable::class) {
                $payablesByAccount[$installmentBankAccountId] = ($payablesByAccount[$installmentBankAccountId] ?? 0) + $installment->value;
            }

            if ($installment->installmentable_type === AccountReceivable::class) {
                $receivablesByAccount[$installmentBankAccountId] = ($receivablesByAccount[$installmentBankAccountId] ?? 0) + $installment->value;
            }
        }

        $accountsResult = $bankAccounts->map(function ($bankAccount) use ($payablesByAccount, $receivablesByAccount) {
            return ($receivablesByAccount[$bankAccount->id] ?? 0) - ($payablesByAccount[$bankAccount->id] ?? 0);
        });

        $totalOpen = $accountsResult->sum();

        $totalBankAccounts = BankAccount::sum('current_balance');

        $total = $totalBankAccounts + $totalOpen;

        return Inertia::render('tenant/finance/cash-flow/Index', [
            'accounts' => $accounts,
            'totalPeriod' => $totalPeriod,
            'totalOpen' => $totalOpen,
            'accountsResult' => $accountsResult,
            'expenses' => $expenses,
            'revenues' => $revenues,
            'period' => $period,
            'perPage' => $request->input('quantity'),
            'start' => $request->input('start'),
            'end' => $request->input('end'),
            'categoryId' => $request->input('category_id'),
            'accountId' => $accountId,
            'financialCategories' => $financialCategories,
            'type' => $type,
            'bankAccounts' => $bankAccounts,
            'bankAccount' => $bankAccount,
            'totalBankAccounts' => $totalBankAccounts,
            'total' => $total,
        ]);
    }
}
