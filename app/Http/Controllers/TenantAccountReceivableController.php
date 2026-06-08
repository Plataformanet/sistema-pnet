<?php

namespace App\Http\Controllers;

use App\Enums\TypeContactEnum;
use App\Exceptions\UpdateInstallmentException;
use App\Http\Requests\StoreAccountReceivableRequest;
use App\Http\Requests\UpdateAccountReceivableRequest;
use App\Http\Requests\UpdateInstallmentValueRequest;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\Cost;
use App\Models\FinancialCategory;
use App\Services\AccountReceivableService;
use App\Services\BankAccountService;
use App\Services\ContactService;
use App\Services\FinancialCategoryService;
use App\Services\FinancialSubcategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class TenantAccountReceivableController extends Controller
{
    public function __construct(
        protected AccountReceivableService $accountReceivableService,
        protected FinancialCategoryService $financialCategoryService,
        protected FinancialSubcategoryService $financialSubcategoryService,
        protected ContactService $contactService,
        protected BankAccountService $bankAccountService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $period = $request->input('periodo', now()->format('Y-m'));
        $days = 7;

        $accountsReceivable = $this->accountReceivableService->findAll($request, $period, tenant());

        if (! $request->has('conta_id')) {
            $bankAccount = BankAccount::select('id', 'name', 'bank', 'current_balance')->where('main_account', 1)->first();
        }

        if ($request->has('conta_id')) {
            $bankAccount = BankAccount::select('id', 'name', 'bank', 'current_balance')->where('id', $request->query('conta_id'))->first();
        }

        $totalPeriod = $this->accountReceivableService->totalPeriod($request, $period, tenant(), $bankAccount?->id);
        $totalPaid = $this->accountReceivableService->totalPaid($request, $period, tenant(), $bankAccount?->id);
        $totalDueToday = $this->accountReceivableService->totalDueToday($request, $period, tenant(), $bankAccount?->id);
        $totalToDue = $this->accountReceivableService->totalToDue($request, $days, $period, tenant(), $bankAccount?->id);
        $totalOverdue = $this->accountReceivableService->totalOverdue($request, $period, tenant(), $bankAccount?->id);

        $financialCategories = $this->financialCategoryService->findAll(tenant());

        $searchedCategory = FinancialCategory::select('name')->find($request->input('categoria_id'));

        $bankAccounts = BankAccount::select('id', 'name', 'bank', 'current_balance', 'main_account')->get();

        return Inertia::render('tenant/finance/accounts-receivable/list/List', [
            'accountsReceivable' => $accountsReceivable,
            'totalPeriod' => $totalPeriod,
            'totalPaid' => $totalPaid,
            'totalDueToday' => $totalDueToday,
            'totalToDue' => $totalToDue,
            'totalOverdue' => $totalOverdue,
            'period' => $period,
            'perPage' => $request->input('quantidade'),
            'start' => $request->input('inicio'),
            'end' => $request->input('fim'),
            'categoryId' => $request->input('categoria_id'),
            'financialCategories' => $financialCategories,
            'searchedCategory' => $searchedCategory,
            'bankAccounts' => $bankAccounts,
            'bankAccount' => $bankAccount,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $financialCategories = $this->financialCategoryService->findCategoryAccountsReceivable(tenant());
        $financialSubcategories = $this->financialSubcategoryService->findAll(tenant());
        $costs = Cost::select('id', 'type')->get();

        $financialSubcategories = $financialSubcategories->map(function ($item) {
            if ($item->active) {
                return $item->name;
            }
        });

        $contacts = collect();
        Contact::select('id', 'name_corporatereason')
            ->where('type', TypeContactEnum::CLIENT->value)
            ->chunkById(500, function ($chunk) use (&$contacts) {
                $contacts = $contacts->merge($chunk);
            });

        $paymentConditions = $this->accountReceivableService->paymentConditions();

        $bankAccounts = $this->bankAccountService->findAll(tenant());

        return Inertia::render('tenant/finance/accounts-receivable/create/Create', [
            'financialCategories' => $financialCategories,
            'financialSubcategories' => $financialSubcategories,
            'costs' => $costs,
            'contacts' => $contacts,
            'paymentConditions' => $paymentConditions,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccountReceivableRequest $request)
    {
        $accountReceivable = $this->accountReceivableService->create($request->validated(), tenant());

        if ($accountReceivable) {
            return redirect()->route('tenant.finance.accounts-receivable.list')->with('success', 'Account receivable created successfully!');
        }

        return redirect()->route('tenant.finance.accounts-receivable.list')->with('error', 'Error creating account receivable!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $accountReceivable = $this->accountReceivableService->showById($id, tenant());

        return Inertia::render('tenant/finance/accounts-receivable/show/Show', [
            'accountReceivable' => $accountReceivable,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $accountReceivable = $this->accountReceivableService->findById($id, tenant());

        $financialCategories = $this->financialCategoryService->findCategoryAccountsReceivable(tenant());
        $financialSubcategories = $this->financialSubcategoryService->findAll(tenant());
        $costs = Cost::select('id', 'type')->get();

        $financialSubcategories = $financialSubcategories->map(function ($item) {
            if ($item->active) {
                return ['id' => $item->id, 'name' => $item->name];
            }
        });

        $contacts = collect();
        Contact::select('id', 'name_corporatereason')
            ->where('type', TypeContactEnum::CLIENT->value)
            ->chunkById(500, function ($chunk) use (&$contacts) {
                $contacts = $contacts->merge($chunk);
            });

        $paymentConditions = $this->accountReceivableService->paymentConditions();

        $bankAccounts = $this->bankAccountService->findAll(tenant());

        return Inertia::render('tenant/finance/accounts-receivable/edit/Edit', [
            'accountReceivable' => $accountReceivable,
            'financialCategories' => $financialCategories,
            'financialSubcategories' => $financialSubcategories,
            'costs' => $costs,
            'contacts' => $contacts,
            'paymentConditions' => $paymentConditions,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccountReceivableRequest $request, string $id)
    {
        $accountReceivable = $this->accountReceivableService->update($id, $request->validated(), tenant());

        if ($accountReceivable) {
            return redirect()->route('tenant.finance.accounts-receivable.edit', ['id' => $accountReceivable->id])->with('success', 'Account receivable updated successfully!');
        }

        return redirect()->route('tenant.finance.accounts-receivable.edit', ['id' => $accountReceivable->id])->with('error', 'Error updating account receivable!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $accountReceivable = $this->accountReceivableService->delete($id, tenant());

        if ($accountReceivable) {
            return redirect()->route('tenant.finance.accounts-receivable.list')->with('success', 'Account receivable deleted successfully!');
        }

        return redirect()->route('tenant.finance.accounts-receivable.list')->with('error', 'Error deleting account receivable!');
    }

    public function updateInstallments(Request $request)
    {
        try {
            $this->accountReceivableService->updateInstallment($request->input('id'), tenant());

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
        $updatedInstallments = $this->accountReceivableService->updateInstallmentValue($request->validated(), tenant());

        if ($updatedInstallments) {
            return response()->json(['status' => 200, 'message' => 'Installment value updated successfully!']);
        }

        return response()->json(['status' => 500, 'message' => 'Error updating installment!']);
    }
}
