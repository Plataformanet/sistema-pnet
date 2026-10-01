<?php

namespace App\Services;

use App\Enums\ContactTypeEnum;
use App\Models\AccountReceivable;
use App\Models\Tenant;

class AccountReceivableService extends AccountService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, Tenant $tenant): AccountReceivable
    {
        /** @var AccountReceivable */
        return $this->createAccount($data, $tenant);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data, Tenant $tenant): AccountReceivable
    {
        /** @var AccountReceivable */
        return $this->updateAccount($id, $data, $tenant);
    }

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
                'cost:id,name',
                'bankAccount:id,name',
                'installments',
            ]
        )->findOrFail($id));
    }

    protected function getModel(): string
    {
        return AccountReceivable::class;
    }

    protected function contactType(): ContactTypeEnum
    {
        return ContactTypeEnum::CLIENT;
    }

    protected function balanceDirection(): int
    {
        return 1;
    }
}
