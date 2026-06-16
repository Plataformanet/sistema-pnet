<?php

namespace App\Services;

use App\Enums\AccountsEnum;
use App\Enums\TypeContactEnum;
use App\Models\AccountReceivable;
use App\Models\BankAccount;
use App\Models\Tenant;
use DateTime;
use Illuminate\Support\Facades\DB;

class AccountReceivableService extends AccountService
{
    public function create(array $data, Tenant $tenant): AccountReceivable
    {
        return $tenant->run(function () use ($data) {
            return DB::transaction(function () use ($data) {

                $data['financial_contact_id'] = $this->resolveFinancialContactId($data['financial_contact_id'], TypeContactEnum::CLIENT);

                $data['total_installments'] = $data['payment_condition'] === 'a-vista' ? 1 : $data['payment_condition'];
                $data['value'] = $data['total'] / $data['total_installments'];

                $accountReceivable = AccountReceivable::create($data);

                foreach ($data['installments'] as $idx => $inst) {
                    $accountReceivable->installments()->create([
                        'installment_number' => $idx + 1,
                        'value' => $inst['value'],
                        'description' => $data['description'],
                        'due_date' => $inst['due_date'],
                        'payment_date' => $inst['due_date'],
                        'status' => $data['status'] ?? AccountsEnum::OPEN->value,
                    ]);
                }

                if ($data['status'] === AccountsEnum::PAID->value) {
                    $bankAccount = BankAccount::find($data['bank_account_id']);
                    $bankAccount->current_balance += $data['value'];
                    $bankAccount->save();
                }

                return $accountReceivable;
            });
        });
    }

    public function update(string $id, array $data, Tenant $tenant): AccountReceivable
    {
        return $tenant->run(function () use ($id, $data) {
            $account = AccountReceivable::findOrFail($id);

            if (isset($data['financial_contact_id'])) {
                $data['financial_contact_id'] = $this->resolveFinancialContactId($data['financial_contact_id'], TypeContactEnum::CLIENT);
            }

            if ($account->total != $data['total']) {

                $account->installments()->delete();

                $data['total_installments'] = $data['payment_condition'] === 'a-vista' ? 1 : $data['payment_condition'];
                $data['value'] = $data['total'] / $data['total_installments'];

                $startDate = DateTime::createFromFormat('Y-m-d', $data['due_date']);
                $originalDay = (int) $startDate->format('d');
                $year = (int) $startDate->format('Y');
                $month = (int) $startDate->format('m');

                $count = 0;
                while ($count < $data['total_installments']) {

                    // Calculate the adjusted month and year
                    $monthCurrent = $month + $count;
                    $yearCurrent = $year + intdiv($monthCurrent - 1, 12);
                    $monthAdjust = (($monthCurrent - 1) % 12) + 1;

                    // Create the new date
                    $tempDate = new DateTime;
                    $tempDate->setDate($yearCurrent, $monthAdjust, 1);

                    $lastDayOfTheMonth = (int) $tempDate->format('t');
                    $adjustedDay = min($originalDay, $lastDayOfTheMonth);

                    $tempDate->setDate($yearCurrent, $monthAdjust, $adjustedDay);

                    $dueDate = $tempDate->format('Y-m-d');

                    $account->installments()->create([
                        'installment_number' => $data['payment_condition'] === 'a-vista' ? 1 : $count + 1,
                        'value' => $data['value'],
                        'description' => $data['description'],
                        'due_date' => $dueDate,
                        'payment_date' => $dueDate,
                        'status' => $data['status'] ?? AccountsEnum::OPEN->value,
                    ]);

                    $count++;
                }
            } else {
                foreach ($data['installments'] as $installment) {
                    if (empty($installment['installment_id'])) {
                        continue;
                    }

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
        return $tenant->run(fn () => AccountReceivable::with('installments')->findOrFail($id));
    }

    public function showById(string $id, Tenant $tenant): AccountReceivable
    {
        return $tenant->run(fn () => AccountReceivable::with(
            [
                'financialContact:id,contact_id',
                'financialContact.contact:id,name_corporatereason',
                'financialCategory:id,name',
                'financialSubcategory:id,name',
                'cost:id,type',
                'bankAccount:id,name',
                'installments',
            ]
        )->findOrFail($id));
    }

    protected function getModel(): string
    {
        return AccountReceivable::class;
    }
}
