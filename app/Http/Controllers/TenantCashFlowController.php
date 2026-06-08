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

        if (! $request->has('account_id')) {
            $bankAccount = BankAccount::select('id', 'name', 'bank', 'current_balance')->where('main_account', 1)->first();
        }

        if ($request->has('account_id')) {
            $bankAccount = BankAccount::select('id', 'name', 'bank', 'current_balance')->where('id', $request->query('account_id'))->first();
        }

        $totalPeriod = $this->cashFlowService->totalPeriod($request, $period, tenant(), $bankAccount?->id);
        $expenses = $this->cashFlowService->expenses($request, $period, tenant(), $bankAccount?->id);
        $revenues = $this->cashFlowService->revenues($request, $period, tenant(), $bankAccount?->id);

        $financialCategories = $this->financialCategoryService->findAll(tenant());

        $accounts = $this->cashFlowService->findAll($request, $period, tenant());

        $bankAccounts = BankAccount::select('id', 'name', 'bank', 'current_balance', 'main_account')->get();

        $payableAccounts = [];
        $receivableAccounts = [];

        $bankAccounts->map(function ($bankAccount, $key) use ($request, $period, &$payableAccounts, &$receivableAccounts) {

            $calculatedAccounts = $this->cashFlowService->calculateAccounts($request, $period, tenant(), $bankAccount->id);

            $calculatedAccounts->map(function ($installment) use ($key, &$payableAccounts, &$receivableAccounts) {
                if ($installment->installmentable_type === AccountPayable::class) {
                    $payableAccounts[$key][] = $installment->value;
                }
                if ($installment->installmentable_type === AccountReceivable::class) {
                    $receivableAccounts[$key][] = $installment->value;
                }
            });
        });

        foreach ($payableAccounts as $key => $payable) {
            $payableAccounts[$key] = array_sum($payable);
        }

        foreach ($receivableAccounts as $key => $receivable) {
            $receivableAccounts[$key] = array_sum($receivable);
        }

        $accountsResult = $bankAccounts->map(function ($bankAccount, $key) use (&$payableAccounts, &$receivableAccounts) {
            return ($receivableAccounts[$key] ?? 0) - ($payableAccounts[$key] ?? 0);
        });

        $totalOpen = array_sum($accountsResult->toArray());

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
            'financialCategories' => $financialCategories,
            'type' => $type,
            'bankAccounts' => $bankAccounts,
            'bankAccount' => $bankAccount,
            'totalBankAccounts' => $totalBankAccounts,
            'total' => $total,
        ]);
    }
}
