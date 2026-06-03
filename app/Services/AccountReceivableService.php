<?php

namespace App\Services;

use App\Enums\AccountsEnum;
use App\Models\AccountBank;
use App\Models\AccountReceivable;
use App\Models\Tenant;
use DateTime;
use Illuminate\Support\Facades\DB;

class AccountReceivableService extends AccountService
{
    public function create(array $data, Tenant $tenant): AccountReceivable
    {
        return $tenant->run(function () use ($data) {
            return DB::transaction(function () use ($data) {

                $data['total_installments'] = $data['condicao_de_pagamento'] === 'a-vista' ? 1 : $data['condicao_de_pagamento'];
                $data['valor'] = $data['total'] / $data['total_installments'];

                $accountReceivable = AccountReceivable::create($data);

                $startDate = DateTime::createFromFormat('Y-m-d', $data['data_de_vencimento']);
                $originalDay = (int) $startDate->format('d');
                $year = (int) $startDate->format('Y');
                $month = (int) $startDate->format('m');

                $count = 0;
                while ($count < $data['total_installments']) {

                    // Calcula mês e ano ajustados
                    $currentMonth = $month + $count;
                    $currentYear = $year + intdiv($currentMonth - 1, 12);
                    $monthAdjust = (($currentMonth - 1) % 12) + 1;

                    // Cria nova data
                    $tempDate = new DateTime;
                    $tempDate->setDate($currentYear, $monthAdjust, 1);

                    $lastDayOfTheMonth = (int) $tempDate->format('t');
                    $adjustedDay = min($originalDay, $lastDayOfTheMonth);

                    $tempDate->setDate($currentYear, $monthAdjust, $adjustedDay);

                    $dueDate = $tempDate->format('Y-m-d');

                    $accountReceivable->installments()->create([
                        'installment_number' => $data['payment_condition'] === 'a-vista' ? 1 : $count + 1,
                        'value' => $data['value'],
                        'description' => $data['description'],
                        'due_date' => $dueDate,
                        'payment_date' => $dueDate,
                        'status' => $data['status'] ?? AccountsEnum::OPEN->value,
                    ]);
                    $count++;
                }

                if ($data['status'] === AccountsEnum::PAID->value) {
                    $accountBank = AccountBank::find($data['account_bank_id']);
                    $accountBank->current_balance += $data['value'];
                    $accountBank->save();
                }

                return $accountReceivable;
            });
        });
    }

    public function update(string $id, array $data, Tenant $tenant): AccountReceivable
    {
        return $tenant->run(function () use ($id, $data) {
            $account = AccountReceivable::findOrFail($id);

            if ($account->total != $data['total']) {

                $account->installments()->delete();

                $data['total_installments'] = $data['payment_condition'] === 'a-vista' ? 1 : $data['payment_condition'];
                $data['valor'] = $data['total'] / $data['total_installments'];

                $startDate = DateTime::createFromFormat('Y-m-d', $data['due_date']);
                $originalDay = (int) $startDate->format('d');
                $year = (int) $startDate->format('Y');
                $month = (int) $startDate->format('m');

                $count = 0;
                while ($count < $data['total_installments']) {

                    // Calcula mês e ano ajustados
                    $monthCurrent = $month + $count;
                    $yearCurrent = $year + intdiv($monthCurrent - 1, 12);
                    $monthAdjust = (($monthCurrent - 1) % 12) + 1;

                    // Cria nova data
                    $tempDate = new DateTime;
                    $tempDate->setDate($yearCurrent, $monthAdjust, 1);

                    $lastDayOfTheMonth = (int) $tempDate->format('t');
                    $adjustedDay = min($originalDay, $lastDayOfTheMonth);

                    $tempDate->setDate($yearCurrent, $monthAdjust, $adjustedDay);

                    $dueDate = $tempDate->format('Y-m-d');

                    $account->installments()->create([
                        'installment_number' => $data['payment_condition'] === 'a-vista' ? 1 : $count + 1,
                        'value' => $data['valor'],
                        'description' => $data['description'],
                        'due_date' => $dueDate,
                        'payment_date' => $dueDate,
                        'status' => $data['status'] ?? AccountsEnum::OPEN->value,
                    ]);

                    $count++;
                }
            } else {
                foreach ($data['installments'] as $installment) {
                    $account->installments()
                        ->where('id', $installment['installment_id'])
                        ->update([
                            'value' => $installment['value'],
                            'due_date' => $installment['due_date'],
                        ]);
                }
            }

            $account->update($data);

            return $account;
        });
    }

    // public function delete(string $id): bool
    // {
    //     return AccountReceivable::findOrFail($id)->delete();
    // }

    public function findById(string $id, Tenant $tenant): AccountReceivable
    {
        return $tenant->run(fn () => AccountReceivable::findOrFail($id));
    }

    public function showById(string $id, Tenant $tenant): AccountReceivable
    {
        return $tenant->run(fn () => AccountReceivable::with(
            [
                'contactFinancial:id,name_corporatereason',
                'financialCategory:id,name',
                'financialSubcategory:id,name',
                'cost:id,type',
            ]
        )->findOrFail($id));
    }

    protected function getModel(): string
    {
        return AccountReceivable::class;
    }
}
