<?php

namespace App\Services;

use App\Enums\AccountsEnum;
use App\Enums\TypeContactEnum;
use App\Models\AccountPayable;
use App\Models\BankAccount;
use App\Models\Tenant;
use App\Utils\Utils;
use DateTime;
use Illuminate\Support\Facades\DB;

class AccountPayableService extends AccountService
{
    public function create(array $data, Tenant $tenant): AccountPayable
    {
        return $tenant->run(function () use ($data) {
            return DB::transaction(function () use ($data) {
                $data['financial_contact_id'] = $this->resolveFinancialContactId($data['financial_contact_id'], TypeContactEnum::SUPPLIER);

                $data['total_installments'] = $data['payment_condition'] === 'a-vista' ? 1 : $data['payment_condition'];
                $data['valor'] = $data['total'] / $data['total_installments'];

                $accountPayable = AccountPayable::create($data);

                $startDate = DateTime::createFromFormat('Y-m-d', $data['due_date']);
                $dayOriginal = (int) $startDate->format('d');
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

                    $lastDayOfMonth = (int) $tempDate->format('t');
                    $dayAdjust = min($dayOriginal, $lastDayOfMonth);

                    $tempDate->setDate($yearCurrent, $monthAdjust, $dayAdjust);

                    $dueDate = $tempDate->format('Y-m-d');

                    $accountPayable->installments()->create([
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
                    $bankAccount = BankAccount::find($data['bank_account_id']);
                    $bankAccount->current_balance -= $data['value'];
                    $bankAccount->save();
                }

                return $accountPayable;
            });
        });
    }

    public function update(string $id, array $data, Tenant $tenant): AccountPayable
    {
        return $tenant->run(function () use ($id, $data) {
            return DB::transaction(function () use ($id, $data) {
                $account = AccountPayable::findOrFail($id);

                if (isset($data['financial_contact_id'])) {
                    $data['financial_contact_id'] = $this->resolveFinancialContactId($data['financial_contact_id'], TypeContactEnum::SUPPLIER);
                }

                if ($account->total != $data['total']) {

                    $account->installments()->delete();

                    $data['total_installments'] = $data['payment_condition'] === 'a-vista' ? 1 : $data['payment_condition'];
                    $data['valor'] = $data['total'] / $data['total_installments'];

                    $startDate = DateTime::createFromFormat('Y-m-d', $data['due_date']);
                    $dayOriginal = (int) $startDate->format('d');
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

                        $lastDayOfMonth = (int) $tempDate->format('t');
                        $dayAdjust = min($dayOriginal, $lastDayOfMonth);

                        $tempDate->setDate($yearCurrent, $monthAdjust, $dayAdjust);

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
                                'value' => Utils::format_coin_sql($installment['value']),
                                'due_date' => $installment['due_date'],
                            ]);
                    }
                }

                $account->update($data);

                return $account;
            });
        });
    }

    public function findById(string $id, Tenant $tenant): AccountPayable
    {
        return $tenant->run(fn () => AccountPayable::findOrFail($id));
    }

    public function showById(string $id, Tenant $tenant): AccountPayable
    {
        return $tenant->run(fn () => AccountPayable::with(
            [
                'financialContact:id,contact_id',
                'financialContact.contact:id,name_corporatereason',
                'financialCategory:id,name',
                'financialSubcategory:id,name',
                'cost:id,type',
            ]
        )->findOrFail($id));
    }

    protected function getModel(): string
    {
        return AccountPayable::class;
    }
}
