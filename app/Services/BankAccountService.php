<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankAccountService
{
    public function create(array $data, Tenant $tenant): BankAccount
    {
        return $tenant->run(function () use ($data) {
            $getBankAccount = BankAccount::all();

            foreach ($getBankAccount as $account) {
                if ($account->main_account == 1 && $data['main_account'] == 1) {
                    throw ValidationException::withMessages([
                        'error' => 'Já existe uma conta definida como principal.',
                    ]);
                }
            }

            return BankAccount::create($data);
        });
    }

    public function update(string $id, array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($id, $data) {
            $bankAccount = BankAccount::findOrFail($id);

            $getBankAccountMain = BankAccount::where('main_account', true)->first();

            if (! $getBankAccountMain) {
                throw ValidationException::withMessages([
                    'error' => 'É necessário ter pelo menos uma conta principal.',
                ]);
            }

            if ($getBankAccountMain->id != $id && $data['main_account'] == 1) {
                $getBankAccountMain->main_account = 0;
                $getBankAccountMain->save();
            }

            return $bankAccount->update($data);
        });
    }

    public function delete(string $id, Tenant $tenant)
    {
        return $tenant->run(function () use ($id) {
            return DB::transaction(function () use ($id) {
                $bankAccount = BankAccount::findOrFail($id);

                $accountPayable = $bankAccount->accountsPayable()->exists();
                $accountReceivable = $bankAccount->accountsReceivable()->exists();

                $bankAccount->delete();

                if ($bankAccount && $accountPayable) {
                    $bankAccount->accountsPayable()->delete();
                }

                if ($bankAccount && $accountReceivable) {
                    $bankAccount->accountsReceivable()->delete();
                }

                return $bankAccount;
            });
        });
    }

    public function findAll(Tenant $tenant)
    {
        return $tenant->run(fn () => BankAccount::all());
    }

    public function findById(string $id, Tenant $tenant)
    {
        return $tenant->run(fn () => BankAccount::findOrFail($id));
    }
}
