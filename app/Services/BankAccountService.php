<?php

namespace App\Services;

use App\Models\AccountBank;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankAccountService
{
    public function create(array $data, Tenant $tenant): AccountBank
    {
        return $tenant->run(function () use ($data) {
            $getAccountBank = AccountBank::all();

            foreach ($getAccountBank as $account) {
                if ($account->main_account == 1 && $data['main_account'] == 1) {
                    throw ValidationException::withMessages([
                        'error' => 'Já existe uma conta definida como principal.',
                    ]);
                }
            }

            return AccountBank::create($data);
        });
    }

    public function update(string $id, array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($id, $data) {
            $accountBank = AccountBank::findOrFail($id);

            $getAccountBankMain = AccountBank::where('main_account', true)->first();

            if (! $getAccountBankMain) {
                throw ValidationException::withMessages([
                    'error' => 'É necessário ter pelo menos uma conta principal.',
                ]);
            }

            if ($getAccountBankMain->id != $id && $data['main_account'] == 1) {
                $getAccountBankMain->main_account = 0;
                $getAccountBankMain->save();
            }

            return $accountBank->update($data);
        });
    }

    public function delete(string $id, Tenant $tenant)
    {
        return $tenant->run(function () use ($id) {
            return DB::transaction(function () use ($id) {
                $accountBank = AccountBank::findOrFail($id);

                $accountPayable = $accountBank->accountsPayable()->exists();
                $accountReceivable = $accountBank->accountsReceivable()->exists();

                $accountBank->delete();

                if ($accountBank && $accountPayable) {
                    $accountBank->accountsPayable()->delete();
                }

                if ($accountBank && $accountReceivable) {
                    $accountBank->accountsReceivable()->delete();
                }

                return $accountBank;
            });
        });
    }

    public function findAll(Tenant $tenant)
    {
        return $tenant->run(fn () => AccountBank::all());
    }

    public function findById(string $id, Tenant $tenant)
    {
        return $tenant->run(fn () => AccountBank::findOrFail($id));
    }
}
