<?php

namespace App\Services;

use App\Exceptions\BankAccountHasFinancialEntriesException;
use App\Models\BankAccount;
use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankAccountService
{
    /**
     * O saldo atual nasce igual ao saldo inicial e, depois disso, só é alterado
     * pelas baixas e estornos dos lançamentos financeiros.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, Tenant $tenant): BankAccount
    {
        $data['initial_balance'] = (int) ($data['initial_balance'] ?? 0);
        $data['current_balance'] = $data['initial_balance'];

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

    /**
     * Os saldos nunca vêm do formulário de edição: o inicial é fixo após a
     * criação e o atual é movimentado apenas pelos lançamentos financeiros.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data, Tenant $tenant)
    {
        $data = Arr::except($data, ['initial_balance', 'current_balance']);

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

    /**
     * Exclui a conta bancária (soft delete). Com lançamentos ativos a exclusão é
     * bloqueada: os saldos dependem das baixas e estornos deles (RN-FIN-02).
     *
     * @throws BankAccountHasFinancialEntriesException
     */
    public function delete(string $id, Tenant $tenant)
    {
        return $tenant->run(function () use ($id) {
            return DB::transaction(function () use ($id) {
                $bankAccount = BankAccount::findOrFail($id);

                if ($bankAccount->accountsPayable()->exists() || $bankAccount->accountsReceivable()->exists()) {
                    throw new BankAccountHasFinancialEntriesException;
                }

                $bankAccount->delete();

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
