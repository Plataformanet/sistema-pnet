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
            return DB::transaction(function () use ($data) {

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

                return $contact;

            });
        });
    }

    public function update(array $data, Tenant $tenant, string $id)
    {
        return $tenant->run(function () use ($data, $id) {
            return DB::transaction(function () use ($data, $id) {

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

                return $contact;
            });
        });
    }

    public function destroy(Tenant $tenant, string $id)
    {
        return $tenant->run(function () use ($id) {
            return DB::transaction(function () use ($id) {
                $contact = Contact::findOrFail($id);
                $contact->delete();

                return $contact;
            });
        });
    }

    public function getContactByCpfCnpj(string $cpfCnpj, Tenant $tenant)
    {
        return $tenant->run(function () use ($cpfCnpj) {
            return Contact::where('cpf_cnpj', $cpfCnpj)->first();
        });
    }
}
