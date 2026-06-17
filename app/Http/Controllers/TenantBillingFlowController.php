<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Services\BillingFlowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantBillingFlowController extends Controller
{
    public function __construct(private BillingFlowService $billingFlowService) {}

    public function index(Request $request)
    {
        $bankAccountId = $request->filled('bank_account_id') ? (int) $request->input('bank_account_id') : null;
        $startYear = (int) $request->input('start_year', Carbon::now()->subYears(5)->year);
        $endYear = (int) $request->input('end_year', Carbon::now()->year);

        $bankAccounts = BankAccount::where('active', true)
            ->orderBy('main_account', 'desc')
            ->orderBy('name')
            ->get();

        $data = $this->billingFlowService->calculateBilling($startYear, $endYear, tenant(), $bankAccountId);

        return Inertia::render('tenant/finance/billing-flow/Index', [
            'data' => $data,
            'bankAccounts' => $bankAccounts,
            'bankAccountId' => $bankAccountId,
            'startYear' => $startYear,
            'endYear' => $endYear,
        ]);
    }
}
