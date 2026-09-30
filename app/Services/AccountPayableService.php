<?php

namespace App\Services;

use App\Enums\ContactTypeEnum;
use App\Models\AccountPayable;
use App\Models\Tenant;

class AccountPayableService extends AccountService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, Tenant $tenant): AccountPayable
    {
        /** @var AccountPayable */
        return $this->createAccount($data, $tenant);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data, Tenant $tenant): AccountPayable
    {
        /** @var AccountPayable */
        return $this->updateAccount($id, $data, $tenant);
    }

    public function findById(string $id, Tenant $tenant): AccountPayable
    {
        return $tenant->run(fn () => AccountPayable::with('installments')->findOrFail($id));
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
                'bankAccount:id,name',
                'installments',
            ]
        )->findOrFail($id));
    }

    protected function getModel(): string
    {
        return AccountPayable::class;
    }

    protected function contactType(): ContactTypeEnum
    {
        return ContactTypeEnum::SUPPLIER;
    }

    protected function balanceDirection(): int
    {
        return -1;
    }
}
