<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Supplier;
use App\Models\Suppliers;
use App\Models\Tenant;
use DB;

class SuppliersService
{
    public function store(Contact $contact, array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($contact, $data) {

            try {
                DB::beginTransaction();

                $contact->supplier()->create([
                    'responsible_person' => $data['responsible_person'],
                    'description'        => $data['description'],
                    'supply_category'    => $data['supply_category'],
                ]);

                DB::commit();

                return $contact;
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }

        });
    }

    public function update(Contact $contact, array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($contact, $data) {
            try {
                DB::beginTransaction();

                $contact->supplier()->update([
                    'responsible_person' => $data['responsible_person'],
                    'description'        => $data['description'],
                    'supply_category'    => $data['supply_category'],
                ]);

                DB::commit();

                return $contact;
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }

    public function findById(string $id, Tenant $tenant)
    {
        return $tenant->run(fn() => Supplier::with(['contact', 'contact.address'])->where('contact_id', $id)->firstOrFail());
    }

    public function findAll(Tenant $tenant)
    {
        return $tenant->run(fn() => Supplier::with(['contact'])->get()->map(function ($supplier) {
            return [
                'id'              => $supplier->contact->id,
                'name'            => $supplier->contact->name_corporatereason,
                'email'           => $supplier->contact->email,
                'cpf_cnpj'        => $supplier->contact->cpf_cnpj,
                'supply_category' => $supplier->supply_category,
            ];
        }));
    }
}
