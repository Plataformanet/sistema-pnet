<?php

namespace App\Services;

use App\Models\Contact;
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

    public function findById(string $id)
    {
        return Contact::with(['supplier', 'address'])->find($id);
    }

    public function findAll()
    {
        return Contact::with('supplier')->get()->map(function ($contact) {
            return [
                'id'       => $contact->id,
                'name'     => $contact->name_corporatereason,
                'email'    => $contact->email,
                'cpf_cnpj' => $contact->cpf_cnpj,
            ];
        });
    }
}
