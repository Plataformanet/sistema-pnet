<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Tenant;
use DB;

class ContactService
{
    public function store(array $data, Tenant $tenant)
    {
        return $tenant->run(function () use ($data) {
            DB::beginTransaction();

            try {
                $contact = Contact::create([
                    'type' => $data['type'] ?? 'PF',
                    'name_corporatereason' => $data['name_corporatereason'],
                    'fantasy_name' => $data['fantasy_name'] ?? null,
                    'cpf_cnpj' => $data['cpf_cnpj'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'cell_phone' => $data['cell_phone'],
                ]);

                $contact->address()->create([
                    'zip_code' => $data['zip_code'],
                    'street' => $data['street'],
                    'number' => $data['number'],
                    'complement' => $data['complement'],
                    'neighborhood' => $data['neighborhood'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                ]);

                DB::commit();

                return $contact;

            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }

    public function update(array $data, Tenant $tenant, string $id)
    {
        return $tenant->run(function () use ($data, $id) {
            DB::beginTransaction();

            try {
                $contact = Contact::findOrFail($id);

                $contact->update([
                    'type' => $data['type'] ?? 'PF',
                    'name_corporatereason' => $data['name_corporatereason'],
                    'fantasy_name' => $data['fantasy_name'] ?? null,
                    'cpf_cnpj' => $data['cpf_cnpj'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'cell_phone' => $data['cell_phone'],
                ]);

                $contact->address()->update([
                    'zip_code' => $data['zip_code'],
                    'street' => $data['street'],
                    'number' => $data['number'],
                    'complement' => $data['complement'],
                    'neighborhood' => $data['neighborhood'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                ]);

                DB::commit();

                return $contact;

            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }

    public function destroy(Tenant $tenant, string $id)
    {
        return $tenant->run(function () use ($id) {
            DB::beginTransaction();

            try {
                $contact = Contact::findOrFail($id);
                $contact->delete();
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });
    }
}
