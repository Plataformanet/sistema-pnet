<?php

namespace App\Http\Controllers;

use App\Exceptions\UpdateInstallmentException;
use App\Http\Requests\StoreAccountPayableRequest;
use App\Http\Requests\UpdateAccountPayableRequest;
use App\Http\Requests\UpdateInstallmentValueRequest;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\Cost;
use App\Models\FinancialCategory;
use App\Services\AccountPayableService;
use App\Services\BankAccountService;
use App\Services\ContactService;
use App\Services\FinancialCategoryService;
use App\Services\FinancialSubcategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class TenantAccountPayableController extends Controller
{
    public function __construct(
        protected AccountPayableService $accountPayableService,
        protected FinancialCategoryService $financialCategoryService,
        protected FinancialSubcategoryService $financialSubcategoryService,
        protected ContactService $contactService,
        protected BankAccountService $bankAccountService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $period = $request->input('periodo', now()->format('Y-m'));
        $days   = 7;

        $accountsPayable = $this->accountPayableService->findAll($request, $period, tenant());

        if (!$request->has('conta_id')) {
            $bankAccount = BankAccount::select('id', 'name', 'bank', 'current_balance')->where('main_account', 1)->first();
        }

        if ($request->has('conta_id')) {
            $bankAccount = BankAccount::select('id', 'name', 'bank', 'current_balance')->where('id', $request->query('conta_id'))->first();
        }

        $totalPeriod   = $this->accountPayableService->totalPeriod($request, $period, tenant(), $bankAccount?->id);
        $totalPaid     = $this->accountPayableService->totalPaid($request, $period, tenant(), $bankAccount?->id);
        $totalDueToday = $this->accountPayableService->totalDueToday($request, $period, tenant(), $bankAccount?->id);
        $totalToDue    = $this->accountPayableService->totalToDue($request, $days, $period, tenant(), $bankAccount?->id);
        $totalOverdue  = $this->accountPayableService->totalOverdue($request, $period, tenant(), $bankAccount?->id);

        $financialCategories = $this->financialCategoryService->findAll(tenant());

        $searchedCategory = FinancialCategory::select('name')->find($request->input('categoria_id'));

        $bankAccounts = BankAccount::select('id', 'name', 'bank', 'current_balance', 'main_account')->get();

        return Inertia::render('tenant/finance/accounts-payable/list/List', [
            'accountsPayable'     => $accountsPayable,
            'totalPeriod'         => $totalPeriod,
            'totalPaid'           => $totalPaid,
            'totalDueToday'       => $totalDueToday,
            'totalToDue'          => $totalToDue,
            'totalOverdue'        => $totalOverdue,
            'period'              => $period,
            'perPage'             => $request->input('quantidade'),
            'start'               => $request->input('inicio'),
            'end'                 => $request->input('fim'),
            'categoryId'          => $request->input('categoria_id'),
            'financialCategories' => $financialCategories,
            'searchedCategory'    => $searchedCategory,
            'bankAccounts'        => $bankAccounts,
            'bankAccount'         => $bankAccount,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $financialCategories    = $this->financialCategoryService->findCategoryAccountsPayable(tenant());
        $financialSubcategories = $this->financialSubcategoryService->findAll(tenant());
        $costs                  = Cost::select('id', 'type')->get();

        $financialSubcategories = $financialSubcategories->map(function ($item) {
            if ($item->active) {
                return $item->name;
            }
        });

        $contacts = collect();
        Contact::select('id', 'name_corporatereason')
            ->chunkById(500, function ($chunk) use (&$contacts) {
                $contacts = $contacts->merge($chunk);
            });

        $paymentConditions = $this->accountPayableService->paymentConditions();

        $bankAccounts = $this->bankAccountService->findAll(tenant());

        return Inertia::render('tenant/finance/accounts-payable/create/Create', [
            'financialCategories'    => $financialCategories,
            'financialSubcategories' => $financialSubcategories,
            'costs'                  => $costs,
            'contacts'               => $contacts,
            'paymentConditions'      => $paymentConditions,
            'bankAccounts'           => $bankAccounts,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccountPayableRequest $request)
    {
        $accountPayable = $this->accountPayableService->create($request->validated(), tenant());

        if ($accountPayable) {
            return redirect()->route('tenant.finance.accounts-payable.list')->with('success', 'Account payable created successfully!');
        }

        return redirect()->route('tenant.finance.accounts-payable.list')->with('error', 'Error creating account payable!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $accountPayable = $this->accountPayableService->showById($id, tenant());

        return Inertia::render('tenant/finance/accounts-payable/show/Show', [
            'accountPayable' => $accountPayable,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $accountPayable = $this->accountPayableService->findById($id, tenant());

        $financialCategories    = $this->financialCategoryService->findCategoryAccountsPayable(tenant());
        $financialSubcategories = $this->financialSubcategoryService->findAll(tenant());
        $costs                  = Cost::select('id', 'type')->get();

        $financialSubcategories = $financialSubcategories->map(function ($item) {
            if ($item->active) {
                return ['id' => $item->id, 'name' => $item->name];
            }
        });

        $contacts = collect();
        Contact::select('id', 'name_corporatereason')
            ->chunkById(500, function ($chunk) use (&$contacts) {
                $contacts = $contacts->merge($chunk);
            });

        $paymentConditions = $this->accountPayableService->paymentConditions();

        $bankAccounts = $this->bankAccountService->findAll(tenant());

        return Inertia::render('tenant/finance/accounts-payable/edit/Edit', [
            'accountPayable'         => $accountPayable,
            'financialCategories'    => $financialCategories,
            'financialSubcategories' => $financialSubcategories,
            'costs'                  => $costs,
            'contacts'               => $contacts,
            'paymentConditions'      => $paymentConditions,
            'bankAccounts'           => $bankAccounts,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccountPayableRequest $request, string $id)
    {
        $accountPayable = $this->accountPayableService->update($id, $request->validated(), tenant());

        if ($accountPayable) {
            return redirect()->route('tenant.finance.accounts-payable.edit', ['id' => $accountPayable->id])->with('success', 'Account payable updated successfully!');
        }

        return redirect()->route('tenant.finance.accounts-payable.edit', ['id' => $accountPayable->id])->with('error', 'Error updating account payable!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $accountPayable = $this->accountPayableService->delete($id, tenant());

        if ($accountPayable) {
            return redirect()->route('tenant.finance.accounts-payable.list')->with('success', 'Account payable deleted successfully!');
        }

        return redirect()->route('tenant.finance.accounts-payable.list')->with('error', 'Error deleting account payable!');
    }

    public function updateInstallments(Request $request)
    {
        try {
            $this->accountPayableService->updateInstallment($request->input('id'), tenant());

            return response()->json([
                'success' => true,
                'message' => 'Installments updated successfully!',
            ], Response::HTTP_CREATED);
        } catch (\Throwable) {
            throw new UpdateInstallmentException('Error updating installments!');
        }
    }

    public function updateInstallmentValue(UpdateInstallmentValueRequest $request)
    {
        $updatedInstallments = $this->accountPayableService->updateInstallmentValue($request->validated(), tenant());

        if ($updatedInstallments) {
            return response()->json(['status' => 200, 'message' => 'Installment value updated successfully!']);
        }

        return response()->json(['status' => 500, 'message' => 'Error updating installment!']);
    }
}
