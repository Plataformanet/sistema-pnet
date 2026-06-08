<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Services\BankAccountService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TenantBankAccountController extends Controller
{
    public function __construct(
        protected BankAccountService $bankAccountService,
    ) {}

    public function index()
    {
        $bankAccounts = $this->bankAccountService->findAll(tenant());

        return Inertia::render('tenant/finance/bank-accounts/list/List', [
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function create()
    {
        return Inertia::render('tenant/finance/bank-accounts/create/Create');
    }

    public function store(StoreBankAccountRequest $request)
    {
        try {
            $data = $request->validated();

            // Set current_balance equal to initial_balance on creation if not explicitly set
            if (! isset($data['current_balance']) || $data['current_balance'] === null) {
                $data['current_balance'] = $data['initial_balance'] ?? 0;
            }

            // Convert string/numeric active & main_account to boolean if needed
            $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $data['main_account'] = filter_var($data['main_account'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $this->bankAccountService->create($data, tenant());

            return redirect()->route('tenant.finance.bank-accounts.list')->with('success', 'Conta bancária criada com sucesso!');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error('Erro ao criar conta bancária: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar conta bancária!');
        }
    }

    public function edit($id)
    {
        $bankAccount = $this->bankAccountService->findById($id, tenant());

        return Inertia::render('tenant/finance/bank-accounts/edit/Edit', [
            'bankAccount' => $bankAccount,
        ]);
    }

    public function update(UpdateBankAccountRequest $request, $id)
    {
        try {
            $data = $request->validated();

            $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $data['main_account'] = filter_var($data['main_account'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $this->bankAccountService->update($id, $data, tenant());

            return redirect()->route('tenant.finance.bank-accounts.list')->with('success', 'Conta bancária atualizada com sucesso!');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar conta bancária: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar conta bancária!');
        }
    }

    public function destroy($id)
    {
        try {
            $this->bankAccountService->delete($id, tenant());

            return redirect()->route('tenant.finance.bank-accounts.list')->with('success', 'Conta bancária excluída com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir conta bancária: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir conta bancária!');
        }
    }
}
