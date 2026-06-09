<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Services\BillingFlowService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

class TenantBillingFlowController extends Controller
{
    public function __construct(private BillingFlowService $billingFlowService)
    {
    }

    public function index(Request $request)
    {
        $bankAccountId = $request->input('bank_account_id');
        $startYear     = $request->input('start_year', Carbon::now()->subYears(5)->year);
        $endYear       = $request->input('end_year', Carbon::now()->year);

        $bankAccounts = BankAccount::where('active', true)
            ->orderBy('main_account', 'desc')
            ->orderBy('name')
            ->get();

        $data = $this->billingFlowService->calculateBilling($bankAccountId, $startYear, $endYear, tenant());

        return Inertia::render('tenant/finance/billing-flow/', [
            'data'          => $data,
            'bankAccounts'  => $bankAccounts,
            'bankAccountId' => $bankAccountId,
            'startYear'     => $startYear,
            'endYear'       => $endYear,
        ]);
    }
}
